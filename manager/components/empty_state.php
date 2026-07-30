<?php
if (!defined('_GNUBOARD_')) exit;

function mgr_empty_state($title, $desc = '', $opts = array())
{
    $icon = isset($opts['icon']) ? $opts['icon'] : 'inbox';
    $action_html = isset($opts['action_html']) ? $opts['action_html'] : '';
    ob_start();
?>
<div class="mgr-empty-state">
    <div class="mgr-empty-icon mgr-icon-<?php echo $icon ?>" aria-hidden="true"></div>
    <p class="mgr-empty-title"><?php echo get_text($title) ?></p>
    <?php if ($desc !== ''): ?>
    <p class="mgr-empty-desc"><?php echo get_text($desc) ?></p>
    <?php endif; ?>
    <?php if ($action_html !== ''): ?>
    <div class="mgr-empty-action"><?php echo $action_html ?></div>
    <?php endif; ?>
</div>
<?php
    return ob_get_clean();
}
