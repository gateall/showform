<?php
include_once('./_common.php');

auth_check_menu($auth, '900600', 'd');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    alert('삭제할 공지가 없습니다.', './notice_list.php');
}

$table = G5_TABLE_PREFIX . 'landing_notices';
sql_query(" delete from {$table} where id = '{$id}' ");
alert('공지 항목을 삭제했습니다.', './notice_list.php');