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

        $css_file = str_replace(G5_ADMIN_PATH, G5_ADMIN_URL, $css_file);
        add_stylesheet('<link rel="stylesheet" href="' . $css_file . '">', $k);
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
// Function to find the auth key based on URL from the original $menu arrays
function get_sf_auth_key_by_url($url) {
    global $menu;
    foreach($menu as $key => $sub_menus) {
        foreach($sub_menus as $idx => $m) {
            if ($idx > 0 && isset($m[2]) && $m[2] === $url) {
                return $m[0];
            }
        }
    }
    return null;
}

$sf_menus = array(
    '쇼폼' => array(
        array('title' => '쇼폼 대시보드', 'href' => '#'),
        array('title' => '쇼폼 관리', 'href' => '#'),
        array('title' => '쇼폼 등록', 'href' => '#'),
        array('title' => '신청·문의 관리', 'href' => '#'),
        array('title' => '고객 관리', 'href' => '#'),
        array('title' => '템플릿 관리', 'href' => '#'),
        array('title' => '사용 현황', 'href' => '#'),
        array('title' => '쇼폼 설정', 'href' => '#'),
    ),
    '블로그' => array(
        array('title' => '블로그 대시보드', 'href' => G5_ADMIN_URL.'/blog/project_list.php'),
        array('title' => '광고주 관리', 'href' => G5_ADMIN_URL.'/blog/advertiser_list.php'),
        array('title' => '사이트 관리', 'href' => G5_ADMIN_URL.'/blog/site_list.php'),
        array('title' => '계정 관리', 'href' => '#'),
        array('title' => 'AI 제공자 관리', 'href' => G5_ADMIN_URL.'/blog/ai_provider_list.php'),
        array('title' => '콘텐츠 프로젝트', 'href' => G5_ADMIN_URL.'/blog/project_list.php'),
        array('title' => '키워드 관리', 'href' => '#'),
        array('title' => '포스트 관리', 'href' => '#'),
        array('title' => '발행 대상 관리', 'href' => '#'),
        array('title' => '발행 작업', 'href' => '#'),
        array('title' => '발행 시도', 'href' => '#'),
        array('title' => '활동 로그', 'href' => '#'),
        array('title' => '블로그 설정', 'href' => G5_ADMIN_URL.'/blog/install.php'),
    ),
    '랜딩' => array(
        array('title' => '랜딩 대시보드', 'href' => G5_ADMIN_URL.'/landing/landing_list.php'),
        array('title' => '랜딩페이지 관리', 'href' => G5_ADMIN_URL.'/landing/landing_list.php'),
        array('title' => '랜딩페이지 등록', 'href' => G5_ADMIN_URL.'/landing/landing_form.php'),
        array('title' => '템플릿 관리', 'href' => G5_ADMIN_URL.'/landing/template_list.php'),
        array('title' => '상담·문의 관리', 'href' => G5_ADMIN_URL.'/landing/inquiry_list.php'),
        array('title' => '고객 관리', 'href' => '#'),
        array('title' => '도메인 관리', 'href' => '#'),
        array('title' => '방문·전환 통계', 'href' => G5_ADMIN_URL.'/landing/inquiry_stats.php'),
        array('title' => '랜딩 설정', 'href' => G5_ADMIN_URL.'/landing/ai_setting.php'),
    ),
    'SaaS 관리' => array(
        array('title' => 'SaaS 대시보드', 'href' => '#'),
        array('title' => '고객사 관리', 'href' => '#'),
        array('title' => '요금제 관리', 'href' => '#'),
        array('title' => '구독 관리', 'href' => '#'),
        array('title' => '결제 관리', 'href' => '#'),
        array('title' => '사용량 관리', 'href' => '#'),
        array('title' => '도메인 관리', 'href' => '#'),
        array('title' => '파트너 관리', 'href' => '#'),
        array('title' => 'API 관리', 'href' => '#'),
        array('title' => 'Webhook 관리', 'href' => '#'),
        array('title' => '시스템 로그', 'href' => '#'),
    ),
    '공통 관리' => array(
        array('title' => '통합 고객 관리', 'href' => G5_ADMIN_URL.'/member_list.php'),
        array('title' => '통합 상담 관리', 'href' => '#'),
        array('title' => '관리자 계정', 'href' => G5_ADMIN_URL.'/auth_list.php'),
        array('title' => '역할·권한 관리', 'href' => G5_ADMIN_URL.'/auth_list.php'),
        array('title' => '파일 관리', 'href' => '#'),
        array('title' => '알림 관리', 'href' => '#'),
        array('title' => '공통 설정', 'href' => G5_ADMIN_URL.'/config_form.php'),
    )
);
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
        <a href="<?php echo G5_URL ?>/" target="_blank" title="홈페이지" aria-label="홈페이지"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
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
        foreach($sf_menus as $group_title => $sub_menus): 
            $filtered_subs = array();
            foreach($sub_menus as $sub) {
                if ($sub['href'] === '#') {
                    if ($is_admin === 'super') $filtered_subs[] = $sub;
                    continue;
                }
                $auth_key = get_sf_auth_key_by_url($sub['href']);
                if ($is_admin === 'super' || ($auth_key && isset($auth[$auth_key]) && strpos($auth[$auth_key], 'r') !== false)) {
                    $filtered_subs[] = $sub;
                }
            }
            if (count($filtered_subs) === 0) continue;
            $sf_group_id++;
            $group_html_id = 'sf-menu-group-' . $sf_group_id;
        ?>
        <li class="sf-menu-group">
            <button type="button" class="sf-menu-btn" aria-expanded="false" aria-controls="<?php echo $group_html_id; ?>">
                <span><?php echo $group_title; ?></span>
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </button>
            <ul class="sf-menu-sub" id="<?php echo $group_html_id; ?>">
                <?php foreach($filtered_subs as $sub): ?>
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

    // Active Menu Highlighting
    var currentPath = window.location.pathname;
    var isForm = currentPath.indexOf('_form.php') !== -1;
    var listPath = currentPath.replace('_form.php', '_list.php');

    $('.sf-menu-link').each(function() {
        var href = $(this).attr('href');
        if (!href || href === '#') return;
        
        var hrefPath = href.split('?')[0];

        if (hrefPath === currentPath || (isForm && hrefPath === listPath)) {
            $(this).addClass('active').attr('aria-current', 'page');
            var $group = $(this).closest('.sf-menu-group');
            $group.addClass('active');
            $group.find('.sf-menu-btn').attr('aria-expanded', 'true');
            $group.find('.sf-menu-sub').show();
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

<style>
.sf-admin-shortcut-wrap {
    margin: 30px 20px 20px;
    padding: 18px;
    background: #f8f8f8;
    border: 1px solid #e5e5e5;
}
.sf-admin-shortcut-title {
    margin: 0 0 8px;
    color: #ff0033;
    font-weight: 700;
}
.sf-admin-shortcut-desc {
    margin: 0 0 22px;
    color: #111;
}
.sf-admin-shortcut-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}
.sf-admin-shortcut-card {
    min-height: 230px;
    padding: 22px 18px;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 7px;
}
.sf-admin-shortcut-card h3 {
    margin: 0 0 16px;
    font-size: 20px;
    color: #000;
}
.sf-admin-shortcut-card a {
    display: block;
    margin: 0 0 13px;
    color: #0046cc;
    text-decoration: none;
    font-weight: 700;
}
.sf-admin-shortcut-card a span {
    display: block;
    margin-top: 2px;
    color: #666;
    font-size: 12px;
    font-weight: 400;
}
.sf-admin-shortcut-card a:hover {
    color: #ff0033;
}
@media (max-width: 900px) {
    .sf-admin-shortcut-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="sf-admin-shortcut-wrap">
    <p class="sf-admin-shortcut-title">ShowForm 랜딩 자동화 관리자 바로가기</p>
    <p class="sf-admin-shortcut-desc">
        랜딩페이지 자동 생성, 문의관리, AI 문구 생성 관련 메뉴를 바로 확인할 수 있습니다.
    </p>

    <div class="sf-admin-shortcut-grid">

        <div class="sf-admin-shortcut-card">
            <h3>랜딩관리</h3>

            <a href="/adm/landing/landing_list.php">
                랜딩목록
                <span>/adm/landing/landing_list.php</span>
            </a>

            <a href="/adm/landing/landing_form.php">
                랜딩생성
                <span>/adm/landing/landing_form.php</span>
            </a>

            <a href="/adm/landing/template_list.php">
                템플릿관리
                <span>/adm/landing/template_list.php</span>
            </a>

            <a href="/adm/landing/category_list.php">
                업종관리
                <span>/adm/landing/category_list.php</span>
            </a>
        </div>

        <div class="sf-admin-shortcut-card">
            <h3>문의관리</h3>

            <a href="/adm/landing/inquiry_list.php">
                문의목록
                <span>/adm/landing/inquiry_list.php</span>
            </a>

            <a href="/adm/landing/inquiry_stats.php">
                문의통계
                <span>/adm/landing/inquiry_stats.php</span>
            </a>
        </div>

        <div class="sf-admin-shortcut-card">
            <h3>AI관리</h3>

            <a href="/adm/landing/ai_prompt.php">
                프롬프트관리
                <span>/adm/landing/ai_prompt.php</span>
            </a>

            <a href="/adm/landing/ai_log.php">
                생성로그
                <span>/adm/landing/ai_log.php</span>
            </a>

            <a href="/adm/landing/ai_setting.php">
                API설정
                <span>/adm/landing/ai_setting.php</span>
            </a>
        </div>

    </div>
</div>

        <h1 id="container_title"><?php echo $g5['title'] ?></h1>
        <div class="container_wr">