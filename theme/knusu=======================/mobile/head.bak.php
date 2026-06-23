<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if(G5_COMMUNITY_USE === false) {
    define('G5_IS_COMMUNITY_PAGE', true);
    include_once(G5_THEME_SHOP_PATH.'/shop.head.php');
    return;
}

include_once(G5_THEME_PATH.'/head.sub.php');

if (!function_exists('knu_is_admin_login_request')) {
    function knu_is_admin_login_request()
    {
        $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '';
        if (strpos($script_name, '/bbs/login.php') === false) {
            return false;
        }

        $login_url = isset($_GET['url']) ? (string) $_GET['url'] : '';
        if ($login_url === '' && isset($GLOBALS['url'])) {
            $login_url = (string) $GLOBALS['url'];
        }

        return $login_url !== '' && preg_match('#(^|/)(adm|admin)(/|$)#', urldecode($login_url));
    }
}

if (knu_is_admin_login_request()) {
    return;
}

include_once(G5_LIB_PATH.'/latest.lib.php');
include_once(G5_LIB_PATH.'/outlogin.lib.php');
include_once(G5_LIB_PATH.'/poll.lib.php');
include_once(G5_LIB_PATH.'/visit.lib.php');
include_once(G5_LIB_PATH.'/connect.lib.php');
include_once(G5_LIB_PATH.'/popular.lib.php');
?>
<style>
/* Mobile GNB hard-visibility fix */
#gnb.hd_div{
    display:none;
    position:fixed;
    top:0;
    right:0;
    width:min(360px, 92vw);
    height:100vh;
    overflow-y:auto;
    background:#fff;
    box-shadow:-20px 0 40px rgba(15,23,42,.22);
    z-index:10020;
    padding:14px 0 24px;
}
#gnb.is-open{display:block !important;}
#gnb_1dul{
    margin:8px 0 0;
    padding:0;
    list-style:none;
}
#gnb_1dul > li{
    border-bottom:1px solid #edf1f5;
}
#gnb_1dul .gnb_1da{
    display:block;
    color:#111f33 !important;
    font-size:18px;
    font-weight:700;
    line-height:1.5;
    text-decoration:none;
    padding:14px 18px;
}
#gnb_1dul .gnb_2dul{
    display:none;
    margin:0;
    padding:4px 0 10px 12px;
    list-style:none;
    background:#fff;
}
#gnb_1dul .gnb_2da{
    display:block;
    color:#4b5563 !important;
    font-size:15px;
    font-weight:600;
    line-height:1.5;
    text-decoration:none;
    padding:10px 14px;
}
#gnb_close.hd_closer{
    position:sticky;
    top:0;
    margin-left:auto;
    margin-right:12px;
    width:44px;
    height:44px;
    border:0;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:rgba(0,0,0,.72);
    color:#fff;
    z-index:5;
}
#gnb_close.hd_closer i{font-size:20px;line-height:1;}
#gnb_open.hd_opener{
    width:46px;
    height:46px;
    border:1px solid #d6dde5;
    border-radius:11px;
    background:#fff;
    box-shadow:0 4px 12px rgba(16,23,34,.08);
}
#gnb_open.hd_opener i{font-size:22px;color:#16202b;}
</style>

<header id="hd">
    <h1 id="hd_h1"><?php echo $g5['title'] ?></h1>

    <div class="to_content"><a href="#container">본문 바로가기</a></div>

    <?php
    if(defined('_INDEX_')) { // index에서만 실행
        include G5_MOBILE_PATH.'/newwin.inc.php'; // 팝업레이어
    } ?>

    <div id="hd_wrapper">

        <div id="logo">
            <a href="<?php echo G5_URL ?>"><img src="<?php echo G5_IMG_URL ?>/m_logo.png" alt="인터넷2424"></a>
        </div>

        <button type="button" id="gnb_open" class="hd_opener"><i class="fa fa-bars" aria-hidden="true"></i><span class="sound_only"> 메뉴열기</span></button>

        <div id="gnb" class="hd_div">
            <button type="button" id="gnb_close" class="hd_closer"><span class="sound_only">메뉴 닫기</span><i class="fa fa-times" aria-hidden="true"></i></button>
            <!-- <?php echo outlogin('theme/basic'); // 회원정보 박스 출력 - 제거함 ?> -->
            <ul id="gnb_1dul">
            <?php
            $menu_datas = get_menu_db(1, true);
			$i = 0;
			foreach( $menu_datas as $row ){
				if( empty($row) ) continue;
            ?>
                <li class="gnb_1dli">
                    <a href="<?php echo $row['me_link']; ?>" target="_<?php echo $row['me_target']; ?>" class="gnb_1da"><?php echo $row['me_name'] ?></a>
                    <?php
                    $k = 0;
                    foreach( (array) $row['sub'] as $row2 ){
						if( empty($row2) ) continue;
                        if($k == 0)
                            echo '<button type="button" class="btn_gnb_op"><span class="sound_only">하위분류</span></button><ul class="gnb_2dul">'.PHP_EOL;
                    ?>
                        <li class="gnb_2dli"><a href="<?php echo $row2['me_link']; ?>" target="_<?php echo $row2['me_target']; ?>" class="gnb_2da"><span></span><?php echo $row2['me_name'] ?></a></li>
                    <?php
					$k++;
                    }	//end foreach $row2

                    if($k > 0)
                        echo '</ul>'.PHP_EOL;
                    ?>
                </li>
            <?php
			$i++;
            }	//end foreach $row

            if ($i == 0) {  ?>
                <li id="gnb_empty">메뉴 준비 중입니다.<?php if ($is_admin) { ?> <br><a href="<?php echo G5_ADMIN_URL; ?>/menu_list.php">관리자모드 &gt; 환경설정 &gt; 메뉴설정</a>에서 설정하세요.<?php } ?></li>
            <?php } ?>
            <?php if (defined('G5_USE_SHOP') && G5_USE_SHOP) { ?>
                <li class="gnb_1dli"><a href="<?php echo G5_SHOP_URL ?>" class="gnb_1da"> 쇼핑몰</a></li>
            <?php } ?>
            </ul>

            <ul id="hd_nb">
            	<li class="hd_nb1"><a href="<?php echo G5_BBS_URL ?>/faq.php" id="snb_faq"><i class="fa fa-question" aria-hidden="true"></i>FAQ</a></li>
                <li class="hd_nb2"><a href="<?php echo G5_BBS_URL ?>/qalist.php" id="snb_qa"><i class="fa fa-comments" aria-hidden="true"></i>1:1문의</a></li>
                <li class="hd_nb3"><a href="<?php echo G5_BBS_URL ?>/current_connect.php" id="snb_cnt"><i class="fa fa-users" aria-hidden="true"></i>접속자 <span><?php echo connect('theme/basic'); // 현재 접속자수 ?></span></a></li>
                <li class="hd_nb4"><a href="<?php echo G5_BBS_URL ?>/new.php" id="snb_new"><i class="fa fa-history" aria-hidden="true"></i>새글</a></li>   
            </ul>
        </div>

        <button type="button" id="user_btn" class="hd_opener"><i class="fa fa-search" aria-hidden="true"></i><span class="sound_only">사용자메뉴</span></button>
        <div class="hd_div" id="user_menu">
            <button type="button" id="user_close" class="hd_closer"><span class="sound_only">메뉴 닫기</span><i class="fa fa-times" aria-hidden="true"></i></button>
            <div id="hd_sch">
                <h2>사이트 내 전체검색</h2>
                <form name="fsearchbox" action="<?php echo G5_BBS_URL ?>/search.php" onsubmit="return fsearchbox_submit(this);" method="get">
                <input type="hidden" name="sfl" value="wr_subject||wr_content">
                <input type="hidden" name="sop" value="and">
                <input type="text" name="stx" id="sch_stx" placeholder="검색어를 입력해주세요" required maxlength="20">
                <button type="submit" value="검색" id="sch_submit"><i class="fa fa-search" aria-hidden="true"></i><span class="sound_only">검색</span></button>
                </form>

                <script>
                function fsearchbox_submit(f)
                {
                    var stx = f.stx.value.trim();
                    if (stx.length < 2) {
                        alert("검색어는 두글자 이상 입력하십시오.");
                        f.stx.select();
                        f.stx.focus();
                        return false;
                    }

                    // 검색에 많은 부하가 걸리는 경우 이 주석을 제거하세요.
                    var cnt = 0;
                    for (var i = 0; i < stx.length; i++) {
                        if (stx.charAt(i) == ' ')
                            cnt++;
                    }

                    if (cnt > 1) {
                        alert("빠른 검색을 위하여 검색어에 공백은 한개만 입력할 수 있습니다.");
                        f.stx.select();
                        f.stx.focus();
                        return false;
                    }
                    f.stx.value = stx;

                    return true;
                }
                </script>
            </div>
            <?php echo popular('theme/basic'); // 인기검색어 ?>
            <div id="text_size">
            <!-- font_resize('엘리먼트id', '제거할 class', '추가할 class'); -->
                <button id="size_down" onclick="font_resize('container', 'ts_up ts_up2', '', this);" class="select"><img src="<?php echo G5_URL; ?>/img/ts01.png" width="20" alt="기본"></button>
                <button id="size_def" onclick="font_resize('container', 'ts_up ts_up2', 'ts_up', this);"><img src="<?php echo G5_URL; ?>/img/ts02.png" width="20" alt="크게"></button>
                <button id="size_up" onclick="font_resize('container', 'ts_up ts_up2', 'ts_up2', this);"><img src="<?php echo G5_URL; ?>/img/ts03.png" width="20" alt="더크게"></button>
            </div>
        </div>

        <script>
        $(function () {
            //폰트 크기 조정 위치 지정
            var font_resize_class = get_cookie("ck_font_resize_add_class");
            if( font_resize_class == 'ts_up' ){
                $("#text_size button").removeClass("select");
                $("#size_def").addClass("select");
            } else if (font_resize_class == 'ts_up2') {
                $("#text_size button").removeClass("select");
                $("#size_up").addClass("select");
            }

            $(".hd_opener").on("click", function() {
                var $this = $(this);
                var $hd_layer = $this.next(".hd_div");

                if($hd_layer.is(":visible")) {
                    $hd_layer.hide();
                    $this.find("span").text("열기");
                } else {
                    var $hd_layer2 = $(".hd_div:visible");
                    $hd_layer2.prev(".hd_opener").find("span").text("열기");
                    $hd_layer2.hide();

                    $hd_layer.show();
                    $this.find("span").text("닫기");
                }
            });

            $("#container").on("click", function() {
                $(".hd_div").hide();

            });

            $(".btn_gnb_op").click(function(){
                $(this).toggleClass("btn_gnb_cl").next(".gnb_2dul").slideToggle(300);
                
            });

            $(".hd_closer").on("click", function() {
                var idx = $(".hd_closer").index($(this));
                $(".hd_div:visible").hide();
                $(".hd_opener:eq("+idx+")").find("span").text("열기");
            });
        });
        </script>

        <script>
        (function mobileGnbFallback(){
            if (window.__mobileGnbFallbackBound) return;
            window.__mobileGnbFallbackBound = true;

            function ready(fn){
                if(document.readyState !== 'loading') fn();
                else document.addEventListener('DOMContentLoaded', fn);
            }

            ready(function(){
                var openBtn = document.getElementById('gnb_open');
                var closeBtn = document.getElementById('gnb_close');
                var gnb = document.getElementById('gnb');
                var container = document.getElementById('container');

                if(!openBtn || !gnb) return;

                function isOpen(){
                    return gnb.classList.contains('is-open') || gnb.style.display === 'block';
                }

                function openMenu(){
                    gnb.classList.add('is-open');
                    gnb.style.display = 'block';
                    gnb.style.visibility = 'visible';
                    gnb.style.opacity = '1';
                    openBtn.setAttribute('aria-expanded','true');
                }

                function closeMenu(){
                    gnb.classList.remove('is-open');
                    gnb.style.display = 'none';
                    gnb.style.visibility = '';
                    gnb.style.opacity = '';
                    openBtn.setAttribute('aria-expanded','false');
                }

                closeMenu();

                openBtn.addEventListener('click', function(e){
                    e.preventDefault();
                    isOpen() ? closeMenu() : openMenu();
                });

                if(closeBtn){
                    closeBtn.addEventListener('click', function(e){
                        e.preventDefault();
                        closeMenu();
                    });
                }

                if(container){
                    container.addEventListener('click', function(){
                        closeMenu();
                    });
                }
            });
        })();
        </script>
        
    </div>
</header>



<div id="wrapper">

    <div id="container">
    <?php if (!defined("_INDEX_")) { ?>
    	<h2 id="container_title" class="top" title="<?php echo get_text($g5['title']); ?>">
    		<a href="javascript:history.back();"><i class="fa fa-chevron-left" aria-hidden="true"></i><span class="sound_only">뒤로가기</span></a> <?php echo get_head_title($g5['title']); ?>
    	</h2>
    <?php }
