<?php
include_once('./_common.php');

$landing_id = isset($_POST['landing_id']) ? (int)$_POST['landing_id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';
$agree = isset($_POST['agree']) ? trim($_POST['agree']) : '';

if ($landing_id < 1) {
    alert('잘못된 접근입니다.', G5_URL);
}

$landing_table = G5_TABLE_PREFIX . 'landing_pages';
$landing = sql_fetch(" select id, is_active from {$landing_table} where id = '{$landing_id}' limit 1 ");
if (!$landing) {
    alert('존재하지 않는 랜딩페이지입니다.', G5_URL);
}
if (!isset($landing['is_active']) || (string)$landing['is_active'] !== 'Y') {
    alert('현재 접수가 불가능한 랜딩페이지입니다.', G5_URL);
}

if ($name === '') {
    alert('이름을 입력하세요.', '/page/landing.php?id=' . $landing_id . '#contact');
}
if ($phone === '') {
    alert('연락처를 입력하세요.', '/page/landing.php?id=' . $landing_id . '#contact');
}
if ($agree !== '1') {
    alert('개인정보 수집 및 이용에 동의해 주세요.', '/page/landing.php?id=' . $landing_id . '#contact');
}

$table = G5_TABLE_PREFIX . 'landing_inquiries';
$sql = " insert into {$table}
            set landing_id = '" . (int)$landing_id . "',
                name = '" . sql_real_escape_string($name) . "',
                phone = '" . sql_real_escape_string($phone) . "',
                message = '" . sql_real_escape_string($message) . "',
                status = 'new',
                ip = '" . sql_real_escape_string($_SERVER['REMOTE_ADDR']) . "',
                created_at = '" . G5_TIME_YMDHIS . "' ";
sql_query($sql);

alert('문의가 접수되었습니다.', '/page/landing.php?id=' . $landing_id . '#contact');