<?php
include_once('./_common.php');
header('Content-Type: application/json; charset=utf-8');

if ($is_admin != 'super') {
    echo json_encode(array('error' => '권한이 없습니다.'));
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if (!$id) {
    echo json_encode(array('error' => '잘못된 요청입니다.'));
    exit;
}

$table = G5_TABLE_PREFIX . 'landing_page';
$row = sql_fetch(" select * from {$table} where id = '{$id}' ");

if (!$row) {
    echo json_encode(array('error' => '존재하지 않는 템플릿입니다.'));
    exit;
}

// ID 필드 제외하고 쿼리 생성
unset($row['id']);
$row['subject'] = $row['subject'] . ' _복사본';
$row['hero_title'] = $row['hero_title'] . ' _복사본';

$sql_common = "";
foreach($row as $key => $val) {
    $val = sql_real_escape_string($val);
    $sql_common .= " `{$key}` = '{$val}', ";
}
$sql_common = rtrim($sql_common, ", ");

$sql = " insert into {$table} set {$sql_common} ";
$result = sql_query($sql, false);

if($result) {
    echo json_encode(array('success' => true));
} else {
    echo json_encode(array('error' => 'DB 인서트 실패'));
}
?>
