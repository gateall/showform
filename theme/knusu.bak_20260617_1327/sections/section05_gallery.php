<?php
if (!defined('_GNUBOARD_')) exit;
?>

<style>
/* Section Scoped Style: Gallery (Landing Upgrade) */
#knuGallery {
    background: linear-gradient(180deg, #f6f8fb 0%, #eef3f7 100%);
    padding: 110px 0;
}

#knuGallery .knu-sec-head {
    margin-bottom: 48px;
}

#knuGallery .knu-gallery-badge {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0 12px;
    margin-bottom: 14px;
    border-radius: 999px;
    background: rgba(23, 58, 109, 0.08);
    color: #163a6d;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.06em;
}

#knuGallery .knu-gallery-grid {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 20px;
}

#knuGallery .gallery-card {
    border-radius: 18px;
    overflow: hidden;
    background: #fff;
    border: 1px solid #e6ebf1;
    box-shadow: 0 10px 28px rgba(16, 26, 36, 0.06);
    transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
}

#knuGallery .gallery-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 18px 36px rgba(13, 42, 82, 0.12);
    border-color: rgba(217, 176, 107, 0.55);
}

#knuGallery .gallery-thumb {
    position: relative;
    display: block;
    overflow: hidden;
    aspect-ratio: 4 / 3;
    background: #e9eff6;
}

#knuGallery .gallery-thumb::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, rgba(0, 0, 0, 0) 42%, rgba(0, 0, 0, .32) 100%);
    z-index: 1;
    pointer-events: none;
}

#knuGallery .gallery-thumb-inner {
    width: 100%;
    height: 100%;
    background-size: cover;
    background-position: center;
    transition: transform .45s ease;
}

#knuGallery .gallery-card:hover .gallery-thumb-inner {
    transform: scale(1.05);
}

#knuGallery .gallery-badge {
    position: absolute;
    z-index: 2;
    top: 12px;
    left: 12px;
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0 11px;
    border-radius: 999px;
    background: rgba(15, 59, 50, .94);
    color: #fff;
    font-size: 12px;
    font-weight: 800;
    box-shadow: 0 8px 18px rgba(0, 0, 0, .12);
    transition: background .3s ease;
}

#knuGallery .gallery-card:hover .gallery-badge {
    background: rgba(14, 45, 80, .94);
}

#knuGallery .gallery-body {
    padding: 16px 16px 18px;
    border-top: 1px solid #eef2f7;
}

#knuGallery .gallery-body h5 {
    margin: 0 0 10px;
    font-size: 19px;
    font-weight: 900;
    line-height: 1.35;
    letter-spacing: -0.03em;
    color: #1a2433;
    min-height: 52px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

#knuGallery .gallery-link {
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #163a6d;
    font-size: 14px;
    font-weight: 800;
    text-decoration: none;
}

#knuGallery .gallery-link svg {
    width: 16px;
    height: 16px;
    transition: transform .25s ease;
}

#knuGallery .gallery-card:hover .gallery-link svg {
    transform: translateX(4px);
}

#knuGallery .gallery-empty {
    grid-column: 1 / -1;
    padding: 42px 20px;
    text-align: center;
    border: 1px dashed #cfd9e5;
    border-radius: 16px;
    background: rgba(255, 255, 255, 0.8);
    color: #5c6877;
}

#knuGallery .gallery-more-btn {
    margin-top: 42px;
    display: flex;
    justify-content: center;
}

#knuGallery .gallery-more-btn .knu-btn {
    box-shadow: 0 14px 28px rgba(10, 22, 40, 0.2);
}

#knuGallery .gallery-more-btn .knu-btn:hover {
    box-shadow: 0 18px 32px rgba(10, 22, 40, 0.28);
}

@media (max-width: 1100px) {
    #knuGallery .knu-gallery-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }
}

@media (max-width: 768px) {
    #knuGallery {
        padding: 78px 0;
    }

    #knuGallery .knu-gallery-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 12px;
    }

    #knuGallery .gallery-body {
        padding: 14px 14px 16px;
    }

    #knuGallery .gallery-body h5 {
        font-size: 16px;
        min-height: 44px;
    }
}

@media (max-width: 480px) {
    #knuGallery .knu-gallery-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="knu-section" id="knuGallery">
    <div class="knu-container">
        <div class="knu-sec-head" data-knu-fade="data-knu-fade">
            <span class="knu-gallery-badge">05.LATEST WORKS</span>
            <h3>이사 현장 갤러리</h3>
            <p>인터넷2424의 생생한 이사 현장 모습을 확인해보세요.</p>
        </div>

        <?php echo latest('theme/gallery_main', 'gallery', 8, 52); ?>

        <div class="gallery-more-btn" data-knu-fade="data-knu-fade">
            <a href="<?php echo $knu_brand['gallery_link']; ?>" class="knu-btn knu-btn-dark">이사 현장 더보기</a>
        </div>
    </div>
</section>