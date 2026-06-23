<?php
include_once('./_common.php');

auth_check_menu($auth, '900300', 'w');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$landing_id = isset($_POST['landing_id']) ? (int)$_POST['landing_id'] : 0;
$customer_name = isset($_POST['customer_name']) ? trim($_POST['customer_name']) : '';
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
$is_active = isset($_POST['is_active']) ? trim($_POST['is_active']) : 'Y';

if ($landing_id < 1) {
    alert('랜딩을 선택하세요.', './review_form.php' . ($id ? '?id=' . $id : ''));
}
if ($customer_name === '') {
    alert('고객명을 입력하세요.', './review_form.php' . ($id ? '?id=' . $id : ''));
}
if ($content === '') {
    alert('후기내용을 입력하세요.', './review_form.php' . ($id ? '?id=' . $id : ''));
}
if ($rating < 1 || $rating > 5) {
    $rating = 5;
}
if ($is_active !== 'N') {
    $is_active = 'Y';
}

$table = G5_TABLE_PREFIX . 'landing_reviews';
$data = " landing_id = '" . (int)$landing_id . "', customer_name = '" . sql_real_escape_string($customer_name) . "', rating = '" . (int)$rating . "', content = '" . sql_real_escape_string($content) . "', sort_order = '" . (int)$sort_order . "', is_active = '" . sql_real_escape_string($is_active) . "' ";

if ($id) {
    sql_query(" update {$table} set {$data} where id = '{$id}' ");
    alert('후기를 수정했습니다.', './review_form.php?id=' . $id);
}

sql_query(" insert into {$table} set {$data}, created_at = '" . G5_TIME_YMDHIS . "' ");
$new_id = sql_insert_id();
alert('후기를 등록했습니다.', './review_form.php?id=' . $new_id);