<?php
include_once('./_common.php');

header('Content-Type: application/json; charset=utf-8');

if ($is_admin != 'super') {
    echo json_encode(array('error' => '최고관리자만 접근 가능합니다.'));
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$token = isset($_POST['token']) ? trim($_POST['token']) : '';

if (!$id) {
    echo json_encode(array('error' => '이미지 ID가 전달되지 않았습니다.'));
    exit;
}

if (!check_admin_token($token, true)) {
    echo json_encode(array('error' => '올바른 접근이 아닙니다. (토큰 검증 실패)'));
    exit;
}

$tbl_images = bp_table('images');
$tbl_post_images = bp_table('post_images');

$image = sql_fetch(" select * from {$tbl_images} where id = '{$id}' ");
if (!$image) {
    echo json_encode(array('error' => '존재하지 않는 이미지입니다.'));
    exit;
}

// 1. 참조 여부 검사
$sql = " select count(*) as cnt from {$tbl_post_images} where image_id = '{$id}' ";
$ref = sql_fetch($sql);
if ($ref['cnt'] > 0) {
    echo json_encode(array('error' => '현재 ' . $ref['cnt'] . '개의 포스트에서 이 이미지를 사용 중입니다. 포스트에서 삭제한 후 다시 시도해주세요.'));
    exit;
}

// 2. 실제 파일 삭제
$file_path = G5_PATH . '/' . $image['file_path'];
if (is_file($file_path)) {
    @unlink($file_path);
}

// 3. DB 삭제
sql_query(" delete from {$tbl_images} where id = '{$id}' ");

echo json_encode(array('success' => true));
