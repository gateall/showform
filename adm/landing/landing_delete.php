<?php
include_once('./_common.php');

$sub_menu = '900100';
auth_check_menu($auth, $sub_menu, 'w');

$table = G5_TABLE_PREFIX . 'landing_page';
$ids = array();

if (isset($_POST['chk']) && is_array($_POST['chk'])) {
    foreach ($_POST['chk'] as $id) {
        $id = (int) $id;
        if ($id) {
            $ids[] = $id;
        }
    }
}

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    if ($id) {
        $ids[] = $id;
    }
}

$ids = array_values(array_unique($ids));
if (!count($ids)) {
    alert('삭제할 대상이 없습니다.', './landing_list.php');
}

foreach ($ids as $id) {
    sql_query(" delete from {$table} where id = '{$id}' ");
}

alert('랜딩이 삭제되었습니다.', './landing_list.php');