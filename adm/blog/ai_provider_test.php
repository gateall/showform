<?php
$sub_menu = '360400';
include_once('./_common.php');
auth_check_menu($auth, '360400', 'w');
header('Content-Type: application/json; charset=utf-8');

// AJAX 전용으로 설계
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(array('ok' => false, 'error' => 'POST 요청만 허용됩니다.'));
    exit;
}

// 저장 폼이 쓰는 그누보드 공용 관리자 토큰(check_admin_token, 1회성)을 여기서 쓰면 검사
// 즉시 세션에서 지워져 - 테스트를 한 번만 실행해도 같은 화면의 저장 버튼이 "새로고침 후
// 재시도"를 요구하게 된다. 연결 테스트는 상태를 바꾸지 않는 조회 액션이라 완전히 분리된
// 전용 토큰(bp_check_test_token)을 쓴다.
if (!bp_check_test_token()) {
    echo json_encode(array('ok' => false, 'error' => '세션이 만료되었습니다. 화면을 새로고침한 후 다시 시도해 주세요.'));
    exit;
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    echo json_encode(array('ok' => false, 'error' => '잘못된 제공자 ID입니다.'));
    exit;
}

$table = bp_table('ai_providers');
$row = sql_fetch(" select * from {$table} where id = '{$id}' ");
if (!$row) {
    echo json_encode(array('ok' => false, 'error' => '제공자 레코드를 찾을 수 없습니다.'));
    exit;
}

$provider_code = $row['provider_code'];

function log_ai_test($id, $action, $detail) {
    global $member;
    $table = bp_table('content_activity_logs');
    $actor = isset($member['mb_id']) ? $member['mb_id'] : 'admin';
    $action_safe = sql_real_escape_string($action);
    $actor_safe = sql_real_escape_string($actor);
    $detail_safe = sql_real_escape_string($detail);
    // project_id는 content_projects 외래키라서 0을 넣으면 위반으로 조용히 실패한다(V23으로
    // NULL 허용). post_id는 애초에 이 테이블에 존재하지 않는 컬럼이라 그것만으로도 매번
    // 실패하고 있었다 - 둘 다 고쳐야 이 로그가 실제로 저장된다.
    sql_query(" insert into {$table} set project_id = NULL, action = '{$action_safe}', actor = '{$actor_safe}', detail = '{$detail_safe}', created_at = NOW() ");
}

// 에러 메시지 마스킹 헬퍼 (API 키 노출 방지) - openai/gemini 공용
function mask_api_key($str, $key) {
    if (empty($key)) return $str;
    $masked = substr($key, 0, 3) . '...' . substr($key, -4);
    return str_replace($key, $masked, $str);
}

if (empty($row['api_key_enc'])) {
    $msg = 'API 키가 설정되지 않았습니다.';
    log_ai_test($id, 'ai_test_fail', "테스트 실패 (ID: {$id}, 코드: {$provider_code}) - {$msg}");
    bp_ai_save_test_result($id, 'error', $msg);
    echo json_encode(array('ok' => false, 'error' => $msg));
    exit;
}

$api_key = bp_decrypt_secret($row['api_key_enc']);
if ($api_key === '') {
    $msg = 'API 키 복호화에 실패했습니다.';
    log_ai_test($id, 'ai_test_fail', "테스트 실패 (ID: {$id}, 코드: {$provider_code}) - {$msg}");
    bp_ai_save_test_result($id, 'error', $msg);
    echo json_encode(array('ok' => false, 'error' => $msg));
    exit;
}

// 지원 제공자 제한 - 실제 연동이 있는 코드만 여기서 연결 테스트를 진행한다.
// (연동이 아직 없는 코드는 상태값을 건드리지 않는다 - "테스트 필요"로 계속 남는 게 정확하다.)
if (!bp_ai_provider_is_live($provider_code)) {
    $msg = "현재 연결 테스트는 '{$provider_code}' 제공자를 지원하지 않습니다.";
    log_ai_test($id, 'ai_test_blocked', "테스트 미지원 (ID: {$id}, 코드: {$provider_code})");
    echo json_encode(array('ok' => false, 'error' => $msg));
    exit;
}

if (!function_exists('curl_init')) {
    echo json_encode(array('ok' => false, 'error' => '서버에 cURL 확장이 설치되어 있지 않습니다.'));
    exit;
}

$start_time = microtime(true);
$target_model = $row['default_model'];

if ($provider_code === 'gemini') {
    // Gemini는 API 키를 Authorization 헤더가 아니라 URL 쿼리스트링으로 받는다 - $endpoint에
    // 키가 포함되므로, 이 변수를 오류 메시지나 로그에 그대로 남기지 않는다.
    $defaults = bp_ai_provider_defaults('gemini');
    $base = !empty($row['api_endpoint']) ? rtrim($row['api_endpoint'], '/') : $defaults['endpoint'];
    $endpoint = $base . '/models?key=' . rawurlencode($api_key);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $response = curl_exec($ch);
    $curl_err = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $elapsed_ms = round((microtime(true) - $start_time) * 1000);

    if ($curl_err) {
        $msg = "cURL 네트워크 연결 실패: {$curl_err}";
        log_ai_test($id, 'ai_test_fail', "네트워크 오류 (ID: {$id}, 시간: {$elapsed_ms}ms) - {$curl_err}");
        bp_ai_save_test_result($id, 'error', $msg);
        echo json_encode(array('ok' => false, 'error' => $msg));
        exit;
    }

    $res_data = json_decode((string) $response, true);
    if (!$res_data) {
        $msg = "올바르지 않은 JSON 응답입니다. (HTTP {$http_code})";
        log_ai_test($id, 'ai_test_fail', "응답 오류 (ID: {$id}, HTTP: {$http_code})");
        bp_ai_save_test_result($id, 'error', $msg);
        echo json_encode(array('ok' => false, 'error' => $msg));
        exit;
    }

    if ($http_code >= 400 || isset($res_data['error'])) {
        $raw_err_msg = isset($res_data['error']['message']) ? $res_data['error']['message'] : '알 수 없는 API 오류';

        $reason = '';
        if ($http_code == 400) $reason = '인증 실패 (API 키 오류)';
        else if ($http_code == 403) $reason = '권한 없음 (API 키 또는 프로젝트 설정 확인 필요)';
        else if ($http_code == 429) $reason = '요청 한도 초과 (Rate Limit)';
        else $reason = "API 에러 응답 (HTTP {$http_code})";

        $full_msg = "{$reason} - {$raw_err_msg}";
        log_ai_test($id, 'ai_test_fail', "API 오류 (ID: {$id}, HTTP: {$http_code}) - {$raw_err_msg}");
        bp_ai_save_test_result($id, 'error', $full_msg);
        echo json_encode(array('ok' => false, 'error' => $full_msg));
        exit;
    }

    // Gemini 모델명은 "models/gemini-2.5-pro" 형태로 온다 - 저장된 값(gemini-2.5-pro)과
    // 비교하려면 접두어를 떼어내야 한다.
    $model_found = false;
    if (isset($res_data['models']) && is_array($res_data['models'])) {
        foreach ($res_data['models'] as $m) {
            $name = isset($m['name']) ? preg_replace('#^models/#', '', $m['name']) : '';
            if ($target_model !== '' && $name === $target_model) {
                $model_found = true;
                break;
            }
        }
    }

    $model_msg = "접근 가능함";
    if ($target_model !== '' && !$model_found) {
        $model_msg = "접근 권한이 확인되지 않거나 지원하지 않음 (모델 리스트에 없음)";
    }

    $success_detail = "테스트 성공 (ID: {$id}, 모델: {$target_model}, HTTP: {$http_code}, 시간: {$elapsed_ms}ms)";
    log_ai_test($id, 'ai_test_success', $success_detail);
    bp_ai_save_test_result($id, 'success', "모델 {$model_msg}, {$elapsed_ms}ms");

    echo json_encode(array(
        'ok' => true,
        'provider' => 'Gemini',
        'http_code' => $http_code,
        'elapsed_ms' => $elapsed_ms,
        'model' => $target_model !== '' ? $target_model : '(미지정)',
        'model_status' => $model_msg,
        'timestamp' => date('Y-m-d H:i:s'),
    ));
    exit;
}

// ---- openai ----
$endpoint = 'https://api.openai.com/v1/models';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// 모델 목록 조회는 GET 요청
curl_setopt($ch, CURLOPT_HTTPGET, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $api_key,
));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

$response = curl_exec($ch);
$curl_err = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$elapsed_ms = round((microtime(true) - $start_time) * 1000);

if ($curl_err) {
    $safe_err = mask_api_key($curl_err, $api_key);
    $msg = "cURL 네트워크 연결 실패: {$safe_err}";
    log_ai_test($id, 'ai_test_fail', "네트워크 오류 (ID: {$id}, 시간: {$elapsed_ms}ms) - {$safe_err}");
    bp_ai_save_test_result($id, 'error', $msg);
    echo json_encode(array('ok' => false, 'error' => $msg));
    exit;
}

$res_data = json_decode((string)$response, true);
if (!$res_data) {
    $msg = "올바르지 않은 JSON 응답입니다. (HTTP {$http_code})";
    log_ai_test($id, 'ai_test_fail', "응답 오류 (ID: {$id}, HTTP: {$http_code})");
    bp_ai_save_test_result($id, 'error', $msg);
    echo json_encode(array('ok' => false, 'error' => $msg));
    exit;
}

if ($http_code >= 400 || isset($res_data['error'])) {
    $raw_err_msg = isset($res_data['error']['message']) ? $res_data['error']['message'] : '알 수 없는 API 오류';
    $safe_err_msg = mask_api_key($raw_err_msg, $api_key);

    $reason = '';
    if ($http_code == 401) $reason = '인증 실패 (API 키 오류)';
    else if ($http_code == 429) $reason = '요청 한도 초과 (잔액 부족 또는 Rate Limit)';
    else $reason = "API 에러 응답 (HTTP {$http_code})";

    $full_msg = "{$reason} - {$safe_err_msg}";
    log_ai_test($id, 'ai_test_fail', "API 오류 (ID: {$id}, HTTP: {$http_code}) - {$safe_err_msg}");
    bp_ai_save_test_result($id, 'error', $full_msg);
    echo json_encode(array('ok' => false, 'error' => $full_msg));
    exit;
}

// 모델 검증 (DB에 지정된 모델이 있으면 리스트에 존재하는지 간접 확인)
$model_found = false;
$available_models = array();
if (isset($res_data['data']) && is_array($res_data['data'])) {
    foreach ($res_data['data'] as $m) {
        if (isset($m['id'])) {
            $available_models[] = $m['id'];
            if ($target_model !== '' && $m['id'] === $target_model) {
                $model_found = true;
            }
        }
    }
}

$model_msg = "접근 가능함";
if ($target_model !== '' && !$model_found) {
    $model_msg = "접근 권한이 확인되지 않거나 지원하지 않음 (모델 리스트에 없음)";
    // 주의: 실패가 아니라 권한 문제일 수 있음 (fine-tuning 등). 경고 문구로만 표시.
}

log_ai_test($id, 'ai_test_success', "테스트 성공 (ID: {$id}, 모델: {$target_model}, HTTP: {$http_code}, 시간: {$elapsed_ms}ms)");
bp_ai_save_test_result($id, 'success', "모델 {$model_msg}, {$elapsed_ms}ms");

echo json_encode(array(
    'ok' => true,
    'provider' => 'OpenAI',
    'http_code' => $http_code,
    'elapsed_ms' => $elapsed_ms,
    'model' => $target_model !== '' ? $target_model : '(미지정)',
    'model_status' => $model_msg,
    'timestamp' => date('Y-m-d H:i:s')
));
exit;
