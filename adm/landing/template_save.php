<?php
include_once('./_common.php');

header('Content-Type: application/json; charset=utf-8');

if ($is_admin != 'super') {
    echo json_encode(array('error' => '권한이 없습니다.'));
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$html = isset($_POST['html']) ? trim($_POST['html']) : '';
$css = isset($_POST['css']) ? trim($_POST['css']) : '';

if (!$id) {
    echo json_encode(array('error' => '유효하지 않은 요청입니다.'));
    exit;
}

$table = G5_TABLE_PREFIX . 'landing_page';

// SQL Injection 방지를 위한 이스케이프
$safe_html = sql_real_escape_string($html);
$safe_css = sql_real_escape_string($css);

$sql = " update {$table} 
            set custom_html = '{$safe_html}',
                custom_css = '{$safe_css}',
                updated_at = '".G5_TIME_YMDHIS."'
          where id = '{$id}' ";
sql_query($sql);

echo json_encode(array('success' => true));
?>
