<?php
include_once('./_common.php');

$sub_menu = '360100';
auth_check_menu($auth, $sub_menu, 'd');
check_admin_token();

$table = bp_table('advertisers');
$sites_table = bp_table('sites');
$projects_table = bp_table('content_projects');

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
if ($id < 1) {
    alert('삭제할 광고주를 선택해 주세요.');
}

$site_cnt = sql_fetch(" select count(*) as cnt from {$sites_table} where advertiser_id = '{$id}' ");
// project_action.php의 mode=delete는 실제로 행을 지우지 않고 deleted_at만 채우는 소프트
// 삭제라서, 이 조건에 deleted_at is null이 없으면 이미 삭제된(soft-deleted) 프로젝트까지
// "연결된 프로젝트"로 계속 잡혀 광고주를 영원히 삭제할 수 없게 된다.
$project_cnt = sql_fetch(" select count(*) as cnt from {$projects_table} where advertiser_id = '{$id}' and deleted_at is null ");
if ((int)$site_cnt['cnt'] > 0 || (int)$project_cnt['cnt'] > 0) {
    alert('이 광고주와 연결된 사이트 또는 콘텐츠 프로젝트가 있어 삭제할 수 없습니다. 먼저 연결된 항목을 정리해 주세요.');
}

// content_projects.advertiser_id는 ON DELETE RESTRICT라서, 위에서 걸러낸 소프트 삭제된
// 프로젝트도 여전히 실제 참조 행이라 바로 아래 DELETE가 DB 단에서 막힌다
// (G5_DISPLAY_SQL_ERROR=false라 화면엔 아무 에러도 안 뜨고 "삭제되었습니다"만 뜨면서
// 실제로는 아무 것도 안 지워지는 걸 실사용자가 그대로 재현했다) - 실제로 지우기 전에
// 이 광고주의 소프트 삭제된 프로젝트를 완전히 치운다.
$soft_deleted_res = sql_query(" select id from {$projects_table} where advertiser_id = '{$id}' and deleted_at is not null ");
while ($soft_deleted_row = sql_fetch_array($soft_deleted_res)) {
    bp_purge_deleted_project((int) $soft_deleted_row['id']);
}

$deleted = sql_query(" delete from {$table} where id = '{$id}' ");
if (!$deleted) {
    alert('삭제 중 오류가 발생했습니다. 이 광고주와 연결된 다른 데이터가 남아있을 수 있습니다.');
}
// advertiser_list.php(옛 화면)로 보내면, 새 manager 화면에서 삭제를 눌러도 옛 화면으로
// 튕기는 혼란스러운 흐름이 된다 - 실제 목록 화면은 이제 content_management.php다.
alert('광고주가 삭제되었습니다.', SF_MANAGER_URL . '/blog/content_management.php?tab=advertisers');
