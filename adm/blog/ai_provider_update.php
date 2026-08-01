<?php
include_once('./_common.php');

$sub_menu = '360400';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$table = bp_table('ai_providers');
$actor = bp_current_admin_id();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$provider_code = isset($_POST['provider_code']) ? strtolower(trim($_POST['provider_code'])) : '';
$display_name = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';
$is_active = isset($_POST['is_active']) && $_POST['is_active'] === 'Y' ? 'Y' : 'N';
$default_model = isset($_POST['default_model']) ? trim($_POST['default_model']) : '';
$max_tokens = isset($_POST['max_tokens']) ? (int) $_POST['max_tokens'] : 2000;
$temperature = isset($_POST['temperature']) ? (float) $_POST['temperature'] : 0.70;
$api_endpoint = isset($_POST['api_endpoint']) ? trim($_POST['api_endpoint']) : '';
$api_key = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';
$return = isset($_POST['return']) ? $_POST['return'] : '';
$manager_redirect = SF_MANAGER_URL . '/blog/settings.php?tab=ai';

if ($display_name === '') {
    alert('공급자명을 입력해 주세요.');
}

$key_sql = '';
if ($api_key !== '') {
    $enc = bp_encrypt_secret($api_key);
    $hint = bp_mask_secret($api_key);
    // encryption_key_version은 방금 만든 암호문 자체에서 그대로 뽑는다(별도로 "현재 버전"을
    // 다시 계산하지 않음 - 두 값이 어긋날 일이 없게 단일 진실 공급원을 그대로 따름).
    $key_version = bp_crypto_extract_version($enc);
    $key_version_sql = $key_version !== null ? "'" . (int) $key_version . "'" : 'NULL';
    $key_sql = ", api_key_enc = '" . sql_real_escape_string($enc) . "', masked_hint = '" . sql_real_escape_string($hint) . "', encryption_key_version = {$key_version_sql}";
}

// ---- 수정(id 기반) ----
if ($id > 0) {
    $existing = sql_fetch(" select id, provider_code from {$table} where id = '{$id}' ");
    if (!$existing) {
        alert('AI 공급자를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/ai_provider_list.php');
    }
    // provider_code는 폼에서 readonly로 고정 전송되지만, 변조 방지를 위해 서버에서도 기존 값으로 강제 고정
    $provider_code = $existing['provider_code'];

    // 프로젝트별로 공급자를 지정해서 쓰는 구조(v19)이므로, 여러 공급자를 동시에
    // "사용함"으로 켜둘 수 있어야 한다 - 예전처럼 하나를 켜면 나머지를 자동으로
    // 끄지 않는다.

    sql_query(" update {$table}
                    set display_name = '" . sql_real_escape_string($display_name) . "',
                        is_active = '{$is_active}',
                        default_model = '" . sql_real_escape_string($default_model) . "',
                        api_endpoint = '" . sql_real_escape_string($api_endpoint) . "',
                        max_tokens = '" . (int) $max_tokens . "',
                        temperature = '" . (float) $temperature . "',
                        updated_by = '" . sql_real_escape_string($actor) . "',
                        updated_at = '" . G5_TIME_YMDHIS . "'
                        {$key_sql}
                    where id = '{$id}' ");

    alert('AI 공급자 설정이 저장되었습니다.', $return === 'manager' ? $manager_redirect : G5_ADMIN_URL . '/blog/ai_provider_form.php?id=' . $id);
}

// ---- 신규 등록 ----
if (!preg_match('/^[a-z0-9_]{1,30}$/', $provider_code)) {
    alert('공급자 코드는 영문 소문자·숫자·밑줄만 사용할 수 있습니다.');
}

$dup = sql_fetch(" select id from {$table} where provider_code = '" . sql_real_escape_string($provider_code) . "' ");
if ($dup) {
    alert('이미 등록된 공급자 코드입니다.');
}

sql_query(" insert into {$table}
                set provider_code = '" . sql_real_escape_string($provider_code) . "',
                    display_name = '" . sql_real_escape_string($display_name) . "',
                    is_active = '{$is_active}',
                    default_model = '" . sql_real_escape_string($default_model) . "',
                    api_endpoint = '" . sql_real_escape_string($api_endpoint) . "',
                    max_tokens = '" . (int) $max_tokens . "',
                    temperature = '" . (float) $temperature . "',
                    updated_by = '" . sql_real_escape_string($actor) . "',
                    created_at = '" . G5_TIME_YMDHIS . "',
                    updated_at = '" . G5_TIME_YMDHIS . "'
                    {$key_sql} ");
$new_id = (int) sql_insert_id();

alert('AI 공급자가 등록되었습니다.', $return === 'manager' ? $manager_redirect : G5_ADMIN_URL . '/blog/ai_provider_form.php?id=' . $new_id);
