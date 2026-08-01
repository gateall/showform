<?php
// 이 파일은 manager 페이지가 include하는 공통 레이아웃 오프너다.
// 페이지는 include 전에 $page_title, $sf_current_menu(선택)을 지정해야 한다.
if (!defined('_GNUBOARD_')) exit;

$page_title = isset($page_title) ? $page_title : '쇼폼 통합관리';
$site_title = get_text($config['cf_title']);
$mgr_admin_name = $member['mb_nick'] ? $member['mb_nick'] : $member['mb_name'];

// $manager_menu / $manager_menu_prefix는 _common.php가 manager/config/menu.php를
// require하면서 이미 채워져 있다. 권한 필터를 통과한 그룹/항목만 남긴다.
$mgr_visible_groups = array();
foreach ($manager_menu as $group) {
    if (!empty($group['is_system'])) {
        continue; // 시스템 관리 그룹은 사이드바 하단에 별도 렌더링
    }
    $visible_items = array_values(array_filter($group['items'], 'mgr_menu_visible'));
    if (empty($visible_items)) {
        continue;
    }
    $group['items'] = $visible_items;
    $group['is_active'] = mgr_group_is_active($group['id']);
    $mgr_visible_groups[] = $group;
}
$mgr_system_group = null;
foreach ($manager_menu as $group) {
    if (!empty($group['is_system'])) {
        $mgr_system_group = $group;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?php echo $page_title . ' - ' . $site_title; ?> 통합관리자</title>
<link rel="stylesheet" href="<?php echo SF_MANAGER_URL ?>/assets/css/manager.css">
<link rel="stylesheet" href="<?php echo SF_MANAGER_URL ?>/assets/css/components.css">
<link rel="stylesheet" href="<?php echo SF_MANAGER_URL ?>/assets/css/responsive.css">
</head>
<body class="mgr-body">
<div class="mgr-app" id="mgrApp">

    <header class="mgr-topbar">
        <button type="button" class="mgr-hamburger" id="mgrHamburger" aria-label="메뉴 열기" aria-controls="mgrMobileNav" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
        <a href="<?php echo SF_MANAGER_URL ?>/index.php" class="mgr-brand">
            <span class="mgr-brand-mark">쇼폼</span>
            <span class="mgr-brand-text">통합관리자</span>
        </a>
        <div class="mgr-topbar-spacer"></div>
        <a href="<?php echo G5_URL ?>/" target="_blank" rel="noopener" class="mgr-topbar-link">홈페이지 보기</a>
        <div class="mgr-account">
            <button type="button" class="mgr-account-btn" id="mgrAccountBtn" aria-haspopup="true" aria-expanded="false">
                <span class="mgr-avatar"><?php echo mb_substr($mgr_admin_name, 0, 1); ?></span>
                <span class="mgr-account-name"><?php echo get_text($mgr_admin_name); ?></span>
            </button>
            <div class="mgr-account-menu" id="mgrAccountMenu">
                <a href="<?php echo G5_ADMIN_URL ?>/member_form.php?w=u&amp;mb_id=<?php echo urlencode($member['mb_id']) ?>">관리자정보</a>
                <a href="<?php echo SF_MANAGER_URL ?>/logout.php">로그아웃</a>
            </div>
        </div>
    </header>

    <div class="mgr-body-row">
        <aside class="mgr-sidebar" id="mgrSidebar">
            <button type="button" class="mgr-sidebar-collapse" id="mgrSidebarCollapse" aria-label="사이드바 접기/펼치기">
                <span aria-hidden="true">&laquo;</span>
            </button>
            <nav class="mgr-sidebar-nav" aria-label="쇼폼 통합관리 주메뉴">
                <?php foreach ($mgr_visible_groups as $group): ?>
                <div class="mgr-nav-group<?php echo $group['is_active'] ? ' is-active' : ''; ?>" data-mgr-group="<?php echo $group['id'] ?>">
                    <button type="button" class="mgr-nav-group-toggle" data-mgr-group-toggle="<?php echo $group['id'] ?>" aria-expanded="<?php echo $group['is_active'] ? 'true' : 'false'; ?>">
                        <span class="mgr-nav-icon mgr-icon-<?php echo $group['icon'] ?>" aria-hidden="true"><?php echo isset($group['icon_emoji']) ? $group['icon_emoji'] : ''; ?></span>
                        <span class="mgr-nav-label"><?php echo get_text($group['title']) ?></span>
                        <span class="mgr-nav-group-chevron" aria-hidden="true">&#9662;</span>
                    </button>
                    <ul class="mgr-nav-group-list">
                        <?php foreach ($group['items'] as $item): ?>
                        <li>
                            <a href="<?php echo $item['url'] ?>" class="mgr-nav-link<?php echo mgr_item_is_active($item) ? ' is-current' : ''; ?>"<?php echo !empty($item['external']) ? ' target="_blank" rel="noopener"' : ''; ?>>
                                <?php echo get_text($item['title']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endforeach; ?>
            </nav>
            <?php if ($mgr_system_group): ?>
            <div class="mgr-sidebar-system">
                <p class="mgr-sidebar-system-title">시스템 관리</p>
                <?php foreach ($mgr_system_group['items'] as $item): ?>
                <a href="<?php echo $item['url'] ?>" class="mgr-nav-link mgr-nav-link-system"<?php echo !empty($item['external']) ? ' target="_blank" rel="noopener"' : ''; ?>>
                    <span class="mgr-nav-label"><?php echo get_text($item['title']) ?></span>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </aside>

        <nav class="mgr-mobile-nav" id="mgrMobileNav" aria-label="모바일 메뉴">
            <?php include __DIR__ . '/mobile_nav.php'; ?>
        </nav>
        <div class="mgr-mobile-overlay" id="mgrMobileOverlay"></div>

        <main class="mgr-content">
            <div class="mgr-page-head">
                <h1 class="mgr-page-title"><?php echo get_text($page_title) ?></h1>
            </div>
