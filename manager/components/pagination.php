<?php
if (!defined('_GNUBOARD_')) exit;

// $base_url 에 이미 '?page=' 로 끝나는 쿼리스트링을 포함해서 넘긴다(마지막에 페이지번호만 붙임).
function mgr_pagination($current_page, $total_pages, $base_url)
{
    if ($total_pages <= 1) {
        return '';
    }

    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);

    ob_start();
?>
<nav class="mgr-pagination" aria-label="페이지 이동">
    <?php if ($current_page > 1): ?>
    <a href="<?php echo $base_url . ($current_page - 1) ?>" class="mgr-page-link">이전</a>
    <?php endif; ?>
    <?php for ($p = $start; $p <= $end; $p++): ?>
    <a href="<?php echo $base_url . $p ?>" class="mgr-page-link<?php echo $p === (int)$current_page ? ' is-current' : ''; ?>"><?php echo $p ?></a>
    <?php endfor; ?>
    <?php if ($current_page < $total_pages): ?>
    <a href="<?php echo $base_url . ($current_page + 1) ?>" class="mgr-page-link">다음</a>
    <?php endif; ?>
</nav>
<?php
    return ob_get_clean();
}
