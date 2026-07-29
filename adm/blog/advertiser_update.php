<?php
include_once('./_common.php');

$sub_menu = '360200';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$table = bp_table('advertisers');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$sub_phone = isset($_POST['sub_phone']) ? trim($_POST['sub_phone']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$domain = isset($_POST['domain']) ? trim($_POST['domain']) : '';
$consult_url = isset($_POST['consult_url']) ? trim($_POST['consult_url']) : '';
$kakao_channel = isset($_POST['kakao_channel']) ? trim($_POST['kakao_channel']) : '';
$service_region = isset($_POST['service_region']) ? trim($_POST['service_region']) : '';
$core_service = isset($_POST['core_service']) ? trim($_POST['core_service']) : '';
$intro_text = isset($_POST['intro_text']) ? trim($_POST['intro_text']) : '';
$forbidden_words = isset($_POST['forbidden_words']) ? trim($_POST['forbidden_words']) : '';
$mandatory_notice = isset($_POST['mandatory_notice']) ? trim($_POST['mandatory_notice']) : '';
$status = isset($_POST['status']) && $_POST['status'] === 'N' ? 'N' : 'Y';

if ($name === '') {
    alert('상호를 입력해 주세요.');
}

$set_sql = "
    name = '" . sql_real_escape_string($name) . "',
    phone = '" . sql_real_escape_string($phone) . "',
    sub_phone = '" . sql_real_escape_string($sub_phone) . "',
    address = '" . sql_real_escape_string($address) . "',
    domain = '" . sql_real_escape_string($domain) . "',
    consult_url = '" . sql_real_escape_string($consult_url) . "',
    kakao_channel = '" . sql_real_escape_string($kakao_channel) . "',
    service_region = '" . sql_real_escape_string($service_region) . "',
    core_service = '" . sql_real_escape_string($core_service) . "',
    intro_text = '" . sql_real_escape_string($intro_text) . "',
    forbidden_words = '" . sql_real_escape_string($forbidden_words) . "',
    mandatory_notice = '" . sql_real_escape_string($mandatory_notice) . "',
    status = '{$status}',
    updated_at = '" . G5_TIME_YMDHIS . "'
";

if ($id > 0) {
    sql_query(" update {$table} set {$set_sql} where id = '{$id}' ");
    alert('광고주 정보가 수정되었습니다.', G5_ADMIN_URL . '/blog/advertiser_list.php');
}

sql_query(" insert into {$table} set {$set_sql}, created_at = '" . G5_TIME_YMDHIS . "' ");
alert('광고주가 등록되었습니다.', G5_ADMIN_URL . '/blog/advertiser_list.php');
