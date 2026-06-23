<?php
include_once('./_common.php');

$sub_menu = '900100';
auth_check_menu($auth, $sub_menu, 'w');

header('Content-Type: application/json; charset=UTF-8');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (!$id) {
    echo json_encode(array('error' => '잘못된 요청입니다.'));
    exit;
}

if (!in_array($status, array('live', 'stop', 'end'), true)) {
    echo json_encode(array('error' => '상태 값이 올바르지 않습니다.'));
    exit;
}

$table = G5_TABLE_PREFIX . 'landing_page';
$row = sql_fetch(" select id from {$table} where id = '{$id}' ");
if (!$row) {
    echo json_encode(array('error' => '랜딩페이지를 찾을 수 없습니다.'));
    exit;
}

sql_query(" update {$table} set status = '" . sql_real_escape_string($status) . "', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$id}' ");

echo json_encode(array('success' => true));