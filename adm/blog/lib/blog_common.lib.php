<?php
if (!defined('_GNUBOARD_')) exit;

// 운영 DB에 아직 아무 테이블도 없는 시점에 blog_ 네임스페이스로 확정했다(PM 지시,
// 2026-07-29). advertisers/sites 등은 애초 서비스 중립 공통 테이블로 설계됐으나
// (BLOG_AUTOMATION_DATA_MODEL.md 6절 — 원래는 blog_ 접두어 금지 방침이었음) 실제
// 운영 설치 직전에 이 방침을 뒤집고 전부 blog_ 네임스페이스로 통일하기로 결정했다.
// 실제 테이블명은 {G5_TABLE_PREFIX}blog_{name} 형태이며, 호출부는 이 짧은 이름만
// 그대로 쓰면 된다(bp_table('advertisers') 호출 자체는 변경 없음, 매핑만 바뀜).
function bp_table(string $name): string
{
    $allowed = array(
        'advertisers', 'sites', 'site_credentials', 'ai_providers',
        'content_projects', 'content_keywords', 'posts', 'post_targets',
        'publish_jobs', 'publish_attempts', 'content_activity_logs',
        'content_title_candidates', 'content_quality_checks',
        'content_generation_logs',
        // v7~v14에서 추가된 테이블 — bp_table() 호출부는 이미 여러 파일에 존재했으나
        // 이 허용 목록이 갱신되지 않아 전부 '잘못된 테이블 요청입니다' 처리되고 있었다.
        'images', 'post_images', 'category_mappings', 'naver_packages',
        'post_performance', 'report_snapshots', 'advertiser_accounts',
        'channel_apps',
    );
    if (!in_array($name, $allowed, true)) {
        alert('잘못된 테이블 요청입니다.');
    }
    return G5_TABLE_PREFIX . 'blog_' . $name;
}

// "연결 테스트"류 AJAX 액션 전용 CSRF 토큰. 그누보드 공용 get_admin_token()/
// check_admin_token()과 완전히 분리된 세션 키(bp_test_token)를 쓴다 - 저장 폼과
// 같은 토큰을 공유하면, 연결 테스트를 한 번 실행하는 순간(그누보드 토큰은 1회성이라
// 검사 즉시 세션에서 지워짐) 저장 폼에 이미 찍혀 있던 토큰이 무효가 되어 "새로고침 후
// 저장"을 강제하게 된다. 테스트는 상태를 바꾸지 않는 조회성 액션이라, 그누보드처럼
// 검사 즉시 폐기하지 않고 같은 세션 안에서는 재사용을 허용한다(여러 번 테스트 가능).
function bp_get_test_token(): string
{
    $token = get_session('bp_test_token');
    if (!$token) {
        $token = md5(uniqid((string) mt_rand(), true));
        set_session('bp_test_token', $token);
    }
    return $token;
}

function bp_check_test_token(): bool
{
    $token = get_session('bp_test_token');
    $given = isset($_REQUEST['test_token']) ? (string) $_REQUEST['test_token'] : '';
    return $token !== '' && $given !== '' && hash_equals($token, $given);
}

// 비밀값을 화면에 다시 표시하지 않기 위한 마스킹 힌트 생성 (BLOG_AUTOMATION_SECURITY.md)
// 원문은 절대 반환하지 않는다 — 앞 2자 + **** + 뒤 4자 형태의 힌트만 만든다.
function bp_mask_secret(string $plain): string
{
    $len = mb_strlen($plain);
    if ($len === 0) {
        return '';
    }
    if ($len <= 6) {
        return str_repeat('*', $len);
    }
    $head = mb_substr($plain, 0, 2);
    $tail = mb_substr($plain, $len - 4, 4);
    return $head . '****' . $tail;
}

// 상태 변경 감사 로그
function bp_log_activity(int $project_id, string $action, string $actor, string $detail = ''): void
{
    $table = bp_table('content_activity_logs');
    sql_query(" insert into {$table}
                    set project_id = '" . (int)$project_id . "',
                        action = '" . sql_real_escape_string($action) . "',
                        actor = '" . sql_real_escape_string($actor) . "',
                        detail = '" . sql_real_escape_string(mb_substr($detail, 0, 500)) . "',
                        created_at = '" . G5_TIME_YMDHIS . "' ");
}

function bp_current_admin_id(): string
{
    global $member;
    return isset($member['mb_id']) && $member['mb_id'] ? $member['mb_id'] : 'admin';
}

// 사이트 전역 G5_DB_CHARSET(config.php, 현재 'utf8')과는 별개로, 블로그 자동화 화면이
// 사용하는 이번 요청의 DB 연결만 utf8mb4로 재설정한다. blog_* 테이블이 전부 utf8mb4이므로
// 이모지 등 4바이트 문자가 연결 단계에서 잘리지 않도록 하기 위함이며, PHP는 요청마다
// 독립적으로 실행되므로 다른(비-블로그) 페이지의 연결에는 영향을 주지 않는다.
// config.php의 전역 설정은 이 작업 범위에서 변경하지 않는다.
function bp_ensure_utf8mb4_connection(): void
{
    global $g5;
    if (!isset($g5['connect_db'])) {
        return;
    }
    if (!mysqli_set_charset($g5['connect_db'], 'utf8mb4')) {
        alert('블로그 자동화 DB 연결 문자셋(utf8mb4) 설정에 실패했습니다.');
    }
}

// AI가 생성한(또는 템플릿이 만든) 본문의 {{...}} 자리표시자를 실제 광고주 정보로 치환한다.
// 실 API 공급자·템플릿 공급자 양쪽 모두 이 함수를 거쳐야 전화번호·상호가 항상 정확하다
// (BLOG_AUTOMATION_SECURITY.md: "전화번호·상호 자동 치환 — 수기 오기 방지").
function bp_apply_business_placeholders(string $body, array $advertiser): string
{
    $map = array(
        '{{business_name}}' => isset($advertiser['name']) ? $advertiser['name'] : '',
        '{{phone}}' => isset($advertiser['phone']) ? $advertiser['phone'] : '',
        '{{address}}' => isset($advertiser['address']) ? $advertiser['address'] : '',
        '{{service_region}}' => isset($advertiser['service_region']) ? $advertiser['service_region'] : '',
        '{{consult_url}}' => isset($advertiser['consult_url']) ? $advertiser['consult_url'] : '',
    );
    return str_replace(array_keys($map), array_values($map), $body);
}

// 비밀정보로 오인/유출될 수 있는 문자열이 저장·표시용 텍스트에 섞여 들어가는 것을 막는
// 마지막 방어선. 호출부가 애초에 비밀값을 넣지 않는 것이 1차 방어이며, 이 함수는 그 위에
// 얹는 2차 방어(defense in depth)다 — Authorization 헤더 값, Basic 인증 문자열, WordPress
// Application Password 형식(xxxx xxxx xxxx xxxx xxxx xxxx)을 마스킹한다.
function bp_scrub_secrets(string $text): string
{
    $text = preg_replace('/Authorization:\s*.+/i', 'Authorization: [REDACTED]', $text);
    $text = preg_replace('/Basic\s+[A-Za-z0-9+\/=]+/', 'Basic [REDACTED]', $text);
    $text = preg_replace('/\b([a-zA-Z0-9]{4}\s){5}[a-zA-Z0-9]{4}\b/', '[REDACTED]', $text);
    return $text;
}

// 제목/본문 생성 시도(성공·실패 모두)를 content_generation_logs에 기록한다.
// $result는 bp_ai_generate_titles()/bp_ai_generate_body()의 반환값 그대로 받는다.
// 비용 추정치는 provider='openai'이고 실제 토큰 사용량이 있을 때만 계산한다
// (템플릿 폴백은 외부 호출이 없으므로 비용이 발생하지 않는다).
function bp_log_generation_attempt(int $projectId, string $action, string $provider, string $model, array $result): void
{
    $table = bp_table('content_generation_logs');
    $status = !empty($result['ok']) ? 'success' : 'fail';
    $tokens_prompt = isset($result['tokens_prompt']) ? (int) $result['tokens_prompt'] : null;
    $tokens_completion = isset($result['tokens_completion']) ? (int) $result['tokens_completion'] : null;

    $cost_estimate = null;
    if ($provider === 'openai' && ($tokens_prompt > 0 || $tokens_completion > 0) && function_exists('bp_estimate_openai_cost')) {
        $cost_estimate = bp_estimate_openai_cost($model, (int) $tokens_prompt, (int) $tokens_completion);
    }

    $error_message = isset($result['error']) ? bp_scrub_secrets(mb_substr((string) $result['error'], 0, 500)) : '';

    $tokens_prompt_sql = $tokens_prompt !== null ? "'{$tokens_prompt}'" : 'NULL';
    $tokens_completion_sql = $tokens_completion !== null ? "'{$tokens_completion}'" : 'NULL';
    $cost_sql = $cost_estimate !== null ? "'{$cost_estimate}'" : 'NULL';

    sql_query(" insert into {$table}
                    set project_id = '{$projectId}',
                        action = '" . sql_real_escape_string($action) . "',
                        provider = '" . sql_real_escape_string($provider) . "',
                        model = '" . sql_real_escape_string($model) . "',
                        status = '{$status}',
                        tokens_prompt = {$tokens_prompt_sql},
                        tokens_completion = {$tokens_completion_sql},
                        cost_estimate = {$cost_sql},
                        error_message = '" . sql_real_escape_string($error_message) . "',
                        created_at = '" . G5_TIME_YMDHIS . "' ");
}
