<?php
include_once('./_common.php');

header('Content-Type: application/json; charset=UTF-8');

auth_check_menu($auth, '900200', 'w');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';
$allow = array('new', 'contacted', 'completed');

if (!$id || !in_array($status, $allow, true)) {
    echo json_encode(array('error' => '잘못된 요청입니다.'));
    exit;
}

$table = G5_TABLE_PREFIX . 'landing_inquiries';
$row = sql_fetch(" select id from {$table} where id = '{$id}' limit 1 ");
if (!$row) {
    echo json_encode(array('error' => '문의를 찾을 수 없습니다.'));
    exit;
}

sql_query(" update {$table} set status = '" . sql_real_escape_string($status) . "' where id = '{$id}' ");

echo json_encode(array('success' => true));