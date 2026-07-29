<?php
// adm/blog/mock_receiver.php
// 자체 PHP 블로그 외부 API 연동 테스트를 위한 모의(Mock) 수신 스크립트

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(array('success' => false, 'error_code' => 'METHOD_NOT_ALLOWED', 'message' => 'POST만 허용됩니다.'));
    exit;
}

$input = file_get_contents('php://input');
$data = @json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(array('success' => false, 'error_code' => 'BAD_REQUEST', 'message' => '잘못된 JSON 형식입니다.'));
    exit;
}

// 실패 테스트 시나리오 트리거
if (isset($data['title']) && strpos($data['title'], '[API_FAIL_500]') !== false) {
    http_response_code(500);
    echo json_encode(array('success' => false, 'error_code' => 'INTERNAL_SERVER_ERROR', 'message' => '모의 테스트: 500 에러 발생'));
    exit;
}

if (isset($data['title']) && strpos($data['title'], '[API_FAIL_401]') !== false) {
    http_response_code(401);
    echo json_encode(array('success' => false, 'error_code' => 'UNAUTHORIZED', 'message' => '모의 테스트: 401 권한 에러 발생'));
    exit;
}

// HMAC 서명 검증 시뮬레이션
$headers = getallheaders();
$signature = '';
foreach ($headers as $key => $value) {
    if (strtolower($key) === 'x-blog-signature') {
        $signature = $value;
        break;
    }
}

// 실제로는 여기서 site_id에 매칭되는 API Secret으로 HMAC를 재계산하여 비교해야 함.
// Mock 에서는 서명이 넘어왔는지만 확인.
if (empty($signature)) {
    http_response_code(401);
    echo json_encode(array('success' => false, 'error_code' => 'MISSING_SIGNATURE', 'message' => '서명이 누락되었습니다.'));
    exit;
}

// 성공 응답 시뮬레이션
$remote_post_id = 'php-remote-' . time() . '-' . rand(100, 999);
$published_url = 'https://example.com/mock-post/' . $remote_post_id;

http_response_code(200);
echo json_encode(array(
    'success' => true,
    'request_id' => $data['request_id'] ?? '',
    'remote_post_id' => $remote_post_id,
    'published_url' => $published_url,
    'published_at' => date('Y-m-d H:i:s'),
    'message' => '발행 완료'
));
exit;
