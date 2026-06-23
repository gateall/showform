<?php
if (!defined('_GNUBOARD_')) exit;

add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);

?>

<div class="register-wrap">
    <div class="register-box">
        <div class="register-left">
            <div class="banner-content">
                <span class="banner-badge">회원가입 안내</span>
                <h2>팜마을 회원가입</h2>
                <p class="banner-desc">한 번의 가입으로 다양한 서비스 이용이 가능합니다.</p>
                <ul class="benefit-list">
                    <li><i class="fa fa-check-circle" aria-hidden="true"></i><span>구매, 생산자 등록, HUB 신청까지 한 번에 이용</span></li>
                    <li><i class="fa fa-shield" aria-hidden="true"></i><span>안전한 회원 정보 관리</span></li>
                    <li><i class="fa fa-comment" aria-hidden="true"></i><span>소셜 계정과 연동 가능한 간편 가입</span></li>
                </ul>
            </div>
        </div>

        <div class="register-right">
            <div class="register-header">
                <h3>회원가입약관 동의</h3>
                <p>약관에 동의하신 뒤 최종 회원가입 버튼을 눌러 다음 단계로 이동합니다.</p>
            </div>

            <form name="fregister" id="fregister" action="<?php echo G5_BBS_URL ?>/register_form.php" method="post" onsubmit="return fregister_submit(this);" autocomplete="off">
                <?php @include_once(get_social_skin_path().'/social_register.skin.php'); ?>

                <div class="social-divider"><span>회원가입약관</span></div>

                <section id="fregister_term">
                    <h4>회원가입약관</h4>
                    <textarea readonly class="agree-textarea">이 사이트의 회원가입 및 견적 제출용으로만 사용됩니다.
수집된 정보는 서비스 제공과 문의 응대를 위한 목적 외에는 사용하지 않습니다.</textarea>
                    <div class="agree-chk-row chk_box">
                        <input type="checkbox" name="agree" value="1" id="agree11" class="selec_chk">
                        <label for="agree11"><span></span>회원가입약관 내용에 동의합니다. (필수)</label>
                    </div>
                </section>

                <section id="fregister_private">
                    <h4>개인정보처리방침</h4>
                    <textarea readonly class="agree-textarea">회원가입 및 견적 제출 목적에 필요한 범위에서만 개인정보를 수집·이용합니다.
수집된 정보는 관련 법령 및 내부 보관 기준에 따라 안전하게 관리됩니다.</textarea>
                    <div class="table-container">
                        <table>
                            <caption>개인정보 수집 및 이용</caption>
                            <thead>
                                <tr>
                                    <th>목적</th>
                                    <th>항목</th>
                                    <th>보유기간</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>회원 식별 및 본인 확인</td>
                                    <td>아이디, 이름, 비밀번호<?php echo ($config['cf_cert_use']) ? ", 생년월일, 휴대폰 번호" : ""; ?></td>
                                    <td>회원 탈퇴 시까지</td>
                                </tr>
                                <tr>
                                    <td>고객지원 및 안내</td>
                                    <td>연락처(이메일, 휴대전화번호)</td>
                                    <td>회원 탈퇴 시까지</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="agree-chk-row chk_box">
                        <input type="checkbox" name="agree2" value="1" id="agree21" class="selec_chk">
                        <label for="agree21"><span></span>개인정보처리방침에 동의합니다. (필수)</label>
                    </div>
                </section>

                <div id="fregister_chkall" class="chk_all chk_box">
                    <input type="checkbox" name="chk_all" id="chk_all">
                    <label for="chk_all"><span></span><strong>회원가입약관에 모두 동의합니다</strong></label>
                </div>

                <div class="btn_confirm">
                    <a href="<?php echo G5_URL ?>" class="btn_close">취소</a>
                    <button type="submit" class="btn_submit">최종 회원가입</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function fregister_submit(f)
{
    if (!f.agree.checked) {
        alert('회원가입약관의 내용에 동의하셔야 회원가입 하실 수 있습니다.');
        f.agree.focus();
        return false;
    }

    if (!f.agree2.checked) {
        alert('개인정보처리방침의 내용에 동의하셔야 회원가입 하실 수 있습니다.');
        f.agree2.focus();
        return false;
    }

    return true;
}

jQuery(function($) {
    $('#chk_all').on('change', function() {
        $('input[name="agree"], input[name="agree2"]').prop('checked', $(this).is(':checked'));
    });

    $('input[name="agree"], input[name="agree2"]').on('change', function() {
        $('#chk_all').prop('checked', $('input[name="agree"]').is(':checked') && $('input[name="agree2"]').is(':checked'));
    });
});
</script>
