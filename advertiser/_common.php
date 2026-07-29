<?php
// /advertiser/_common.php
include_once('../common.php');

define('IS_ADVERTISER_PORTAL', true);

require_once(G5_ADMIN_PATH . '/blog/lib/blog_common.lib.php');
require_once(G5_ADMIN_PATH . '/blog/lib/blog_report.lib.php');
include_once(G5_PATH . '/advertiser/lib/auth.lib.php');

// 세션 시작
if (!session_id()) {
    session_start();
}

// 라이브러리 함수가 필요하다면 추가 로드
$adv_path = G5_PATH . '/advertiser';
$adv_url = G5_URL . '/advertiser';

// URL의 advertiser_id 변조 방지를 위해 전역에서 필터링
if (isset($_GET['advertiser_id']) && isset($_SESSION['ss_adv_id'])) {
    if ((int)$_GET['advertiser_id'] !== (int)$_SESSION['ss_adv_advertiser_id']) {
        alert('잘못된 접근입니다. 본인의 데이터만 조회할 수 있습니다.', $adv_url);
    }
}
?>
