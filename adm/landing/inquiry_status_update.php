<?php
include_once('./_common.php');

header('Content-Type: application/json; charset=utf-8');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$status = isset($_POST['status']) ? trim($_POST['status']) : '';

if (!$id || !$status) {
    echo json_encode(array('error' => '잘못된 요청입니다.'));
    exit;
}

$table = G5_TABLE_PREFIX . 'landing_inquiry';
$sql = " update {$table} set status = '{$status}', updated_at = '".G5_TIME_YMDHIS."' where id = '{$id}' ";
sql_query($sql);

echo json_encode(array('success' => true));
