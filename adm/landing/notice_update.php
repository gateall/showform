<?php
include_once('./_common.php');

auth_check_menu($auth, '900600', 'w');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$landing_id = isset($_POST['landing_id']) ? (int)$_POST['landing_id'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$content = isset($_POST['content']) ? trim($_POST['content']) : '';
$is_active = isset($_POST['is_active']) ? trim($_POST['is_active']) : 'Y';

if ($landing_id < 1) {
    alert('랜딩을 선택하세요.', './notice_form.php' . ($id ? '?id=' . $id : ''));
}
if ($title === '') {
    alert('공지 제목을 입력하세요.', './notice_form.php' . ($id ? '?id=' . $id : ''));
}
if ($content === '') {
    alert('공지 내용을 입력하세요.', './notice_form.php' . ($id ? '?id=' . $id : ''));
}
if ($is_active !== 'N') {
    $is_active = 'Y';
}

$table = G5_TABLE_PREFIX . 'landing_notices';
$set_sql = " landing_id = '" . (int)$landing_id . "', title = '" . sql_real_escape_string($title) . "', content = '" . sql_real_escape_string($content) . "', is_active = '" . sql_real_escape_string($is_active) . "' ";

if ($id) {
    sql_query(" update {$table} set {$set_sql} where id = '{$id}' ");
    alert('공지 항목을 수정했습니다.', './notice_form.php?id=' . $id);
}

sql_query(" insert into {$table} set {$set_sql}, created_at = '" . G5_TIME_YMDHIS . "' ");
$new_id = sql_insert_id();
alert('공지 항목을 등록했습니다.', './notice_form.php?id=' . $new_id);