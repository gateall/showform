<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if (!$config['cf_social_login_use']) {     //소셜 로그인을 사용하지 않으면
    return;
}

// 리모델 모달 및 스타일 추가
add_stylesheet('<link rel="stylesheet" href="'.G5_JS_URL.'/remodal/remodal.css">', 11);
add_stylesheet('<link rel="stylesheet" href="'.G5_JS_URL.'/remodal/remodal-default-theme.css">', 12);
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 13);
add_javascript('<script src="'.G5_JS_URL.'/remodal/remodal.js"></script>', 10);
add_javascript('<script src="'.G5_JS_URL.'/jquery.register_form.js"></script>', 14);

$email_msg = $is_exists_email ? '등록할 이메일이 중복되었습니다. 다른 이메일을 입력해 주세요.' : ''; 
?>

<div class="farmaul-signup-container social-register-container">
    <h2>소셜 계정 회원가입 완료</h2>
    <div class="green-info-box">
        🌱 카카오 계정 연동이 성공적으로 완료되었습니다.<br>서비스 이용을 위한 추가 정보를 기입해주세요.
    </div>
    
    <form name="fregisterform" id="fregisterform" action="<?php echo $register_action_url; ?>" onsubmit="return fregisterform_submit(this);" method="POST" autocomplete="off">
        <input type="hidden" name="w" value="<?php echo $w; ?>">
        <input type="hidden" name="url" value="<?php echo $urlencode; ?>">
        <input type="hidden" name="provider" value="<?php echo $provider_name; ?>">
        <input type="hidden" name="action" value="register">
        <input type="hidden" name="cert_type" value="<?php echo $member['mb_certify']; ?>">
        <input type="hidden" name="cert_no" value="">
        <input type="hidden" name="mb_id" value="<?php echo $user_id; ?>" id="reg_mb_id">
        <input type="hidden" name="agree" value="1">
        <input type="hidden" name="agree2" value="1">
        
        <?php if ($config["cf_cert_use"]) { ?>
            <input type="hidden" id="reg_mb_name" name="mb_name" value="<?php echo $user_name ? $user_name : $user_nick ?>">
        <?php } ?>
        <?php if ($config['cf_use_hp'] || ($config["cf_cert_use"] && ($config['cf_cert_hp'] || $config['cf_cert_simple']))) {  ?>
            <input type="hidden" name="mb_hp" value="<?php echo get_text($user_phone); ?>" id="reg_mb_hp">
        <?php }  ?>

        <div class="signup-form-fields">
            <!-- 닉네임 입력 (필수) -->
            <?php if ($req_nick) {  ?>
                <div class="input-group">
                    <label class="group-label">닉네임 (필수)</label>
                    <input type="hidden" name="mb_nick_default" value="<?php echo isset($user_nick) ? get_text($user_nick) : ''; ?>">
                    <input type="text" name="mb_nick" value="<?php echo isset($user_nick) ? get_text($user_nick) : ''; ?>" id="reg_mb_nick" required class="signup-input" placeholder="닉네임을 입력해 주세요">
                    <span class="validation-msg" id="nick-validation-msg"></span>
                </div>
            <?php }  ?>

            <!-- 이메일 주소 입력 (필수) -->
            <div class="input-group">
                <label class="group-label">이메일 주소 (필수)</label>
                <input type="hidden" name="old_email" value="<?php echo $member['mb_email'] ?>">
                <input type="email" name="mb_email" value="<?php echo isset($user_email) ? $user_email : ''; ?>" id="reg_mb_email" required <?php echo (isset($user_email) && $user_email != '' && !$is_exists_email)? "readonly":''; ?> class="signup-input" placeholder="이메일 주소를 입력해 주세요">
                <span class="validation-msg error" id="email-validation-msg"><?php echo $email_msg; ?></span>
            </div>

            <!-- 약관 동의 -->
            <div class="agree-checkbox-group">
                <label class="checkbox-label">
                    <input type="checkbox" id="agree_terms" required>
                    <span class="checkbox-custom"></span>
                    <span class="checkbox-text">
                        파마울 <a href="javascript:void(0);" onclick="open_terms_modal('stipulation');">이용약관</a> 및 <a href="javascript:void(0);" onclick="open_terms_modal('privacy');">개인정보처리방침</a>에 동의합니다. (필수)
                    </span>
                </label>
            </div>
        </div>

        <!-- 회원가입 완료 버튼 -->
        <div class="submit-btn-group">
            <button type="submit" id="btn_submit" class="signup-submit-btn">회원가입 완료</button>
        </div>
    </form>
    
    <!-- 기존 계정 연결 -->
    <div class="member-connect-section">
        <p>혹시 이미 가입한 회원이신가요?</p>
        <button type="button" class="connect-linking-btn" data-remodal-target="modal">
            기존 계정에 연결하기 ➔
        </button>
    </div>
</div>

<!-- 약관 보기 모달 레이어 -->
<div id="terms-modal" class="terms-modal-overlay">
    <div class="terms-modal-content">
        <div class="terms-modal-header">
            <h3 id="modal-title">약관 및 정책</h3>
            <button type="button" class="modal-close-btn" onclick="close_terms_modal();">×</button>
        </div>
        <div class="terms-modal-body" id="modal-body-content">
            <!-- 약관 내용 로드 -->
        </div>
    </div>
</div>

<!-- 기존 계정 연결 모달 -->
<div id="sns-link-pnl" class="remodal" data-remodal-id="modal" role="dialog" aria-labelledby="modal1Title" aria-describedby="modal1Desc">
    <button type="button" class="connect-close" data-remodal-action="close">
        <span class="txt">닫기 ×</span>
    </button>
    <div class="connect-fg">
        <form method="post" action="<?php echo $login_action_url ?>" onsubmit="return social_obj.flogin_submit(this);">
            <input type="hidden" id="url" name="url" value="<?php echo $login_url ?>">
            <input type="hidden" id="provider" name="provider" value="<?php echo $provider_name ?>">
            <input type="hidden" id="action" name="action" value="social_account_linking">

            <div class="connect-title">기존 계정에 연결하기</div>

            <div class="connect-desc">
                기존 아이디에 SNS 아이디를 연결합니다.<br>
                이 후 SNS 아이디로 로그인 하시면 기존 아이디로 로그인 할 수 있습니다.
            </div>

            <div class="linking-form-fields">
                <input type="text" name="mb_id" id="login_id" class="signup-input linking-input" placeholder="기본 계정 아이디 (이메일)" required>
                <input type="password" name="mb_password" id="login_pw" class="signup-input linking-input" placeholder="비밀번호" required style="margin-top:12px;">
                <input type="submit" value="연결 완료하기" class="signup-submit-btn" style="margin-top:20px; height:52px; font-size:16px;">
            </div>
        </form>
    </div>
</div>

<script>
// 약관 모달 데이터
var termsContent = {
    stipulation: <?php echo json_encode(nl2br(get_text($config['cf_stipulation']))); ?>,
    privacy: <?php echo json_encode(nl2br(get_text($config['cf_privacy']))); ?>
};

function open_terms_modal(type) {
    var title = type === 'stipulation' ? '파마울 서비스 이용약관' : '개인정보처리방침';
    var content = termsContent[type] || '내용이 없습니다.';
    
    document.getElementById('modal-title').innerText = title;
    document.getElementById('modal-body-content').innerHTML = content;
    document.getElementById('terms-modal').classList.add('is-active');
}

function close_terms_modal() {
    document.getElementById('terms-modal').classList.remove('is-active');
}

$(document).ready(function() {
    // 닉네임 실시간 중복 검증
    $('#reg_mb_nick').on('keyup change blur', function() {
        var nick = $(this).val();
        if (!nick) {
            $('#nick-validation-msg').text('').removeClass('success error');
            return;
        }
        
        $.ajax({
            type: "POST",
            url: g5_bbs_url+"/ajax.mb_nick.php",
            data: {
                "reg_mb_nick": nick,
                "reg_mb_id": encodeURIComponent($('#reg_mb_id').val())
            },
            success: function(msg) {
                if (msg) {
                    $('#nick-validation-msg').text(msg).addClass('error').removeClass('success');
                } else {
                    $('#nick-validation-msg').text('사용 가능한 닉네임입니다.').addClass('success').removeClass('error');
                }
            }
        });
    });
});

// submit 최종 폼체크
function fregisterform_submit(f) {
    if (!document.getElementById('agree_terms').checked) {
        alert("파마울 이용약관 및 개인정보처리방침에 동의하셔야 회원가입이 가능합니다.");
        return false;
    }

    if (f.reg_mb_nick && !f.reg_mb_nick.value) {
        alert("닉네임을 입력해 주세요.");
        f.reg_mb_nick.focus();
        return false;
    }

    if ($('#nick-validation-msg').hasClass('error')) {
        alert("닉네임에 오류가 있거나 이미 사용 중입니다.");
        f.reg_mb_nick.focus();
        return false;
    }

    if (!f.mb_email.value) {
        alert("이메일을 입력해 주세요.");
        f.mb_email.focus();
        return false;
    }

    // AJAX 검사 세션 대기 동기 처리 후 제출
    var submitOk = false;
    $.ajax({
        type: "POST",
        url: g5_bbs_url+"/ajax.mb_email.php",
        data: {
            "reg_mb_email": f.mb_email.value,
            "reg_mb_id": encodeURIComponent($('#reg_mb_id').val())
        },
        async: false,
        success: function(emailMsg) {
            if (emailMsg) {
                alert(emailMsg);
            } else {
                $.ajax({
                    type: "POST",
                    url: g5_bbs_url+"/ajax.mb_nick.php",
                    data: {
                        "reg_mb_nick": $('#reg_mb_nick').val(),
                        "reg_mb_id": encodeURIComponent($('#reg_mb_id').val())
                    },
                    async: false,
                    success: function(nickMsg) {
                        if (nickMsg) {
                            alert(nickMsg);
                        } else {
                            submitOk = true;
                        }
                    }
                });
            }
        }
    });

    if (!submitOk) {
        return false;
    }

    document.getElementById("btn_submit").disabled = "disabled";
    return true;
}
</script>

<!-- } 회원정보 입력/수정 끝 -->