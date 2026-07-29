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
// 사이드바/상단 전체메뉴는 admin.lib.php가 admin.menu*.php 파일들을 글롭으로 읽어
// 채운 실제 $menu 전역을 그대로 사용한다. 이전에는 여기서 하드코딩한 $sf_menus
// 배열을 따로 그려서 그누보드 기본 관리자 메뉴(환경설정/회원관리/게시판관리 등)가
// 화면에 전혀 나오지 않는 문제가 있었다 — $menu 자체는 정상적으로 채워져 있었지만
// 렌더링에서 아예 사용되지 않았다. admin.menu100.php 등 원본 파일의 관례대로
// $menu[$key][0]을 그룹 제목/대표링크로, $menu[$key][1..]을 하위 메뉴로 사용하고,
// print_menu2()와 동일한 권한 검사를 항목 단위로 그대로 적용한다.
$sf_menus_filtered = array();
if (isset($menu) && is_array($menu)) {
    foreach ($menu as $sf_menu_key => $sf_group) {
        if (!isset($sf_group[0])) {
            continue;
        }
        $sf_subs = array();
        for ($i = 1; $i < count($sf_group); $i++) {
            if (!isset($sf_group[$i])) {
                continue;
            }
            $sf_item = $sf_group[$i];
            $sf_auth_code = $sf_item[0];
            if ($is_admin != 'super' && (!array_key_exists($sf_auth_code, $auth) || !strstr($auth[$sf_auth_code], 'r'))) {
                continue;
            }
            $sf_subs[] = array('title' => $sf_item[1], 'href' => $sf_item[2]);
        }
        if (count($sf_subs) > 0) {
            $sf_menus_filtered[$sf_menu_key] = array('title' => $sf_group[0][1], 'subs' => $sf_subs);
        }
    }
}
?>
<header class="sf-admin-header">
    <div class="sf-header-left">
        <button type="button" class="sf-btn-gnb" id="sf-btn-gnb" aria-expanded="false" aria-controls="sf-admin-sidebar" aria-label="메뉴 열기">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div class="sf-header-logo">
            <a href="<?php echo correct_goto_url(G5_ADMIN_URL); ?>"><img src="<?php echo G5_ADMIN_URL ?>/img/logo.png" alt="관리자"></a>
        </div>
    </div>
    <div class="sf-header-right">
        <span class="sf-admin-whoami">
            <?php
            $sf_admin_label = ($is_admin === 'super') ? '최고관리자' : '관리자';
            $sf_admin_name = isset($member['mb_nick']) && $member['mb_nick'] !== '' ? $member['mb_nick'] : (isset($member['mb_id']) ? $member['mb_id'] : '');
            echo get_text($sf_admin_label . '(' . $sf_admin_name . ') 로그인 중');
            ?>
        </span>
        <a href="<?php echo correct_goto_url(G5_ADMIN_URL); ?>" title="관리자 메인" aria-label="관리자 메인"><i class="fa-solid fa-gauge" aria-hidden="true"></i></a>
        <a href="<?php echo G5_URL ?>/" target="_blank" rel="noopener noreferrer" title="홈페이지" aria-label="홈페이지"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
        <a href="<?php echo G5_BBS_URL ?>/logout.php" title="로그아웃" aria-label="로그아웃"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></a>
    </div>
</header>

<div class="sf-sidebar-overlay" id="sf-sidebar-overlay" aria-hidden="true"></div>
<nav class="sf-admin-sidebar" id="sf-admin-sidebar" aria-label="관리자 주메뉴">
    <div class="sf-sidebar-header">
        <h2>관리자 메뉴</h2>
        <button type="button" class="sf-btn-close" id="sf-btn-close" aria-label="메뉴 닫기">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    <ul class="sf-menu-list">
        <?php
        $sf_group_id = 0;
        foreach($sf_menus_filtered as $sf_menu_key => $sf_group_data):
            $sf_group_id++;
            $group_html_id = 'sf-menu-group-' . $sf_group_id;
        ?>
        <li class="sf-menu-group">
            <button type="button" class="sf-menu-btn" aria-expanded="false" aria-controls="<?php echo $group_html_id; ?>">
                <span><?php echo $sf_group_data['title']; ?></span>
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </button>
            <ul class="sf-menu-sub" id="<?php echo $group_html_id; ?>">
                <?php foreach($sf_group_data['subs'] as $sub): ?>
                <li><a href="<?php echo $sub['href']; ?>" class="sf-menu-link"><?php echo $sub['title']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>



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

    // Accordion Menu
    $('.sf-menu-btn').on('click', function() {
        var $btn = $(this);
        var $group = $btn.parent('.sf-menu-group');
        var $sub = $group.find('.sf-menu-sub');
        var isExpanded = $btn.attr('aria-expanded') === 'true';

        $group.toggleClass('active');
        $btn.attr('aria-expanded', !isExpanded);
        $sub.slideToggle(300);
    });

    // Top Sitemap: single expand/collapse toggle (no per-category accordion)
    var SF_SITEMAP_STORAGE_KEY = 'sf_admin_sitemap_expanded';
    var $sitemapToggle = $('#sf-admin-sitemap-toggle');
    var $sitemapBody = $('#sf-admin-sitemap-body');

    function setSitemapExpanded(expanded) {
        if (expanded) {
            $sitemapBody.removeAttr('hidden');
            $sitemapToggle.attr('aria-expanded', 'true');
            $sitemapToggle.find('.sf-sitemap-toggle-text').text('전체 메뉴 닫기');
        } else {
            $sitemapBody.attr('hidden', 'hidden');
            $sitemapToggle.attr('aria-expanded', 'false');
            $sitemapToggle.find('.sf-sitemap-toggle-text').text('전체 메뉴 펼치기');
        }
        try {
            sessionStorage.setItem(SF_SITEMAP_STORAGE_KEY, expanded ? '1' : '0');
        } catch (e) { /* sessionStorage unavailable (private mode etc.) — state just won't persist */ }
    }

    var sfSitemapInitialExpanded = false;
    try {
        sfSitemapInitialExpanded = sessionStorage.getItem(SF_SITEMAP_STORAGE_KEY) === '1';
    } catch (e) { /* default to collapsed */ }
    setSitemapExpanded(sfSitemapInitialExpanded);

    $sitemapToggle.on('click', function() {
        setSitemapExpanded($sitemapToggle.attr('aria-expanded') !== 'true');
    });

    $(document).keyup(function(e) {
        if (e.key === 'Escape' && $sitemapToggle.attr('aria-expanded') === 'true') {
            setSitemapExpanded(false);
            $sitemapToggle.focus();
        }
    });

    // Active Menu Highlighting — uses the anchor element's own (browser-normalized)
    // pathname/search so it works regardless of how each href was authored, and so
    // query-string filter links (e.g. project_list.php?status=draft) compare correctly.
    var currentPath = window.location.pathname;
    var currentSearch = window.location.search;
    var isForm = currentPath.indexOf('_form.php') !== -1;
    var listPath = currentPath.replace('_form.php', '_list.php');

    $('.sf-menu-link, .sf-sitemap-link').each(function() {
        var href = $(this).attr('href');
        if (!href || href === '#') return;

        var linkPath = this.pathname;
        var linkSearch = this.search;

        var isMatch = linkSearch
            ? (linkPath === currentPath && linkSearch === currentSearch)
            : (linkPath === currentPath || (isForm && linkPath === listPath));

        if (isMatch) {
            $(this).addClass('active').attr('aria-current', 'page');
            var $group = $(this).closest('.sf-menu-group');
            if ($group.length) {
                $group.addClass('active');
                $group.find('.sf-menu-btn').attr('aria-expanded', 'true');
                $group.find('.sf-menu-sub').show();
            }

            if ($(this).hasClass('sf-sitemap-link')) {
                var groupTitle = $(this).closest('.sf-sitemap-group').find('.sf-sitemap-group-title').text();
                var subTitle = $(this).find('.sf-sitemap-link-text').text();
                $('#sf-sitemap-current-path').text(' > ' + groupTitle + ' > ' + subTitle);
            }
        }
    });

    // On mobile, if active is found, scroll to it
    if ($('.sf-menu-link.active').length) {
        var top = $('.sf-menu-link.active').offset().top;
        if(top > $(window).height()) {
            $sidebar.animate({ scrollTop: top - 100 }, 300);
        }
    }
});
</script>


<div id="wrapper">

    <div id="container" class="<?php echo $adm_menu_cookie['container']; ?>">

<h1 id="container_title"><?php echo $g5['title'] ?></h1>

<nav class="sf-admin-sitemap" id="sf-admin-sitemap" aria-label="관리자 전체 메뉴">
    <div class="sf-sitemap-bar">
        <div class="sf-sitemap-breadcrumb">
            <span class="sf-sitemap-bar-title">전체 메뉴</span>
            <span class="sf-sitemap-current-path" id="sf-sitemap-current-path"></span>
        </div>
        <button type="button" class="sf-sitemap-toggle" id="sf-admin-sitemap-toggle" aria-expanded="false" aria-controls="sf-admin-sitemap-body">
            <span class="sf-sitemap-toggle-text">전체 메뉴 펼치기</span>
            <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
        </button>
    </div>
    <div class="sf-admin-sitemap-body" id="sf-admin-sitemap-body" hidden>
        <div class="sf-admin-sitemap-grid">
            <?php foreach($sf_menus_filtered as $sf_menu_key => $sf_group_data): ?>
            <div class="sf-sitemap-group">
                <h3 class="sf-sitemap-group-title"><?php echo $sf_group_data['title']; ?></h3>
                <div class="sf-sitemap-links">
                    <?php foreach($sf_group_data['subs'] as $sub): ?>
                        <?php if ($sub['href'] === '#' || $sub['href'] === '') { ?>
                        <span class="sf-sitemap-link sf-sitemap-link-disabled" aria-disabled="true"><?php echo $sub['title']; ?><em>준비 중</em></span>
                        <?php } else { ?>
                        <a href="<?php echo $sub['href']; ?>" class="sf-sitemap-link">
                            <span class="sf-sitemap-link-text"><?php echo $sub['title']; ?></span>
                            <span class="sf-sitemap-url"><?php echo $sub['href']; ?></span>
                        </a>
                        <?php } ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</nav>
        <div class="container_wr">