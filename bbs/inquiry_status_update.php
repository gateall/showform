<?php
include_once('./_common.php');

if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}

$bo_table = isset($_GET['bo_table']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['bo_table']) : '';
$wr_id = isset($_GET['wr_id']) ? (int)$_GET['wr_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

$allow_status = array('견적접수', '견적확인', '견적제출');

if (!$bo_table || !$wr_id) {
    alert('잘못된 접근입니다.');
}

if (!in_array($status, $allow_status)) {
    alert('허용되지 않은 상태값입니다.');
}

$table = $g5['write_prefix'] . $bo_table;

sql_query(" update {$table} set wr_8 = '".sql_real_escape_string($status)."' where wr_id = '{$wr_id}' ");

goto_url(G5_URL.'/estimate01.php');
?>