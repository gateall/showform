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

$posts_table = bp_table('posts');

// advertiser_delete.php와 같은 이유 - project_action.php의 삭제는 소프트 삭제(deleted_at만
// 채움)라서 행이 그대로 남고, content_projects.primary_site_id/post_targets 체인이
// RESTRICT로 sites를 참조하고 있어 실제 DELETE가 DB 단에서 막힌다. 이 사이트와 관련된
// 소프트 삭제 프로젝트(primary_site_id로 직접 연결됐거나, 그 프로젝트의 포스트가 이
// 사이트로 발행 대상이었던 것 전부)를 먼저 완전히 치운다.
$linked_deleted_project_ids = array();
$dp1 = sql_query(" select id from {$projects_table} where primary_site_id = '{$id}' and deleted_at is not null ");
while ($dp1_row = sql_fetch_array($dp1)) {
    $linked_deleted_project_ids[(int) $dp1_row['id']] = true;
}
$dp2 = sql_query(" select p.project_id as project_id from {$targets_table} t
                    join {$posts_table} p on p.id = t.post_id
                    join {$projects_table} cp on cp.id = p.project_id
                    where t.site_id = '{$id}' and cp.deleted_at is not null ");
while ($dp2_row = sql_fetch_array($dp2)) {
    $linked_deleted_project_ids[(int) $dp2_row['project_id']] = true;
}
foreach (array_keys($linked_deleted_project_ids) as $purge_project_id) {
    bp_purge_deleted_project($purge_project_id);
}

// 위에서 이미 소프트 삭제 잔재를 치웠으므로, 여기서부터는 진짜 남아있는(활성) 참조만 센다.
$target_cnt = sql_fetch(" select count(*) as cnt from {$targets_table} where site_id = '{$id}' ");
$project_cnt = sql_fetch(" select count(*) as cnt from {$projects_table} where primary_site_id = '{$id}' and deleted_at is null ");
if ((int)$target_cnt['cnt'] > 0 || (int)$project_cnt['cnt'] > 0) {
    alert('이 사이트로 발행된 콘텐츠가 있어 삭제할 수 없습니다.');
}

sql_query(" delete from {$cred_table} where site_id = '{$id}' ");
$deleted = sql_query(" delete from {$table} where id = '{$id}' ");
if (!$deleted) {
    alert('삭제 중 오류가 발생했습니다. 이 사이트와 연결된 다른 데이터가 남아있을 수 있습니다.');
}
alert('사이트가 삭제되었습니다.', SF_MANAGER_URL . '/blog/settings.php?tab=channels');
