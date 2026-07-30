<?php
$sub_menu = '360800';
include_once('./_common.php');

check_admin_token();
auth_check_menu($auth, $sub_menu, 'w');

$tbl_images = bp_table('images');
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$is_ai_generated = isset($_POST['is_ai_generated']) && $_POST['is_ai_generated'] ? 1 : 0;
$ai_prompt = isset($_POST['ai_prompt']) ? trim($_POST['ai_prompt']) : '';
$created_by = $member['mb_id'];
$created_at = G5_TIME_YMDHIS;

if (isset($_FILES['upload_file']) && is_uploaded_file($_FILES['upload_file']['tmp_name'])) {

    $tmp_file  = $_FILES['upload_file']['tmp_name'];
    $filesize  = $_FILES['upload_file']['size'];
    $filename  = $_FILES['upload_file']['name'];
    $convert_to_webp = isset($_POST['convert_to_webp']) && $_POST['convert_to_webp'] ? true : false;

    // 파일 확장자 검사 — 빠른 1차 필터일 뿐, 신뢰의 기준은 아래 getimagesize() 콘텐츠 검사다.
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    if (!in_array($ext, $allowed_ext)) {
        alert('허용되지 않는 파일 확장자입니다. (jpg, jpeg, png, gif, webp만 가능)');
    }

    // 실제 파일 내용 기반 검증 — 확장자·브라우저가 보낸 Content-Type은 모두 클라이언트가
    // 조작할 수 있으므로, 파일을 직접 디코딩해 진짜 이미지인지와 실제 형식을 서버에서 확인한다.
    $image_info = @getimagesize($tmp_file);
    if ($image_info === false) {
        alert('올바른 이미지 파일이 아닙니다(파일 내용을 이미지로 인식할 수 없습니다).');
    }
    $allowed_image_types = array(
        IMAGETYPE_JPEG => 'image/jpeg',
        IMAGETYPE_PNG  => 'image/png',
        IMAGETYPE_GIF  => 'image/gif',
        IMAGETYPE_WEBP => 'image/webp',
    );
    $detected_type = $image_info[2];
    if (!isset($allowed_image_types[$detected_type])) {
        alert('지원하지 않는 이미지 형식입니다. (jpg, png, gif, webp만 허용)');
    }
    $detected_mime = $allowed_image_types[$detected_type];

    // 업로드 경로 확인
    $blog_img_dir = G5_DATA_PATH . '/blog_images';
    if (!is_dir($blog_img_dir)) {
        @mkdir($blog_img_dir, G5_DIR_PERMISSION);
        @chmod($blog_img_dir, G5_DIR_PERMISSION);
    }

    // 저장될 파일명 생성 (유니크) — 확장자는 업로드된 파일명이 아니라 실제 검증된 형식을 기준으로 부여한다.
    $ext_by_type = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif', IMAGETYPE_WEBP => 'webp');
    $unique_name = date('Ymd_His') . '_' . substr(md5(uniqid(rand(), true)), 0, 8);
    $save_filename = $unique_name . '.' . $ext_by_type[$detected_type];
    $dest_file = $blog_img_dir . '/' . $save_filename;

    if (!move_uploaded_file($tmp_file, $dest_file)) {
        alert('파일 업로드에 실패했습니다. 서버 권한을 확인해주세요.');
    }
    chmod($dest_file, G5_FILE_PERMISSION);

    $final_filename = $save_filename;
    $final_mime = $detected_mime;

    // WebP 변환 옵션 — 원본이 이미 webp가 아니고 사용자가 선택했으며 서버가 GD/webp를
    // 지원할 때만 변환한다. 변환에 실패하거나 미지원이면 업로드 자체는 그대로 성공시키고
    // 원본 형식을 유지한다(선택 기능이 업로드를 막아서는 안 된다).
    if ($convert_to_webp && $detected_type !== IMAGETYPE_WEBP && function_exists('imagewebp')) {
        $src_image = false;
        if ($detected_type === IMAGETYPE_JPEG && function_exists('imagecreatefromjpeg')) {
            $src_image = @imagecreatefromjpeg($dest_file);
        } elseif ($detected_type === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
            $src_image = @imagecreatefrompng($dest_file);
        } elseif ($detected_type === IMAGETYPE_GIF && function_exists('imagecreatefromgif')) {
            $src_image = @imagecreatefromgif($dest_file);
        }

        if ($src_image !== false) {
            $webp_filename = $unique_name . '.webp';
            $webp_dest = $blog_img_dir . '/' . $webp_filename;
            if (@imagewebp($src_image, $webp_dest, 90)) {
                imagedestroy($src_image);
                @unlink($dest_file);
                chmod($webp_dest, G5_FILE_PERMISSION);
                $final_filename = $webp_filename;
                $final_mime = 'image/webp';
                $filesize = filesize($webp_dest);
            } else {
                imagedestroy($src_image);
            }
        }
    }

    // DB 등록
    $original_name = sql_real_escape_string($filename);
    $save_filename_safe = sql_real_escape_string($final_filename);
    $file_path = sql_real_escape_string('data/blog_images/' . $final_filename);
    $file_type_safe = sql_real_escape_string($final_mime);
    $ai_prompt_safe = sql_real_escape_string($ai_prompt);

    $sql = " insert into {$tbl_images}
             set project_id = '{$project_id}',
                 filename = '{$save_filename_safe}',
                 original_name = '{$original_name}',
                 file_path = '{$file_path}',
                 file_size = '{$filesize}',
                 mime_type = '{$file_type_safe}',
                 is_ai_generated = '{$is_ai_generated}',
                 ai_prompt = '{$ai_prompt_safe}',
                 created_by = '{$created_by}',
                 created_at = '{$created_at}' ";
    sql_query($sql);

    goto_url(SF_MANAGER_URL . '/blog/image_list.php');
} else {
    alert('업로드된 파일이 없습니다.');
}
