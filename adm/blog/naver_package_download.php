<?php
include_once('./_common.php');
$sub_menu = '360700';
auth_check_menu($auth, $sub_menu, 'r');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id < 1) {
    alert('잘못된 접근입니다.');
}

$tbl_pkg = bp_table('naver_packages');
$pkg = sql_fetch(" select * from {$tbl_pkg} where id = '{$id}' ");
if (!$pkg) {
    alert('패키지가 존재하지 않습니다.');
}

$filepath = $pkg['package_path'];
if (!is_file($filepath)) {
    alert('패키지 파일이 서버에 존재하지 않습니다.');
}

// 다운로드 횟수 증가
sql_query(" update {$tbl_pkg} set download_count = download_count + 1 where id = '{$id}' ");

// 파일 전송
$filename = basename($filepath);
header('Content-Description: File Transfer');
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filepath));
flush();
readfile($filepath);
exit;
