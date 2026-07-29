<?php
// /advertiser/login.php
include_once('./_common.php');

if (isset($_SESSION['ss_adv_id']) && $_SESSION['ss_adv_id']) {
    goto_url(G5_URL . '/advertiser/index.php');
}

$g5['title'] = '광고주 로그인';
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0,maximum-scale=1.0,user-scalable=no">
<title><?php echo $g5['title']; ?></title>
<link rel="stylesheet" href="<?php echo G5_URL; ?>/advertiser/css/dashboard.css?ver=<?php echo time(); ?>">
</head>
<body class="adv_login_body">
<div class="adv_login_wrap">
    <h1>광고주 전용 포털</h1>
    <form name="flogin" action="<?php echo G5_URL; ?>/advertiser/login_check.php" method="post" autocomplete="off">
        <div class="adv_login_fields">
            <label for="login_id" class="sound_only">아이디</label>
            <input type="text" name="login_id" id="login_id" required class="frm_input" placeholder="아이디">
            
            <label for="login_pw" class="sound_only">비밀번호</label>
            <input type="password" name="login_pw" id="login_pw" required class="frm_input" placeholder="비밀번호">
            
            <button type="submit" class="btn_submit">로그인</button>
        </div>
    </form>
    <div class="adv_login_info">
        * 계정 발급 및 비밀번호 분실 시 담당자에게 문의해 주세요.
    </div>
</div>
</body>
</html>
