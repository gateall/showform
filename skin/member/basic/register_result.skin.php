<?php
if (!defined('_GNUBOARD_')) exit;

add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
?>

<div id="reg_result" class="register register-result">
    <div class="register-result-card">
        <div class="register-result-badge" aria-hidden="true">
            <i class="fa fa-check" aria-hidden="true"></i>
        </div>

        <h3 class="register-result-title">
            회원가입이 완료되었습니다
        </h3>

        <p class="register-result-text">
            <?php echo get_text($mb['mb_name']); ?>님, 회원가입을 환영합니다.
        </p>

        <div class="register-result-meta">
            <div class="register-result-row">
                <span class="label">아이디</span>
                <strong><?php echo get_text($mb['mb_id']); ?></strong>
            </div>
            <div class="register-result-row">
                <span class="label">이메일</span>
                <strong><?php echo get_text($mb['mb_email']); ?></strong>
            </div>
        </div>

        <?php if (is_use_email_certify()) { ?>
        <div class="register-result-notice">
            <p>
                입력하신 이메일 주소로 인증 메일을 발송했습니다.
                메일함에서 인증 메일을 확인한 뒤 안내에 따라 인증을 완료해 주세요.
            </p>
            <p>
                인증을 마쳐야 정상적으로 서비스 이용이 가능합니다.
            </p>
        </div>
        <?php } ?>

        <div class="register-result-tip">
            <p>
                비밀번호는 암호화되어 저장되므로 안전하게 관리됩니다.
                로그인 정보를 잊지 않도록 메모해 두시면 좋습니다.
            </p>
        </div>
    </div>

    <div class="btn_confirm_reg">
        <a href="<?php echo G5_URL; ?>/" class="reg_btn_submit">메인으로 이동</a>
    </div>
</div>
