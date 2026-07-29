<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 만료된 잠금(Orphaned Lock) 복구
 * 10분 이상 processing 상태로 머물러 있는 작업을 failed로 되돌려 다음 스케줄러가 재시도하게 한다.
 */
function bp_scheduler_recover_locks(): int
{
    $jobs_table = bp_table('publish_jobs');
    $timeout_minutes = 10;
    
    // processing 상태이고 locked_at이 지정된 시간보다 오래된 것
    $sql = " UPDATE {$jobs_table} 
             SET status = 'failed', 
                 lock_token = NULL, 
                 locked_at = NULL,
                 last_error_message = 'Worker timeout / orphaned lock recovered',
                 next_retry_at = NOW(),
                 updated_at = '" . G5_TIME_YMDHIS . "'
             WHERE status = 'processing' 
               AND locked_at IS NOT NULL 
               AND locked_at < DATE_SUB(NOW(), INTERVAL {$timeout_minutes} MINUTE) ";
               
    sql_query($sql);
    
    global $g5;
    return isset($g5['connect_db']) ? (int) @mysqli_affected_rows($g5['connect_db']) : 0;
}

/**
 * 스케줄러: 폴링 및 원자적 락 획득
 */
function bp_scheduler_poll_and_lock_jobs(int $limit = 20): array
{
    $jobs_table = bp_table('publish_jobs');
    $lock_token = 'cron_' . uniqid('', true);
    
    // 서브쿼리를 사용해 락 대상 ID 추출 후 업데이트
    $sql_update = "
        UPDATE {$jobs_table}
        SET status = 'processing',
            lock_token = '" . sql_real_escape_string($lock_token) . "',
            locked_at = '" . G5_TIME_YMDHIS . "',
            updated_at = '" . G5_TIME_YMDHIS . "'
        WHERE id IN (
            SELECT id FROM (
                SELECT id FROM {$jobs_table}
                WHERE (
                    (status = 'pending' AND (scheduled_at IS NULL OR scheduled_at <= NOW()))
                    OR
                    (status = 'failed' AND next_retry_at IS NOT NULL AND next_retry_at <= NOW())
                )
                LIMIT {$limit}
            ) tmp
        )
    ";
    sql_query($sql_update);
    
    $res = sql_query(" SELECT * FROM {$jobs_table} WHERE lock_token = '" . sql_real_escape_string($lock_token) . "' AND status = 'processing' ");
    
    $jobs = array();
    while ($row = sql_fetch_array($res)) {
        $jobs[] = $row;
    }
    return $jobs;
}

/**
 * 스케줄러: 단일 작업 처리
 */
function bp_scheduler_process_job(array $job): array
{
    $targets_table = bp_table('post_targets');
    $posts_table = bp_table('posts');
    $projects_table = bp_table('content_projects');
    $jobs_table = bp_table('publish_jobs');
    $attempts_table = bp_table('publish_attempts');
    $sites_table = bp_table('sites');
    $creds_table = bp_table('site_credentials');

    $job_id = (int) $job['id'];
    $post_target_id = (int) $job['post_target_id'];

    $target = sql_fetch(" select * from {$targets_table} where id = '{$post_target_id}' ");
    if (!$target) {
        return bp_scheduler_fail_job($job, '발행 대상을 찾을 수 없습니다.', 'NOT_FOUND', true);
    }
    
    $post = sql_fetch(" select * from {$posts_table} where id = '" . (int)$target['post_id'] . "' ");
    $project = sql_fetch(" select * from {$projects_table} where id = '" . (int)($post['project_id'] ?? 0) . "' ");
    
    // 승인 상태 검증
    if (!$project || !bp_is_publish_allowed($project['status'])) {
        return bp_scheduler_fail_job($job, '승인되지 않은 프로젝트이거나 삭제되었습니다.', 'NOT_APPROVED', true);
    }
    
    $site = sql_fetch(" select * from {$sites_table} where id = '" . (int)$target['site_id'] . "' ");
    if (!$site || $site['is_active'] === 'N') {
        return bp_scheduler_fail_job($job, '사이트가 비활성화되었거나 삭제되었습니다.', 'SITE_INACTIVE', true);
    }
    
    $cred = sql_fetch(" select * from {$creds_table} where site_id = '" . (int)$target['site_id'] . "' limit 1 ");
    
    // 시도 횟수 증가
    sql_query(" update {$jobs_table} set attempt_count = attempt_count + 1 where id = '{$job_id}' ");
    $attempt_no = (int) $job['attempt_count'] + 1;
    
    // 발행 처리
    $publisher = bp_get_publisher($site['platform']);
    $target['job_id'] = $job_id;
    $result = $publisher->publish($target, $site, $cred);
    
    $status = $result['ok'] ? 'success' : 'failed';
    $error_code = $result['error_code'] ?? ($result['ok'] ? '200' : 'ERR');
    $safe_message = bp_scrub_secrets(mb_substr($result['error'] ?? '', 0, 500));
    
    // 시도 이력 기록
    sql_query(" insert into {$attempts_table}
                    set publish_job_id = '{$job_id}',
                        attempt_no = '{$attempt_no}',
                        status = '" . sql_real_escape_string($status) . "',
                        response_code = '" . sql_real_escape_string($error_code) . "',
                        response_message = '" . sql_real_escape_string($safe_message) . "',
                        created_at = '" . G5_TIME_YMDHIS . "' ");
                        
    if ($result['ok']) {
        // 성공
        sql_query(" update {$targets_table}
                        set publish_status = 'published',
                            external_post_id = '" . sql_real_escape_string($result['external_post_id'] ?? '') . "',
                            published_url = '" . sql_real_escape_string($result['published_url'] ?? '') . "',
                            last_error = '',
                            updated_at = '" . G5_TIME_YMDHIS . "'
                        where id = '{$post_target_id}' ");
                        
        sql_query(" update {$jobs_table} 
                        set status = 'published', 
                            lock_token = NULL, 
                            locked_at = NULL, 
                            completed_at = '" . G5_TIME_YMDHIS . "',
                            updated_at = '" . G5_TIME_YMDHIS . "' 
                        where id = '{$job_id}' ");
                        
        bp_transition_project($project['id'], 'published', 'scheduler', 'job#' . $job_id . ' success');
        return $result;
    }
    
    // 실패 처리 (일시 오류 vs 영구 오류)
    $is_permanent = false;
    // 401, 403 등 권한 오류나 설정 오류는 영구 실패로 간주
    if (in_array((string)$error_code, array('401', '403', 'NOT_FOUND', 'SITE_INACTIVE'))) {
        $is_permanent = true;
    }
    
    // 최대 시도 횟수 초과 여부
    if ($attempt_no >= BP_MAX_PUBLISH_ATTEMPTS) {
        $is_permanent = true;
        $safe_message = "[최대 재시도 초과] " . $safe_message;
    }
    
    return bp_scheduler_fail_job($job, $safe_message, $error_code, $is_permanent);
}

/**
 * 실패 처리 헬퍼 함수
 */
function bp_scheduler_fail_job(array $job, string $error_message, string $error_code, bool $is_permanent): array
{
    $targets_table = bp_table('post_targets');
    $jobs_table = bp_table('publish_jobs');
    $projects_table = bp_table('content_projects');
    $posts_table = bp_table('posts');
    
    $job_id = (int) $job['id'];
    $post_target_id = (int) $job['post_target_id'];
    $attempt_no = (int) $job['attempt_count'] + 1; // 이미 증가했다고 가정하거나 호출부에서 증가
    
    $safe_message = sql_real_escape_string($error_message);
    
    sql_query(" update {$targets_table}
                    set publish_status = 'failed',
                        last_error = '{$safe_message}',
                        retry_count = retry_count + 1,
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = '{$post_target_id}' ");
                    
    if ($is_permanent) {
        sql_query(" update {$jobs_table}
                        set status = 'failed', 
                            lock_token = NULL, 
                            locked_at = NULL,
                            next_retry_at = NULL,
                            last_error_code = '" . sql_real_escape_string($error_code) . "',
                            last_error_message = '{$safe_message}',
                            completed_at = '" . G5_TIME_YMDHIS . "',
                            updated_at = '" . G5_TIME_YMDHIS . "'
                        where id = '{$job_id}' ");
    } else {
        $backoff_minutes = bp_next_retry_backoff_minutes($attempt_no);
        sql_query(" update {$jobs_table}
                        set status = 'failed', 
                            lock_token = NULL, 
                            locked_at = NULL,
                            next_retry_at = DATE_ADD('" . G5_TIME_YMDHIS . "', INTERVAL {$backoff_minutes} MINUTE),
                            last_error_code = '" . sql_real_escape_string($error_code) . "',
                            last_error_message = '{$safe_message}',
                            updated_at = '" . G5_TIME_YMDHIS . "'
                        where id = '{$job_id}' ");
    }
    
    // 프로젝트 상태 갱신
    $target = sql_fetch(" select post_id from {$targets_table} where id = '{$post_target_id}' ");
    if ($target) {
        $post = sql_fetch(" select project_id from {$posts_table} where id = '" . (int)$target['post_id'] . "' ");
        if ($post) {
            bp_transition_project((int)$post['project_id'], 'failed', 'scheduler', 'job#' . $job_id . ' ' . $error_message);
        }
    }
    
    return array('ok' => false, 'error' => $error_message, 'error_code' => $error_code);
}
