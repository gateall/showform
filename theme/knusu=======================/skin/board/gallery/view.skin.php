<?php
if (!defined("_GNUBOARD_")) exit;
include_once(G5_LIB_PATH.'/thumbnail.lib.php');

add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
?>

<script src="<?php echo G5_JS_URL; ?>/viewimageresize.js"></script>

<article id="bo_v" style="width:<?php echo $width; ?>">
    <header>
        <h2 id="bo_v_title">
            <?php if ($category_name) { ?>
            <span class="bo_v_cate"><?php echo $view['ca_name']; ?></span>
            <?php } ?>
            <span class="bo_v_tit"><?php echo cut_str(get_text($view['wr_subject']), 70); ?></span>
        </h2>
    </header>

    <div class="bo_v_simple_btn">
        <?php if ($list_href) { ?><a href="<?php echo $list_href ?>" class="btn_b01 btn" title="목록"><i class="fa fa-list" aria-hidden="true"></i><span class="sound_only">목록</span></a><?php } ?>
    </div>

    <section id="bo_v_atc">
        <?php
        $v_img_count = count($view['file']);
        if($v_img_count) {
            echo '<div id="bo_v_img">';
            foreach($view['file'] as $view_file) {
                echo get_file_thumbnail($view_file);
            }
            echo '</div>';
        }
        ?>

        <div id="bo_v_con"><?php echo get_view_thumbnail($view['content']); ?></div>
    </section>

    <div class="bo_v_simple_btn bo_v_simple_btn_bottom">
        <?php if ($list_href) { ?><a href="<?php echo $list_href ?>" class="btn_b01 btn" title="목록"><i class="fa fa-list" aria-hidden="true"></i><span class="sound_only">목록</span></a><?php } ?>
    </div>
</article>

<script>
$(function() {
    $("a.view_image").click(function() {
        window.open(this.href, "large_image", "location=yes,links=no,toolbar=no,top=10,left=10,width=10,height=10,resizable=yes,scrollbars=no,status=no");
        return false;
    });

    $("#bo_v_atc").viewimageresize();
});
</script>

