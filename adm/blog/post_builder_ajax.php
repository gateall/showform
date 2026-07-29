<?php
// 통합 포스팅 제작 폼 AJAX 허브
include_once('./_common.php');

$action = isset($_POST['action']) ? $_POST['action'] : '';
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

header('Content-Type: application/json; charset=utf-8');

if ($is_admin != 'super') {
    die(json_encode(['error' => '권한이 없습니다.']));
}

$response = ['ok' => true, 'action' => $action];

switch($action) {
    case 'save_step':
        $step = isset($_POST['step']) ? $_POST['step'] : '';
        // TODO: 스텝별로 JSON 페이로드 추출 후 DB(builder_state)에 저장
        $response['message'] = "Step {$step} saved successfully.";
        break;

    case 'load_state':
        // TODO: builder_state 파싱하여 클라이언트에 전달
        $response['state'] = [];
        break;
        
    case 'complete_post':
        // TODO: 블록 합쳐서 HTML 생성 후 posts.content 덮어쓰기
        $response['message'] = "Post completed.";
        break;

    default:
        $response['error'] = 'Unknown action';
        $response['ok'] = false;
        break;
}

echo json_encode($response);
exit;
