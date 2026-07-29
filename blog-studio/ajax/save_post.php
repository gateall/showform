<?php
include_once('./_common.php');

header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$post_id = isset($data['post_id']) ? (int)$data['post_id'] : 0;
$state = isset($data['state']) ? $data['state'] : null;

if (!$post_id || !$state) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

// Convert state back to JSON string
$state_json = sql_real_escape_string(json_encode($state, JSON_UNESCAPED_UNICODE));
$title = sql_real_escape_string(isset($state['title']['title_text']) ? $state['title']['title_text'] : '');
$project_id = isset($state['basic']['project_id']) ? (int)$state['basic']['project_id'] : 0;
$now = G5_TIME_YMDHIS;

$sql = "UPDATE g5_blog_posts
        SET title = '{$title}',
            project_id = '{$project_id}',
            builder_state = '{$state_json}',
            updated_at = '{$now}'
        WHERE id = '{$post_id}'";
        
$result = sql_query($sql, false);

if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'DB 저장 실패: ' . sql_error_info()]);
}
