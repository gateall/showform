<?php
if (!defined('_GNUBOARD_')) exit;

$strengths = array(
    array(
        'title' => '전문 인력 투입',
        'label' => '베테랑 이사 전문가',
        'desc'  => '수년간의 현장 경험을 갖춘 이사 전문 인력이 투입되어 안전하고 신속하게 이사를 진행합니다.',
        'img'   => '/theme/knusu/img/strength/internet2424-expert.jpg',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>'
    ),
    array(
        'title' => '완벽한 맞춤포장',
        'label' => '물품별 전용 포장재',
        'desc'  => '가구, 가전, 파손되기 쉬운 물품까지 각 특성에 맞는 전용 포장재를 사용하여 꼼꼼하게 포장합니다.',
        'img'   => '/theme/knusu/img/strength/internet2424-packing.jpg',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>'
    ),
    array(
        'title' => '안전·신속 운송',
        'label' => '체계적인 운송 시스템',
        'desc'  => '이사 화물 전용 차량과 최적의 이동 경로 설계로 고객님의 소중한 짐을 안전하고 빠르게 운송합니다.',
        'img'   => '/theme/knusu/img/strength/internet2424-transport.jpg',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>'
    ),
    array(
        'title' => '바닥·벽면 보양',
        'label' => '안전한 작업 환경',
        'desc'  => '이동 경로에 보양재를 설치하여 바닥재 찍힘이나 벽지 훼손 등 이사 중 발생할 수 있는 스크래치를 방지합니다.',
        'img'   => '/theme/knusu/img/strength/internet2424-protection.jpg',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>'
    ),
    array(
        'title' => '정리 정돈 및 청소',
        'label' => '바로 생활 가능한 마무리',
        'desc'  => '가구 배치부터 작은 짐 정리까지 완벽하게 수행하며, 마무리 기본 청소로 기분 좋은 시작을 돕습니다.',
        'img'   => '/theme/knusu/img/strength/internet2424-cleaning.jpg',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2v6h-6"/><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>'
    ),
    array(
        'title' => '확실한 A/S 보장',
        'label' => '사후관리 시스템',
        'desc'  => '철저한 주의에도 발생할 수 있는 문제에 대비해 책임 배상 시스템을 운영하며 끝까지 책임집니다.',
        'img'   => '/theme/knusu/img/strength/internet2424-aftercare.jpg',
        'icon'  => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>'
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
    width: 100%;
    aspect-ratio: 800/520;
    overflow: hidden;
    border-radius: 18px;
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
            <span class="knu-badge">01.WHY 인터넷2424</span>
            <h3>짐작이 아닌 정확한 계획으로<br>완벽한 이사 서비스를 제공합니다</h3>
            <p>견적, 포장, 운송, 보양, 정리정돈, 사후관리까지 인터넷2424의 차별화된 이사 프로세스를 확인해보세요.</p>
        </div>

        <div class="knu-strength-lead" data-knu-fade="data-knu-fade">
            <div class="knu-lead-visual">
                <img src="/theme/knusu/img/hero2.png" alt="인터넷2424 이사 현장 이미지" loading="lazy">
                <div class="knu-lead-visual-text">
                    <strong>전국 어디든 안심 이사 솔루션</strong>
                    <span>포장이사 · 일반이사 · 보관이사 · 원룸이사</span>
                </div>
            </div>
            <div class="knu-lead-copy">
                <h4>마음까지 편안해지는 프리미엄 이사</h4>
                <p>고객님의 소중한 물건을 내 짐처럼 아끼고 다루는 마음가짐. 인터넷2424는 다년간 축적된 노하우와 전문 인력 네트워크를 통해 이사의 처음부터 끝까지 빈틈없는 만족을 드립니다. 복잡하고 힘든 이사, 이제 전문가에게 맡기고 새로운 출발에만 집중하세요.</p>
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