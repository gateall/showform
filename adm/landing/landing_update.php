<?php
include_once('./_common.php');

auth_check_menu($auth, '900100', 'w');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$table = G5_TABLE_PREFIX . 'landing_pages';

$data = array(
    'template_type' => isset($_POST['template_type']) ? trim($_POST['template_type']) : 'service',
    'industry' => isset($_POST['industry']) ? trim($_POST['industry']) : '',
    'company_name' => isset($_POST['company_name']) ? trim($_POST['company_name']) : '',
    'ceo_name' => isset($_POST['ceo_name']) ? trim($_POST['ceo_name']) : '',
    'phone' => isset($_POST['phone']) ? trim($_POST['phone']) : '',
    'kakao_url' => isset($_POST['kakao_url']) ? trim($_POST['kakao_url']) : '',
    'address' => isset($_POST['address']) ? trim($_POST['address']) : '',
    'area_name' => isset($_POST['area_name']) ? trim($_POST['area_name']) : '',
    'intro_text' => isset($_POST['intro_text']) ? trim($_POST['intro_text']) : '',
    'main_copy' => isset($_POST['main_copy']) ? trim($_POST['main_copy']) : '',
    'sub_copy' => isset($_POST['sub_copy']) ? trim($_POST['sub_copy']) : '',
    'problem_text' => isset($_POST['problem_text']) ? trim($_POST['problem_text']) : '',
    'strength_text' => isset($_POST['strength_text']) ? trim($_POST['strength_text']) : '',
    'faq_text' => isset($_POST['faq_text']) ? trim($_POST['faq_text']) : '',
    'cta_text' => isset($_POST['cta_text']) ? trim($_POST['cta_text']) : '',
    'theme_color' => isset($_POST['theme_color']) ? trim($_POST['theme_color']) : '',
    'short_alias' => isset($_POST['short_alias']) ? trim($_POST['short_alias']) : '',
    'short_url' => isset($_POST['short_url']) ? trim($_POST['short_url']) : '',
    'qr_code_path' => isset($_POST['qr_code_path']) ? trim($_POST['qr_code_path']) : '',
    'tracking_code' => isset($_POST['tracking_code']) ? trim($_POST['tracking_code']) : '',
    'utm_source' => isset($_POST['utm_source']) ? trim($_POST['utm_source']) : '',
    'utm_medium' => isset($_POST['utm_medium']) ? trim($_POST['utm_medium']) : '',
    'utm_campaign' => isset($_POST['utm_campaign']) ? trim($_POST['utm_campaign']) : '',
    'is_active' => isset($_POST['is_active']) ? trim($_POST['is_active']) : 'Y'
);

if ($data['company_name'] === '') {
    alert('???? ?????.', './landing_form.php' . ($id ? '?id=' . $id : ''));
}

$main_image = '';
if ($id) {
    $old = sql_fetch(" select main_image from {$table} where id = '{$id}' limit 1 ");
    if ($old && !empty($old['main_image'])) {
        $main_image = $old['main_image'];
    }
}

$upload_dir = G5_DATA_PATH . '/landing';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, G5_DIR_PERMISSION);
    @chmod($upload_dir, G5_DIR_PERMISSION);
}

if (!empty($_FILES['main_image_file']['name'])) {
    $ext = strtolower(pathinfo($_FILES['main_image_file']['name'], PATHINFO_EXTENSION));
    $allow = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    if (!in_array($ext, $allow, true)) {
        alert('??? ??? ??? ?????.', './landing_form.php' . ($id ? '?id=' . $id : ''));
    }
    $filename = 'landing_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $dest = $upload_dir . '/' . $filename;
    if (move_uploaded_file($_FILES['main_image_file']['tmp_name'], $dest)) {
        @chmod($dest, G5_FILE_PERMISSION);
        $main_image = '/data/landing/' . $filename;
    }
}

$add_cols = array('problem_text', 'strength_text', 'faq_text', 'cta_text', 'short_alias', 'short_url', 'qr_code_path', 'tracking_code', 'utm_source', 'utm_medium', 'utm_campaign');
foreach ($add_cols as $col) {
    $res = sql_query("SHOW COLUMNS FROM {$table} LIKE '{$col}'", false);
    if (!sql_fetch_array($res)) {
        if (in_array($col, array('utm_source', 'utm_medium', 'utm_campaign', 'short_alias'), true)) {
            sql_query("ALTER TABLE {$table} ADD `{$col}` varchar(100) NOT NULL DEFAULT ''", false);
        } elseif ($col === 'short_url') {
            sql_query("ALTER TABLE {$table} ADD `{$col}` varchar(255) NOT NULL DEFAULT ''", false);
        } else {
            sql_query("ALTER TABLE {$table} ADD `{$col}` text NOT NULL", false);
        }
    }
}

$short_alias = trim((string)$data['short_alias']);
if ($short_alias !== '') {
    $short_alias = strtolower(preg_replace('/[^a-z0-9_-]/i', '', $short_alias));
}
$short_url = $short_alias !== '' ? '/s/' . $short_alias : '';

$set_sql = "
    template_type = '" . sql_real_escape_string($data['template_type']) . "',
    industry = '" . sql_real_escape_string($data['industry']) . "',
    company_name = '" . sql_real_escape_string($data['company_name']) . "',
    ceo_name = '" . sql_real_escape_string($data['ceo_name']) . "',
    phone = '" . sql_real_escape_string($data['phone']) . "',
    kakao_url = '" . sql_real_escape_string($data['kakao_url']) . "',
    address = '" . sql_real_escape_string($data['address']) . "',
    area_name = '" . sql_real_escape_string($data['area_name']) . "',
    intro_text = '" . sql_real_escape_string($data['intro_text']) . "',
    main_copy = '" . sql_real_escape_string($data['main_copy']) . "',
    sub_copy = '" . sql_real_escape_string($data['sub_copy']) . "',
    problem_text = '" . sql_real_escape_string($data['problem_text']) . "',
    strength_text = '" . sql_real_escape_string($data['strength_text']) . "',
    faq_text = '" . sql_real_escape_string($data['faq_text']) . "',
    cta_text = '" . sql_real_escape_string($data['cta_text']) . "',
    theme_color = '" . sql_real_escape_string($data['theme_color']) . "',
    main_image = '" . sql_real_escape_string($main_image) . "',
    short_alias = '" . sql_real_escape_string($short_alias) . "',
    short_url = '" . sql_real_escape_string($short_url) . "',
    qr_code_path = '" . sql_real_escape_string($data['qr_code_path']) . "',
    tracking_code = '" . sql_real_escape_string($data['tracking_code']) . "',
    utm_source = '" . sql_real_escape_string($data['utm_source']) . "',
    utm_medium = '" . sql_real_escape_string($data['utm_medium']) . "',
    utm_campaign = '" . sql_real_escape_string($data['utm_campaign']) . "',
    is_active = '" . sql_real_escape_string($data['is_active']) . "',
    updated_at = '" . G5_TIME_YMDHIS . "'
";

if ($id) {
    if ($short_url === '') {
        $short_url = '/s/' . (int)$id;
    }
    sql_query(" update {$table} set {$set_sql}, short_url = '" . sql_real_escape_string($short_url) . "' where id = '{$id}' ");
    alert('?????? ??????.', './landing_form.php?id=' . $id);
}

sql_query(" insert into {$table} set {$set_sql}, short_alias = '', short_url = '', created_at = '" . G5_TIME_YMDHIS . "' ");
$new_id = sql_insert_id();
$short_url = '/s/' . (int)$new_id;
sql_query(" update {$table} set short_url = '" . sql_real_escape_string($short_url) . "' where id = '{$new_id}' ");
alert('?????? ??????.', './landing_form.php?id=' . $new_id);
