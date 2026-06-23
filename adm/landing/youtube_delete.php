<?php
include_once('./_common.php');

auth_check_menu($auth, '900500', 'd');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    alert('삭제할 유튜브 항목이 없습니다.', './youtube_list.php');
}

$table = G5_TABLE_PREFIX . 'landing_youtube';
sql_query(" delete from {$table} where id = '{$id}' ");
alert('유튜브 항목을 삭제했습니다.', './youtube_list.php');