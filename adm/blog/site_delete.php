<?php
include_once('./_common.php');

$sub_menu = '360300';
auth_check_menu($auth, $sub_menu, 'd');
check_admin_token();

$table = bp_table('sites');
$cred_table = bp_table('site_credentials');
$targets_table = bp_table('post_targets');
$projects_table = bp_table('content_projects');

$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
if ($id < 1) {
    alert('삭제할 사이트를 선택해 주세요.');
}

$target_cnt = sql_fetch(" select count(*) as cnt from {$targets_table} where site_id = '{$id}' ");
// advertiser_delete.php와 같은 이유로 deleted_at is null을 걸어야 한다 - project_action.php의
// 삭제는 소프트 삭제(deleted_at만 채움)라서, 안 걸면 이미 삭제된 프로젝트까지 계속
// "발행된 콘텐츠 있음"으로 잡혀 사이트를 영원히 삭제할 수 없게 된다.
$project_cnt = sql_fetch(" select count(*) as cnt from {$projects_table} where primary_site_id = '{$id}' and deleted_at is null ");
if ((int)$target_cnt['cnt'] > 0 || (int)$project_cnt['cnt'] > 0) {
    alert('이 사이트로 발행된 콘텐츠가 있어 삭제할 수 없습니다.');
}

sql_query(" delete from {$cred_table} where site_id = '{$id}' ");
sql_query(" delete from {$table} where id = '{$id}' ");
alert('사이트가 삭제되었습니다.', G5_ADMIN_URL . '/blog/site_list.php');
