<?php
if (!defined('_GNUBOARD_')) exit;
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

$thumb_width = 560;
$thumb_height = 420;
$list_count = (isset($list) && is_array($list)) ? count($list) : 0;
?>

<div class="knu-gallery-grid">
    <?php for ($i = 0; $i < $list_count; $i++) {
        $thumb = get_list_thumbnail($bo_table, $list[$i]['wr_id'], $thumb_width, $thumb_height, false, true);
        $img = !empty($thumb['src']) ? $thumb['src'] : G5_URL.'/img/no_img.png';
        $subject = isset($list[$i]['subject']) ? cut_str(strip_tags($list[$i]['subject']), 52) : '';
        $ca_name = isset($list[$i]['ca_name']) ? $list[$i]['ca_name'] : '';
        $wr_href = get_pretty_url($bo_table, $list[$i]['wr_id']);
    ?>
    <article class="gallery-card" data-knu-fade>
        <a href="<?php echo $wr_href; ?>" class="gallery-thumb">
            <div class="gallery-thumb-inner" style="background-image:url('<?php echo $img; ?>');"></div>
            <?php if ($ca_name) { ?><span class="gallery-badge"><?php echo $ca_name; ?></span><?php } ?>
        </a>
        <div class="gallery-body">
            <h5><?php echo $subject; ?></h5>
            <a href="<?php echo $wr_href; ?>" class="gallery-link">
                <span>더보기</span>
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M7 17L17 7M9 7h8v8" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </a>
        </div>
    </article>
    <?php } ?>

    <?php if ($list_count === 0) { ?>
    <div class="gallery-empty">등록된 시공사례가 없습니다. 갤러리에 글을 등록하면 자동으로 노출됩니다.</div>
    <?php } ?>
</div>