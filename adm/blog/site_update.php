<?php
include_once('./_common.php');

$sub_menu = '360200';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$table = bp_table('sites');
$cred_table = bp_table('site_credentials');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$advertiser_id = isset($_POST['advertiser_id']) ? (int) $_POST['advertiser_id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$platform = isset($_POST['platform']) ? trim($_POST['platform']) : 'wordpress';
$base_url = isset($_POST['base_url']) ? trim($_POST['base_url']) : '';
$status = isset($_POST['status']) && $_POST['status'] === 'N' ? 'N' : 'Y';

if ($advertiser_id < 1) {
    alert('광고주를 선택해 주세요.');
}
if ($name === '') {
    alert('사이트명을 입력해 주세요.');
}
if (!in_array($platform, array('wordpress', 'php', 'naver'), true)) {
    $platform = 'wordpress';
}

$set_sql = "
    advertiser_id = '{$advertiser_id}',
    name = '" . sql_real_escape_string($name) . "',
    platform = '{$platform}',
    base_url = '" . sql_real_escape_string($base_url) . "',
    status = '{$status}',
    updated_at = '" . G5_TIME_YMDHIS . "'
";

if ($id > 0) {
    sql_query(" update {$table} set {$set_sql} where id = '{$id}' ");
    $site_id = $id;
} else {
    sql_query(" insert into {$table} set {$set_sql}, created_at = '" . G5_TIME_YMDHIS . "' ");
    $site_id = (int) sql_insert_id();
}

// WordPress Application Password — 값을 새로 입력한 경우에만 암호화하여 교체, 비워두면 기존 값을 유지한다.
$wp_username = isset($_POST['wp_username']) ? trim($_POST['wp_username']) : '';
$wp_app_password = isset($_POST['wp_app_password']) ? trim($_POST['wp_app_password']) : '';

if ($wp_username !== '' || $wp_app_password !== '') {
    $existing = sql_fetch(" select id from {$cred_table} where site_id = '{$site_id}' and cred_type = 'wp_app_password' ");
    $actor = bp_current_admin_id();

    if ($wp_app_password !== '') {
        $enc = bp_encrypt_secret($wp_app_password);
        $hint = bp_mask_secret($wp_app_password);
    } else {
        $enc = null;
        $hint = null;
    }

    if ($existing) {
        $extra = '';
        if ($enc !== null) {
            $extra = ", cred_value_enc = '" . sql_real_escape_string($enc) . "', masked_hint = '" . sql_real_escape_string($hint) . "'";
        }
        sql_query(" update {$cred_table}
                        set cred_username = '" . sql_real_escape_string($wp_username) . "',
                            updated_by = '" . sql_real_escape_string($actor) . "',
                            updated_at = '" . G5_TIME_YMDHIS . "'
                            {$extra}
                        where id = '" . (int)$existing['id'] . "' ");
    } elseif ($enc !== null) {
        sql_query(" insert into {$cred_table}
                        set site_id = '{$site_id}',
                            cred_type = 'wp_app_password',
                            cred_username = '" . sql_real_escape_string($wp_username) . "',
                            cred_value_enc = '" . sql_real_escape_string($enc) . "',
                            masked_hint = '" . sql_real_escape_string($hint) . "',
                            updated_by = '" . sql_real_escape_string($actor) . "',
                            created_at = '" . G5_TIME_YMDHIS . "',
                            updated_at = '" . G5_TIME_YMDHIS . "' ");
    }
}

alert($id > 0 ? '사이트 정보가 수정되었습니다.' : '사이트가 등록되었습니다.', G5_ADMIN_URL . '/blog/site_list.php');
