<?php
// header.php가 include한다. $mgr_visible_groups / $mgr_system_group / $mgr_admin_name을 그대로 쓴다.
if (!defined('_GNUBOARD_')) exit;
?>
<div class="mgr-mobile-nav-head">
    <span class="mgr-brand-mark">쇼폼</span>
    <span class="mgr-brand-text">통합관리자</span>
    <button type="button" class="mgr-mobile-close" id="mgrMobileClose" aria-label="메뉴 닫기">&times;</button>
</div>
<div class="mgr-mobile-nav-body">
    <p class="mgr-mobile-user"><?php echo get_text($mgr_admin_name); ?>님</p>

    <?php foreach ($mgr_visible_groups as $group): ?>
    <div class="mgr-mobile-group<?php echo $group['is_active'] ? ' is-active' : ''; ?>">
        <button type="button" class="mgr-mobile-group-toggle" data-mgr-group-toggle="<?php echo $group['id'] ?>" aria-expanded="<?php echo $group['is_active'] ? 'true' : 'false'; ?>">
            <?php echo get_text($group['title']) ?>
            <span class="mgr-nav-group-chevron" aria-hidden="true">&#9662;</span>
        </button>
        <ul class="mgr-mobile-list mgr-nav-group-list">
            <?php foreach ($group['items'] as $item): ?>
            <li>
                <a href="<?php echo $item['url'] ?>" class="<?php echo mgr_item_is_active($item) ? 'is-current' : ''; ?>"<?php echo !empty($item['external']) ? ' target="_blank" rel="noopener"' : ''; ?>>
                    <?php echo get_text($item['title']) ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endforeach; ?>

    <?php if ($mgr_system_group): ?>
    <div class="mgr-mobile-system">
        <?php foreach ($mgr_system_group['items'] as $item): ?>
        <a href="<?php echo $item['url'] ?>"<?php echo !empty($item['external']) ? ' target="_blank" rel="noopener"' : ''; ?>><?php echo get_text($item['title']) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
