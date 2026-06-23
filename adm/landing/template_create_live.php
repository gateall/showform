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
$row = sql_fetch(" select * from {$table} where id = '{$id}' and is_template = 'Y' ");

if (!$row) {
    echo json_encode(array('error' => '존재하지 않는 템플릿입니다.'));
    exit;
}

// ID 필드 제외, 파생 데이터로 설정
unset($row['id']);
$row['subject'] = $row['subject'] . ' (라이브 배포)';
$row['is_template'] = 'N'; // 이제 마스터가 아니라 라이브 페이지임
$row['parent_id'] = $id;   // 어떤 템플릿에서 복사되었는지 출처 기록
$row['is_display'] = 'N';  // 라이브 페이지는 최초 생성 시 OFF 상태로 대기

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
