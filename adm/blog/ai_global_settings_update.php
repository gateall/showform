<?php
include_once('./_common.php');

$sub_menu = '360400';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$is_enabled = (isset($_POST['is_enabled']) && $_POST['is_enabled'] === 'Y') ? 'Y' : 'N';
$table = bp_table('ai_global_settings');

$exists = sql_fetch(" select id from {$table} where id = 1 ", false);
if ($exists) {
    sql_query(" update {$table}
                    set is_enabled = '{$is_enabled}',
                        updated_by = '" . sql_real_escape_string(bp_current_admin_id()) . "',
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = 1 ");
} else {
    sql_query(" insert into {$table}
                    set id = 1,
                        is_enabled = '{$is_enabled}',
                        updated_by = '" . sql_real_escape_string(bp_current_admin_id()) . "',
                        updated_at = '" . G5_TIME_YMDHIS . "' ");
}

$toggle_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
bp_log_activity(0, 'ai_global_toggle', bp_current_admin_id(), "AI 기능 전체 사용 여부 변경: {$is_enabled}, IP: {$toggle_ip}");

$return = isset($_POST['return']) ? $_POST['return'] : '';
$redirect = ($return === 'manager')
    ? SF_MANAGER_URL . '/blog/settings.php?tab=ai'
    : G5_ADMIN_URL . '/blog/ai_provider_list.php';

alert($is_enabled === 'Y' ? 'AI 기능을 전체적으로 켰습니다.' : 'AI 기능을 전체적으로 껐습니다.', $redirect);
