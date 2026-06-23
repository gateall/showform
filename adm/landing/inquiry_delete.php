<?php
include_once('./_common.php');

auth_check_menu($auth, '900200', 'd');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    alert('삭제할 문의가 없습니다.', './inquiry_list.php');
}

$table = G5_TABLE_PREFIX . 'landing_inquiries';
sql_query(" delete from {$table} where id = '{$id}' ");

alert('문의가 삭제되었습니다.', './inquiry_list.php');