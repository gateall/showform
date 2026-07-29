<?php
// /advertiser/login_check.php
include_once('./_common.php');

$login_id = isset($_POST['login_id']) ? trim($_POST['login_id']) : '';
$login_pw = isset($_POST['login_pw']) ? trim($_POST['login_pw']) : '';

if (!$login_id || !$login_pw) {
    alert('아이디와 비밀번호를 입력해 주세요.', './login.php');
}

$tbl_acc = bp_table('advertiser_accounts');
$tbl_adv = bp_table('advertisers');

$sql = " select a.*, ad.contract_status from {$tbl_acc} a left join {$tbl_adv} ad on a.advertiser_id = ad.id where a.login_id = '" . sql_real_escape_string($login_id) . "' ";
$row = sql_fetch($sql);

if (!$row) {
    alert('가입된 아이디가 아니거나 비밀번호가 틀립니다.\\n비밀번호는 대소문자를 구분합니다.', './login.php');
}

if ($row['status'] !== 'active') {
    alert('이용이 정지된 계정입니다. 담당자에게 문의하세요.', './login.php');
}

if ($row['contract_status'] === '계약 종료') {
    alert('계약이 종료되어 시스템에 접근할 수 없습니다.', './login.php');
}

// 비밀번호 확인 (password_verify)
if (!password_verify($login_pw, $row['password_hash'])) {
    // race condition 방지를 위해 DB 원자적 증가 사용
    sql_query(" update {$tbl_acc} set login_fail_count = login_fail_count + 1 where id = '{$row['id']}' ");
    
    // 증가된 실패 횟수 다시 가져오기
    $fail_row = sql_fetch(" select login_fail_count from {$tbl_acc} where id = '{$row['id']}' ");
    $fail_count = (int)$fail_row['login_fail_count'];
    
    if ($fail_count >= 5) {
        // 보안: 5회 이상 실패 시 계정 잠금 처리
        sql_query(" update {$tbl_acc} set status = 'inactive' where id = '{$row['id']}' ");
        alert('비밀번호를 5회 이상 틀려 계정이 잠겼습니다. 담당자에게 문의하세요.', './login.php');
    }
    alert('가입된 아이디가 아니거나 비밀번호가 틀립니다.\\n비밀번호는 대소문자를 구분합니다. (실패: '.$fail_count.'회)', './login.php');
}

// 로그인 성공
session_regenerate_id(true); // 세션 고정 공격 방지
$_SESSION['ss_adv_id'] = $row['id'];
$_SESSION['ss_adv_login_id'] = $row['login_id'];
$_SESSION['ss_adv_advertiser_id'] = $row['advertiser_id'];
$_SESSION['ss_adv_manager_name'] = $row['manager_name'];

// 로그인 기록 및 실패 카운트 리셋
sql_query(" update {$tbl_acc} set last_login_at = '" . G5_TIME_YMDHIS . "', login_fail_count = 0 where id = '{$row['id']}' ");

goto_url(G5_URL . '/advertiser/index.php');
?>
