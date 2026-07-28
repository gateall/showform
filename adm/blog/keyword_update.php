<?php
include_once('./_common.php');

$sub_menu = '360400';
$mode = isset($_REQUEST['mode']) ? trim($_REQUEST['mode']) : '';
auth_check_menu($auth, $sub_menu, $mode === 'delete' ? 'd' : 'w');
check_admin_token();

$table = bp_table('content_keywords');
$project_id = isset($_REQUEST['project_id']) ? (int) $_REQUEST['project_id'] : 0;

if ($mode === 'delete') {
    $id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
    if ($id > 0) {
        sql_query(" delete from {$table} where id = '{$id}' and project_id = '{$project_id}' ");
    }
    alert('키워드가 삭제되었습니다.', G5_ADMIN_URL . '/blog/keyword_list.php?project_id=' . $project_id);
}

$keyword_group = isset($_POST['keyword_group']) ? trim($_POST['keyword_group']) : 'primary';
$allowed_groups = array('primary', 'secondary', 'question', 'region', 'service', 'comparison', 'intent', 'exclude');
if (!in_array($keyword_group, $allowed_groups, true)) {
    $keyword_group = 'primary';
}
$keyword = isset($_POST['keyword']) ? trim($_POST['keyword']) : '';
$is_locked = isset($_POST['is_locked']) && $_POST['is_locked'] === 'Y' ? 'Y' : 'N';

if ($keyword === '') {
    alert('키워드를 입력해 주세요.');
}
if ($project_id < 1) {
    alert('잘못된 접근입니다.');
}

sql_query(" insert into {$table}
                set project_id = '{$project_id}',
                    keyword_group = '{$keyword_group}',
                    keyword = '" . sql_real_escape_string($keyword) . "',
                    is_locked = '{$is_locked}',
                    created_at = '" . G5_TIME_YMDHIS . "' ");

alert('키워드가 추가되었습니다.', G5_ADMIN_URL . '/blog/keyword_list.php?project_id=' . $project_id);
