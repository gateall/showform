<?php
include_once('./_common.php');

if ($is_admin != 'super') {
    alert('권한이 없습니다.');
}

$table = G5_TABLE_PREFIX . 'landing_ai_log';

// 검색 조건 그대로 연동
$fr_date = isset($_GET['fr_date']) ? $_GET['fr_date'] : date('Y-m-d', strtotime('-6 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$model = isset($_GET['model']) ? trim($_GET['model']) : '';
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';

$sql_search = " WHERE DATE(created_at) between '{$fr_date}' and '{$to_date}' ";
if ($model) $sql_search .= " and model_name = '{$model}' ";
if ($action) $sql_search .= " and action_type = '{$action}' ";
if ($status) $sql_search .= " and status = '{$status}' ";
if ($stx) {
    $sql_search .= " and (mb_id like '%{$stx}%' or prompt like '%{$stx}%' or error_msg like '%{$stx}%') ";
}

$sql = " select * from {$table} {$sql_search} order by id desc ";
$result = sql_query($sql);

$filename = 'AI호출로그내역_'.date('Ymd').'.csv';

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
echo "\xEF\xBB\xBF"; // UTF-8 BOM

$fp = fopen('php://output', 'w');
fputcsv($fp, array('번호', '호출일시', '요청자', '기능분류', '사용모델', '토큰', '상태', '프롬프트 원문', '에러메시지'));

while($row = sql_fetch_array($result)) {
    fputcsv($fp, array(
        $row['id'],
        $row['created_at'],
        $row['mb_id'],
        $row['action_type'],
        $row['model_name'],
        $row['tokens'],
        $row['status'],
        $row['prompt'],
        $row['error_msg']
    ));
}
fclose($fp);
exit;
?>
