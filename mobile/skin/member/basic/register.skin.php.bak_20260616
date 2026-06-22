<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// add_stylesheet('css 구문', 출력순서); 숫자가 작을 수록 먼저 출력됨
add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
?>

<!-- 회원가입약관 동의 시작 { -->
<div class="register-wrap">
    <div class="register-box">
        <!-- 왼쪽: 서비스 소개 배너 영역 -->
        <div class="register-left">
            <div class="banner-content">
                <span class="banner-badge">이사 견적 비교 플랫폼</span>
                <h2>인디넷 2424</h2>
                <p class="banner-desc">전국 어디서나 이사 파트너들의 견적을<br>실시간으로 비교해 보세요.</p>
                <ul class="benefit-list">
                    <li>
                        <i class="fa fa-check-circle" aria-hidden="true"></i>
                        <span>빠르고 간편한 무료 견적 신청</span>
                    </li>
                    <li>
                        <i class="fa fa-shield" aria-hidden="true"></i>
                        <span>검증된 우수 이사업체 정보 제공</span>
                    </li>
                    <li>
                        <i class="fa fa-comment" aria-hidden="true"></i>
                        <span>소셜 계정으로 3초 간편 가입</span>
                    </li>
                </ul>
                <div class="banner-footer">
                    <span>INDINET 2424 © 2026</span>
                </div>
            </div>
        </div>
        
        <!-- 오른쪽: 회원가입 약관 및 소셜로그인 영역 -->
        <div class="register-right">
            <div class="register-header">
                <h3>회원가입 동의</h3>
                <p>회원가입 약관 및 개인정보 수집·이용에 동의해 주세요.</p>
            </div>
            
            <form name="fregister" id="fregister" action="<?php echo $register_action_url ?>" onsubmit="return fregister_submit(this);" method="POST" autocomplete="off">
                
                <?php
                // 소셜로그인 사용시 소셜로그인 버튼
                @include_once(get_social_skin_path().'/social_register.skin.php');
                ?>
                
                <div class="social-divider">
                    <span>또는 약관 동의 후 일반 가입</span>
                </div>

                <section id="fregister_term">
                    <h4>회원가입약관</h4>
                    <textarea readonly class="agree-textarea"><?php echo get_text($config['cf_stipulation']) ?></textarea>
                    <div class="agree-chk-row">
                        <input type="checkbox" name="agree" value="1" id="agree11" class="selec_chk">
                        <label for="agree11"><span></span>회원가입약관 내용에 동의합니다. (필수)</label>
                    </div>
                </section>

                <section id="fregister_private">
                    <h4>개인정보 수집 및 이용</h4>
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
                                    <td>이용자 식별 및 본인여부 확인</td>
                                    <td>아이디, 이름, 비밀번호<?php echo ($config['cf_cert_use'])? ", 생년월일, 휴대폰 번호(본인인증 할 때만, 암호화된 개인식별부호(CI)" : ""; ?></td>
                                    <td>회원 탈퇴 시까지</td>
                                </tr>
                                <tr>
                                    <td>고객서비스 이용 통지 및 CS대응</td>
                                    <td>연락처 (이메일, 휴대전화번호)</td>
                                    <td>회원 탈퇴 시까지</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="agree-chk-row">
                        <input type="checkbox" name="agree2" value="1" id="agree21" class="selec_chk">
                        <label for="agree21"><span></span>개인정보 수집 및 이용에 동의합니다. (필수)</label>
                    </div>
                </section>
                
                <div id="fregister_chkall" class="chk_all">
                    <input type="checkbox" name="chk_all" id="chk_all" class="selec_chk">
                    <label for="chk_all"><span></span><strong>회원가입 약관에 모두 동의합니다</strong></label>
                </div>
                    
                <div class="btn_confirm">
                    <a href="<?php echo G5_URL ?>" class="btn_close">취소</a>
                    <button type="submit" class="btn_submit">회원가입</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function fregister_submit(f)
{
    if (!f.agree.checked) {
        alert("회원가입약관의 내용에 동의하셔야 회원가입 하실 수 있습니다.");
        f.agree.focus();
        return false;
    }

    if (!f.agree2.checked) {
        alert("개인정보 수집 및 이용의 내용에 동의하셔야 회원가입 하실 수 있습니다.");
        f.agree2.focus();
        return false;
    }

    return true;
}

jQuery(function($){
    // 모두선택
    $("input[name=chk_all]").click(function() {
        if ($(this).prop('checked')) {
            $("input[name^=agree]").prop('checked', true);
        } else {
            $("input[name^=agree]").prop("checked", false);
        }
    });
});
</script>
<!-- } 회원가입 약관 동의 끝 -->
