<?php
include_once('./_common.php');

$table = G5_TABLE_PREFIX . 'landing_inquiry';

$sfl = isset($_GET['sfl']) ? trim($_GET['sfl']) : '';
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
$sdt = isset($_GET['sdt']) ? trim($_GET['sdt']) : ''; 
$edt = isset($_GET['edt']) ? trim($_GET['edt']) : ''; 
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$landing_id = isset($_GET['landing_id']) ? (int)$_GET['landing_id'] : 0;

$sql_search = " WHERE 1=1 ";

if ($sdt) $sql_search .= " and DATE(created_at) >= '{$sdt}' ";
if ($edt) $sql_search .= " and DATE(created_at) <= '{$edt}' ";
if ($status) $sql_search .= " and status = '{$status}' ";
if ($landing_id) $sql_search .= " and landing_id = '{$landing_id}' ";

if ($stx) {
    if ($sfl == 'name') $sql_search .= " and name like '%{$stx}%' ";
    else if ($sfl == 'tel') $sql_search .= " and tel like '%{$stx}%' ";
    else if ($sfl == 'content') $sql_search .= " and content like '%{$stx}%' ";
    else $sql_search .= " and (name like '%{$stx}%' or tel like '%{$stx}%' or content like '%{$stx}%') ";
}

$sql = " select * from {$table} {$sql_search} order by id desc ";
$result = sql_query($sql);

$filename = '상담문의내역_'.date('Ymd').'.csv';

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
// UTF-8 BOM for Excel
echo "\xEF\xBB\xBF";

$fp = fopen('php://output', 'w');
fputcsv($fp, array('번호', '랜딩ID', '이름', '연락처', '희망일정', '참여인원', '문의내용', '상태', '등록일시', 'IP'));

while ($row = sql_fetch_array($result)) {
    fputcsv($fp, array(
        $row['id'],
        $row['landing_id'],
        $row['name'],
        $row['tel'],
        $row['schedule'],
        $row['people'],
        $row['content'],
        $row['status'],
        $row['created_at'],
        $row['remote_ip']
    ));
}
fclose($fp);
exit;
