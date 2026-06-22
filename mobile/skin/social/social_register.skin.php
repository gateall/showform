<?php
if (!defined('_GNUBOARD_')) exit; // 媛쒕퀎 ?섏씠吏 ?묎렐 遺덇?

if( ! $config['cf_social_login_use']) {     //?뚯뀥 濡쒓렇?몄쓣 ?ъ슜?섏? ?딆쑝硫?
    return;
}

$social_pop_once = false;

$self_url = G5_BBS_URL."/login.php";

//?덉갹???ъ슜?쒕떎硫?
if( G5_SOCIAL_USE_POPUP ) {
    $self_url = G5_SOCIAL_LOGIN_URL.'/popup.php';
}

// add_stylesheet('css 援щЦ', 異쒕젰?쒖꽌); ?レ옄媛 ?묒쓣 ?섎줉 癒쇱? 異쒕젰??
add_stylesheet('<link rel="stylesheet" href="'.get_social_skin_url().'/style.css?ver='.G5_CSS_VER.'">', 10);
?>
<div>
    <div class="login-sns sns-wrap-32 sns-wrap-over" id="sns_register">
        <h2>SNS 怨꾩젙?쇰줈 媛??/h2>
        <div class="sns-wrap">
            <?php if( social_service_check('naver') ) {     //?ㅼ씠踰?濡쒓렇?몄쓣 ?ъ슜?쒕떎硫??>
            <a href="<?php echo $self_url;?>?provider=naver&amp;url=<?php echo $urlencode;?>" class="sns-icon social_link sns-naver" title="?ㅼ씠踰?>
                <span class="ico"></span>
                <span class="txt">?ㅼ씠踰?i> 濡쒓렇??/i></span>
            </a>
            <?php }     //end if ?>
            <?php if( social_service_check('kakao') ) {     //移댁뭅??濡쒓렇?몄쓣 ?ъ슜?쒕떎硫??>
            <a href="<?php echo G5_PLUGIN_URL;?>/social/popup.php?provider=kakao&amp;url=<?php echo $urlencode;?>&amp;redirect_to_idp=1" class="sns-icon social_link sns-kakao" title="移댁뭅??>
                <span class="ico"></span>
                <span class="txt">移댁뭅??i> 濡쒓렇??/i></span>
            </a>
            <?php }     //end if ?>
            <?php if( social_service_check('facebook') ) {     //?섏씠?ㅻ턿 濡쒓렇?몄쓣 ?ъ슜?쒕떎硫??>
            <a href="<?php echo $self_url;?>?provider=facebook&amp;url=<?php echo $urlencode;?>" class="sns-icon social_link sns-facebook" title="?섏씠?ㅻ턿">
                <span class="ico"></span>
                <span class="txt">?섏씠?ㅻ턿<i> 濡쒓렇??/i></span>
            </a>
            <?php }     //end if ?>
            <?php if( social_service_check('google') ) {     //援ш? 濡쒓렇?몄쓣 ?ъ슜?쒕떎硫??>
            <a href="<?php echo $self_url;?>?provider=google&amp;url=<?php echo $urlencode;?>" class="sns-icon social_link sns-google" title="援ш?">
                <span class="ico"></span>
                <span class="txt">援ш?<i> 濡쒓렇??/i></span>
            </a>
            <?php }     //end if ?>
            <?php if( social_service_check('twitter') ) {     //?몄쐞??濡쒓렇?몄쓣 ?ъ슜?쒕떎硫??>
            <a href="<?php echo $self_url;?>?provider=twitter&amp;url=<?php echo $urlencode;?>" class="sns-icon social_link sns-twitter" title="?몄쐞??>
                <span class="ico"></span>
                <span class="txt">?몄쐞??i> ?몄쐞??/i></span>
            </a>
            <?php }     //end if ?>
            <?php if( social_service_check('payco') ) {     //?섏씠肄?濡쒓렇?몄쓣 ?ъ슜?쒕떎硫??>
            <a href="<?php echo $self_url;?>?provider=payco&amp;url=<?php echo $urlencode;?>" class="sns-icon social_link sns-payco" title="?섏씠肄?>
                <span class="ico"></span>
                <span class="txt">?섏씠肄?i> 濡쒓렇??/i></span>
            </a>
            <?php }     //end if ?>

            <?php if( G5_SOCIAL_USE_POPUP && !$social_pop_once ){
            $social_pop_once = true;
            ?>
            <script>
                jQuery(function($){
                    $(".sns-wrap").on("click", "a.social_link", function(e){
                        e.preventDefault();

                        var pop_url = $(this).attr("href");
                        var newWin = window.open(
                            pop_url, 
                            "social_sing_on", 
                            "location=0,status=0,scrollbars=1,width=600,height=500"
                        );

                        if(!newWin || newWin.closed || typeof newWin.closed=='undefined')
                             alert('釉뚮씪?곗??먯꽌 ?앹뾽??李⑤떒?섏뼱 ?덉뒿?덈떎. ?앹뾽 ?쒖꽦?????ㅼ떆 ?쒕룄??二쇱꽭??');

                        return false;
                    });
                });
            </script>
            <?php } ?>

        </div>
    </div>
</div>
