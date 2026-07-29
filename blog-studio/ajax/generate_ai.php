<?php
include_once('./_common.php');
include_once(G5_ADMIN_PATH.'/blog/lib/blog_crypto.lib.php');
include_once(G5_ADMIN_PATH.'/blog/lib/blog_ai_service.lib.php');

header('Content-Type: application/json');

if (!$is_admin) {
    echo json_encode(['success' => false, 'message' => '관리자 권한이 필요합니다.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$action = isset($data['action']) ? $data['action'] : '';
$state = isset($data['state']) ? $data['state'] : null;

if (!$action || !$state) {
    echo json_encode(['success' => false, 'message' => '필수 파라미터가 누락되었습니다.']);
    exit;
}

try {
    $params = [
        'tone' => $state['basic']['tone'] ?? '자연스러운 블로그체',
        'target_audience' => $state['basic']['target_audience'] ?? '',
        'main_keyword' => $state['title']['main_keyword'] ?? '',
        'sub_keywords' => $state['title']['sub_keywords'] ?? '',
        'title' => $state['title']['title_text'] ?? ''
    ];

    if ($action === 'title') {
        $result = bp_ai_generate_titles($params, 1);
        if (!empty($result['titles'][0])) {
            echo json_encode(['success' => true, 'data' => $result['titles'][0]]);
        } else {
            throw new Exception("제목 생성 결과가 비어있습니다.");
        }
    } 
    else if ($action === 'body') {
        $block_id = $data['block_id'] ?? '';
        $subtitle = '';
        foreach ($state['body'] as $block) {
            if ($block['id'] === $block_id) {
                $subtitle = $block['subtitle'];
                break;
            }
        }
        
        $params['subtitle'] = $subtitle;
        $result = bp_ai_generate_body($params);
        if (!empty($result['body'])) {
            echo json_encode(['success' => true, 'data' => $result['body']]);
        } else {
            throw new Exception("본문 생성 결과가 비어있습니다.");
        }
    }
    else {
        throw new Exception("지원하지 않는 액션입니다.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
