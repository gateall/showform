<?php
if (!defined('_GNUBOARD_')) exit;
?>

<style>
/* Section Styles: YouTube Focus */
.youtube-section {
    position: relative;
    padding: 100px 0;
    background: #f8fafc;
}

.youtube-section .section-title {
    text-align: center;
    margin-bottom: 50px;
}

.youtube-section .section-title h2 {
    font-size: clamp(30px, 4vw, 42px);
    font-weight: 900;
    color: #0f2747;
    margin-bottom: 20px;
    letter-spacing: -0.03em;
}

.youtube-section .section-title p {
    font-size: 18px;
    color: #4a5563;
    line-height: 1.6;
    max-width: 700px;
    margin: 0 auto;
}

/* Responsive YouTube wrapper */
.youtube-wrap {
    position: relative;
    padding-bottom: 56.25%; /* 16:9 Aspect Ratio */
    height: 0;
    overflow: hidden;
    max-width: 1000px;
    margin: 0 auto;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    background: #000;
}

.youtube-wrap iframe,
.youtube-wrap img.youtube-cover {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: 0;
}

/* Optional Cover Image specific styles */
.youtube-cover {
    object-fit: cover;
    cursor: pointer;
    z-index: 10;
    transition: transform 0.4s ease, filter 0.4s ease;
}

.youtube-wrap:hover .youtube-cover {
    transform: scale(1.03);
    filter: brightness(0.85);
}

.youtube-play-btn {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80px;
    height: 80px;
    background: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 15;
    cursor: pointer;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
    transition: transform 0.3s ease;
    pointer-events: none; /* Let clicks pass through to the image */
}

.youtube-wrap:hover .youtube-play-btn {
    transform: translate(-50%, -50%) scale(1.1);
}

.youtube-play-btn svg {
    width: 32px;
    height: 32px;
    fill: #db2b2b;
    margin-left: 4px;
}
</style>

<section class="youtube-section">
    <div class="container">

        <div class="section-title">
            <h2>포장이사 서비스 영상</h2>
            <p>
                인터넷2424의 안전하고 신속한 포장이사 서비스를 영상으로 확인해보세요.<br>
                전문 인력과 체계적인 작업 프로세스로 고객님의 소중한 이삿짐을 안전하게 운송합니다.
            </p>
        </div>

        <div class="youtube-wrap" id="youtubeWrap">
            <!-- Cover Image and Play Button Overlay -->
            <img class="youtube-cover" id="ytCover" src="<?php echo G5_THEME_URL; ?>/img/youtube_cover.jpg" alt="인터넷2424 포장이사" onclick="document.getElementById('ytIframe').src += '&autoplay=1'; this.style.display='none'; document.getElementById('ytPlayBtn').style.display='none';">
            
            <div class="youtube-play-btn" id="ytPlayBtn">
                <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </div>

            <!-- YouTube iframe -->
            <iframe
                id="ytIframe"
                src="https://www.youtube.com/embed/WNzEoTzk-B8?si=TeciQ22MAoIgvjUy"
                title="인터넷2424 포장이사 서비스"
                frameborder="0"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen>
            </iframe>
        </div>

    </div>
</section>
