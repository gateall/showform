<?php
if (!defined('_GNUBOARD_')) exit;

// 쇼폼·콘텐츠 운영 영역 전용 PC 가로 1차/2차 메뉴. 좌측 사이드바를 쓰지 않는다(§8).
// 모바일에서는 이 nav 대신 상단 햄버거가 여는 공용 드로어(sf-admin-sidebar)를 사용한다.
// $sf_content_buckets 는 admin_menu_build.php의 bp_sf_build_menus() 결과를 호출부에서 넘긴다.
if (empty($sf_content_buckets)) {
    return;
}
?>
<nav class="sf-content-nav" aria-label="쇼폼·콘텐츠 운영 메뉴">
    <ul class="sf-content-nav-list">
        <?php foreach ($sf_content_buckets as $bucket_label => $items): ?>
        <li class="sf-content-nav-item">
            <button type="button" class="sf-content-nav-btn" aria-expanded="false" aria-haspopup="true">
                <span><?php echo get_text($bucket_label); ?></span>
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </button>
            <ul class="sf-content-nav-flyout">
                <?php foreach ($items as $item): ?>
                <li><a href="<?php echo $item['href']; ?>" class="sf-content-nav-link"><?php echo get_text($item['title']); ?></a></li>
                <?php endforeach; ?>
            </ul>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>
