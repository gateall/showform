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

<?php
// 관리자 메뉴를 "기본 관리자"(그누보드/영카트 원본)와 "쇼폼·콘텐츠 운영"(신규 업무)
// 두 영역으로 완전히 분리해 그린다. 실제 $menu(admin.lib.php가 admin.menu*.php를
// 글롭으로 읽어 채운 원본)를 그대로 쓰고, 어느 그룹이 어느 영역인지는
// adm/inc/admin_area_map.php의 명시적 설정으로만 판단한다(코드 숫자 추측 금지).
require_once __DIR__ . '/inc/admin_menu_build.php';

$sf_menu = isset($menu) && is_array($menu) ? $menu : array();
$sf_auth = isset($auth) && is_array($auth) ? $auth : array();
$sf_built = bp_sf_build_menus($sf_menu, $sf_auth, $is_admin);
$sf_core_menus = $sf_built['core'];
$sf_content_buckets = $sf_built['content'];
$sf_current_area = bp_sf_resolve_current_area($sf_menu, bp_sf_admin_area_map(), isset($sub_menu) ? $sub_menu : null);
$sf_content_dashboard_url = bp_sf_content_dashboard_url($sf_menu);

require __DIR__ . '/inc/admin_area_nav.php';
require __DIR__ . '/inc/admin_drawer_menu.php';
?>

<script>
jQuery(function($) {
    var $btnGnb = $('#sf-btn-gnb');
    var $sidebar = $('#sf-admin-sidebar');
    var $overlay = $('#sf-sidebar-overlay');
    var $body = $('body');

    function closeSidebar() {
        $sidebar.removeClass('active');
        $overlay.removeClass('active');
        $body.css('overflow', '');
        $btnGnb.attr('aria-expanded', 'false');
        $btnGnb.focus();
    }

    function openSidebar() {
        $sidebar.addClass('active');
        $overlay.addClass('active');
        $body.css('overflow', 'hidden');
        $btnGnb.attr('aria-expanded', 'true');
    }

    $btnGnb.on('click', function() {
        if ($sidebar.hasClass('active')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    $('#sf-btn-close, #sf-sidebar-overlay').on('click', function() {
        closeSidebar();
    });

    $(document).keyup(function(e) {
        if (e.key === "Escape" && $sidebar.hasClass('active')) {
            closeSidebar();
        }
    });

    // 드로어 아코디언 — 한 번에 하나의 1차 메뉴만 펼침(§7 모바일 요구사항)
    $('.sf-menu-btn').on('click', function() {
        var $btn = $(this);
        var $group = $btn.parent('.sf-menu-group');
        var $sub = $group.find('.sf-menu-sub');
        var isExpanded = $btn.attr('aria-expanded') === 'true';

        $('.sf-menu-group').not($group).removeClass('active')
            .find('.sf-menu-btn').attr('aria-expanded', 'false');
        $('.sf-menu-group').not($group).find('.sf-menu-sub').slideUp(200);

        $group.toggleClass('active', !isExpanded);
        $btn.attr('aria-expanded', !isExpanded);
        $sub.slideToggle(200);
    });

    // 드로어 메뉴 링크 클릭 후 자동 닫힘(§7)
    $('.sf-menu-link').on('click', function() {
        closeSidebar();
    });

    // PC 쇼폼·콘텐츠 운영 가로 메뉴 — 클릭으로 열고닫기(호버 전용 금지, 터치 접근성)
    $('.sf-content-nav-btn').on('click', function(e) {
        e.stopPropagation();
        var $btn = $(this);
        var $item = $btn.closest('.sf-content-nav-item');
        var isExpanded = $btn.attr('aria-expanded') === 'true';

        $('.sf-content-nav-item').not($item).removeClass('active')
            .find('.sf-content-nav-btn').attr('aria-expanded', 'false');

        $item.toggleClass('active', !isExpanded);
        $btn.attr('aria-expanded', !isExpanded);
    });

    $(document).on('click', function() {
        $('.sf-content-nav-item').removeClass('active')
            .find('.sf-content-nav-btn').attr('aria-expanded', 'false');
    });

    $(document).keyup(function(e) {
        if (e.key === 'Escape') {
            $('.sf-content-nav-item').removeClass('active')
                .find('.sf-content-nav-btn').attr('aria-expanded', 'false');
        }
    });

    // 현재 위치 강조 — 링크 자체의(브라우저가 정규화한) pathname/search로 비교하므로
    // href 작성 방식이나 쿼리스트링 필터 링크(?status=draft 등)에도 안정적으로 동작한다.
    var currentPath = window.location.pathname;
    var currentSearch = window.location.search;
    var isForm = currentPath.indexOf('_form.php') !== -1;
    var listPath = currentPath.replace('_form.php', '_list.php');

    $('.sf-menu-link, .sf-content-nav-link').each(function() {
        var href = $(this).attr('href');
        if (!href || href === '#') return;

        var linkPath = this.pathname;
        var linkSearch = this.search;

        var isMatch = linkSearch
            ? (linkPath === currentPath && linkSearch === currentSearch)
            : (linkPath === currentPath || (isForm && linkPath === listPath));

        if (isMatch) {
            $(this).addClass('active').attr('aria-current', 'page');

            var $drawerGroup = $(this).closest('.sf-menu-group');
            if ($drawerGroup.length) {
                $drawerGroup.addClass('active');
                $drawerGroup.find('.sf-menu-btn').attr('aria-expanded', 'true');
                $drawerGroup.find('.sf-menu-sub').show();
            }

            var $navItem = $(this).closest('.sf-content-nav-item');
            if ($navItem.length) {
                $navItem.find('.sf-content-nav-btn').addClass('sf-current-bucket');
            }
        }
    });

    if ($('.sf-menu-link.active').length) {
        var top = $('.sf-menu-link.active').offset().top;
        if (top > $(window).height()) {
            $sidebar.animate({ scrollTop: top - 100 }, 300);
        }
    }
});
</script>

<div id="wrapper" class="sf-area-<?php echo $sf_current_area; ?>">

    <div id="container" class="<?php echo $adm_menu_cookie['container']; ?>">

<h1 id="container_title"><?php echo $g5['title'] ?></h1>

<?php if ($sf_current_area === 'content') {
    require __DIR__ . '/inc/admin_content_nav.php';
} ?>
        <div class="container_wr">
