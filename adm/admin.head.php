<?php
if (!defined('_GNUBOARD_')) {
    exit;
}

$g5_debug['php']['begin_time'] = $begin_time = get_microtime();

$files = glob(G5_ADMIN_PATH . '/css/admin_extend_*');
if (is_array($files)) {
    foreach ((array) $files as $k => $css_file) {

        $fileinfo = pathinfo($css_file);
        $ext = $fileinfo['extension'];

        if ($ext !== 'css') {
            continue;
        }

        $css_file_url = str_replace(G5_ADMIN_PATH, G5_ADMIN_URL, $css_file);
        $css_ver = filemtime($css_file);
        add_stylesheet('<link rel="stylesheet" href="' . $css_file_url . '?ver=' . $css_ver . '">', $k);
    }
}

require_once G5_PATH . '/head.sub.php';

function print_menu1($key, $no = '')
{
    global $menu;

    $str = print_menu2($key, $no);

    return $str;
}

function print_menu2($key, $no = '')
{
    global $menu, $auth_menu, $is_admin, $auth, $g5, $sub_menu;

    $str = "<ul>";
    for ($i = 1; $i < count($menu[$key]); $i++) {
        if (!isset($menu[$key][$i])) {
            continue;
        }

        if ($is_admin != 'super' && (!array_key_exists($menu[$key][$i][0], $auth) || !strstr($auth[$menu[$key][$i][0]], 'r'))) {
            continue;
        }

        $gnb_grp_div = $gnb_grp_style = '';

        if (isset($menu[$key][$i][4])) {
            if (($menu[$key][$i][4] == 1 && $gnb_grp_style == false) || ($menu[$key][$i][4] != 1 && $gnb_grp_style == true)) {
                $gnb_grp_div = 'gnb_grp_div';
            }

            if ($menu[$key][$i][4] == 1) {
                $gnb_grp_style = 'gnb_grp_style';
            }
        }

        $current_class = '';

        if ($menu[$key][$i][0] == $sub_menu) {
            $current_class = ' on';
        }

        $str .= '<li data-menu="' . $menu[$key][$i][0] . '"><a href="' . $menu[$key][$i][2] . '" class="gnb_2da ' . $gnb_grp_style . ' ' . $gnb_grp_div . $current_class . '">' . $menu[$key][$i][1] . '</a></li>';

        $auth_menu[$menu[$key][$i][0]] = $menu[$key][$i][1];
    }
    $str .= "</ul>";

    return $str;
}

$adm_menu_cookie = array(
    'container' => '',
    'gnb'       => '',
    'btn_gnb'   => '',
);

if (!empty($_COOKIE['g5_admin_btn_gnb'])) {
    $adm_menu_cookie['container'] = 'container-small';
    $adm_menu_cookie['gnb'] = 'gnb_small';
    $adm_menu_cookie['btn_gnb'] = 'btn_gnb_open';
}

// 신규 업무(쇼폼/블로그 자동화/랜딩페이지)는 네이티브 관리자 메뉴(#gnb, 아래)와
// 완전히 분리된 별도 상단 바로 그린다 — 네이티브 메뉴는 그누보드 원본 구조 그대로
// 유지하고, 그 안에 신규 메뉴가 섞여 들어가지 않도록 $amenu를 core 그룹만 남도록 거른다.
require_once __DIR__ . '/inc/admin_menu_build.php';

$sf_menu_all = isset($menu) && is_array($menu) ? $menu : array();
$sf_auth_all = isset($auth) && is_array($auth) ? $auth : array();
$sf_amenu_all = isset($amenu) && is_array($amenu) ? $amenu : array();

$sf_area_map = bp_sf_admin_area_map();
$sf_core_amenu = bp_sf_filter_core_amenu($sf_amenu_all, $sf_area_map);

// auth_list.php(최고관리자 권한배정 화면)가 신규 업무(쇼폼/랜딩/블로그) 메뉴 코드도
// 선택할 수 있도록, 화면에는 출력하지 않고 $auth_menu만 채우는 목적으로 content
// 그룹에 대해서도 print_menu2()를 호출한다. 반환된 HTML은 버리므로 $sf_core_amenu
// 기반의 네이티브 #gnb 렌더링(아래)이나 .sf-vswitch에는 전혀 영향이 없다.
foreach ($sf_amenu_all as $sf_key => $sf_file) {
    $sf_area = isset($sf_area_map['menu' . $sf_key]) ? $sf_area_map['menu' . $sf_key] : 'core';
    if ($sf_area === 'content' && isset($menu['menu' . $sf_key])) {
        print_menu2('menu' . $sf_key);
    }
}

$sf_content_verticals = bp_sf_build_content_verticals($sf_menu_all, $sf_auth_all, $is_admin);
$sf_current_vertical = bp_sf_resolve_current_vertical($sf_menu_all, isset($sub_menu) ? $sub_menu : null);
?>

<script>
    var g5_admin_csrf_token_key = "<?php echo (function_exists('admin_csrf_token_key')) ? admin_csrf_token_key() : ''; ?>";
    var tempX = 0;
    var tempY = 0;

    function imageview(id, w, h) {

        menu(id);

        var el_id = document.getElementById(id);

        //submenu = eval(name+".style");
        submenu = el_id.style;
        submenu.left = tempX - (w + 11);
        submenu.top = tempY - (h / 2);

        selectBoxVisible();

        if (el_id.style.display != 'none')
            selectBoxHidden(id);
    }
</script>

<div id="to_content"><a href="#container">본문 바로가기</a></div>

<header id="hd">
    <h1><?php echo $config['cf_title'] ?></h1>
    <div id="hd_top">
        <button type="button" id="btn_gnb" class="btn_gnb_close <?php echo $adm_menu_cookie['btn_gnb']; ?>">메뉴</button>
        <div id="logo"><a href="<?php echo correct_goto_url(G5_ADMIN_URL); ?>"><img src="<?php echo G5_ADMIN_URL ?>/img/logo.png" alt="<?php echo get_text($config['cf_title']); ?> 관리자"></a></div>

        <div id="tnb">
            <ul>
                <?php if (defined('G5_USE_SHOP') && G5_USE_SHOP) { ?>
                    <li class="tnb_li"><a href="<?php echo G5_SHOP_URL ?>/" class="tnb_shop" target="_blank" title="쇼핑몰 바로가기">쇼핑몰 바로가기</a></li>
                <?php } ?>
                <li class="tnb_li"><a href="<?php echo G5_URL ?>/" class="tnb_community" target="_blank" title="커뮤니티 바로가기">커뮤니티 바로가기</a></li>
                <li class="tnb_li"><a href="<?php echo G5_ADMIN_URL ?>/service.php" class="tnb_service">부가서비스</a></li>
                <li class="tnb_li"><button type="button" class="tnb_mb_btn">관리자<span class="./img/btn_gnb.png">메뉴열기</span></button>
                    <ul class="tnb_mb_area">
                        <li><a href="<?php echo G5_ADMIN_URL ?>/member_form.php?w=u&amp;mb_id=<?php echo $member['mb_id'] ?>">관리자정보</a></li>
                        <li id="tnb_logout"><a href="<?php echo G5_BBS_URL ?>/logout.php">로그아웃</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <nav id="gnb" class="gnb_large <?php echo $adm_menu_cookie['gnb']; ?>">
        <h2>관리자 주메뉴</h2>
        <ul class="gnb_ul">
            <?php
            $jj = 1;
            foreach ($sf_core_amenu as $key => $value) {
                $href1 = $href2 = '';

                if (isset($menu['menu' . $key][0][2]) && $menu['menu' . $key][0][2]) {
                    $href1 = '<a href="' . $menu['menu' . $key][0][2] . '" class="gnb_1da">';
                    $href2 = '</a>';
                } else {
                    continue;
                }

                $current_class = "";
                if (isset($sub_menu) && (substr($sub_menu, 0, 3) == substr($menu['menu' . $key][0][0], 0, 3))) {
                    $current_class = " on";
                }

                $button_title = $menu['menu' . $key][0][1];
            ?>
                <li class="gnb_li<?php echo $current_class; ?>">
                    <button type="button" class="btn_op menu-<?php echo $key; ?> menu-order-<?php echo $jj; ?>" title="<?php echo $button_title; ?>"><?php echo $button_title; ?></button>
                    <div class="gnb_oparea_wr">
                        <div class="gnb_oparea">
                            <h3><?php echo $menu['menu' . $key][0][1]; ?></h3>
                            <?php echo print_menu1('menu' . $key, 1); ?>
                        </div>
                    </div>
                </li>
            <?php
                $jj++;
            }     //end foreach
            ?>
        </ul>
    </nav>

</header>
<script>
    jQuery(function($) {

        var menu_cookie_key = 'g5_admin_btn_gnb';

        $(".tnb_mb_btn").click(function() {
            $(".tnb_mb_area").toggle();
        });

        $("#btn_gnb").click(function() {

            var $this = $(this);

            try {
                if (!$this.hasClass("btn_gnb_open")) {
                    set_cookie(menu_cookie_key, 1, 60 * 60 * 24 * 365);
                } else {
                    delete_cookie(menu_cookie_key);
                }
            } catch (err) {}

            $("#container").toggleClass("container-small");
            $("#gnb").toggleClass("gnb_small");
            $this.toggleClass("btn_gnb_open");

        });

        $(".gnb_ul li .btn_op").click(function() {
            $(this).parent().addClass("on").siblings().removeClass("on");
        });

    });
</script>


<div id="wrapper">

    <div id="container" class="<?php echo $adm_menu_cookie['container']; ?>">

        <?php require __DIR__ . '/inc/admin_content_switch.php'; ?>

        <h1 id="container_title"><?php echo $g5['title'] ?></h1>
        <div class="container_wr">
