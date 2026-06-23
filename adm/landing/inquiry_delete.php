<?php
$sub_menu = "990200";
include_once('../../common.php');
include_once(G5_ADMIN_PATH.'/admin.lib.php');
if (!$is_admin) alert('관리자만 접근 가능합니다.', G5_URL);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id) {
    sql_query(" delete from " . G5_TABLE_PREFIX . "landing_inquiry where id = '{$id}' ");
}
alert('삭제되었습니다.', './inquiry_list.php');
