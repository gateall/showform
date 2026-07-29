<?php
// /advertiser/lib/auth.lib.php
if (!defined('_GNUBOARD_')) exit;

// 광고주 로그인 체크
function adv_check_login() {
    global $g5;
    $adv_url = G5_URL . '/advertiser';

    if (!isset($_SESSION['ss_adv_id']) || !$_SESSION['ss_adv_id']) {
        alert('로그인이 필요한 서비스입니다.', $adv_url . '/login.php');
    }

    $tbl_acc = bp_table('advertiser_accounts');
    $tbl_adv = bp_table('advertisers');
    
    // DB에서 계정 상태 다시 확인 (비활성되거나 계약 종료된 경우 차단)
    $acc_id = (int)$_SESSION['ss_adv_id'];
    $sql = " select a.status as acc_status, ad.contract_status 
             from {$tbl_acc} a 
             left join {$tbl_adv} ad on a.advertiser_id = ad.id 
             where a.id = '{$acc_id}' ";
    $row = sql_fetch($sql);

    if (!$row) {
        adv_logout('존재하지 않는 계정입니다.');
    }
    if ($row['acc_status'] !== 'active') {
        adv_logout('이용이 정지된 계정입니다. 관리자에게 문의하세요.');
    }
    if ($row['contract_status'] === '계약 종료') {
        adv_logout('계약이 종료되어 더 이상 접근할 수 없습니다.');
    }
}

// 로그아웃 처리 헬퍼
function adv_logout($msg = '') {
    $adv_url = G5_URL . '/advertiser';
    unset($_SESSION['ss_adv_id']);
    unset($_SESSION['ss_adv_login_id']);
    unset($_SESSION['ss_adv_advertiser_id']);
    unset($_SESSION['ss_adv_manager_name']);
    
    if ($msg) {
        alert($msg, $adv_url . '/login.php');
    } else {
        goto_url($adv_url . '/login.php');
    }
}

// 템플릿 헤더 (모바일 퍼스트 공통 헤더)
function adv_head($title = '광고주 대시보드') {
    global $g5;
    $adv_url = G5_URL . '/advertiser';
    $name = isset($_SESSION['ss_adv_manager_name']) ? $_SESSION['ss_adv_manager_name'] : '게스트';
    
    echo '<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title>'.$title.'</title>
<link rel="stylesheet" href="'.$adv_url.'/css/dashboard.css?ver='.time().'">
<script src="'.G5_URL.'/js/jquery-1.12.4.min.js"></script>
</head>
<body>
<header id="adv_hd">
    <div class="adv_hd_inner">
        <h1><a href="'.$adv_url.'/index.php">광고주 포털</a></h1>
        <div class="adv_tnb">
            <span><b>'.$name.'</b>님</span>
            <a href="'.$adv_url.'/logout.php" class="btn_logout">로그아웃</a>
        </div>
    </div>
</header>
<nav id="adv_gnb">
    <ul>
        <li><a href="'.$adv_url.'/index.php">대시보드</a></li>
        <li><a href="'.$adv_url.'/sites.php">운영 사이트</a></li>
        <li><a href="'.$adv_url.'/posts_scheduled.php">발행 예정</a></li>
        <li><a href="'.$adv_url.'/posts_published.php">발행 완료</a></li>
        <li><a href="'.$adv_url.'/reports.php">보고서 조회</a></li>
    </ul>
</nav>
<div id="adv_wrapper">
    <h2 class="adv_page_title">'.$title.'</h2>
    <div id="adv_container">
';
}

function adv_tail() {
    echo '
    </div>
</div>
</body>
</html>
';
}
?>
