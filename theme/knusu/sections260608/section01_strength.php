<?php
if (!defined('_GNUBOARD_')) exit;

$strengths = array(
    array(
        'title' => '신속출동',
        'label' => '접수 즉시 배정',
        'desc'  => '대구/경북/경남/부산 전지역 접수 즉시 가장 가까운 현장팀을 배정해 빠르게 출동합니다.',
        'img'   => G5_THEME_URL . '/img/index/strength/strength-rapid.jpg',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>'
    ),
    array(
        'title' => '정밀탐지',
        'label' => '원인 중심 진단',
        'desc'  => '청음, 가스, 열화상 장비를 활용해 보이지 않는 누수 지점까지 정확히 찾습니다.',
        'img'   => G5_THEME_URL . '/img/index/strength/strength-detect.jpg',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>'
    ),
    array(
        'title' => '배관보수',
        'label' => '문제 구간 근본 보수',
        'desc'  => '누수 원인이 된 배관 구간을 선별해 재누수 가능성을 낮추는 방식으로 보수합니다.',
        'img'   => G5_THEME_URL . '/img/index/strength/strength-pipe.jpg',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>'
    ),
    array(
        'title' => '방수공사',
        'label' => '부위별 맞춤 공법',
        'desc'  => '옥상, 외벽, 베란다 등 누수 유형에 맞는 방수 공법으로 재발을 예방합니다.',
        'img'   => G5_THEME_URL . '/img/index/strength/strength-waterproof.jpg',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22a7 7 0 0 0 7-7c0-2-1-3.9-3-5.5s-3.5-4-4-6.5c-.5 2.5-2 4.9-4 6.5C6 11.1 5 13 5 15a7 7 0 0 0 7 7z"/></svg>'
    ),
    array(
        'title' => '보험처리안내',
        'label' => '서류/증빙 체계화',
        'desc'  => '보험 청구에 필요한 사진, 공사내역, 증빙자료를 누락 없이 준비하도록 안내합니다.',
        'img'   => G5_THEME_URL . '/img/index/strength/strength-insurance.jpg',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/></svg>'
    ),
    array(
        'title' => '사후관리',
        'label' => '시공 이후 점검',
        'desc'  => '시공 완료 후에도 점검 기준에 따라 후속 확인을 진행해 결과를 책임집니다.',
        'img'   => G5_THEME_URL . '/img/index/strength/strength-aftercare.jpg',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m9 12 2 2 4-4"/></svg>'
    )
);
?>

<style>
/* Strength Section: Visual + Text Balance */
#knuStrength {
    position: relative;
    padding: 105px 0;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
}

#knuStrength .knu-sec-head {
    text-align: center;
    margin-bottom: 56px;
}

#knuStrength .knu-badge {
    display: inline-flex;
    align-items: center;
    height: 32px;
    padding: 0 12px;
    border-radius: 999px;
    background: rgba(215, 179, 114, 0.14);
    color: var(--knu-gold-strong);
    font-weight: 800;
    font-size: 12px;
    letter-spacing: 0.06em;
    margin-bottom: 16px;
}

#knuStrength .knu-sec-head h3 {
    margin: 0 0 14px;
    font-size: clamp(30px, 4vw, 48px);
    line-height: 1.22;
    color: #121820;
    letter-spacing: -0.03em;
    font-weight: 900;
}

#knuStrength .knu-sec-head p {
    margin: 0 auto;
    max-width: 780px;
    color: #596473;
    font-size: clamp(16px, 1.9vw, 20px);
    line-height: 1.75;
}

#knuStrength .knu-strength-lead {
    display: grid;
    grid-template-columns: 1.15fr 1fr;
    gap: 28px;
    margin-bottom: 30px;
}

#knuStrength .knu-lead-visual,
#knuStrength .knu-lead-copy {
    border-radius: 18px;
    overflow: hidden;
}

#knuStrength .knu-lead-visual {
    position: relative;
    min-height: 320px;
}

#knuStrength .knu-lead-visual img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

#knuStrength .knu-lead-visual::before {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(120deg, rgba(10, 19, 30, 0.58) 10%, rgba(10, 19, 30, 0.12) 70%);
}

#knuStrength .knu-lead-visual-text {
    position: absolute;
    left: 24px;
    bottom: 24px;
    z-index: 2;
    color: #fff;
}

#knuStrength .knu-lead-visual-text strong {
    display: block;
    font-size: clamp(22px, 3vw, 34px);
    line-height: 1.2;
    font-weight: 900;
    letter-spacing: -0.02em;
}

#knuStrength .knu-lead-visual-text span {
    display: block;
    margin-top: 8px;
    font-size: 14px;
    opacity: 0.92;
}

#knuStrength .knu-lead-copy {
    padding: 30px 28px;
    border: 1px solid #e5ebf2;
    background: #fff;
    box-shadow: 0 14px 30px rgba(16, 31, 46, 0.06);
}

#knuStrength .knu-lead-copy h4 {
    margin: 0 0 12px;
    font-size: 26px;
    line-height: 1.3;
    letter-spacing: -0.02em;
    color: #121820;
    font-weight: 900;
}

#knuStrength .knu-lead-copy p {
    margin: 0;
    color: #5f6b79;
    line-height: 1.75;
}

#knuStrength .knu-strength-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 22px;
}

#knuStrength .knu-strength-card {
    border-radius: 16px;
    overflow: hidden;
    border: 1px solid #e6ebf1;
    background: #fff;
    box-shadow: 0 12px 28px rgba(16, 31, 46, 0.05);
    transition: transform .28s ease, box-shadow .28s ease, border-color .28s ease;
}

#knuStrength .knu-strength-card:hover {
    transform: translateY(-8px);
    border-color: var(--knu-gold);
    box-shadow: 0 18px 32px rgba(16, 31, 46, 0.12);
}

#knuStrength .knu-card-thumb {
    position: relative;
    aspect-ratio: 4 / 3;
    overflow: hidden;
}

#knuStrength .knu-card-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transform: scale(1.02);
    transition: transform .5s ease;
}

#knuStrength .knu-strength-card:hover .knu-card-thumb img {
    transform: scale(1.08);
}

#knuStrength .knu-card-thumb::after {
    content: "";
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 52%, rgba(9, 17, 28, 0.52) 100%);
}

#knuStrength .knu-strength-icon {
    position: absolute;
    left: 14px;
    bottom: 14px;
    z-index: 2;
    width: 44px;
    height: 44px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.92);
    color: var(--knu-gold-strong);
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

#knuStrength .knu-strength-icon svg {
    width: 23px;
    height: 23px;
}

#knuStrength .knu-card-content {
    padding: 18px 18px 20px;
}

#knuStrength .knu-card-label {
    display: inline-flex;
    align-items: center;
    min-height: 24px;
    padding: 0 9px;
    border-radius: 999px;
    background: #eff4ff;
    color: #3c6fc7;
    font-size: 12px;
    font-weight: 700;
}

#knuStrength .knu-card-content h4 {
    margin: 10px 0 8px;
    font-size: 22px;
    line-height: 1.3;
    letter-spacing: -0.02em;
    color: #151b23;
    font-weight: 800;
}

#knuStrength .knu-card-content p {
    margin: 0;
    font-size: 15px;
    line-height: 1.72;
    color: #5f6b79;
}

@media (max-width: 1200px) {
    #knuStrength .knu-strength-lead { grid-template-columns: 1fr; }
    #knuStrength .knu-strength-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}

@media (max-width: 768px) {
    #knuStrength { padding: 74px 0; }
    #knuStrength .knu-sec-head { margin-bottom: 34px; }
    #knuStrength .knu-strength-lead { gap: 14px; margin-bottom: 16px; }
    #knuStrength .knu-lead-copy { padding: 20px 18px; }
    #knuStrength .knu-lead-copy h4 { font-size: 22px; }
    #knuStrength .knu-strength-grid { grid-template-columns: 1fr; gap: 14px; }
}
</style>

<section class="knu-section" id="knuStrength">
    <div class="knu-container">
        <div class="knu-sec-head" data-knu-fade="data-knu-fade">
            <span class="knu-badge">01.WHY KOREA NUSU</span>
            <h3>문제 발견부터 복구 마무리까지<br>현장에서 검증된 방식으로 대응합니다</h3>
            <p>누수탐지, 배관보수, 방수, 보험처리안내, 사후관리까지 코리아누수의 실전 프로세스를 한눈에 확인해보세요.</p>
        </div>

        <div class="knu-strength-lead" data-knu-fade="data-knu-fade">
            <div class="knu-lead-visual">
                <img src="<?php echo G5_THEME_URL; ?>/img/hero2.jpg" alt="코리아누수 현장 작업 이미지" loading="lazy">
                <div class="knu-lead-visual-text">
                    <strong>대구/경북/경남/부산 전지역 누수 솔루션</strong>
                    <span>원인 진단 · 공사 · 복구 · 사후관리</span>
                </div>
            </div>
            <div class="knu-lead-copy">
                <h4>이미지로 바로 이해되는 현장 중심 서비스</h4>
                <p>작업 전후를 글로만 설명하지 않고, 실제 현장 이미지를 함께 배치해 어떤 방식으로 문제를 해결하는지 직관적으로 전달합니다. 카드별 핵심 문구와 설명도 읽기 쉬운 길이로 정리해 가독성을 높였습니다.</p>
            </div>
        </div>

        <div class="knu-strength-grid">
            <?php foreach ($strengths as $item) { ?>
            <article class="knu-strength-card" data-knu-fade="data-knu-fade">
                <div class="knu-card-thumb">
                    <img src="<?php echo $item['img']; ?>" alt="<?php echo $item['title']; ?>" loading="lazy">
                    <div class="knu-strength-icon"><?php echo $item['icon']; ?></div>
                </div>
                <div class="knu-card-content">
                    <span class="knu-card-label"><?php echo $item['label']; ?></span>
                    <h4><?php echo $item['title']; ?></h4>
                    <p><?php echo $item['desc']; ?></p>
                </div>
            </article>
            <?php } ?>
        </div>
    </div>
</section>