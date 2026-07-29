<?php
include_once('../_common.php');

header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => '관리자만 접근 가능합니다.']);
    exit;
}

// Check menu auth for '360000' (Blog Automation)
auth_check_menu($auth, '360000', 'w');

// Create a draft post in g5_blog_posts
$now = G5_TIME_YMDHIS;
$sql = "INSERT INTO g5_blog_posts
        SET project_id = 0,
            title = '새 포스팅',
            created_at = '{$now}',
            updated_at = '{$now}'";
$result = sql_query($sql, false);

if ($result) {
    $post_id = sql_insert_id();
    echo json_encode(['success' => true, 'post_id' => $post_id]);
} else {
    echo json_encode(['success' => false, 'message' => '초안 생성에 실패했습니다. DB 확인 필요: ' . sql_error_info()]);
}
