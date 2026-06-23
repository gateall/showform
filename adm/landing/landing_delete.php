<?php
include_once('./_common.php');

auth_check_menu($auth, '900100', 'd');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$table = G5_TABLE_PREFIX . 'landing_pages';

if (!$id) {
    alert('삭제할 대상이 없습니다.', './landing_list.php');
}

sql_query(" delete from {$table} where id = '{$id}' ");
alert('랜딩페이지를 삭제했습니다.', './landing_list.php');