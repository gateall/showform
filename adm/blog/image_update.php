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
    $file_type = $_FILES['upload_file']['type'];
    
    // 파일 확장자 검사
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed_ext = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    if (!in_array($ext, $allowed_ext)) {
        alert('허용되지 않는 파일 확장자입니다. (jpg, jpeg, png, gif, webp만 가능)');
    }
    
    // 업로드 경로 확인
    $blog_img_dir = G5_DATA_PATH . '/blog_images';
    if (!is_dir($blog_img_dir)) {
        @mkdir($blog_img_dir, G5_DIR_PERMISSION);
        @chmod($blog_img_dir, G5_DIR_PERMISSION);
    }
    
    // 저장될 파일명 생성 (유니크)
    $save_filename = date('Ymd_His') . '_' . substr(md5(uniqid(rand(), true)), 0, 8) . '.' . $ext;
    $dest_file = $blog_img_dir . '/' . $save_filename;
    
    if (move_uploaded_file($tmp_file, $dest_file)) {
        chmod($dest_file, G5_FILE_PERMISSION);
        
        // DB 등록
        $original_name = sql_real_escape_string($filename);
        $save_filename_safe = sql_real_escape_string($save_filename);
        $file_path = sql_real_escape_string('data/blog_images/' . $save_filename);
        $file_type_safe = sql_real_escape_string($file_type);
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
        
        goto_url('./image_list.php');
    } else {
        alert('파일 업로드에 실패했습니다. 서버 권한을 확인해주세요.');
    }
} else {
    alert('업로드된 파일이 없습니다.');
}
