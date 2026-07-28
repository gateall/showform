<?php
if (!defined('_GNUBOARD_')) exit;

function bp_table($name)
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
function bp_mask_secret($plain)
{
    $plain = (string) $plain;
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
function bp_log_activity($project_id, $action, $actor, $detail = '')
{
    $table = bp_table('content_activity_logs');
    sql_query(" insert into {$table}
                    set project_id = '" . (int)$project_id . "',
                        action = '" . sql_real_escape_string($action) . "',
                        actor = '" . sql_real_escape_string($actor) . "',
                        detail = '" . sql_real_escape_string(mb_substr($detail, 0, 500)) . "',
                        created_at = '" . G5_TIME_YMDHIS . "' ");
}

function bp_current_admin_id()
{
    global $member;
    return isset($member['mb_id']) && $member['mb_id'] ? $member['mb_id'] : 'admin';
}
