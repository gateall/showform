<?php
$sub_menu = '360400';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
header('Content-Type: application/json; charset=utf-8');
// 평문 비밀값을 담은 응답이므로 어떤 캐시에도 남지 않게 한다.
header('Cache-Control: no-store');

// AJAX 전용 - 화면에서 "보기"를 누를 때만 호출된다.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('ok' => false, 'error' => 'POST 요청만 허용됩니다.'));
    exit;
}

if ($is_admin != 'super') {
    echo json_encode(array('ok' => false, 'error' => '최고관리자만 접근 가능합니다.'));
    exit;
}

// 실제 비밀값을 반환하는 조회이므로, 연결 테스트보다 한 단계 더 민감하다 - 그누보드
// 1회성 토큰(check_admin_token)을 쓰면 같은 화면의 저장 버튼 토큰이 깨지므로, 다른
// 조회성 액션과 동일하게 세션 재사용 가능한 테스트 토큰을 그대로 쓴다.
if (!bp_check_test_token()) {
    echo json_encode(array('ok' => false, 'error' => '세션이 만료되었습니다. 화면을 새로고침한 후 다시 시도해 주세요.'));
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(array('ok' => false, 'error' => '잘못된 제공자 ID입니다.'));
    exit;
}

$table = bp_table('ai_providers');
$row = sql_fetch(" select id, provider_code, api_key_enc from {$table} where id = '{$id}' ");
if (!$row) {
    echo json_encode(array('ok' => false, 'error' => '제공자 레코드를 찾을 수 없습니다.'));
    exit;
}

if (empty($row['api_key_enc'])) {
    echo json_encode(array('ok' => false, 'error' => '저장된 API 키가 없습니다.'));
    exit;
}

$plain = bp_decrypt_secret($row['api_key_enc']);
if ($plain === '') {
    echo json_encode(array('ok' => false, 'error' => 'API 키 복호화에 실패했습니다.'));
    exit;
}

// 비밀값을 화면에 노출하는 행위 자체를 감사 로그에 남긴다(IP 포함 - 누가/어디서 열람했는지).
$reveal_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
bp_log_activity(0, 'ai_key_reveal', bp_current_admin_id(), "공급자 ID {$id}({$row['provider_code']}) API 키 열람, IP: {$reveal_ip}");

echo json_encode(array('ok' => true, 'api_key' => $plain));
exit;
