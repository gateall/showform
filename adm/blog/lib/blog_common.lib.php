<?php
if (!defined('_GNUBOARD_')) exit;

function bp_table(string $name): string
{
    $allowed = array(
        'advertisers', 'sites', 'site_credentials', 'ai_providers',
        'content_projects', 'content_keywords', 'posts', 'post_targets',
        'publish_jobs', 'publish_attempts', 'content_activity_logs',
    );
    if (!in_array($name, $allowed, true)) {
        alert('잘못된 테이블 요청입니다.');
    }
    return G5_TABLE_PREFIX . $name;
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
