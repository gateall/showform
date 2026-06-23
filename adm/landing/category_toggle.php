<?php
include_once('./_common.php');

$sub_menu = '990054';
auth_check_menu($auth, $sub_menu, 'w');

$table = G5_TABLE_PREFIX . 'landing_category';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = sql_fetch(" select id, is_display from {$table} where id = '{$id}' ");
if (!$row) {
    alert('카테고리 정보를 찾을 수 없습니다.');
}
$new_status = $row['is_display'] === 'Y' ? 'N' : 'Y';
sql_query(" update {$table} set is_display = '{$new_status}', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$id}' ");
alert('노출 상태가 변경되었습니다.', G5_ADMIN_URL . '/landing/category_list.php');
