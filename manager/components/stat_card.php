<?php
if (!defined('_GNUBOARD_')) exit;

// 대시보드 요약 카드. $opts: icon(css class suffix), tone(primary|success|warning|danger|ai|muted), delta(문자열, 증감 표시)
function mgr_stat_card($label, $value, $opts = array())
{
    $tone = isset($opts['tone']) ? $opts['tone'] : 'primary';
    $icon = isset($opts['icon']) ? $opts['icon'] : 'gauge';
    $delta = isset($opts['delta']) ? $opts['delta'] : '';
    $delta_dir = isset($opts['delta_dir']) ? $opts['delta_dir'] : ''; // up|down|''
    ob_start();
?>
<div class="mgr-card mgr-stat-card mgr-tone-<?php echo $tone ?>">
    <div class="mgr-stat-icon mgr-icon-<?php echo $icon ?>" aria-hidden="true"></div>
    <div class="mgr-stat-body">
        <p class="mgr-stat-label"><?php echo get_text($label) ?></p>
        <p class="mgr-stat-value"><?php echo get_text($value) ?></p>
        <?php if ($delta !== ''): ?>
        <p class="mgr-stat-delta mgr-delta-<?php echo $delta_dir ?>"><?php echo get_text($delta) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php
    return ob_get_clean();
}
