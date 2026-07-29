<?php
$sub_menu = '360300';
include_once('./_common.php');
header('Content-Type: application/json; charset=utf-8');

auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$site_id = isset($_POST['site_id']) ? (int) $_POST['site_id'] : 0;
$base_url_input = isset($_POST['base_url']) ? trim($_POST['base_url']) : '';
$username_input = isset($_POST['wp_username']) ? trim($_POST['wp_username']) : '';
$app_password_input = isset($_POST['wp_app_password']) ? trim($_POST['wp_app_password']) : '';

$sites_table = bp_table('sites');
$cred_table = bp_table('site_credentials');

$base_url = $base_url_input;
$username = $username_input;
$app_password = $app_password_input;

// 새로 입력한 값이 없으면(수정 화면에서 비밀번호 칸을 비워둔 채 테스트하는 경우)
// 이미 저장된 값으로 대체 시도한다.
if ($site_id > 0) {
    $site = sql_fetch(" select * from {$sites_table} where id = '{$site_id}' ");
    if ($site) {
        if ($base_url === '') {
            $base_url = $site['base_url'];
        }
        if ($username === '' || $app_password === '') {
            $cred = sql_fetch(" select * from {$cred_table} where site_id = '{$site_id}' and cred_type = 'wp_app_password' ");
            if ($cred) {
                if ($username === '') {
                    $username = $cred['cred_username'];
                }
                if ($app_password === '' && !empty($cred['cred_value_enc'])) {
                    $app_password = bp_decrypt_secret($cred['cred_value_enc']);
                }
            }
        }
    }
}

$result = bp_wp_test_connection($base_url, $username, $app_password);

if ($result['ok']) {
    echo json_encode(array('success' => true));
} else {
    echo json_encode(array('error' => $result['error']));
}
