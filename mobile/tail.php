<?php
if (!defined('_GNUBOARD_')) exit;

if (defined('G5_THEME_MOBILE_PATH') && is_file(G5_THEME_MOBILE_PATH.'/tail.php')) {
    require_once(G5_THEME_MOBILE_PATH.'/tail.php');
    return;
}

if (defined('G5_THEME_PATH') && is_file(G5_THEME_PATH.'/mobile/tail.php')) {
    require_once(G5_THEME_PATH.'/mobile/tail.php');
    return;
}
?>
    </div>
</div>

<?php echo poll('basic'); ?>
<?php echo visit('basic'); ?>

<div id="ft">
    <div id="ft_copy">
        <div id="ft_company">
            <a href="<?php echo get_pretty_url('content', 'company'); ?>">회사소개</a>
            <a href="<?php echo get_pretty_url('content', 'privacy'); ?>">개인정보처리방침</a>
            <a href="<?php echo get_pretty_url('content', 'provision'); ?>">서비스이용약관</a>
        </div>
        Copyright &copy; <b>코리아누수</b> All rights reserved.<br>
    </div>

    <div class="ft_cnt">
        <h2>사이트 정보</h2>
        <p class="ft_info">
            상호명 : 코리아누수 / 대표 : 박태환<br>
            주소 : 대구광역시 수성구 달구벌대로 79-1 3층<br>
            사업자등록번호 : 387-10-02850<br>
            전화 : 010-2169-8148<br>
            개인정보책임자 : 박태환<br>
        </p>
    </div>

    <button type="button" id="top_btn"><i class="fa fa-arrow-up" aria-hidden="true"></i><span class="sound_only">상단으로</span></button>
    <?php if (G5_DEVICE_BUTTON_DISPLAY && G5_IS_MOBILE) { ?>
    <a href="<?php echo get_device_change_url(); ?>" id="device_change">PC 버전으로 보기</a>
    <?php } ?>
</div>

<script>
jQuery(function($) {
    $(document).ready(function() {
        font_resize('container', get_cookie('ck_font_resize_rmv_class'), get_cookie('ck_font_resize_add_class'));

        if ($('.top').length) {
            var jbOffset = $('.top').offset();
            $(window).scroll(function() {
                if ($(document).scrollTop() > jbOffset.top) {
                    $('.top').addClass('fixed');
                } else {
                    $('.top').removeClass('fixed');
                }
            });
        }

        $('#top_btn').on('click', function() {
            $('html, body').animate({scrollTop:0}, '500');
            return false;
        });
    });
});
</script>

<?php
include_once(G5_PATH.'/tail.sub.php');
