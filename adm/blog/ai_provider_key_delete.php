<?php
include_once('./_common.php');

$sub_menu = '360400';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$table = bp_table('ai_providers');
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
if ($id < 1) {
    alert('공급자를 선택해 주세요.');
}

$row = sql_fetch(" select id, provider_code from {$table} where id = '{$id}' ");
if (!$row) {
    alert('AI 공급자를 찾을 수 없습니다.');
}

// 공급자 등록 자체는 남기고, 저장된 키/마스킹 힌트만 비운다(전체 삭제는 ai_provider_delete.php).
sql_query(" update {$table}
                set api_key_enc = '',
                    masked_hint = '',
                    updated_by = '" . sql_real_escape_string(bp_current_admin_id()) . "',
                    updated_at = '" . G5_TIME_YMDHIS . "'
                where id = '{$id}' ");

$delete_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
bp_log_activity(0, 'ai_key_delete', bp_current_admin_id(), "공급자 ID {$id}({$row['provider_code']}) API 키 삭제, IP: {$delete_ip}");

$return = isset($_REQUEST['return']) ? $_REQUEST['return'] : '';
$redirect = ($return === 'manager')
    ? SF_MANAGER_URL . '/blog/settings.php?tab=ai'
    : G5_ADMIN_URL . '/blog/ai_provider_form.php?id=' . $id;

alert('저장된 API 키가 삭제되었습니다.', $redirect);
