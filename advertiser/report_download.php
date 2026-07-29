<?php
// /advertiser/report_download.php
include_once('./_common.php');
adv_check_login();

// 강제로 세션의 advertiser_id 로 고정하여 다른 광고주 데이터 유출 차단
$_GET['advertiser_id'] = $_SESSION['ss_adv_advertiser_id'];

// 그누보드 관리자 인증 우회를 위해 auth 검사 패스
// (export_excel.php 내부에는 별도 auth_check 가 없는 상태이므로 그냥 require 해도 동작함)
$export_path = G5_ADMIN_PATH . '/blog/export_excel.php';

if (is_file($export_path)) {
    require_once($export_path);
} else {
    alert('보고서 생성 모듈을 찾을 수 없습니다.');
}
?>
