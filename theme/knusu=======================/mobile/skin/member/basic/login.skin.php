<?php
if (!defined('_GNUBOARD_')) exit;

add_stylesheet('<link rel="stylesheet" href="'.$member_skin_url.'/style.css">', 0);
?>

<div id="mb_login" class="login-page">
    <div class="login-card">
        <div class="login-top">
            <a href="#login" class="login-tab is-active">로그인</a>
            <a href="<?php echo G5_BBS_URL ?>/register.php" class="login-tab">회원가입</a>
        </div>

        <form name="flogin" action="<?php echo $login_action_url ?>" onsubmit="return flogin_submit(this);" method="post" id="flogin" class="login-form">
            <input type="hidden" name="url" value="<?php echo $login_url ?>">

            <div class="login-divider"><span>또는 이메일로 로그인</span></div>

            <div class="login-field">
                <label for="login_id">이메일 주소</label>
                <input type="text" name="mb_id" id="login_id" required class="frm_input required" maxlength="100" placeholder="이메일 주소 (아이디)">
            </div>

            <div class="login-field">
                <label for="login_pw">비밀번호</label>
                <div class="login-password-wrap">
                    <input type="password" name="mb_password" id="login_pw" required class="frm_input required" maxlength="20" placeholder="비밀번호">
                    <button type="button" class="login-password-toggle" id="login_pw_toggle" aria-label="비밀번호 보기">보기</button>
                </div>
            </div>

            <button type="submit" class="login-submit">로그인</button>

            <div class="login-links">
                <a href="<?php echo G5_BBS_URL ?>/password_lost.php">비밀번호 찾기</a>
                <a href="<?php echo G5_BBS_URL ?>/register.php">이메일로 회원가입</a>
            </div>
        </form>
    </div>
</div>

<script>
jQuery(function($){
    $("#login_pw_toggle").on("click", function(){
        var $pw = $("#login_pw");
        var isPassword = $pw.attr("type") === "password";

        $pw.attr("type", isPassword ? "text" : "password");
        $(this).text(isPassword ? "숨기기" : "보기");
        $(this).attr("aria-label", isPassword ? "비밀번호 숨기기" : "비밀번호 보기");
    });
});

function flogin_submit(f)
{
    if($(document.body).triggerHandler('login_sumit', [f, 'flogin']) !== false){
        return true;
    }

    return false;
}
</script>

<style>
#mb_login.login-page{max-width:450px;margin:0 auto;padding:24px 16px 40px;box-sizing:border-box}
#mb_login .login-card{background:#fff;border:1px solid #e7ebef;border-radius:24px;box-shadow:0 18px 50px rgba(16,24,40,.08);padding:24px;box-sizing:border-box}
#mb_login .login-top{display:flex;gap:10px;margin-bottom:20px}
#mb_login .login-tab{flex:1;text-align:center;padding:13px 10px;border-radius:999px;background:#f3f5f7;color:#667085;font-weight:700;text-decoration:none}
#mb_login .login-tab.is-active{background:#111827;color:#fff}
#mb_login .login-divider{position:relative;margin:18px 0;text-align:center;color:#98a2b3;font-size:14px}
#mb_login .login-divider:before{content:"";position:absolute;left:0;right:0;top:50%;height:1px;background:#e5e7eb}
#mb_login .login-divider span{position:relative;background:#fff;padding:0 12px}
#mb_login .login-sns,#mb_login #sns_login{width:100%;box-sizing:border-box}
#mb_login .login-sns h3{font-size:15px;margin:0 0 10px;color:#344054}
#mb_login .sns-wrap{width:100%;box-sizing:border-box}
#mb_login .sns-icon.social_link.sns-kakao{display:flex;align-items:center;justify-content:center;gap:10px;width:100%;min-height:54px;padding:0 16px;border-radius:14px;background:#fee500;color:#191919;font-weight:800;text-decoration:none;box-sizing:border-box;white-space:normal;overflow-wrap:anywhere}
#mb_login .sns-icon.social_link.sns-kakao .ico{width:24px;height:24px;flex:0 0 auto;background:url('<?php echo G5_IMG_URL; ?>/kakao.png') no-repeat center;background-size:contain}
#mb_login .sns-icon.social_link.sns-kakao .txt{display:inline-flex;align-items:center;justify-content:center;flex-wrap:wrap;gap:2px;min-width:0;line-height:1.25;white-space:normal;word-break:keep-all}
#mb_login .sns-icon.social_link.sns-kakao .txt i{font-style:normal}
#mb_login .login-field{margin-bottom:14px}
#mb_login .login-field label{display:block;margin-bottom:8px;font-size:14px;font-weight:700;color:#344054}
#mb_login .login-field .frm_input{width:100%;height:52px;border-radius:14px;border:1px solid #d0d5dd;padding:0 16px;font-size:16px;background:#fff;box-sizing:border-box}
#mb_login .login-password-wrap{display:flex;gap:10px;align-items:center}
#mb_login .login-password-wrap .frm_input{flex:1}
#mb_login .login-password-toggle{height:52px;padding:0 14px;border:1px solid #d0d5dd;border-radius:14px;background:#fff;color:#344054;font-weight:700;box-sizing:border-box}
#mb_login .login-submit{width:100%;height:56px;border:0;border-radius:16px;background:linear-gradient(135deg,#b8e986 0%,#7bc96f 100%);color:#fff;font-size:17px;font-weight:800;margin-top:4px;box-sizing:border-box}
#mb_login .login-links{display:flex;justify-content:space-between;gap:12px;margin-top:18px;font-size:14px}
#mb_login .login-links a{color:#475467;text-decoration:none}
@media (max-width:768px){
    #mb_login.login-page{width:100%;max-width:100%;padding:16px 16px 24px}
    #mb_login .login-card{padding:20px 16px;border-radius:20px}
    #mb_login .login-field .frm_input,#mb_login .login-password-toggle,#mb_login .login-submit{height:48px;font-size:15px}
    #mb_login .login-password-wrap{gap:8px}
    #mb_login .login-links{flex-direction:column;align-items:flex-start;gap:8px}
    #mb_login .sns-icon.social_link.sns-kakao{min-height:56px;padding:0 14px}
    #mb_login .sns-icon.social_link.sns-kakao .txt{font-size:14px}
}
@media (max-width:480px){
    #mb_login.login-page{padding:14px}
    #mb_login .login-card{padding:18px 14px;border-radius:20px}
    #mb_login .sns-icon.social_link.sns-kakao{min-height:58px;padding:0 12px}
    #mb_login .sns-icon.social_link.sns-kakao .txt{font-size:13px;line-height:1.2;text-align:center}
}
</style>
