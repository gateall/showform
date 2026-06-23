<?php
include_once('./_common.php');
header('Content-Type: application/json; charset=utf-8');

if ($is_admin != 'super') {
    echo json_encode(array('error' => '권한이 없습니다.'));
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$is_display = isset($_POST['is_display']) && $_POST['is_display'] == 'N' ? 'N' : 'Y';

if (!$id) {
    echo json_encode(array('error' => '잘못된 요청입니다.'));
    exit;
}

$table = G5_TABLE_PREFIX . 'landing_page';
sql_query(" update {$table} set is_display = '{$is_display}' where id = '{$id}' ");

echo json_encode(array('success' => true));
?>
