<?php
include_once('./_common.php');

$sub_menu = '360400';
auth_check_menu($auth, $sub_menu, 'd');
check_admin_token();

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$table = bp_table('ai_providers');
$projects_table = bp_table('content_projects');

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
if ($id < 1) {
    alert('삭제할 AI 공급자를 선택해 주세요.');
}

$in_use = sql_fetch(" select count(*) as cnt from {$projects_table} where ai_provider_id = '{$id}' ");
if ((int) $in_use['cnt'] > 0) {
    alert('이 공급자를 지정해 둔 프로젝트가 있어 삭제할 수 없습니다. 해당 프로젝트에서 먼저 다른 공급자로 바꿔주세요.');
}

sql_query(" delete from {$table} where id = '{$id}' ");

$return = isset($_REQUEST['return']) ? $_REQUEST['return'] : '';
$redirect = ($return === 'manager')
    ? SF_MANAGER_URL . '/blog/settings.php?tab=ai'
    : G5_ADMIN_URL . '/blog/ai_provider_list.php';

alert('AI 공급자가 삭제되었습니다.', $redirect);
