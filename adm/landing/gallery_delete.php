<?php
include_once('./_common.php');

auth_check_menu($auth, '900400', 'd');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) {
    alert('삭제할 갤러리가 없습니다.', './gallery_list.php');
}

$table = G5_TABLE_PREFIX . 'landing_gallery';
$row = sql_fetch(" select image_path from {$table} where id = '{$id}' limit 1 ");
if ($row && !empty($row['image_path']) && is_file(G5_PATH . $row['image_path'])) {
    @unlink(G5_PATH . $row['image_path']);
}

sql_query(" delete from {$table} where id = '{$id}' ");
alert('갤러리를 삭제했습니다.', './gallery_list.php');