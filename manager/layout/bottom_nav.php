<?php
// footer.php가 include한다. 모바일 폭(768px 이하)에서만 CSS로 보인다.
// "전체"는 새 패널을 만들지 않고 기존 mgrMobileNav 드로어를 여는 트리거로만 동작한다.
if (!defined('_GNUBOARD_')) exit;
?>
<nav class="mgr-bottom-nav" aria-label="모바일 빠른 메뉴">
    <?php foreach ($manager_bottom_nav as $bn): ?>
        <?php if (isset($bn['action']) && $bn['action'] === 'open-mobile-nav'): ?>
        <button type="button" class="mgr-bottom-nav-item" data-mgr-open-mobile>
            <span class="mgr-nav-icon mgr-icon-<?php echo $bn['icon'] ?>" aria-hidden="true"></span>
            <span><?php echo get_text($bn['label']) ?></span>
        </button>
        <?php else: ?>
        <a href="<?php echo $bn['url'] ?>" class="mgr-bottom-nav-item<?php echo mgr_group_is_active($bn['id']) ? ' is-current' : ''; ?>">
            <span class="mgr-nav-icon mgr-icon-<?php echo $bn['icon'] ?>" aria-hidden="true"></span>
            <span><?php echo get_text($bn['label']) ?></span>
        </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
