<?php
include_once('./_common.php');

$sub_menu = '900100';
auth_check_menu($auth, $sub_menu, 'w');

$page = isset($_POST['page']) ? (int) $_POST['page'] : 1;
$rows = isset($_POST['rows']) ? (int) $_POST['rows'] : 20;
$category_id = isset($_POST['category_id']) ? (int) $_POST['category_id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$search = isset($_POST['search']) ? trim($_POST['search']) : '';
$sfl = isset($_POST['sfl']) ? trim($_POST['sfl']) : 'subject';
$mode = isset($_POST['mode']) ? trim($_POST['mode']) : '';

$table = G5_TABLE_PREFIX . 'landing_page';

$chk = isset($_POST['chk']) && is_array($_POST['chk']) ? $_POST['chk'] : array();
if (!count($chk)) {
    alert('선택된 항목이 없습니다.', './landing_list.php');
}

if ($mode === 'delete') {
    foreach ($chk as $id) {
        $id = (int) $id;
        if ($id) {
            sql_query(" delete from {$table} where id = '{$id}' ");
        }
    }
    $msg = '선택한 랜딩페이지를 삭제했습니다.';
} else {
    foreach ($chk as $id) {
        $id = (int) $id;
        if ($id) {
            sql_query(" update {$table} set status = 'stop', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$id}' ");
        }
    }
    $msg = '선택한 랜딩페이지 상태를 일시정지로 변경했습니다.';
}

$url = './landing_list.php?' . http_build_query(array(
    'page' => $page,
    'rows' => $rows,
    'category_id' => $category_id,
    'status' => $status,
    'search' => $search,
    'sfl' => $sfl,
));

alert($msg, $url);