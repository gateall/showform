<?php
if (!defined('_GNUBOARD_')) exit;

$knu_customer_items = array(
    array('date' => date('Y-m-d'), 'name' => '홈**', 'text' => '님의 빠른 상담 신청입니다.'),
    array('date' => date('Y-m-d', strtotime('-1 day')), 'name' => '김**', 'text' => '님의 견적 문의가 접수되었습니다.'),
    array('date' => date('Y-m-d', strtotime('-2 day')), 'name' => '박**', 'text' => '님의 이사 일정 문의가 등록되었습니다.'),
    array('date' => date('Y-m-d', strtotime('-3 day')), 'name' => '이**', 'text' => '님의 방문 상담 요청이 확인되었습니다.'),
    array('date' => date('Y-m-d', strtotime('-4 day')), 'name' => '최**', 'text' => '님의 온라인 견적 문의가 접수되었습니다.'),
);
?>
<style>
.knu-footer-customer {
    position: relative;
    overflow: hidden;
    background:
        radial-gradient(circle at 15% 15%, rgba(255, 255, 255, 0.14), transparent 28%),
        radial-gradient(circle at 85% 18%, rgba(255, 255, 255, 0.10), transparent 24%),
        linear-gradient(135deg, #173b6d 0%, #1f5f93 52%, #2b76ff 100%);
    color: #fff;
}

.knu-footer-customer::before,
.knu-footer-customer::after {
    content: "";
    position: absolute;
    border-radius: 50%;
    pointer-events: none;
    background: radial-gradient(circle, rgba(255, 255, 255, 0.16) 0%, rgba(255, 255, 255, 0) 72%);
}

.knu-footer-customer::before {
    width: 320px;
    height: 320px;
    right: -120px;
    top: -120px;
}

.knu-footer-customer::after {
    width: 360px;
    height: 360px;
    left: -140px;
    bottom: -180px;
    opacity: 0.7;
}

.knu-footer-customer__inner {
    max-width: 1280px;
    margin: 0 auto;
    padding: 44px 20px;
    box-sizing: border-box;
    position: relative;
    z-index: 1;
}

.knu-footer-customer__grid {
    display: grid;
    grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
    gap: 24px;
}

.knu-footer-customer__panel {
    min-width: 0;
    border-radius: 24px;
    transition: transform .28s ease, box-shadow .28s ease, opacity .35s ease;
}

.knu-footer-customer__panel--left {
    padding: 28px;
    border: 1px solid rgba(255, 255, 255, 0.16);
    background: rgba(255, 255, 255, 0.08);
    box-shadow: 0 18px 44px rgba(7, 18, 38, 0.18);
    backdrop-filter: blur(10px);
}

.knu-footer-customer__panel--right {
    padding: 28px;
    border: 1px solid rgba(255, 255, 255, 0.08);
    background: rgba(255, 255, 255, 0.96);
    box-shadow: 0 18px 44px rgba(7, 18, 38, 0.14);
}

.knu-footer-customer.is-visible .knu-footer-customer__panel {
    opacity: 1;
    transform: translateY(0);
}

.knu-footer-customer__panel {
    opacity: 0;
    transform: translateY(18px);
}

.knu-footer-customer__eyebrow {
    margin: 0 0 10px;
    color: #edda7d;
    font-size: 12px;
    font-weight: 900;
    letter-spacing: 0.22em;
    text-transform: uppercase;
}

.knu-footer-customer__title,
.knu-footer-customer__recent-title {
    margin: 0;
    line-height: 1.2;
    font-weight: 900;
    letter-spacing: -0.03em;
}

.knu-footer-customer__title {
    font-size: 28px;
    margin-bottom: 18px;
}

.knu-footer-customer__hours {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
}

.knu-footer-customer__hours-item,
.knu-footer-customer__contacts {
    padding: 16px 18px;
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.11);
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.knu-footer-customer__hours-item strong,
.knu-footer-customer__hours-item span,
.knu-footer-customer__contacts span,
.knu-footer-customer__contacts a {
    display: block;
}

.knu-footer-customer__hours-label {
    display: inline-block;
    margin-bottom: 6px;
    color: #edda7d;
    font-size: 12px;
    font-weight: 800;
    letter-spacing: 0.04em;
}

.knu-footer-customer__hours-item strong {
    font-size: 16px;
    margin-bottom: 4px;
}

.knu-footer-customer__hours-item span,
.knu-footer-customer__contacts p {
    font-size: 14px;
    line-height: 1.55;
}

.knu-footer-customer__contacts {
    display: grid;
    gap: 8px;
    margin-top: 14px;
}

.knu-footer-customer__contacts p {
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    align-items: center;
}

.knu-footer-customer__contacts p span {
    color: rgba(255, 255, 255, 0.72);
    font-weight: 700;
}

.knu-footer-customer__contacts a {
    color: #fff;
    font-weight: 900;
    text-decoration: none;
}

.knu-footer-customer__actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 18px;
}

.knu-footer-customer__btn {
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

.knu-footer-customer__btn:hover {
    transform: translateY(-2px);
}

.knu-footer-customer__btn--primary {
    background: #edda7d;
    color: #0a182b;
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.14);
}

.knu-footer-customer__btn--ghost {
    background: rgba(255, 255, 255, 0.1);
    color: #fff;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 14px 28px rgba(0, 0, 0, 0.12);
}

.knu-footer-customer__recent-head {
    margin-bottom: 18px;
}

.knu-footer-customer__recent .knu-footer-customer__eyebrow {
    color: #1f5f93;
}

.knu-footer-customer__recent-title {
    font-size: 24px;
    color: #0f172a;
}

.knu-footer-customer__list {
    list-style: none;
    margin: 0;
    padding: 0;
    display: grid;
    gap: 12px;
}

.knu-footer-customer__item {
    display: grid;
    grid-template-columns: 112px minmax(0, 1fr);
    gap: 12px;
    align-items: start;
    padding: 14px 16px;
    border-radius: 16px;
    background: #f8fafc;
    border: 1px solid #dbe3ee;
    opacity: 0;
    transform: translateY(12px);
    transition: opacity .35s ease, transform .35s ease, box-shadow .25s ease, border-color .25s ease;
}

.knu-footer-customer__item.is-visible {
    opacity: 1;
    transform: translateY(0);
}

.knu-footer-customer__item:hover {
    border-color: rgba(31, 95, 147, 0.28);
    box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
}

.knu-footer-customer__date {
    color: #1f5f93;
    font-size: 13px;
    font-weight: 900;
    letter-spacing: 0.02em;
}

.knu-footer-customer__text {
    color: #334155;
    font-size: 14px;
    line-height: 1.55;
}

.knu-footer-customer__text strong {
    margin-right: 4px;
    color: #0f172a;
}

@media (max-width: 1024px) {
    .knu-footer-customer__grid {
        grid-template-columns: 1fr;
    }

    .knu-footer-customer__title {
        font-size: 26px;
    }
}

@media (max-width: 768px) {
    .knu-footer-customer__inner {
        padding: 28px 16px;
    }

    .knu-footer-customer__grid {
        gap: 16px;
    }

    .knu-footer-customer__panel--left,
    .knu-footer-customer__panel--right {
        padding: 20px;
        border-radius: 20px;
    }

    .knu-footer-customer__title {
        font-size: 24px;
    }

    .knu-footer-customer__hours {
        grid-template-columns: 1fr;
    }

    .knu-footer-customer__recent-title {
        font-size: 22px;
    }

    .knu-footer-customer__item {
        grid-template-columns: 1fr;
        gap: 6px;
    }

    .knu-footer-customer__btn {
        width: 100%;
    }

    .knu-footer-customer__actions {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    .knu-footer-customer__inner {
        padding: 22px 12px;
    }

    .knu-footer-customer__title {
        font-size: 21px;
    }

    .knu-footer-customer__hours-item,
    .knu-footer-customer__contacts {
        padding: 14px;
    }

    .knu-footer-customer__hours-item strong {
        font-size: 15px;
    }

    .knu-footer-customer__hours-item span,
    .knu-footer-customer__contacts p,
    .knu-footer-customer__text {
        font-size: 13px;
    }

    .knu-footer-customer__btn {
        min-height: 48px;
    }
}
</style>
<section class="knu-footer-customer" aria-labelledby="knuFooterCustomerTitle">
    <div class="knu-footer-customer__inner">
        <div class="knu-footer-customer__grid">
            <div class="knu-footer-customer__panel knu-footer-customer__panel--left">
                <p class="knu-footer-customer__eyebrow">CUSTOMER CENTER</p>
                <h2 class="knu-footer-customer__title" id="knuFooterCustomerTitle">고객센터 안내</h2>
                <div class="knu-footer-customer__hours">
                    <div class="knu-footer-customer__hours-item">
                        <span class="knu-footer-customer__hours-label">업무시간</span>
                        <strong>월~금</strong>
                        <span>AM 09:00 ~ PM 21:00</span>
                    </div>
                    <div class="knu-footer-customer__hours-item">
                        <strong>토요일</strong>
                        <span>AM 09:00 ~ PM 20:00</span>
                    </div>
                </div>
                <div class="knu-footer-customer__contacts">
                    <p><span>대표전화</span> <a href="tel:1800-0000">1800-0000</a></p>
                    <p><span>견적문의</span> <a href="tel:010-1234-0000">010-1234-0000</a></p>
                </div>
                <div class="knu-footer-customer__actions">
                    <a class="knu-footer-customer__btn knu-footer-customer__btn--primary" href="tel:1800-0000">전화 상담</a>
                    <a class="knu-footer-customer__btn knu-footer-customer__btn--ghost" href="/estimate01.php">온라인 견적</a>
                </div>
            </div>

            <div class="knu-footer-customer__panel knu-footer-customer__panel--right">
                <div class="knu-footer-customer__recent-head">
                    <p class="knu-footer-customer__eyebrow">FAST CONSULT</p>
                    <h3 class="knu-footer-customer__recent-title">실시간 빠른상담</h3>
                </div>
                <ul class="knu-footer-customer__list">
                    <?php foreach ($knu_customer_items as $index => $item) { ?>
                        <li class="knu-footer-customer__item" data-index="<?php echo (int) $index; ?>">
                            <span class="knu-footer-customer__date"><?php echo $item['date']; ?></span>
                            <span class="knu-footer-customer__text"><strong><?php echo $item['name']; ?></strong><?php echo $item['text']; ?></span>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </div>
    </div>
</section>
<script>
(function () {
    function revealCustomerSection() {
        var section = document.querySelector('.knu-footer-customer');
        if (!section) {
            return;
        }

        section.classList.add('is-visible');

        var items = section.querySelectorAll('.knu-footer-customer__item');
        if (!items.length) {
            return;
        }

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries, obs) {
                entries.forEach(function (entry) {
                    if (!entry.isIntersecting) {
                        return;
                    }

                    items.forEach(function (item, index) {
                        window.setTimeout(function () {
                            item.classList.add('is-visible');
                        }, index * 90);
                    });

                    obs.disconnect();
                });
            }, { threshold: 0.15 });

            observer.observe(section);
            return;
        }

        items.forEach(function (item) {
            item.classList.add('is-visible');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', revealCustomerSection);
    } else {
        revealCustomerSection();
    }
})();
</script>
