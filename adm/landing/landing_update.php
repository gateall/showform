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
    'theme_color' => isset($_POST['theme_color']) ? trim($_POST['theme_color']) : '',
    'is_active' => isset($_POST['is_active']) ? trim($_POST['is_active']) : 'Y'
);

if ($data['company_name'] === '') {
    alert('회사명을 입력하세요.', './landing_form.php' . ($id ? '?id=' . $id : ''));
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

if (isset($_FILES['main_image_file']) && isset($_FILES['main_image_file']['name']) && $_FILES['main_image_file']['name'] !== '') {
    $ext = strtolower(pathinfo($_FILES['main_image_file']['name'], PATHINFO_EXTENSION));
    $allow = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    if (!in_array($ext, $allow, true)) {
        alert('이미지 파일만 업로드 가능합니다.', './landing_form.php' . ($id ? '?id=' . $id : ''));
    }

    $filename = 'landing_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $dest = $upload_dir . '/' . $filename;
    if (move_uploaded_file($_FILES['main_image_file']['tmp_name'], $dest)) {
        @chmod($dest, G5_FILE_PERMISSION);
        $main_image = '/data/landing/' . $filename;
    }
}

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
    theme_color = '" . sql_real_escape_string($data['theme_color']) . "',
    main_image = '" . sql_real_escape_string($main_image) . "',
    is_active = '" . sql_real_escape_string($data['is_active']) . "',
    updated_at = '" . G5_TIME_YMDHIS . "'
";

if ($id) {
    sql_query(" update {$table} set {$set_sql} where id = '{$id}' ");
    alert('랜딩페이지를 수정했습니다.', './landing_form.php?id=' . $id);
}

sql_query(" insert into {$table} set {$set_sql}, created_at = '" . G5_TIME_YMDHIS . "' ");
$new_id = sql_insert_id();
alert('랜딩페이지를 생성했습니다.', './landing_form.php?id=' . $new_id);