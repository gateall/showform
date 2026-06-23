<?php
if (!defined('_GNUBOARD_')) exit;

add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_URL.'/css/knu_custom.css">', 10);
add_javascript('<script src="'.G5_THEME_URL.'/html/js/swiper.min.js"></script>', 10);
add_javascript('<script src="'.G5_THEME_URL.'/js/knu_main.js"></script>', 11);

$knu_brand = array(
    'phone' => '1844-****',
    'phone_link' => 'tel:1844-****',
    'online_link' => '/content/online.php',
    'gallery_link' => '/gallery',
    'email' => 'abc@naver.com'
);

$knu_channels = array(
    'blog' => '#',
    'instagram' => '#',
    'youtube' => '#'
);

include_once(G5_THEME_PATH.'/head.php');
?>

<main class="knu-main">
    <?php
    include_once(G5_THEME_PATH.'/sections/section00_hero.php');
    
    $estimate_latest = G5_THEME_PATH.'/sections/estimate_latest.php';
    if (file_exists($estimate_latest)) {
        include_once($estimate_latest);
    }
    include_once(G5_THEME_PATH.'/sections/section01_strength.php');
    include_once(G5_THEME_PATH.'/sections/section02_counter.php');
    include_once(G5_THEME_PATH.'/sections/section03_service.php');
    include_once(G5_THEME_PATH.'/sections/section04_youtube.php');
    include_once(G5_THEME_PATH.'/sections/section05_gallery.php');
    include_once(G5_THEME_PATH.'/sections/section06_blog.php');
    include_once(G5_THEME_PATH.'/sections/section07_sns.php');
    include_once(G5_THEME_PATH.'/sections/section08_cta.php');
    ?>
</main>

<?php
include_once(G5_THEME_PATH.'/tail.php');
?>
