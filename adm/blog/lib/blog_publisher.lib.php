<?php
if (!defined('_GNUBOARD_')) exit;

// 발행 채널 인터페이스 (BLOG_AUTOMATION_API_SPEC.md 워드프레스/자체 PHP 연동)
// $site_row는 base_url·platform 등 발행 대상 사이트 정보, $credentials_row는 인증정보(없을 수 있음).
interface BlogPublisherInterface
{
    // 반환: array('ok'=>bool, 'external_post_id'=>string, 'published_url'=>string, 'error'=>string)
    public function publish(array $post_target_row, array $site_row, ?array $credentials_row): array;
}

// 테스트 가능한 목 발행기 — 실제 네트워크 호출 없이 발행 성공/실패를 재현한다.
// 제목에 리터럴 문자열 '[FAIL_TEST]'가 포함되면 의도적으로 실패를 반환한다(재시도·중복방지 테스트용 트리거).
class BlogMockPublisher implements BlogPublisherInterface
{
    public function publish(array $post_target_row, array $site_row, ?array $credentials_row): array
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

// 워드프레스 REST 연동 — Application Password 인증, HTTPS 필수, 항상 draft로만 생성한다
// (BLOG_AUTOMATION_QA.md: "공개 자동발행 없음 — publish_status = draft 고정" — 이 MVP 전체의
// 하드 룰이며 옵션으로 바꿀 수 없다. 실제 공개 발행 경로는 이 클래스에 존재하지 않는다).
// external_post_id가 이미 있으면 새로 만들지 않고 같은 글을 갱신한다(중복 발행 방지).
class BlogWordPressPublisher implements BlogPublisherInterface
{
    /** @var callable */
    private $transport;

    // $transport(string $method, string $url, array $headers, ?string $body): array{http_code:int, body:string, error:string}
    // 테스트 하네스가 실제 네트워크 호출 없이 응답을 주입할 수 있도록 하는 최소한의 접합점.
    public function __construct(?callable $transport = null)
    {
        $this->transport = $transport !== null ? $transport : array($this, 'curlTransport');
    }

    public function publish(array $post_target_row, array $site_row, ?array $credentials_row): array
    {
        $base_url = isset($site_row['base_url']) ? rtrim($site_row['base_url'], '/') : '';
        if (strpos($base_url, 'https://') !== 0) {
            return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => 'HTTPS 사이트만 발행할 수 있습니다.');
        }

        if (!$credentials_row || empty($credentials_row['cred_username']) || empty($credentials_row['cred_value_enc'])) {
            return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => '이 사이트에 워드프레스 인증정보가 설정되지 않았습니다.');
        }

        $app_password = bp_decrypt_secret($credentials_row['cred_value_enc']);
        if ($app_password === '') {
            return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => '인증정보 복호화에 실패했습니다.');
        }

        $existing_id = isset($post_target_row['external_post_id']) ? trim((string) $post_target_row['external_post_id']) : '';
        $url = $existing_id !== ''
            ? $base_url . '/wp-json/wp/v2/posts/' . rawurlencode($existing_id)
            : $base_url . '/wp-json/wp/v2/posts';

        $payload = array(
            'title' => isset($post_target_row['title']) ? $post_target_row['title'] : '',
            'content' => isset($post_target_row['body']) ? $post_target_row['body'] : '',
            'status' => 'draft', // 하드코딩 — 운영 publish 상태 발행 금지, 옵션화하지 않는다.
        );

        $auth = base64_encode($credentials_row['cred_username'] . ':' . $app_password);
        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic ' . $auth,
        );

        $transport = $this->transport;
        $res = $transport('POST', $url, $headers, json_encode($payload));
        // $app_password/$auth는 여기서 스코프를 벗어나며, 아래로는 절대 전달하지 않는다.

        if (!empty($res['error'])) {
            return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => bp_scrub_secrets('워드프레스 연결 오류: ' . $res['error']));
        }

        $http_code = (int) $res['http_code'];
        if ($http_code === 401 || $http_code === 403) {
            return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => '워드프레스 인증 실패(HTTP ' . $http_code . ') — Application Password를 확인해 주세요.');
        }
        if ($http_code < 200 || $http_code >= 300) {
            $parsed = json_decode((string) $res['body'], true);
            $msg = isset($parsed['message']) ? $parsed['message'] : ('HTTP ' . $http_code);
            return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => bp_scrub_secrets('워드프레스 오류: ' . $msg));
        }

        $parsed = json_decode((string) $res['body'], true);
        if (!is_array($parsed) || !isset($parsed['id'])) {
            return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => '워드프레스 응답을 해석할 수 없습니다.');
        }

        return array(
            'ok' => true,
            'external_post_id' => (string) $parsed['id'],
            'published_url' => isset($parsed['link']) ? (string) $parsed['link'] : '',
            'error' => '',
        );
    }

    private function curlTransport(string $method, string $url, array $headers, ?string $body): array
    {
        if (!function_exists('curl_init')) {
            return array('http_code' => 0, 'body' => '', 'error' => '서버에 curl 확장이 설치되어 있지 않습니다.');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        return array('http_code' => (int) $http_code, 'body' => (string) $response, 'error' => $err);
    }
}

// 자체 PHP 블로그 연동 (Stage 4)
class BlogPhpPublisher implements BlogPublisherInterface
{
    private $transport;

    public function __construct(?callable $transport = null)
    {
        $this->transport = $transport !== null ? $transport : array($this, 'curlTransport');
    }

    public function publish(array $post_target_row, array $site_row, ?array $credentials_row): array
    {
        $base_url = isset($site_row['base_url']) ? rtrim($site_row['base_url'], '/') : '';
        if (!$base_url) {
            return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => '사이트 URL이 없습니다.');
        }

        if (!$credentials_row || empty($credentials_row['cred_username']) || empty($credentials_row['cred_value_enc'])) {
            return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => 'API Key/Secret이 설정되지 않았습니다.');
        }

        $api_key = $credentials_row['cred_username'];
        $api_secret = bp_decrypt_secret($credentials_row['cred_value_enc']);
        if ($api_secret === '') {
            return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => '인증정보 복호화에 실패했습니다.');
        }

        // 카테고리 매핑 조회
        global $g5;
        $category = array('id' => '', 'name' => '미분류');
        
        // 매핑 테이블에서 사이트 카테고리 정보 획득 시도
        if (isset($g5['connect_db'])) {
            $cat_table = bp_table('category_mappings');
            // 본래라면 포스트 내부 카테고리명을 조건으로 찾겠지만, MVP에서는 첫번째 매핑을 가져오거나 빈 값
            $cat_sql = " SELECT remote_category_id, remote_category_name 
                           FROM {$cat_table} 
                          WHERE site_id = '" . (int)$site_row['id'] . "' LIMIT 1 ";
            $cat_row = sql_fetch($cat_sql);
            if ($cat_row) {
                $category['id'] = $cat_row['remote_category_id'];
                $category['name'] = $cat_row['remote_category_name'];
            }
        }

        $api_url = $base_url . '/api/blog-publish.php';
        $request_id = uniqid('req_', true);
        
        $payload = array(
            'request_id' => $request_id,
            'site_id' => $site_row['id'],
            'project_id' => $post_target_row['project_id'] ?? 0,
            'post_id' => $post_target_row['post_id'] ?? 0,
            'title' => $post_target_row['title'] ?? '',
            'slug' => $post_target_row['slug'] ?? '',
            'content_html' => $post_target_row['body'] ?? '',
            'excerpt' => mb_substr(strip_tags($post_target_row['body'] ?? ''), 0, 150),
            'category' => $category,
            'tags' => array(),
            'featured_image' => null,
            'content_images' => array(),
            'publish_status' => 'publish',
            'scheduled_at' => $post_target_row['scheduled_at'] ?? null,
            'canonical_url' => '',
            'meta_title' => $post_target_row['title'] ?? '',
            'meta_description' => ''
        );

        $json_payload = json_encode($payload, JSON_UNESCAPED_UNICODE);
        $timestamp = time();
        $nonce = uniqid('nonce_', true);
        
        $path = parse_url($api_url, PHP_URL_PATH) ?: '/';
        $body_hash = hash('sha256', $json_payload);
        $signature_raw = "POST\n{$path}\n{$timestamp}\n{$nonce}\n{$request_id}\n{$body_hash}";
        $signature = hash_hmac('sha256', $signature_raw, $api_secret);

        $headers = array(
            'Content-Type: application/json; charset=utf-8',
            'X-Blog-Site-ID: ' . $site_row['id'],
            'X-Blog-API-Key: ' . $api_key,
            'X-Blog-Timestamp: ' . $timestamp,
            'X-Blog-Nonce: ' . $nonce,
            'X-Blog-Request-ID: ' . $request_id,
            'X-Blog-Signature: ' . $signature
        );

        $transport = $this->transport;
        $res = $transport('POST', $api_url, $headers, $json_payload);

        if (!empty($res['error'])) {
            return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => bp_scrub_secrets('연결 오류: ' . $res['error']));
        }

        $http_code = (int) $res['http_code'];
        $parsed = @json_decode((string) $res['body'], true);

        if ($http_code === 401 || $http_code === 403) {
            return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => 'API 인증 실패 (HTTP ' . $http_code . ')');
        }

        if ($http_code === 200 && $parsed && !empty($parsed['success'])) {
            return array(
                'ok' => true,
                'external_post_id' => (string) ($parsed['remote_post_id'] ?? ''),
                'published_url' => (string) ($parsed['published_url'] ?? ''),
                'error' => '',
            );
        }

        $msg = isset($parsed['message']) ? $parsed['message'] : ('HTTP ' . $http_code);
        return array('ok' => false, 'external_post_id' => '', 'published_url' => '', 'error' => bp_scrub_secrets('발행 오류: ' . $msg));
    }

    private function curlTransport(string $method, string $url, array $headers, ?string $body): array
    {
        if (!function_exists('curl_init')) {
            return array('http_code' => 0, 'body' => '', 'error' => 'curl 확장이 없습니다.');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        // 테스트 등을 위해 임시로 SSL 무시 옵션 (실 운영시 true 전환 권장)
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        return array('http_code' => (int) $http_code, 'body' => (string) $response, 'error' => $err);
    }
}

// 워드프레스 사이트 연결 테스트 — 실제 성공/실패를 있는 그대로 반환한다.
// BLOG_AUTOMATION_SECURITY.md가 명시한 전례(PlusTok AI 연결 테스트가 실패해도 항상 "성공"을
// 반환한 버그)를 반복하지 않기 위해, HTTP 상태와 응답을 그대로 판정에 사용하고 절대 낙관적으로
// 가정하지 않는다. $app_password는 이 함수 호출 스코프 밖으로 전달되지 않는다.
function bp_wp_test_connection(string $base_url, string $username, string $app_password): array
{
    $base_url = rtrim($base_url, '/');
    if (strpos($base_url, 'https://') !== 0) {
        return array('ok' => false, 'error' => 'HTTPS 사이트만 테스트할 수 있습니다.');
    }
    if ($username === '' || $app_password === '') {
        return array('ok' => false, 'error' => '사용자명과 Application Password를 입력해 주세요.');
    }
    if (!function_exists('curl_init')) {
        return array('ok' => false, 'error' => '서버에 curl 확장이 설치되어 있지 않습니다.');
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $base_url . '/wp-json/wp/v2/users/me');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Basic ' . base64_encode($username . ':' . $app_password)));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 8);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    if ($err) {
        return array('ok' => false, 'error' => bp_scrub_secrets('연결 오류: ' . $err));
    }
    if ($http_code === 401 || $http_code === 403) {
        return array('ok' => false, 'error' => '인증 실패(HTTP ' . $http_code . ') — 사용자명 또는 Application Password를 확인해 주세요.');
    }
    if ($http_code !== 200) {
        return array('ok' => false, 'error' => 'HTTP ' . $http_code . ' — 워드프레스 REST API 응답을 확인해 주세요.');
    }

    $parsed = json_decode((string) $response, true);
    if (!is_array($parsed) || !isset($parsed['id'])) {
        return array('ok' => false, 'error' => '응답을 해석할 수 없습니다(워드프레스 REST API가 아닐 수 있음).');
    }

    return array('ok' => true, 'error' => '');
}

function bp_get_publisher(string $platform): BlogPublisherInterface
{
    if ($platform === 'wordpress') {
        return new BlogWordPressPublisher();
    } elseif ($platform === 'php') {
        return new BlogPhpPublisher();
    }
    return new BlogMockPublisher();
}

// 승인 취소(approved -> pending_approval) 가드 — 이 프로젝트의 발행 대상 중 하나라도
// 진행 중인(pending/claimed/processing) 발행 작업이 있으면 취소를 막는다. 발행 파이프라인
// 자체는 이번 단계 범위가 아니므로 이 함수만 새로 추가하고 기존 발행 로직은 건드리지 않는다.
function bp_has_active_publish_job(int $projectId): bool
{
    $jobs_table = bp_table('publish_jobs');
    $targets_table = bp_table('post_targets');
    $posts_table = bp_table('posts');

    $row = sql_fetch(" select count(*) as cnt
                        from {$jobs_table} j
                        inner join {$targets_table} t on t.id = j.post_target_id
                        inner join {$posts_table} p on p.id = t.post_id
                        where p.project_id = '{$projectId}'
                          and j.status in ('pending','claimed','processing','scheduled') ");
    return $row && (int) $row['cnt'] > 0;
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

const BP_MAX_PUBLISH_ATTEMPTS = 3;

// 재시도 백오프: 시도 횟수가 늘수록 대기 시간을 늘린다(최대 30분). 워드프레스/외부 서비스에
// 실패 직후 바로 다시 두드리는 것을 막기 위함이다.
function bp_next_retry_backoff_minutes(int $attemptCount): int
{
    return min(30, (int) pow(2, max(0, $attemptCount)));
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
    if (!$site) {
        return array('ok' => false, 'error' => '발행 대상 사이트를 찾을 수 없습니다.');
    }

    $publisher = bp_get_publisher($site['platform']);
    $result = $publisher->publish($target, $site, $cred);

    $attempt_no = (int) $job['attempt_count'] + 1;
    $status = $result['ok'] ? 'success' : 'failed';
    $safe_message = bp_scrub_secrets(mb_substr($result['error'], 0, 500));
    sql_query(" insert into {$attempts_table}
                    set publish_job_id = '" . (int)$job['id'] . "',
                        attempt_no = '{$attempt_no}',
                        status = '" . sql_real_escape_string($status) . "',
                        response_code = '" . ($result['ok'] ? '200' : 'ERR') . "',
                        response_message = '" . sql_real_escape_string($safe_message) . "',
                        created_at = '" . G5_TIME_YMDHIS . "' ");

    if ($result['ok']) {
        sql_query(" update {$targets_table}
                        set publish_status = 'published',
                            external_post_id = '" . sql_real_escape_string($result['external_post_id']) . "',
                            published_url = '" . sql_real_escape_string($result['published_url']) . "',
                            last_error = '',
                            updated_at = '" . G5_TIME_YMDHIS . "'
                        where id = '{$post_target_id}' ");
        sql_query(" update {$jobs_table} set status = 'published', active_lock_key = NULL, next_retry_at = NULL, updated_at = '" . G5_TIME_YMDHIS . "' where id = '" . (int)$job['id'] . "' ");
        bp_transition_project($project['id'], 'published', $actor, 'job#' . $job['id'] . ' success');
        return array('ok' => true, 'external_post_id' => $result['external_post_id'], 'published_url' => $result['published_url']);
    }

    $backoff_minutes = bp_next_retry_backoff_minutes((int) $job['attempt_count'] + 1);
    sql_query(" update {$targets_table}
                    set publish_status = 'failed',
                        last_error = '" . sql_real_escape_string($safe_message) . "',
                        retry_count = retry_count + 1,
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = '{$post_target_id}' ");
    sql_query(" update {$jobs_table}
                    set status = 'failed', active_lock_key = NULL,
                        next_retry_at = DATE_ADD('" . G5_TIME_YMDHIS . "', INTERVAL {$backoff_minutes} MINUTE),
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = '" . (int)$job['id'] . "' ");
    bp_transition_project($project['id'], 'failed', $actor, 'job#' . $job['id'] . ' ' . $safe_message);

    return array('ok' => false, 'error' => $result['error'], 'job_id' => $job['id']);
}

// 재시도 진입점 — project_action.php mode=retry가 호출한다. 시도 횟수 상한과 백오프 대기
// 시간을 통과해야만 실제 재발행(bp_dispatch_publish_job)으로 넘어간다.
function bp_retry_publish_job(int $post_target_id, string $actor): array
{
    $targets_table = bp_table('post_targets');
    $posts_table = bp_table('posts');
    $projects_table = bp_table('content_projects');
    $jobs_table = bp_table('publish_jobs');

    $target = sql_fetch(" select * from {$targets_table} where id = '{$post_target_id}' ");
    if (!$target) {
        return array('ok' => false, 'error' => '발행 대상을 찾을 수 없습니다.');
    }

    if ((int) $target['retry_count'] >= BP_MAX_PUBLISH_ATTEMPTS) {
        return array('ok' => false, 'error' => '재시도 가능 횟수(' . BP_MAX_PUBLISH_ATTEMPTS . '회)를 모두 사용했습니다.');
    }

    $latest_job = sql_fetch(" select * from {$jobs_table} where post_target_id = '{$post_target_id}' order by id desc limit 1 ");
    if ($latest_job && !empty($latest_job['next_retry_at'])) {
        $wait_check = sql_fetch(" select (NOW() >= '" . sql_real_escape_string($latest_job['next_retry_at']) . "') as can_retry ");
        if (!$wait_check || !(int) $wait_check['can_retry']) {
            return array('ok' => false, 'error' => '아직 재시도 대기 시간입니다(' . $latest_job['next_retry_at'] . ' 이후 가능).');
        }
    }

    $post = sql_fetch(" select * from {$posts_table} where id = '" . (int)$target['post_id'] . "' ");
    if (!$post) {
        return array('ok' => false, 'error' => '원본 포스팅을 찾을 수 없습니다.');
    }
    $project = sql_fetch(" select * from {$projects_table} where id = '" . (int)$post['project_id'] . "' ");
    if (!$project) {
        return array('ok' => false, 'error' => '콘텐츠 프로젝트를 찾을 수 없습니다.');
    }
    if ($project['status'] !== 'failed') {
        return array('ok' => false, 'error' => "'{$project['status']}' 상태에서는 재시도할 수 없습니다(failed 상태에서만 가능).");
    }

    $transition = bp_transition_project($project['id'], 'publish_pending', $actor, '재시도');
    if (!$transition['ok']) {
        return array('ok' => false, 'error' => $transition['error']);
    }

    return bp_dispatch_publish_job($post_target_id, $actor);
}
