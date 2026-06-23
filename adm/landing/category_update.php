<?php
include_once('./_common.php');

$sub_menu = '990052';
auth_check_menu($auth, $sub_menu, 'w');

$table = G5_TABLE_PREFIX . 'landing_category';
$landing_table = G5_TABLE_PREFIX . 'landing_page';
$mode = isset($_POST['mode']) ? trim($_POST['mode']) : '';

if ($mode === 'sort_save') {
    $sort_order = isset($_POST['sort_order']) ? (array) $_POST['sort_order'] : array();
    foreach ($sort_order as $id => $order) {
        $id = (int) $id;
        $order = (int) $order;
        if ($id > 0) {
            sql_query(" update {$table} set sort_order = '{$order}', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$id}' ");
        }
    }
    alert('순서가 저장되었습니다.', G5_ADMIN_URL . '/landing/category_list.php');
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$category_name = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';
$category_code = isset($_POST['category_code']) ? trim($_POST['category_code']) : '';
$category_desc = isset($_POST['category_desc']) ? trim($_POST['category_desc']) : '';
$sort_order = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;
$is_display = isset($_POST['is_display']) ? trim($_POST['is_display']) : 'Y';

if ($category_name === '') alert('카테고리명을 입력해 주세요.');
if ($category_code === '') alert('카테고리 코드를 입력해 주세요.');
if ($is_display !== 'Y' && $is_display !== 'N') $is_display = 'Y';

$category_name_sql = sql_real_escape_string($category_name);
$category_code_sql = sql_real_escape_string($category_code);
$dup_sql = " select id from {$table} where (category_name = '{$category_name_sql}' or category_code = '{$category_code_sql}') ";
if ($id > 0) {
    $dup_sql .= " and id <> '{$id}' ";
}
$dup = sql_fetch($dup_sql);
if ($dup) {
    alert('이미 존재하는 카테고리명 또는 코드입니다.');
}

if ($id > 0) {
    sql_query(" update {$table} set category_name = '{$category_name_sql}', category_code = '{$category_code_sql}', category_desc = '" . sql_real_escape_string($category_desc) . "', sort_order = '{$sort_order}', is_display = '{$is_display}', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$id}' ");
    alert('카테고리가 수정되었습니다.', G5_ADMIN_URL . '/landing/category_list.php');
}

sql_query(" insert into {$table} set category_name = '{$category_name_sql}', category_code = '{$category_code_sql}', category_desc = '" . sql_real_escape_string($category_desc) . "', sort_order = '{$sort_order}', is_display = '{$is_display}', created_at = '" . G5_TIME_YMDHIS . "', updated_at = '" . G5_TIME_YMDHIS . "' ");
alert('카테고리가 등록되었습니다.', G5_ADMIN_URL . '/landing/category_list.php');
