<?php
// adm/blog/mock_receiver.php
// 자체 PHP 블로그 외부 API 연동 테스트를 위한 모의(Mock) 수신 스크립트
//
// 저장소 전체에서 이 파일을 호출하는 코드가 없음을 확인함 - 운영 발행 흐름에서
// 쓰이지 않는 테스트 스캐폴드다. 아래 서명검증도 "헤더가 왔는지"만 보는 시뮬레이션일
// 뿐 실제 HMAC 재계산 검증이 아니므로 무인증으로 열어둘 이유가 없다. 실제 외부
// 발행 플랫폼이 이 엔드포인트를 호출하게 된다면 관리자 세션이 아니라
// site_credentials의 API Secret으로 계산한 진짜 HMAC 서명 + 타임스탬프 유효시간 +
// 재전송 방지 nonce로 바꿔야 한다(외부 시스템은 관리자 세션을 가질 수 없다).
// 인증을 먼저 수행한 뒤 응답 형식을 정한다 - header()를 먼저 보내고 _common.php를
// include하면 인증 실패 시에도 이미 응답 헤더가 나가버린 상태로 alert() 계열 HTML이
// 섞여 나올 수 있어, 이 파일은 항상 403 + 순수 JSON으로 직접 종료한다.
include_once('./_common.php');
if ($is_admin !== 'super') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('success' => false, 'message' => 'Forbidden'), JSON_UNESCAPED_UNICODE);
    exit;
}

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
