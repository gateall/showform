<?php
if (!defined('_GNUBOARD_')) exit;

echo "\n<!-- LOADED: theme/knusu/skin/member/basic/register_form.skin.php -->\n";

add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
add_javascript('<script src="'.G5_JS_URL.'/jquery.register_form.js"></script>', 0);
if ($config['cf_cert_use'] && ($config['cf_cert_simple'] || $config['cf_cert_ipin'] || $config['cf_cert_hp'])) {
    add_javascript('<script src="'.G5_JS_URL.'/certify.js?v='.G5_JS_VER.'"></script>', 0);
}
echo '<script src="https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>'.PHP_EOL;

$member_email = isset($member['mb_email']) ? get_text($member['mb_email']) : '';
$member_name = isset($member['mb_name']) ? get_text($member['mb_name']) : '';
$member_hp = isset($member['mb_hp']) ? get_text($member['mb_hp']) : '';
$member_addr1 = isset($member['mb_addr1']) ? get_text($member['mb_addr1']) : '';
$member_addr2 = isset($member['mb_addr2']) ? get_text($member['mb_addr2']) : '';
$member_addr3 = isset($member['mb_addr3']) ? get_text($member['mb_addr3']) : '';
$member_zip = isset($member['mb_zip1'], $member['mb_zip2']) ? $member['mb_zip1'].$member['mb_zip2'] : '';
?>

<div class="register-form-page">
    <div class="register-form-card">
        <form id="fregisterform" name="fregisterform" action="<?php echo $register_action_url ?>" onsubmit="return fregisterform_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="w" value="<?php echo $w ?>">
            <input type="hidden" name="url" value="<?php echo $urlencode ?>">
            <input type="hidden" name="agree" value="<?php echo isset($agree) ? $agree : 1; ?>">
            <input type="hidden" name="agree2" value="<?php echo isset($agree2) ? $agree2 : 1; ?>">
            <input type="hidden" name="cert_type" value="<?php echo isset($member['mb_certify']) ? $member['mb_certify'] : ''; ?>">
            <input type="hidden" name="cert_no" value="">
            <?php if (isset($member['mb_sex'])) { ?><input type="hidden" name="mb_sex" value="<?php echo $member['mb_sex'] ?>"><?php } ?>

            <input type="hidden" name="mb_id" id="reg_mb_id" value="<?php echo $member_email ?>">
            <input type="hidden" name="mb_nick" id="reg_mb_nick" value="<?php echo $member_name ?>">
            <input type="hidden" name="mb_zip" id="reg_mb_zip" value="<?php echo $member_zip ?>">
            <input type="hidden" name="mb_addr3" id="reg_mb_addr3" value="<?php echo $member_addr3 ?>">
            <input type="hidden" name="mb_addr_jibeon" id="reg_mb_addr_jibeon" value="<?php echo isset($member['mb_addr_jibeon']) ? get_text($member['mb_addr_jibeon']) : ''; ?>">

            <div class="signup-form-fields">
                <div class="sns-wrap">
                    <a href="https://logis79.mycafe24.com/plugin/social/popup.php?provider=kakao&amp;url=%2Fbbs%2Flogin.php&amp;redirect_to_idp=1"
                       class="sns-icon social_link sns-kakao"
                       title="카카오">
                        <span class="ico"></span>
                        <span class="txt">
                            카카오<i> 로그인</i>
                        </span>
                    </a>
                </div>

                <div class="signup-divider">
                    <span>또는 이메일로 가입하기</span>
                </div>

                <div class="green-info-box blue-info-box">
                    한 번의 가입으로 구매, 생산자 등록, HUB 신청까지 팜마을의 모든 서비스를 자유롭게 이용하실 수 있습니다.
                </div>
                <div class="input-group">
                    <input type="text" id="reg_mb_name" name="mb_name" value="<?php echo $member_name ?>" required class="signup-input" placeholder="이름 (실명)">
                </div>

                <div class="input-group">
                    <input type="email" name="mb_email" value="<?php echo $member_email ?>" id="reg_mb_email" required class="signup-input" placeholder="이메일 주소 (아이디로 사용)">
                    <span class="validation-msg" id="email-validation-msg"></span>
                </div>

                <div class="register-input-wrap">
                    <input type="password" name="mb_password" id="reg_mb_password" <?php echo $required ?> class="signup-input" placeholder="비밀번호 (영문, 숫자 포함 8자 이상)">
                    <button type="button" class="password-view-toggle eye-icon" data-target="reg_mb_password" aria-label="비밀번호 보기">👁️</button>
                    <span class="validation-msg" id="password-strength-msg"></span>
                </div>

                <div class="register-input-wrap">
                    <input type="password" name="mb_password_re" id="reg_mb_password_re" <?php echo $required ?> class="signup-input" placeholder="비밀번호 확인">
                    <button type="button" class="password-view-toggle eye-icon" data-target="reg_mb_password_re" aria-label="비밀번호 보기">👁️</button>
                    <span class="validation-msg" id="password-match-msg"></span>
                </div>

                <div class="input-group">
                    <label class="group-label" for="reg_mb_hp">휴대폰 번호</label>
                    <input type="text" name="mb_hp" value="<?php echo $member_hp ?>" id="reg_mb_hp" required class="signup-input" placeholder="휴대폰 번호 (- 없이 입력)">
                </div>

                <div class="input-group address-group">
                    <label class="group-label">주소</label>
                    <div class="address-zip-row">
                        <input type="text" name="mb_addr1" value="<?php echo $member_addr1 ?>" id="reg_mb_addr1" required class="signup-input address-detail-input" placeholder="기본 주소" readonly>
                        <button type="button" class="address-search-btn" onclick="win_zip('fregisterform', 'mb_zip', 'mb_addr1', 'mb_addr2', 'mb_addr3', 'mb_addr_jibeon');">주소 검색</button>
                    </div>
                    <input type="text" name="mb_addr2" value="<?php echo $member_addr2 ?>" id="reg_mb_addr2" required class="signup-input address-detail-input" placeholder="상세 주소">
                </div>
            </div>

            <div class="form-agree-box chk_box">
                <input type="checkbox" id="agree_terms" required checked>
                <label for="agree_terms"><span></span>회원가입 및 견적 제출용으로만 사용하는 것에 동의합니다. (필수)</label>
            </div>

            <div class="submit-btn-group">
                <button type="submit" id="btn_submit" class="signup-submit-btn">회원가입 완료</button>
            </div>
        </form>
    </div>
</div>

<script>
function syncRegisterIdentity() {
    var email = $('#reg_mb_email').val().trim();
    var name = $('#reg_mb_name').val().trim();
    $('#reg_mb_id').val(email);
    if (!$('#reg_mb_nick').val().trim()) {
        $('#reg_mb_nick').val(name || email);
    }
}

function win_zip(frm_name, frm_zip, frm_addr1, frm_addr2, frm_addr3, frm_jibeon) {
    if (typeof daum === 'undefined' || typeof daum.Postcode === 'undefined') {
        alert('KAKAO 우편번호 서비스 postcode.v2.js 파일이 로드되지 않았습니다.');
        return false;
    }

    new daum.Postcode({
        oncomplete: function(data) {
            var roadAddr = data.roadAddress;
            var extraRoadAddr = '';

            if (data.bname !== '' && /[동|로|가]$/g.test(data.bname)) {
                extraRoadAddr += data.bname;
            }

            if (data.buildingName !== '' && data.apartment === 'Y') {
                extraRoadAddr += extraRoadAddr !== '' ? ', ' + data.buildingName : data.buildingName;
            }

            if (extraRoadAddr !== '') {
                extraRoadAddr = ' (' + extraRoadAddr + ')';
            }

            var f = document[frm_name];

            f[frm_zip].value = data.zonecode;
            f[frm_addr1].value = roadAddr || data.jibunAddress;

            if (f[frm_addr3]) {
                f[frm_addr3].value = extraRoadAddr;
            }

            if (f[frm_jibeon]) {
                f[frm_jibeon].value = data.userSelectedType === 'R' ? 'R' : 'J';
            }

            f[frm_addr2].focus();
        }
    }).open();

    return false;
}

function fregisterform_submit(f)
{
    syncRegisterIdentity();

    if (!$('#agree_terms').is(':checked')) {
        alert('회원가입 및 견적 제출용으로만 사용됨에 동의하셔야 회원가입을 하실 수 있습니다.');
        $('#agree_terms').focus();
        return false;
    }

    if (!f.mb_name.value) { alert('이름을 입력해 주세요.'); f.mb_name.focus(); return false; }
    if (!f.mb_email.value) { alert('이메일 주소를 입력해 주세요.'); f.mb_email.focus(); return false; }

    // 이메일 실시간 중복/형식 검증상태 최종 체크
    if ($('#email-validation-msg').hasClass('error')) {
        alert("이메일 주소가 올바르지 않거나 이미 사용 중입니다.");
        f.mb_email.focus();
        return false;
    }

    if (!f.mb_password.value) { alert('비밀번호를 입력해 주세요.'); f.mb_password.focus(); return false; }
    if (f.mb_password.value.length < 8) {
        alert("비밀번호는 영문, 숫자 포함 8자 이상이어야 합니다.");
        f.mb_password.focus();
        return false;
    }

    if ($('#password-strength-msg').hasClass('error')) {
        alert("비밀번호 강도 규칙에 부합하지 않습니다.");
        f.mb_password.focus();
        return false;
    }

    if (f.mb_password.value !== f.mb_password_re.value) { alert('비밀번호 확인이 일치하지 않습니다.'); f.mb_password_re.focus(); return false; }
    if (!f.mb_hp.value) { alert('휴대폰 번호를 입력해 주세요.'); f.mb_hp.focus(); return false; }
    if (!f.mb_addr1.value || !f.mb_addr2.value) { alert('주소를 입력해 주세요.'); return false; }

    // AJAX 세션 설정 동기화 대기 후 서브밋
    var submitOk = false;
    $.ajax({
        type: "POST",
        url: g5_bbs_url+"/ajax.mb_email.php",
        data: {
            "reg_mb_email": f.mb_email.value,
            "reg_mb_id": f.mb_email.value
        },
        async: false,
        success: function() {
            $.ajax({
                type: "POST",
                url: g5_bbs_url+"/ajax.mb_id.php",
                data: {
                    "reg_mb_id": f.mb_email.value
                },
                async: false,
                success: function() {
                    submitOk = true;
                }
            });
        }
    });

    if (!submitOk) {
        alert("정보 검증 중 오류가 발생했습니다. 잠시 후 다시 시도해 주세요.");
        return false;
    }

    document.getElementById("btn_submit").disabled = "disabled";
    return true;
}

jQuery(function($) {
    $('.password-view-toggle').on('click', function() {
        var $input = $('#' + $(this).data('target'));
        var isPassword = $input.attr('type') === 'password';
        $input.attr('type', isPassword ? 'text' : 'password');
        $(this).text(isPassword ? '🙈' : '👁️');
    });

    $('#reg_mb_hp').on('input', function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });

    $('#reg_mb_email, #reg_mb_name').on('input blur change', syncRegisterIdentity);
    syncRegisterIdentity();

    // 이메일 실시간 중복체크
    $('#reg_mb_email').on('keyup change blur', function() {
        var email = $(this).val();
        
        if (!email) {
            $('#email-validation-msg').text('').removeClass('success error');
            return;
        }

        // 간단한 이메일 형식 체크
        var emailRegex = /^[0-9a-zA-Z._%+\-]+@[0-9a-zA-Z.\-]+\.[A-Za-z]{2,}$/;
        if (!emailRegex.test(email)) {
            $('#email-validation-msg').text('올바른 이메일 형식이 아닙니다.').addClass('error').removeClass('success');
            return;
        }

        // ajax 실시간 검사
        $.ajax({
            type: "POST",
            url: g5_bbs_url+"/ajax.mb_email.php",
            data: {
                "reg_mb_email": email,
                "reg_mb_id": email
            },
            success: function(emailMsg) {
                if (emailMsg) {
                    $('#email-validation-msg').text(emailMsg).addClass('error').removeClass('success');
                } else {
                    // mb_id 중복 검사도 병행
                    $.ajax({
                        type: "POST",
                        url: g5_bbs_url+"/ajax.mb_id.php",
                        data: {
                            "reg_mb_id": email
                        },
                        success: function(idMsg) {
                            if (idMsg) {
                                $('#email-validation-msg').text(idMsg).addClass('error').removeClass('success');
                            } else {
                                $('#email-validation-msg').text('사용 가능한 이메일 주소입니다.').addClass('success').removeClass('error');
                            }
                        }
                    });
                }
            }
        });
    });

    // 비밀번호 강도 실시간 검증
    $('#reg_mb_password').on('keyup change blur', function() {
        var pw = $(this).val();
        var $msg = $('#password-strength-msg');
        
        if (!pw) {
            $msg.text('').removeClass('success error info warning');
            return;
        }

        if (pw.length < 8) {
            $msg.text('최소 8자 이상 입력해야 합니다.').addClass('error').removeClass('success warning info');
            return;
        }

        // 영문, 숫자 포함 여부 체크
        var hasLetter = /[a-zA-Z]/.test(pw);
        var hasNumber = /[0-9]/.test(pw);
        var hasSpecial = /[^a-zA-Z0-9]/.test(pw);
        
        if (!hasLetter || !hasNumber) {
            $msg.text('영문과 숫자를 포함해야 합니다.').addClass('error').removeClass('success warning info');
            return;
        }

        // 강도 결정
        var strength = 0;
        if (pw.length >= 10) strength++;
        if (hasSpecial) strength++;

        if (strength === 2) {
            $msg.text('비밀번호 안전도: 안전').addClass('success').removeClass('error warning info');
        } else if (strength === 1) {
            $msg.text('비밀번호 안전도: 보통').addClass('info').removeClass('error success warning');
        } else {
            $msg.text('비밀번호 안전도: 약함 (특수문자 등을 섞어보세요)').addClass('warning').removeClass('error success info');
        }
    });

    // 비밀번호 일치 실시간 검증
    $('#reg_mb_password_re').on('keyup change blur', function() {
        var pw = $('#reg_mb_password').val();
        var pwRe = $(this).val();
        var $msg = $('#password-match-msg');

        if (!pwRe) {
            $msg.text('').removeClass('success error');
            return;
        }

        if (pw !== pwRe) {
            $msg.text('비밀번호가 일치하지 않습니다.').addClass('error').removeClass('success');
        } else {
            $msg.text('비밀번호가 일치합니다.').addClass('success').removeClass('error');
        }
    });
});

jQuery(function($){

    $(".register-wrap").on(
        "click",
        "a.social_link",
        function(e){

            e.preventDefault();

            var pop_url = $(this).attr("href");

            var newWin = window.open(
                pop_url,
                "social_sing_on",
                "location=0,status=0,scrollbars=1,width=600,height=500"
            );

            if(
                !newWin ||
                newWin.closed ||
                typeof newWin.closed == 'undefined'
            ){
                alert(
                    '브라우저에서 팝업이 차단되어 있습니다. 팝업 활성화 후 다시 시도해 주세요.'
                );
            }

            return false;
        }
    );

});
</script>
