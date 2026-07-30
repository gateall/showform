<?php
if (!defined('_GNUBOARD_')) exit;

// manager.js의 data-mgr-modal-open="{$id}" / data-mgr-modal-close 로 열고 닫는다.
function mgr_modal($id, $title, $body_html, $opts = array())
{
    $footer_html = isset($opts['footer_html']) ? $opts['footer_html'] : '';
    ob_start();
?>
<div class="mgr-modal" id="<?php echo $id ?>" aria-hidden="true">
    <div class="mgr-modal-backdrop" data-mgr-modal-close></div>
    <div class="mgr-modal-panel" role="dialog" aria-modal="true" aria-labelledby="<?php echo $id ?>_title">
        <div class="mgr-modal-head">
            <h2 id="<?php echo $id ?>_title"><?php echo get_text($title) ?></h2>
            <button type="button" class="mgr-modal-close" data-mgr-modal-close aria-label="닫기">&times;</button>
        </div>
        <div class="mgr-modal-body"><?php echo $body_html ?></div>
        <?php if ($footer_html !== ''): ?>
        <div class="mgr-modal-footer"><?php echo $footer_html ?></div>
        <?php endif; ?>
    </div>
</div>
<?php
    return ob_get_clean();
}
