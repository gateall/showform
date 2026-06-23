<?php
include_once('./_common.php');

$sub_menu = '990053';
auth_check_menu($auth, $sub_menu, 'd');

$table = G5_TABLE_PREFIX . 'landing_category';
$landing_table = G5_TABLE_PREFIX . 'landing_page';

$id = 0;
if (isset($_REQUEST['id'])) {
    $id = (int) $_REQUEST['id'];
}
$ids = array();
if (isset($_POST['chk']) && is_array($_POST['chk'])) {
    $ids = array_map('intval', $_POST['chk']);
} elseif ($id > 0) {
    $ids = array($id);
}

if (!count($ids)) {
    alert('삭제할 카테고리를 선택해 주세요.');
}

foreach ($ids as $cid) {
    if ($cid < 1) continue;
    $cnt_row = sql_fetch(" select count(*) as cnt from {$landing_table} where category_id = '{$cid}' ");
    $cnt = isset($cnt_row['cnt']) ? (int) $cnt_row['cnt'] : 0;
    if ($cnt > 0) {
        alert('해당 카테고리를 사용하는 랜딩페이지가 존재합니다. 먼저 카테고리를 변경하거나 랜딩페이지를 삭제해주세요.');
    }
    sql_query(" delete from {$table} where id = '{$cid}' ");
}

alert('카테고리가 삭제되었습니다.', G5_ADMIN_URL . '/landing/category_list.php');
