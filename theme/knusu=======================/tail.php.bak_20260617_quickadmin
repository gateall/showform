<?php
if (!defined('_GNUBOARD_'))
    exit;

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
    include_once(G5_PATH . '/tail.sub.php');
    return;
}

if (G5_IS_MOBILE) {
    include_once(G5_THEME_MOBILE_PATH . '/tail.php');
    return;
}

if (G5_COMMUNITY_USE === false) {
    include_once(G5_THEME_SHOP_PATH . '/shop.tail.php');
    return;
}

$korea_nusu_quick_links = array(
    array(
        'label' => '견적문의',
        'href' => '/',
        'icon' => 'consult',
        'external' => false
    ),
    array('label' => '전화문의', 'href' => 'tel:1844-****', 'icon' => 'phone', 'external' => false),
);

if (!function_exists('korea_nusu_quick_icon_svg')) {
    function korea_nusu_quick_icon_svg($type)
    {
        switch ($type) {
            case 'consult':
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M4 5.5A2.5 2.5 0 0 1 6.5 3h11A2.5 2.5 0 0 1 20 5.5v7A2.5 2.5 0 0 1 17.5 15H10l-4.6 4.1c-.64.57-1.4.11-1.4-.74V15.9A2.49 2.49 0 0 1 4 15V5.5Zm4 2.25a.75.75 0 0 0 0 1.5h8a.75.75 0 0 0 0-1.5H8Zm0 3.5a.75.75 0 0 0 0 1.5h5.5a.75.75 0 0 0 0-1.5H8Z"/></svg>';
            case 'phone':
            default:
                return '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M6.62 10.79a15.05 15.05 0 0 0 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24c1.12.37 2.33.57 3.57.57c.55 0 1 .45 1 1V20c0 .55-.45 1-1 1C10.85 21 3 13.15 3 3c0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1c0 1.24.2 2.45.57 3.57c.11.35.03.74-.25 1.02l-2.2 2.2Z"/></svg>';
        }
    }
}
?>

<hr>

<style>
    #ft {
        background: #f5f6f8;
        border-top: 1px solid #e5e7eb;
        padding: 0
    }

    #ft * {
        box-sizing: border-box
    }

    #ft .kfooter {
        max-width: 1280px;
        margin: 0 auto;
        padding: 22px 20px 18px
    }

    #ft .kfooter-inner {
        display: flex;
        align-items: flex-start;
        gap: 16px
    }

    #ft .kfooter-logo {
        flex: 0 0 136px;
        min-width: 110px
    }

    #ft .kfooter-logo img {
        display: block;
        max-width: 100%;
        height: auto
    }

    #ft .kfooter-text {
        flex: 1;
        min-width: 0
    }

    #ft .kfooter-text {
        text-align: center
    }

    #ft .kfooter-menu {
        display: flex;
        flex-wrap: wrap;
        justify-content: center;
        align-items: center;
        gap: 8px 12px;
        list-style: none;
        padding: 0;
        margin: 0 0 10px;
        font-weight: 700;
        font-size: 13px;
        line-height: 1.4;
        text-align: center
    }

    #ft .kfooter-menu {
        width: 100%
    }

    #ft .kfooter-menu span {
        display: inline-flex;
        align-items: center
    }

    #ft .kfooter-menu a {
        color: #333;
        text-decoration: none;
        white-space: nowrap
    }

    #ft .kfooter-info {
        display: block;
        margin: 0;
        color: #666;
        font-size: 12px;
        line-height: 1.55;
        word-break: keep-all;
        overflow-wrap: anywhere
    }

    #ft .kfooter-info .line {
        display: block;
        margin: 0 0 2px
    }

    #ft .kfooter-copy {
        margin: 6px 0 0;
        color: #777;
        font-size: 12px;
        line-height: 1.4
    }

    .kn-pc-quick {
        display: none
    }

    @media (min-width:681px) {
        .kn-pc-quick {
            display: block;
            position: fixed;
            right: 24px;
            bottom: 96px;
            z-index: 99999
        }

        .kn-pc-quick-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end
        }

        .kn-pc-quick-item {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            gap: 10px;
            min-width: 164px;
            height: 52px;
            padding: 0 16px;
            border-radius: 999px;
            background: rgba(24, 34, 51, .94);
            box-shadow: 0 10px 24px rgba(7, 18, 38, .18);
            color: #fff;
            text-decoration: none;
            font-size: 15px;
            font-weight: 700;
            transition: transform .2s ease, background .2s ease, box-shadow .2s ease;
            backdrop-filter: blur(8px)
        }

        .kn-pc-quick-item:hover {
            transform: translateX(-6px);
            background: #0f57d6;
            color: #fff
        }

        .kn-pc-quick-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 22px;
            height: 22px;
            flex: 0 0 22px
        }

        .kn-pc-quick-icon svg {
            display: block;
            width: 22px;
            height: 22px
        }

        .kn-pc-quick-text {
            display: block;
            line-height: 1;
            white-space: nowrap
        }

        .kn-pc-quick-top {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 52px;
            height: 52px;
            margin: 12px 0 0 auto;
            border: 0;
            border-radius: 50%;
            background: #0b1b36;
            color: #fff;
            font-size: 20px;
            font-weight: 900;
            cursor: pointer;
            box-shadow: 0 10px 24px rgba(7, 18, 38, .22);
            transition: transform .2s ease, background .2s ease
        }

        .kn-pc-quick-top:hover {
            transform: translateY(-4px);
            background: #0f57d6
        }
    }

    @media (max-width:1280px) {
        #ft .kfooter {
            padding: 20px 18px 16px
        }
    }

    @media (max-width:1200px) {
        #ft .kfooter {
            padding: 18px 12px 14px
        }

        #ft .kfooter-inner {
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            gap: 200px
        }

        #ft .kfooter-logo {
            flex: 0 0 120px;
            min-width: 120px;
            max-width: 120px
        }

        #ft .kfooter-text {
            flex: 1;
            min-width: 0;
            max-width: none;
            text-align: left
        }

        #ft .kfooter-menu {
            justify-content: flex-start;
            gap: 6px 10px
        }

        #ft .kfooter-info,
        #ft .kfooter-copy {
            text-align: left
        }
    }

    @media (max-width:991px) {
        #ft .kfooter {
            padding: 18px 16px 14px
        }

        #ft .kfooter-inner {
            display: flex;
            justify-content: flex-start;
            align-items: flex-start;
            gap: 120px
        }

        #ft .kfooter-logo {
            flex: 0 0 110px;
            min-width: 110px;
            max-width: 110px
        }

        #ft .kfooter-text {
            flex: 1;
            min-width: 0;
            max-width: none;
            text-align: left
        }

        #ft .kfooter-menu {
            gap: 6px 10px;
            font-size: 12px
        }

        #ft .kfooter-copy,
        #ft .kfooter-info {
            font-size: 11px;
            line-height: 1.5
        }
    }

    @media (max-width:680px) {
        .kn-pc-quick {
            display: none !important
        }

        #ft .kfooter {
            padding: 14px 12px 10px
        }

        #ft .kfooter-inner {
            display: block
        }

        #ft .kfooter-logo {
            display: block;
            margin: 0 0 8px;
            max-width: 112px
        }

        #ft .kfooter-menu {
            gap: 4px 8px;
            font-size: 11px
        }

        #ft .kfooter-copy,
        #ft .kfooter-info {
            text-align: left
        }

        #ft .kfooter-info {
            font-size: 10px;
            line-height: 1.4;
            white-space: normal;
            word-break: keep-all;
            overflow-wrap: anywhere;
            max-width: 100%
        }

        #ft .kfooter-info .line {
            white-space: normal;
            overflow-wrap: anywhere
        }

        #ft .kfooter-copy {
            font-size: 10px;
            margin-top: 3px
        }
    }

    @media (max-width:480px) {
        #ft .kfooter {
            padding: 12px 10px 8px
        }

        #ft .kfooter-logo {
            max-width: 96px
        }

        #ft .kfooter-menu {
            display: block;
            margin-bottom: 8px
        }

        #ft .kfooter-menu span {
            display: inline
        }

        #ft .kfooter-menu span+span:before {
            content: ' | ';
            color: #a8a8a8
        }

        #ft .kfooter-info {
            font-size: 10px;
            line-height: 1.35;
            white-space: normal;
            overflow-wrap: anywhere;
            word-break: keep-all
        }

        #ft .kfooter-info .line {
            display: block;
            white-space: normal
        }

        #ft .kfooter-copy {
            font-size: 10px;
            margin-top: 2px
        }
    }
</style>

<div id="ft">
    <div class="kfooter">
        <div class="kfooter-inner">
            <div class="kfooter-logo">
                <img src="/img/logo.png" alt="인터넷2424 로고">
            </div>
            <div class="kfooter-text">
                <div class="kfooter-menu">
                    <span><a href="/">HOME</a></span>
                    <span><a href="/content/company.php">회사소개</a></span>
                    <span><a href="/bbs/content.php?co_id=privacy">개인정보처리방침</a></span>
                    <span><a href="/bbs/content.php?co_id=provision">이용약관</a></span>
                    <span><a href="<?php echo G5_ADMIN_URL; ?>/">관리자 바로가기</a></span>

                </div>
                <p class="kfooter-info">
                    <span class="line">회사명 : 인터넷2424 대표 : 홍길동 사업자등록번호 : 000-00-00000</span>
                    <span class="line">주소 : 서울시 **구 **동 대표전화 : 1844-**** 견적문의 : 010-1234-0000</span>
                    <span class="line">이메일 : abc@naver.com 통신판매업신고번호 : 제2026-서울-0000호</span>
                </p>
                <p class="kfooter-copy">Copyright &copy; 인터넷2424 All Rights Reserved.</p>
            </div>
        </div>
    </div>
</div>

<div class="kn-pc-quick" id="knPcQuick" aria-label="빠른 메뉴">
    <div class="kn-pc-quick-list">
        <?php foreach ($korea_nusu_quick_links as $item) { ?>
            <a href="<?php echo $item['href']; ?>" class="kn-pc-quick-item" <?php echo !empty($item['external']) ? ' target="_blank" rel="noopener"' : ''; ?>>
                <span class="kn-pc-quick-icon"><?php echo korea_nusu_quick_icon_svg($item['icon']); ?></span>
                <span class="kn-pc-quick-text"><?php echo $item['label']; ?></span>
            </a>
        <?php } ?>
    </div>
    <button type="button" class="kn-pc-quick-top" id="knPcQuickTop" aria-label="맨 위로 이동">↑</button>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var quickTopBtn = document.getElementById('knPcQuickTop');
        if (quickTopBtn) {
            quickTopBtn.addEventListener('click', function (e) {
                e.preventDefault();
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        }
    });
</script>

<?php
include_once(G5_THEME_PATH . '/tail.sub.php');
