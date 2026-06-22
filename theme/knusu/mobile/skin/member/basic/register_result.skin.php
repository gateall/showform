<?php
if (!defined('_GNUBOARD_')) exit;

add_stylesheet(
    '<link rel="stylesheet" href="'.$member_skin_url.'/style.css">',
    0
);
?>

<div id="register_result" class="register-result-page">

    <div class="register-result-card">

        <div class="result-icon">✓</div>

        <h2>
            회원가입이 완료되었습니다
        </h2>

        <p class="result-desc">
            인터넷2424 회원가입을 진심으로 환영합니다.<br>
            로그인 후 다양한 서비스를 이용하실 수 있습니다.
        </p>

        <div class="result-info-box">
            <p>
                입력하신 이메일과 비밀번호로 로그인해 주세요.
            </p>
        </div>

        <div class="result-btn-group">

            <a href="<?php echo G5_URL; ?>"
               class="result-btn result-home">

                홈으로 이동

            </a>

            <a href="<?php echo G5_BBS_URL; ?>/login.php"
               class="result-btn result-login">

                로그인하기

            </a>

        </div>

    </div>

</div>
