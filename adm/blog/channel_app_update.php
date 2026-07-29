<?php
include_once('./_common.php');

$sub_menu = '361110';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$table = bp_table('channel_apps');
$actor = bp_current_admin_id();

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$channel_code = isset($_POST['channel_code']) ? strtolower(trim($_POST['channel_code'])) : '';
$display_name = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';
$is_active = isset($_POST['is_active']) && $_POST['is_active'] === 'Y' ? 'Y' : 'N';
$client_id = isset($_POST['client_id']) ? trim($_POST['client_id']) : '';
$redirect_uri = isset($_POST['redirect_uri']) ? trim($_POST['redirect_uri']) : '';
$client_secret = isset($_POST['client_secret']) ? trim($_POST['client_secret']) : '';

if ($display_name === '') {
    alert('표시명을 입력해 주세요.');
}

$secret_sql = '';
if ($client_secret !== '') {
    $enc = bp_encrypt_secret($client_secret);
    $hint = bp_mask_secret($client_secret);
    $secret_sql = ", client_secret_enc = '" . sql_real_escape_string($enc) . "', masked_hint = '" . sql_real_escape_string($hint) . "'";
}

// ---- 수정(id 기반) ----
if ($id > 0) {
    $existing = sql_fetch(" select id, channel_code from {$table} where id = '{$id}' ");
    if (!$existing) {
        alert('채널 앱을 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/channel_app_list.php');
    }
    $channel_code = $existing['channel_code'];

    sql_query(" update {$table}
                    set display_name = '" . sql_real_escape_string($display_name) . "',
                        is_active = '{$is_active}',
                        client_id = '" . sql_real_escape_string($client_id) . "',
                        redirect_uri = '" . sql_real_escape_string($redirect_uri) . "',
                        updated_by = '" . sql_real_escape_string($actor) . "',
                        updated_at = '" . G5_TIME_YMDHIS . "'
                        {$secret_sql}
                    where id = '{$id}' ");

    alert('채널 앱 설정이 저장되었습니다.', G5_ADMIN_URL . '/blog/channel_app_form.php?id=' . $id);
}

// ---- 신규 등록 ----
if (!preg_match('/^[a-z0-9_]{1,30}$/', $channel_code)) {
    alert('채널 코드는 영문 소문자·숫자·밑줄만 사용할 수 있습니다.');
}

$dup = sql_fetch(" select id from {$table} where channel_code = '" . sql_real_escape_string($channel_code) . "' ");
if ($dup) {
    alert('이미 등록된 채널 코드입니다.');
}

sql_query(" insert into {$table}
                set channel_code = '" . sql_real_escape_string($channel_code) . "',
                    display_name = '" . sql_real_escape_string($display_name) . "',
                    is_active = '{$is_active}',
                    client_id = '" . sql_real_escape_string($client_id) . "',
                    redirect_uri = '" . sql_real_escape_string($redirect_uri) . "',
                    updated_by = '" . sql_real_escape_string($actor) . "',
                    created_at = '" . G5_TIME_YMDHIS . "',
                    updated_at = '" . G5_TIME_YMDHIS . "'
                    {$secret_sql} ");
$new_id = (int) sql_insert_id();

alert('채널 앱이 등록되었습니다.', G5_ADMIN_URL . '/blog/channel_app_form.php?id=' . $new_id);
