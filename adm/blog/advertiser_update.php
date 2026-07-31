<?php
include_once('./_common.php');

$sub_menu = '360100';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$table = bp_table('advertisers');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$ceo_name = isset($_POST['ceo_name']) ? trim($_POST['ceo_name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$sub_phone = isset($_POST['sub_phone']) ? trim($_POST['sub_phone']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$address = isset($_POST['address']) ? trim($_POST['address']) : '';
$domain = isset($_POST['domain']) ? trim($_POST['domain']) : '';
$consult_url = isset($_POST['consult_url']) ? trim($_POST['consult_url']) : '';
$kakao_channel = isset($_POST['kakao_channel']) ? trim($_POST['kakao_channel']) : '';
// 서비스 지역은 체크박스 배열(service_region[])로 넘어온다 - 기존 텍스트 컬럼에는
// 그대로 콤마구분 문자열로 합쳐서 저장한다(기존 관례, 스키마 변경 없음).
$service_region_arr = isset($_POST['service_region']) && is_array($_POST['service_region']) ? $_POST['service_region'] : array();
$service_region = implode(',', array_map('trim', $service_region_arr));
$industry_arr = isset($_POST['industry']) && is_array($_POST['industry']) ? $_POST['industry'] : array();
$industry = implode(',', array_map('trim', $industry_arr));
$industry_detail = isset($_POST['industry_detail']) ? trim($_POST['industry_detail']) : '';
$core_service = isset($_POST['core_service']) ? trim($_POST['core_service']) : '';
$intro_text = isset($_POST['intro_text']) ? trim($_POST['intro_text']) : '';
$forbidden_words = isset($_POST['forbidden_words']) ? trim($_POST['forbidden_words']) : '';
$mandatory_notice = isset($_POST['mandatory_notice']) ? trim($_POST['mandatory_notice']) : '';
$memo = isset($_POST['memo']) ? trim($_POST['memo']) : '';
$status = isset($_POST['status']) && $_POST['status'] === 'N' ? 'N' : 'Y';
$contract_start_date = isset($_POST['contract_start_date']) ? trim($_POST['contract_start_date']) : '';
if (!$contract_start_date) $contract_start_date = '0000-00-00';
$contract_end_date = isset($_POST['contract_end_date']) ? trim($_POST['contract_end_date']) : '';
if (!$contract_end_date) $contract_end_date = '0000-00-00';
$contract_status = isset($_POST['contract_status']) ? trim($_POST['contract_status']) : '운영 중';
$monthly_post_quota = isset($_POST['monthly_post_quota']) ? (int)$_POST['monthly_post_quota'] : 0;

if ($name === '') {
    alert('상호를 입력해 주세요.');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    alert('이메일 형식이 올바르지 않습니다.');
}

$set_sql = "
    name = '" . sql_real_escape_string($name) . "',
    ceo_name = '" . sql_real_escape_string($ceo_name) . "',
    phone = '" . sql_real_escape_string($phone) . "',
    sub_phone = '" . sql_real_escape_string($sub_phone) . "',
    email = '" . sql_real_escape_string($email) . "',
    address = '" . sql_real_escape_string($address) . "',
    domain = '" . sql_real_escape_string($domain) . "',
    consult_url = '" . sql_real_escape_string($consult_url) . "',
    kakao_channel = '" . sql_real_escape_string($kakao_channel) . "',
    service_region = '" . sql_real_escape_string($service_region) . "',
    industry = '" . sql_real_escape_string($industry) . "',
    industry_detail = '" . sql_real_escape_string($industry_detail) . "',
    core_service = '" . sql_real_escape_string($core_service) . "',
    intro_text = '" . sql_real_escape_string($intro_text) . "',
    forbidden_words = '" . sql_real_escape_string($forbidden_words) . "',
    mandatory_notice = '" . sql_real_escape_string($mandatory_notice) . "',
    memo = '" . sql_real_escape_string($memo) . "',
    status = '{$status}',
    contract_start_date = IF('{$contract_start_date}'='0000-00-00', NULL, '{$contract_start_date}'),
    contract_end_date = IF('{$contract_end_date}'='0000-00-00', NULL, '{$contract_end_date}'),
    contract_status = '" . sql_real_escape_string($contract_status) . "',
    monthly_post_quota = '{$monthly_post_quota}',
    updated_at = '" . G5_TIME_YMDHIS . "'
";

if ($id > 0) {
    sql_query(" update {$table} set {$set_sql} where id = '{$id}' ");
    alert('광고주 정보가 수정되었습니다.', G5_ADMIN_URL . '/blog/advertiser_list.php');
}

sql_query(" insert into {$table} set {$set_sql}, created_at = '" . G5_TIME_YMDHIS . "' ");
alert('광고주가 등록되었습니다.', G5_ADMIN_URL . '/blog/advertiser_list.php');
