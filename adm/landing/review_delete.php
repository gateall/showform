<?php
include_once('./_common.php');

auth_check_menu($auth, '900300', 'd');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    alert('삭제할 후기가 없습니다.', './review_list.php');
}

$table = G5_TABLE_PREFIX . 'landing_reviews';
sql_query(" delete from {$table} where id = '{$id}' ");
alert('후기를 삭제했습니다.', './review_list.php');