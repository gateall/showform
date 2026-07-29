<?php
include_once('./_common.php');

$sub_menu = '361100';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$table = bp_table('advertiser_accounts');
$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$advertiser_id = isset($_POST['advertiser_id']) ? (int)$_POST['advertiser_id'] : 0;
$login_id = isset($_POST['login_id']) ? trim($_POST['login_id']) : '';
$login_password = isset($_POST['login_password']) ? trim($_POST['login_password']) : '';
$manager_name = isset($_POST['manager_name']) ? trim($_POST['manager_name']) : '';
$manager_email = isset($_POST['manager_email']) ? trim($_POST['manager_email']) : '';
$status = isset($_POST['status']) && $_POST['status'] === 'inactive' ? 'inactive' : 'active';
$reset_fail_count = isset($_POST['reset_fail_count']) ? (int)$_POST['reset_fail_count'] : 0;

if (!$advertiser_id) alert('광고주를 선택해 주세요.');
if ($id == 0 && !$login_id) alert('로그인 아이디를 입력해 주세요.');

$set_sql = "
    advertiser_id = '{$advertiser_id}',
    manager_name = '" . sql_real_escape_string($manager_name) . "',
    manager_email = '" . sql_real_escape_string($manager_email) . "',
    status = '{$status}'
";

if ($login_password) {
    // 그누보드 호환성 위해 동일한 hash 방식 사용하거나 php password_hash 사용
    // 광고주 로그인은 독자 시스템이므로 보안성 높은 password_hash 권장
    $hash = password_hash($login_password, PASSWORD_DEFAULT);
    $set_sql .= ", password_hash = '" . sql_real_escape_string($hash) . "' ";
}

if ($id > 0) {
    if ($reset_fail_count) {
        $set_sql .= ", login_fail_count = 0 ";
    }
    sql_query(" update {$table} set {$set_sql} where id = '{$id}' ");
    alert('계정 정보가 수정되었습니다.', G5_ADMIN_URL . '/blog/advertiser_account_list.php');
} else {
    // 중복 아이디 체크
    $row = sql_fetch(" select id from {$table} where login_id = '" . sql_real_escape_string($login_id) . "' ");
    if ($row) {
        alert('이미 존재하는 로그인 아이디입니다.');
    }
    
    $set_sql .= ", login_id = '" . sql_real_escape_string($login_id) . "' ";
    $set_sql .= ", created_at = '" . G5_TIME_YMDHIS . "' ";
    sql_query(" insert into {$table} set {$set_sql} ");
    alert('새로운 광고주 계정이 생성되었습니다.', G5_ADMIN_URL . '/blog/advertiser_account_list.php');
}
