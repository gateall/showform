<?php
if (!defined('_GNUBOARD_')) exit;

// 모바일 공용 드로어 — 현재 영역(기본 관리자/쇼폼·콘텐츠 운영)에 맞는 메뉴만 담는다.
// PC에서는 기본 관리자만 이 nav를 고정 좌측 사이드바로도 재사용한다(§5, §8).
// $sf_current_area, $sf_core_menus, $sf_content_buckets 는 호출부에서 넘긴다.
$sf_drawer_groups = array();
if ($sf_current_area === 'core') {
    foreach ($sf_core_menus as $group) {
        $sf_drawer_groups[] = array('title' => $group['title'], 'items' => $group['subs']);
    }
    $sf_drawer_label = '기본 관리자 메뉴';
} else {
    foreach ($sf_content_buckets as $bucket_label => $items) {
        $sf_drawer_groups[] = array('title' => $bucket_label, 'items' => $items);
    }
    $sf_drawer_label = '쇼폼·콘텐츠 운영 메뉴';
}
?>
<div class="sf-sidebar-overlay sf-area-<?php echo $sf_current_area; ?>" id="sf-sidebar-overlay" aria-hidden="true"></div>
<nav class="sf-admin-sidebar sf-area-<?php echo $sf_current_area; ?>" id="sf-admin-sidebar" aria-label="<?php echo get_text($sf_drawer_label); ?>">
    <div class="sf-sidebar-header">
        <h2><?php echo get_text($sf_drawer_label); ?></h2>
        <button type="button" class="sf-btn-close" id="sf-btn-close" aria-label="메뉴 닫기">
            <i class="fa-solid fa-xmark" aria-hidden="true"></i>
        </button>
    </div>
    <ul class="sf-menu-list">
        <?php $sf_group_id = 0; foreach ($sf_drawer_groups as $sf_group_data): $sf_group_id++; $group_html_id = 'sf-menu-group-' . $sf_group_id; ?>
        <li class="sf-menu-group">
            <button type="button" class="sf-menu-btn" aria-expanded="false" aria-controls="<?php echo $group_html_id; ?>">
                <span><?php echo $sf_group_data['title']; ?></span>
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </button>
            <ul class="sf-menu-sub" id="<?php echo $group_html_id; ?>">
                <?php foreach ($sf_group_data['items'] as $sub): ?>
                <li><a href="<?php echo $sub['href']; ?>" class="sf-menu-link"><?php echo $sub['title']; ?></a></li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>
