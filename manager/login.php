<?php
// 로그인 화면 자체는 admin.lib.php의 강제 로그인 리다이렉트를 타면 안 되므로
// (그러면 /adm/ 쪽 로그인창으로 튕겨나간다) _common.php 대신 세션 판단만 하는
// common.php만 가볍게 불러온다. 실제 인증 처리는 그누보드 표준 login_check.php가 한다.
$g5_path = '..';
include_once(__DIR__ . '/../common.php');

function mgr_safe_return_url($url)
{
    $default = '/manager/blog/dashboard.php';

    $url = trim((string)$url);

    if ($url === '') {
        return $default;
    }

    if ($url[0] !== '/') {
        return $default;
    }

    if (strpos($url, '//') === 0) {
        return $default;
    }

    if (preg_match('#^(https?:)?//#i', $url)) {
        return $default;
    }

    return $url;
}

$url = mgr_safe_return_url(isset($_GET['url']) ? $_GET['url'] : '');

// 이미 로그인 중이면 바로 이동
if ($is_member) {
    if ($is_admin) {
        goto_url($url);
    } else {
        // 로그인은 됐지만 관리자가 아닌 경우. 여기서 다시 login.php로 돌려보내면
        // 무한 리다이렉트가 되므로 그 자리에서 끊는다.
        http_response_code(403);
        header('Content-Type: text/html; charset=utf-8');
        die('관리자 권한이 없습니다. <a href="' . G5_URL . '/">홈으로 이동</a>');
    }
}

$login_action_url = G5_HTTPS_BBS_URL . '/login_check.php';
$site_title = get_text($config['cf_title']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>로그인 - <?php echo $site_title; ?> 통합관리자</title>
<link rel="stylesheet" href="<?php echo G5_URL ?>/manager/assets/css/manager.css?v=<?php echo filemtime(__DIR__.'/assets/css/manager.css'); ?>">
</head>
<body class="mgr-login-body">
<div class="mgr-login-wrap">
    <div class="mgr-login-card">
        <div class="mgr-login-brand">
            <span class="mgr-login-logo">쇼폼</span>
            <p class="mgr-login-sub">통합관리자</p>
        </div>
        <form name="flogin" action="<?php echo $login_action_url ?>" method="post" class="mgr-login-form">
            <input type="hidden" name="url" value="<?php echo htmlspecialchars($url) ?>">
            <div class="mgr-field">
                <label for="mgr_login_id">아이디 / 이메일</label>
                <input type="text" name="mb_id" id="mgr_login_id" required maxlength="100" class="mgr-input" placeholder="아이디 또는 이메일">
            </div>
            <div class="mgr-field">
                <label for="mgr_login_pw">비밀번호</label>
                <input type="password" name="mb_password" id="mgr_login_pw" required maxlength="20" class="mgr-input" placeholder="비밀번호">
            </div>
            <button type="submit" class="mgr-btn mgr-btn-primary mgr-login-submit">로그인</button>
        </form>
        <div class="mgr-login-links">
            <a href="<?php echo G5_URL ?>/">홈페이지로 이동</a>
        </div>
    </div>
</div>
</body>
</html>
