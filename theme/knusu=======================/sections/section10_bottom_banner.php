<?php
if (!defined('_GNUBOARD_')) exit;
?>
<style>
.knu-footer-banner {
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 84% 20%, rgba(255, 255, 255, 0.14), transparent 24%),
        radial-gradient(circle at 14% 80%, rgba(255, 255, 255, 0.08), transparent 26%),
        linear-gradient(135deg, #0f172a 0%, #173b6d 56%, #1f5f93 100%);
    color: #fff;
}

.knu-footer-banner::before {
    content: "";
    position: absolute;
    inset: -120px -80px auto auto;
    width: 320px;
    height: 320px;
    border-radius: 50%;
    pointer-events: none;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.14) 0%, rgba(255, 255, 255, 0) 72%);
}

.knu-footer-banner__inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 28px 20px;
    box-sizing: border-box;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 20px;
    position: relative;
    z-index: 1;
}

.knu-footer-banner__content {
    min-width: 0;
    opacity: 0;
    transform: translateY(14px);
    transition: opacity .35s ease, transform .35s ease;
}

.knu-footer-banner.is-visible .knu-footer-banner__content {
    opacity: 1;
    transform: translateY(0);
}

.knu-footer-banner__eyebrow {
    margin: 0 0 10px;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.22em;
    color: #edda7d;
    text-transform: uppercase;
}

.knu-footer-banner__title {
    margin: 0 0 8px;
    font-size: 30px;
    font-weight: 900;
    line-height: 1.2;
    letter-spacing: -0.03em;
    color: #fff;
    text-shadow: 0 3px 14px rgba(0,0,0,0.55);
    background: rgba(0,0,0,0.28);
    display: inline-block;
    padding: 10px 18px;
    border-radius: 14px;
    line-height: 1.25;
}

.knu-footer-banner__desc {
    margin: 0;
    font-size: 16px;
    line-height: 1.6;
    color: rgba(255, 255, 255, 0.88);
}

.knu-footer-banner__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    flex: 0 0 auto;
    opacity: 0;
    transform: translateY(14px);
    transition: opacity .35s ease .08s, transform .35s ease .08s;
}

.knu-footer-banner.is-visible .knu-footer-banner__actions {
    opacity: 1;
    transform: translateY(0);
}

.knu-footer-banner__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 52px;
    padding: 0 20px;
    border-radius: 14px;
    text-decoration: none;
    font-size: 15px;
    font-weight: 900;
    white-space: nowrap;
    transition: transform .2s ease, box-shadow .2s ease, background .2s ease;
}

.knu-footer-banner__btn:hover {
    transform: translateY(-2px);
}

.knu-footer-banner__btn--call {
    background: #edda7d;
    color: #0a182b;
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.14);
}

.knu-footer-banner__btn--online {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.12);
}

@media (max-width: 1024px) {
    .knu-footer-banner__title {
        font-size: 28px;
    }
}

@media (max-width: 768px) {
    .knu-footer-banner__inner {
        padding: 24px 16px;
        flex-direction: column;
        align-items: stretch;
    }

    .knu-footer-banner__title {
        font-size: 24px;
        display: block;
    }

    .knu-footer-banner__desc {
        font-size: 14px;
    }

    .knu-footer-banner__actions {
        width: 100%;
        flex-direction: column;
    }

    .knu-footer-banner__btn {
        width: 100%;
        min-height: 48px;
    }
}

@media (max-width: 480px) {
    .knu-footer-banner__inner {
        padding: 22px 12px;
    }

    .knu-footer-banner__title {
        font-size: 21px;
        padding: 9px 14px;
    }

    .knu-footer-banner__desc {
        font-size: 13px;
    }
}
</style>
<section class="knu-footer-banner" aria-labelledby="knuFooterBannerTitle">
    <div class="knu-footer-banner__inner">
        <div class="knu-footer-banner__content">
            <p class="knu-footer-banner__eyebrow">BOTTOM BANNER</p>
            <h2 class="knu-footer-banner__title" id="knuFooterBannerTitle">인터넷 가입부터 설치까지</h2>
            <p class="knu-footer-banner__desc">인터넷2424가 빠르고 안전하게 도와드립니다. 지금 바로 상담받아보세요.</p>
        </div>
        <div class="knu-footer-banner__actions">
            <a class="knu-footer-banner__btn knu-footer-banner__btn--call" href="tel:1800-0000">전화상담</a>
            <a class="knu-footer-banner__btn knu-footer-banner__btn--online" href="/estimate01.php">온라인 견적문의</a>
        </div>
    </div>
</section>
<script>
(function () {
    function revealBanner() {
        var banner = document.querySelector('.knu-footer-banner');
        if (!banner) {
            return;
        }

        window.setTimeout(function () {
            banner.classList.add('is-visible');
        }, 60);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', revealBanner);
    } else {
        revealBanner();
    }
})();
</script>
