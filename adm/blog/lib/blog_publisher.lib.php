<?php
if (!defined('_GNUBOARD_')) exit;

// 발행 채널 인터페이스 (BLOG_AUTOMATION_API_SPEC.md 워드프레스/자체 PHP 연동)
// Phase 1에서는 어떤 구현체도 실제 외부 HTTP 호출을 하지 않는다 (작업지시서 명시 사항).
interface BlogPublisherInterface
{
    // 반환: array('ok'=>bool, 'external_post_id'=>string, 'published_url'=>string, 'error'=>string)
    // $credentials_row는 아직 인증정보가 없는 사이트일 수 있어 null을 허용한다.
    public function publish(array $post_target_row, ?array $credentials_row): array;
}

// 테스트 가능한 목 발행기 — 실제 네트워크 호출 없이 발행 성공/실패를 재현한다.
// 제목에 리터럴 문자열 '[FAIL_TEST]'가 포함되면 의도적으로 실패를 반환한다(재시도·중복방지 테스트용 트리거).
class BlogMockPublisher implements BlogPublisherInterface
{
    public function publish(array $post_target_row, ?array $credentials_row): array
    {
        $title = isset($post_target_row['title']) ? $post_target_row['title'] : '';
        if (strpos($title, '[FAIL_TEST]') !== false) {
            return array(
                'ok' => false,
                'external_post_id' => '',
                'published_url' => '',
                'error' => 'Mock Publisher: 의도적 실패 트리거(FAIL_TEST)',
            );
        }

        $fake_id = 'mock-' . (int) $post_target_row['id'] . '-' . substr(md5((string) time()), 0, 6);
        return array(
            'ok' => true,
            'external_post_id' => $fake_id,
            'published_url' => 'https://mock.local/posts/' . $fake_id,
            'error' => '',
        );
    }
}

// 워드프레스 실연동 구조 자리 — Phase 2에서 Application Password 기반 REST 호출을 이 안에 채운다.
// Phase 1에서는 절대 호출되지 않으며, 호출되더라도 즉시 미구현 오류를 반환한다.
class BlogWordPressPublisher implements BlogPublisherInterface
{
    public function publish(array $post_target_row, ?array $credentials_row): array
    {
        return array(
            'ok' => false,
            'external_post_id' => '',
            'published_url' => '',
            'error' => 'WordPress 실연동은 Phase 2 예정입니다 (Phase 1은 Mock Publisher만 사용).',
        );
    }
}

// Phase 1은 플랫폼과 무관하게 항상 Mock을 반환한다 — 팩토리 구조만 실 연동 대비로 마련해 둔다.
function bp_get_publisher(string $platform): BlogPublisherInterface
{
    return new BlogMockPublisher();
}

// 대상(post_target)에 활성 발행 작업이 있으면 재사용하고, 없으면 새로 만든다.
// active_lock_key 유니크 제약이 최종 방어선이며, 이 함수는 그 전에 애플리케이션 레벨에서 먼저 확인한다.
function bp_get_or_create_publish_job(int $post_target_id): ?array
{
    $jobs_table = bp_table('publish_jobs');

    $active = sql_fetch(" select * from {$jobs_table}
                            where post_target_id = '{$post_target_id}'
                              and status in ('pending','claimed','processing')
                            limit 1 ");
    if ($active) {
        return $active;
    }

    $lock_key = (string) $post_target_id;
    sql_query(" insert into {$jobs_table}
                    set post_target_id = '{$post_target_id}',
                        status = 'pending',
                        active_lock_key = '" . sql_real_escape_string($lock_key) . "',
                        attempt_count = 0,
                        created_at = '" . G5_TIME_YMDHIS . "',
                        updated_at = '" . G5_TIME_YMDHIS . "' ");

    return sql_fetch(" select * from {$jobs_table} where post_target_id = '{$post_target_id}' order by id desc limit 1 ");
}

// 승인 게이트 + 잠금 + 발행 + 이력 기록을 한 번에 처리하는 오케스트레이션 함수.
// project.status가 approved 계열이 아니면 어떤 경우에도 여기서 막힌다(발행 전 승인 차단 게이트).
function bp_dispatch_publish_job(int $post_target_id, string $actor): array
{
    $targets_table = bp_table('post_targets');
    $posts_table = bp_table('posts');
    $projects_table = bp_table('content_projects');
    $jobs_table = bp_table('publish_jobs');
    $attempts_table = bp_table('publish_attempts');
    $sites_table = bp_table('sites');
    $creds_table = bp_table('site_credentials');

    $target = sql_fetch(" select * from {$targets_table} where id = '{$post_target_id}' ");
    if (!$target) {
        return array('ok' => false, 'error' => '발행 대상을 찾을 수 없습니다.');
    }

    $post = sql_fetch(" select * from {$posts_table} where id = '" . (int)$target['post_id'] . "' ");
    if (!$post) {
        return array('ok' => false, 'error' => '원본 포스팅을 찾을 수 없습니다.');
    }

    $project = sql_fetch(" select * from {$projects_table} where id = '" . (int)$post['project_id'] . "' ");
    if (!$project) {
        return array('ok' => false, 'error' => '콘텐츠 프로젝트를 찾을 수 없습니다.');
    }

    // 승인 전 발행 차단 — 이 검사를 통과하지 못하면 publish_jobs 행 자체를 만들지 않는다.
    if (!bp_is_publish_allowed($project['status'])) {
        return array('ok' => false, 'error' => "'{$project['status']}' 상태에서는 발행할 수 없습니다. 먼저 승인(approved)해 주세요.");
    }

    if ($project['status'] === 'approved') {
        bp_transition_project($project['id'], 'publish_pending', $actor, 'publish dispatch');
    }

    $job = bp_get_or_create_publish_job($post_target_id);
    if (!$job) {
        return array('ok' => false, 'error' => '발행 작업 생성에 실패했습니다.');
    }

    if ($job['status'] !== 'pending') {
        // 이미 다른 워커/요청이 claim 했거나 진행 중 — 중복 실행을 여기서 차단한다.
        return array('ok' => false, 'error' => "이미 '{$job['status']}' 상태로 처리 중인 작업입니다(중복 발행 방지).", 'job_id' => $job['id']);
    }

    // claim: pending -> claimed (조건부 UPDATE로 원자적으로 한 번만 성공하도록 함)
    $worker_id = 'admin:' . $actor . ':' . uniqid('', true);
    global $g5;
    sql_query(" update {$jobs_table}
                    set status = 'claimed', claimed_at = '" . G5_TIME_YMDHIS . "', worker_id = '" . sql_real_escape_string($worker_id) . "', updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = '" . (int)$job['id'] . "' and status = 'pending' ");
    // pending 상태였던 행에만 UPDATE가 적용되므로, 다른 요청이 먼저 claim 했다면 영향받은 행이 0건이다.
    $affected = isset($g5['connect_db']) ? @mysqli_affected_rows($g5['connect_db']) : null;
    if ($affected !== null && $affected < 1) {
        return array('ok' => false, 'error' => '다른 요청이 먼저 이 작업을 선점했습니다(중복 발행 방지).', 'job_id' => $job['id']);
    }

    sql_query(" update {$jobs_table} set status = 'processing', attempt_count = attempt_count + 1, updated_at = '" . G5_TIME_YMDHIS . "' where id = '" . (int)$job['id'] . "' ");
    bp_transition_project($project['id'], 'publishing', $actor, 'job#' . $job['id']);

    $site = sql_fetch(" select * from {$sites_table} where id = '" . (int)$target['site_id'] . "' ");
    $cred = sql_fetch(" select * from {$creds_table} where site_id = '" . (int)$target['site_id'] . "' limit 1 ");

    $publisher = bp_get_publisher($site ? $site['platform'] : 'wordpress');
    $result = $publisher->publish($target, $cred);

    $attempt_no = (int) $job['attempt_count'] + 1;
    $status = $result['ok'] ? 'success' : 'failed';
    sql_query(" insert into {$attempts_table}
                    set publish_job_id = '" . (int)$job['id'] . "',
                        attempt_no = '{$attempt_no}',
                        status = '" . sql_real_escape_string($status) . "',
                        response_code = '" . ($result['ok'] ? '200' : 'ERR') . "',
                        response_message = '" . sql_real_escape_string(mb_substr($result['error'], 0, 500)) . "',
                        created_at = '" . G5_TIME_YMDHIS . "' ");

    if ($result['ok']) {
        sql_query(" update {$targets_table}
                        set publish_status = 'published',
                            external_post_id = '" . sql_real_escape_string($result['external_post_id']) . "',
                            published_url = '" . sql_real_escape_string($result['published_url']) . "',
                            last_error = '',
                            updated_at = '" . G5_TIME_YMDHIS . "'
                        where id = '{$post_target_id}' ");
        sql_query(" update {$jobs_table} set status = 'published', active_lock_key = NULL, updated_at = '" . G5_TIME_YMDHIS . "' where id = '" . (int)$job['id'] . "' ");
        bp_transition_project($project['id'], 'published', $actor, 'job#' . $job['id'] . ' success');
        return array('ok' => true, 'external_post_id' => $result['external_post_id'], 'published_url' => $result['published_url']);
    }

    sql_query(" update {$targets_table}
                    set publish_status = 'failed',
                        last_error = '" . sql_real_escape_string(mb_substr($result['error'], 0, 500)) . "',
                        retry_count = retry_count + 1,
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = '{$post_target_id}' ");
    sql_query(" update {$jobs_table} set status = 'failed', active_lock_key = NULL, updated_at = '" . G5_TIME_YMDHIS . "' where id = '" . (int)$job['id'] . "' ");
    bp_transition_project($project['id'], 'failed', $actor, 'job#' . $job['id'] . ' ' . $result['error']);

    return array('ok' => false, 'error' => $result['error'], 'job_id' => $job['id']);
}
