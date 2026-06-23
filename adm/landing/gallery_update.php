<?php
include_once('./_common.php');

auth_check_menu($auth, '900400', 'w');

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$landing_id = isset($_POST['landing_id']) ? (int)$_POST['landing_id'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
$is_active = isset($_POST['is_active']) ? trim($_POST['is_active']) : 'Y';

if ($landing_id < 1) {
    alert('랜딩을 선택하세요.', './gallery_form.php' . ($id ? '?id=' . $id : ''));
}
if ($title === '') {
    alert('제목을 입력하세요.', './gallery_form.php' . ($id ? '?id=' . $id : ''));
}
if ($is_active !== 'N') {
    $is_active = 'Y';
}

$table = G5_TABLE_PREFIX . 'landing_gallery';
$old_image = '';
if ($id) {
    $old = sql_fetch(" select image_path from {$table} where id = '{$id}' limit 1 ");
    if ($old && !empty($old['image_path'])) {
        $old_image = $old['image_path'];
    }
}

$upload_dir = G5_DATA_PATH . '/landing/gallery';
if (!is_dir($upload_dir)) {
    @mkdir($upload_dir, G5_DIR_PERMISSION);
    @chmod($upload_dir, G5_DIR_PERMISSION);
}

$image_path = $old_image;
if (isset($_FILES['image_file']) && isset($_FILES['image_file']['name']) && $_FILES['image_file']['name'] !== '') {
    $ext = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
    $allow = array('jpg', 'jpeg', 'png', 'webp');
    if (!in_array($ext, $allow, true)) {
        alert('이미지 파일은 jpg, jpeg, png, webp만 업로드 가능합니다.', './gallery_form.php' . ($id ? '?id=' . $id : ''));
    }

    $filename = 'gallery_' . date('YmdHis') . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $dest = $upload_dir . '/' . $filename;
    if (move_uploaded_file($_FILES['image_file']['tmp_name'], $dest)) {
        @chmod($dest, G5_FILE_PERMISSION);
        $image_path = '/data/landing/gallery/' . $filename;
        if ($old_image && is_file(G5_PATH . $old_image)) {
            @unlink(G5_PATH . $old_image);
        }
    }
}

$set_sql = " landing_id = '" . (int)$landing_id . "', title = '" . sql_real_escape_string($title) . "', image_path = '" . sql_real_escape_string($image_path) . "', description = '" . sql_real_escape_string($description) . "', sort_order = '" . (int)$sort_order . "', is_active = '" . sql_real_escape_string($is_active) . "' ";

if ($id) {
    sql_query(" update {$table} set {$set_sql} where id = '{$id}' ");
    alert('갤러리를 수정했습니다.', './gallery_form.php?id=' . $id);
}

sql_query(" insert into {$table} set {$set_sql}, created_at = '" . G5_TIME_YMDHIS . "' ");
$new_id = sql_insert_id();
alert('갤러리를 등록했습니다.', './gallery_form.php?id=' . $new_id);