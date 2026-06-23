<?php
include_once('./_common.php');

auth_check_menu($auth, '900100', 'w');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id < 1) {
    alert('Invalid access.', './landing_list.php');
}

$table = G5_TABLE_PREFIX . 'landing_pages';
$row = sql_fetch(" select id from {$table} where id = '{$id}' limit 1 ");
if (!$row) {
    alert('Landing page not found.', './landing_list.php');
}

$short_alias = isset($_POST['short_alias']) ? trim($_POST['short_alias']) : '';
$short_url = isset($_POST['short_url']) ? trim($_POST['short_url']) : '';
$qr_code_path = isset($_POST['qr_code_path']) ? trim($_POST['qr_code_path']) : '';
$tracking_code = isset($_POST['tracking_code']) ? trim($_POST['tracking_code']) : '';
$utm_source = isset($_POST['utm_source']) ? trim($_POST['utm_source']) : '';
$utm_medium = isset($_POST['utm_medium']) ? trim($_POST['utm_medium']) : '';
$utm_campaign = isset($_POST['utm_campaign']) ? trim($_POST['utm_campaign']) : '';

$short_alias = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $short_alias));
if ($short_alias !== '') {
    $short_url = '/s/' . $short_alias;
} elseif ($short_url === '') {
    $short_url = '/s/' . $id;
}

sql_query(" update {$table} set
    short_alias = '" . sql_real_escape_string($short_alias) . "',
    short_url = '" . sql_real_escape_string($short_url) . "',
    qr_code_path = '" . sql_real_escape_string($qr_code_path) . "',
    tracking_code = '" . sql_real_escape_string($tracking_code) . "',
    utm_source = '" . sql_real_escape_string($utm_source) . "',
    utm_medium = '" . sql_real_escape_string($utm_medium) . "',
    utm_campaign = '" . sql_real_escape_string($utm_campaign) . "',
    updated_at = '" . G5_TIME_YMDHIS . "'
    where id = '{$id}' ");

alert('Saved.', './marketing.php?id=' . $id);
