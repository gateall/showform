<?php
$sub_menu = '360400';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
header('Content-Type: application/json; charset=utf-8');
// 평문 비밀값을 담은 응답이므로 어떤 캐시에도 남지 않게 한다.
header('Cache-Control: no-store');

// AJAX 전용 - 화면에서 "보기" 확인을 누를 때만 호출된다.
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
// 조회성 액션과 동일하게 세션 재사용 가능한 테스트 토큰을 CSRF 검증으로 그대로 쓴다.
if (!bp_check_test_token()) {
    echo json_encode(array('ok' => false, 'error' => '세션이 만료되었습니다. 화면을 새로고침한 후 다시 시도해 주세요.'));
    exit;
}

// 연속 비밀번호 실패에 대한 일시 잠금 - 세션 단위 카운터. 잠긴 동안에는 비밀번호를
// 아예 비교하지 않는다(맞고 틀리고를 알려줄 필요가 없음).
$lock_until = (int) get_session('bp_key_reveal_lock_until');
if ($lock_until > 0 && time() < $lock_until) {
    $wait = $lock_until - time();
    echo json_encode(array('ok' => false, 'error' => "비밀번호를 여러 번 틀려 {$wait}초 후 다시 시도할 수 있습니다."));
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(array('ok' => false, 'error' => '잘못된 제공자 ID입니다.'));
    exit;
}

$reveal_ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';

// 최고관리자 세션이 살아있는 것만으로는 원문 열람을 허용하지 않는다 - 지금 로그인한
// 관리자 본인의 비밀번호를 한 번 더 확인해야 진행한다(세션 탈취만으로는 열람 불가).
// 실제 로그인 화면(bbs/login_check.php)이 쓰는 것과 동일한 login_password_check()를
// 그대로 재사용한다 - 레거시/신규 해시 방식을 이 함수가 알아서 구분해 처리해 준다.
$admin_password = isset($_POST['admin_password']) ? (string) $_POST['admin_password'] : '';
$has_password_hash = isset($member['mb_password']) && $member['mb_password'] !== '';
$password_ok = $admin_password !== '' && $has_password_hash && login_password_check($member, $admin_password, $member['mb_password']);

// 실패 여부와 무관하게, 방금 입력받은 평문 비밀번호는 이 시점 이후로 어떤 변수에도
// 다시 담지 않는다(로그에 남기지 않기 위함).
unset($admin_password);

if (!$password_ok) {
    $fail_count = (int) get_session('bp_key_reveal_fail_count') + 1;

    if ($fail_count >= 5) {
        set_session('bp_key_reveal_fail_count', 0);
        set_session('bp_key_reveal_lock_until', time() + 300);
        bp_log_activity(0, 'ai_key_reveal_denied', bp_current_admin_id(), "공급자 ID {$id} API 키 열람 실패 5회 누적 - 5분 잠금, IP: {$reveal_ip}");
        echo json_encode(array('ok' => false, 'error' => '비밀번호를 5회 틀려 5분간 열람이 제한됩니다.'));
    } else {
        set_session('bp_key_reveal_fail_count', $fail_count);
        bp_log_activity(0, 'ai_key_reveal_denied', bp_current_admin_id(), "공급자 ID {$id} API 키 열람 실패(비밀번호 불일치), IP: {$reveal_ip}");
        echo json_encode(array('ok' => false, 'error' => '비밀번호가 일치하지 않습니다.'));
    }
    exit;
}

// 비밀번호 확인 성공 - "재인증 성공" 상태 자체는 세션에 남기지 않고, 이번 요청 안에서만
// 그대로 이어서 복호화까지 처리한다(다음 열람 시도는 다시 비밀번호를 물어야 한다).
set_session('bp_key_reveal_fail_count', 0);
set_session('bp_key_reveal_lock_until', 0);

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

// 비밀값을 화면에 노출하는 행위 자체를 감사 로그에 남긴다(관리자 ID·공급자 ID·IP·시각).
bp_log_activity(0, 'ai_key_reveal', bp_current_admin_id(), "공급자 ID {$id}({$row['provider_code']}) API 키 열람, IP: {$reveal_ip}");

echo json_encode(array('ok' => true, 'api_key' => $plain));
exit;
