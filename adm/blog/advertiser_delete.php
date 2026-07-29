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
$project_cnt = sql_fetch(" select count(*) as cnt from {$projects_table} where advertiser_id = '{$id}' ");
if ((int)$site_cnt['cnt'] > 0 || (int)$project_cnt['cnt'] > 0) {
    alert('이 광고주와 연결된 사이트 또는 콘텐츠 프로젝트가 있어 삭제할 수 없습니다. 먼저 연결된 항목을 정리해 주세요.');
}

sql_query(" delete from {$table} where id = '{$id}' ");
alert('광고주가 삭제되었습니다.', G5_ADMIN_URL . '/blog/advertiser_list.php');
