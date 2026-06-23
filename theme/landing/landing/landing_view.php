<?php
if (!defined('_GNUBOARD_')) exit;
?>
<section class="landing-view">
    <div class="landing-view__hero">
        <h1><?php echo get_text($row['subject']); ?></h1>
        <p><?php echo nl2br(get_text($row['hero_text'])); ?></p>
        <p><?php echo nl2br(get_text($row['intro_text'])); ?></p>
        <?php if (!empty($row['hero_image'])) { ?>
            <p><img src="<?php echo G5_DATA_URL; ?>/landing/<?php echo get_text($row['hero_image']); ?>" alt="" style="max-width:100%;height:auto;"></p>
        <?php } ?>
    </div>
</section>
