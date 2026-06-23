<?php
if (!defined('_GNUBOARD_')) exit;
?>

<style>
/* Section Scoped Style: SNS Enhanced */
#knuSns {
    background: linear-gradient(180deg, #f7f9fc 0%, #eef3f8 100%);
    padding: 108px 0;
}

#knuSns .sns-badge {
    display: inline-flex;
    align-items: center;
    min-height: 30px;
    padding: 0 12px;
    margin-bottom: 12px;
    border-radius: 999px;
    background: rgba(22, 58, 109, 0.08);
    color: #163a6d;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.06em;
}

#knuSns .knu-sec-head {
    margin-bottom: 46px;
}

#knuSns .sns-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
}

#knuSns .sns-card {
    position: relative;
    display: block;
    border-radius: 18px;
    padding: 26px 24px 22px;
    text-decoration: none;
    color: inherit;
    background: #fff;
    border: 1px solid #e6ebf1;
    box-shadow: 0 10px 28px rgba(16, 26, 36, 0.06);
    transition: transform .3s ease, box-shadow .3s ease, border-color .3s ease;
    overflow: hidden;
}

#knuSns .sns-card::before {
    content: "";
    position: absolute;
    right: -36px;
    top: -36px;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    opacity: 0.22;
    transition: transform .35s ease, opacity .35s ease;
}

#knuSns .sns-card::after {
    content: "";
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 3px;
    transform: scaleX(.22);
    transform-origin: left center;
    transition: transform .32s ease;
}

#knuSns .sns-card.blog::before { background: #2db400; }
#knuSns .sns-card.insta::before { background: #e1306c; }
#knuSns .sns-card.youtube::before { background: #ff0000; }

#knuSns .sns-card.blog::after { background: #2db400; }
#knuSns .sns-card.insta::after { background: #e1306c; }
#knuSns .sns-card.youtube::after { background: #ff0000; }

#knuSns .sns-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 18px 36px rgba(13, 42, 82, 0.12);
}

#knuSns .sns-card.blog:hover { border-color: rgba(45, 180, 0, 0.45); }
#knuSns .sns-card.insta:hover { border-color: rgba(225, 48, 108, 0.45); }
#knuSns .sns-card.youtube:hover { border-color: rgba(255, 0, 0, 0.4); }

#knuSns .sns-card:hover::before {
    opacity: 0.3;
    transform: scale(1.08);
}

#knuSns .sns-card:hover::after {
    transform: scaleX(1);
}

#knuSns .sns-icon-box {
    width: 68px;
    height: 68px;
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 14px;
    transition: transform .3s ease;
}

#knuSns .sns-icon-box img {
    width: 40px;
    height: 40px;
    display: block;
}

#knuSns .sns-card.blog .sns-icon-box { background: rgba(45, 180, 0, 0.12); }
#knuSns .sns-card.insta .sns-icon-box { background: rgba(225, 48, 108, 0.12); }
#knuSns .sns-card.youtube .sns-icon-box { background: rgba(255, 0, 0, 0.12); }

#knuSns .sns-card:hover .sns-icon-box {
    transform: scale(1.08);
}

#knuSns .sns-label {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 9px;
    border-radius: 999px;
    font-size: 11px;
    font-weight: 800;
    letter-spacing: 0.06em;
    margin-bottom: 10px;
}

#knuSns .sns-card.blog .sns-label { background: rgba(45, 180, 0, 0.12); color: #1f7f00; }
#knuSns .sns-card.insta .sns-label { background: rgba(225, 48, 108, 0.12); color: #b81f59; }
#knuSns .sns-card.youtube .sns-label { background: rgba(255, 0, 0, 0.12); color: #b40000; }

#knuSns .sns-card h5 {
    margin: 0 0 8px;
    font-size: 24px;
    font-weight: 900;
    line-height: 1.25;
    letter-spacing: -0.03em;
    color: #152032;
}

#knuSns .sns-card p {
    margin: 0;
    color: #5f6b79;
    font-size: 15px;
    line-height: 1.7;
    min-height: 51px;
}

#knuSns .sns-cta {
    margin-top: 16px;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    color: #163a6d;
    font-size: 14px;
    font-weight: 800;
}

#knuSns .sns-cta img {
    width: 16px;
    height: 16px;
    transition: transform .25s ease;
}

#knuSns .sns-card:hover .sns-cta img {
    transform: translateX(4px);
}

#knuSns .sns-card:focus-visible {
    outline: 2px solid #163a6d;
    outline-offset: 2px;
}

@media (max-width: 1100px) {
    #knuSns .sns-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media (max-width: 768px) {
    #knuSns {
        padding: 76px 0;
    }

    #knuSns .sns-grid {
        grid-template-columns: 1fr;
        gap: 14px;
    }

    #knuSns .sns-card {
        padding: 22px 18px 20px;
    }

    #knuSns .sns-icon-box {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        margin-bottom: 12px;
    }

    #knuSns .sns-icon-box img {
        width: 34px;
        height: 34px;
    }

    #knuSns .sns-card h5 {
        font-size: 21px;
    }

    #knuSns .sns-cta {
        min-height: 40px;
    }
}
</style>

<section class="knu-section" id="knuSns">
    <div class="knu-container">
        <div class="knu-sec-head" data-knu-fade="data-knu-fade">
            <span class="sns-badge">07.SNS CHANNEL</span>
            <h3>SNS · 채널 바로가기</h3>
            <p>블로그, 인스타그램, 유튜브에서 코리아누수의 최신 소식과 현장 이야기를 확인하세요.</p>
        </div>

        <div class="sns-grid">
            <a href="<?php echo $knu_channels['blog']; ?>" target="_blank" rel="noopener" class="sns-card blog" data-knu-fade="data-knu-fade">
            <div class="sns-icon-box"><img src="<?php echo G5_THEME_URL; ?>/img/sns-blog.svg" alt="네이버 블로그"></div>
                <span class="sns-label">BLOG</span>
                <h5>네이버 블로그</h5>
                <p>시공사례, 칼럼, 현장 스케치를 정리한 콘텐츠를 빠르게 확인해보세요.</p>
                <span class="sns-cta">블로그 바로가기 <img src="<?php echo G5_THEME_URL; ?>/img/sns-arrow.svg" alt=""></span>
            </a>

            <a href="<?php echo $knu_channels['instagram']; ?>" target="_blank" rel="noopener" class="sns-card insta" data-knu-fade="data-knu-fade">
            <div class="sns-icon-box"><img src="<?php echo G5_THEME_URL; ?>/img/sns-instagram.svg" alt="인스타그램"></div>
                <span class="sns-label">INSTAGRAM</span>
                <h5>인스타그램</h5>
                <p>현장 사진, 작업 전후, 진행 과정 스토리를 카드형으로 확인할 수 있습니다.</p>
                <span class="sns-cta">인스타그램 보기 <img src="<?php echo G5_THEME_URL; ?>/img/sns-arrow.svg" alt=""></span>
            </a>

            <a href="<?php echo $knu_channels['youtube']; ?>" target="_blank" rel="noopener" class="sns-card youtube" data-knu-fade="data-knu-fade">
                <div class="sns-icon-box"><img src="<?php echo G5_THEME_URL; ?>/img/sns-youtube.svg" alt="유튜브"></div>
                <span class="sns-label">YOUTUBE</span>
                <h5>유튜브 채널</h5>
                <p>누수 원인 진단부터 공사 흐름까지 실제 작업 영상을 영상으로 확인해보세요.</p>
                <span class="sns-cta">유튜브 채널 보기 <img src="<?php echo G5_THEME_URL; ?>/img/sns-arrow.svg" alt=""></span>
            </a>
        </div>
    </div>
</section>
