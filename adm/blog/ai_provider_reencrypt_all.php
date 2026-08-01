<?php
include_once('./_common.php');

$sub_menu = '360400';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

// 마스터 키(BP_CRYPTO_KEY)를 회전한 뒤 실행하는 일괄 재암호화 도구. 평소 API 키
// 추가/수정/삭제에서는 이 파일을 거치지 않는다 - 오직 키 회전 시에만 쓴다.
$table = bp_table('ai_providers');
$current_version = bp_crypto_current_version();

$rows = array();
$res = sql_query(" select id, provider_code, display_name, api_key_enc from {$table} where api_key_enc != '' ");
while ($row = sql_fetch_array($res)) {
    $rows[] = $row;
}

$migrated = array();
$failed = array();
$already_current = 0;

foreach ($rows as $row) {
    $actual_version = bp_crypto_extract_version($row['api_key_enc']);
    if ($actual_version === $current_version) {
        $already_current++;
        continue;
    }

    $plain = bp_decrypt_secret($row['api_key_enc']);
    if ($plain === '') {
        // 복호화 실패 - 기존 암호문은 절대 건드리지 않는다(재암호화 성공 전에는 기존
        // 값을 지우거나 덮어쓰지 않는다는 원칙). 이 공급자는 관리자가 API 키를 수동으로
        // 다시 입력해야 한다(옛 키 상수가 이미 지워졌거나 원래 손상된 값).
        $failed[] = $row['display_name'];
        continue;
    }

    $new_enc = bp_encrypt_secret($plain);
    $new_version = bp_crypto_extract_version($new_enc);

    sql_query(" update {$table}
                    set api_key_enc = '" . sql_real_escape_string($new_enc) . "',
                        encryption_key_version = '" . (int) $new_version . "',
                        updated_by = '" . sql_real_escape_string(bp_current_admin_id()) . "',
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = '" . (int) $row['id'] . "' ");

    $migrated[] = $row['display_name'];
}

set_session('bp_reencrypt_result', array(
    'migrated' => $migrated,
    'failed' => $failed,
    'already_current' => $already_current,
));

$summary = count($migrated) . '건 재암호화 완료, ' . count($failed) . '건 실패, ' . $already_current . '건은 이미 최신 버전';
bp_log_activity(0, 'ai_key_reencrypt_all', bp_current_admin_id(), $summary . " (현재 버전 {$current_version})");

$return = isset($_REQUEST['return']) ? $_REQUEST['return'] : '';
$redirect = ($return === 'manager')
    ? SF_MANAGER_URL . '/blog/settings.php?tab=ai'
    : G5_ADMIN_URL . '/blog/ai_provider_list.php';

alert($summary, $redirect);
