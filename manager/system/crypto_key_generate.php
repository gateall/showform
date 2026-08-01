<?php
include_once('../_common.php');
// 감사로그(content_activity_logs)를 남기는 bp_log_activity()/bp_current_admin_id()가
// 이 파일에 필요해서 불러온다 - 이 프로젝트에 그 외 별도의 시스템 전용 감사로그가
// 없어, 있는 걸 그대로 재사용한다(새 로그 인프라를 새로 만들지 않음).
require_once G5_ADMIN_PATH . '/blog/lib/blog_common.lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate', true);
header('Pragma: no-cache', true);
header('X-Robots-Tag: noindex, nofollow', true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('ok' => false, 'error' => 'POST 요청만 허용됩니다.'));
    exit;
}

if ($is_admin !== 'super') {
    echo json_encode(array('ok' => false, 'error' => '최고관리자만 접근 가능합니다.'));
    exit;
}

$session_token_key = 'sys_crypto_key_gen_token';
$given_token = isset($_POST['token']) ? (string) $_POST['token'] : '';
$expected_token = (string) get_session($session_token_key);
if ($expected_token === '' || !hash_equals($expected_token, $given_token)) {
    echo json_encode(array('ok' => false, 'error' => '세션이 만료되었습니다. 새로고침 후 다시 시도해 주세요.'));
    exit;
}

$key = bin2hex(random_bytes(32));
if (!preg_match('/^[a-f0-9]{64}$/', $key)) {
    echo json_encode(array('ok' => false, 'error' => '암호화 키 생성에 실패했습니다.'));
    exit;
}

// 감사로그: 누가·언제·어디서 "생성 버튼을 눌렀는지"만 남긴다. 키 값·일부·해시·파생값은
// 절대 기록하지 않는다 - 그 자체가 또 다른 비밀정보 유출 경로가 될 수 있기 때문이다.
$gen_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
bp_log_activity(0, 'crypto_master_key_generated', bp_current_admin_id(), "운영 암호화 키 생성 도구 사용, IP: {$gen_ip}");

echo json_encode(array('ok' => true, 'key' => $key));
exit;
