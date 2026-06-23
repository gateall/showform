<?php
require_once './admin.lib.php';
if (!defined('_GNUBOARD_')) exit;
check_admin_token();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : '';
$company = isset($_POST['company']) ? trim($_POST['company']) : '';
$tel = isset($_POST['tel']) ? trim($_POST['tel']) : '';
$category = isset($_POST['category']) ? trim($_POST['category']) : '';
$area = isset($_POST['area']) ? trim($_POST['area']) : '';
$hero_text = isset($_POST['hero_text']) ? trim($_POST['hero_text']) : '';
$intro_text = isset($_POST['intro_text']) ? trim($_POST['intro_text']) : '';

if ($subject === '' || $company === '') {
    alert('랜딩제목과 업체명은 필수입니다.');
}

$table = G5_TABLE_PREFIX . 'landing_page';
if ($id > 0) {
    sql_query(" update {$table}
        set subject = '" . sql_real_escape_string($subject) . "',
            company = '" . sql_real_escape_string($company) . "',
            tel = '" . sql_real_escape_string($tel) . "',
            category = '" . sql_real_escape_string($category) . "',
            area = '" . sql_real_escape_string($area) . "',
            hero_text = '" . sql_real_escape_string($hero_text) . "',
            intro_text = '" . sql_real_escape_string($intro_text) . "'
        where id = '{$id}' ");
} else {
    sql_query(" insert into {$table}
        set subject = '" . sql_real_escape_string($subject) . "',
            company = '" . sql_real_escape_string($company) . "',
            tel = '" . sql_real_escape_string($tel) . "',
            category = '" . sql_real_escape_string($category) . "',
            area = '" . sql_real_escape_string($area) . "',
            hero_text = '" . sql_real_escape_string($hero_text) . "',
            intro_text = '" . sql_real_escape_string($intro_text) . "',
            created_at = '" . G5_TIME_YMDHIS . "' ");
    $id = sql_insert_id();
}

alert('저장되었습니다.', G5_ADMIN_URL . '/landing_form.php?id=' . $id);
