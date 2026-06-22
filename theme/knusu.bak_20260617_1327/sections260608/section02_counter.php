<?php
if (!defined('_GNUBOARD_')) exit;

$counters = array(
    array(
        'value'  => 1500,
        'suffix' => '+',
        'label'  => '누적 현장경험',
        'desc'   => '다양한 현장 대응 경험을 바탕으로 상황별로 최적화된 전문 판단을 진행합니다.',
        'icon'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>', // Wrench
        'note'   => '현장 대응 데이터 기반'
    ),
    array(
        'value'  => 980,
        'suffix' => '+',
        'label'  => '시공사례 누적',
        'desc'   => '풍부한 시공 사례는 코리아누수가 지금까지 쌓아온 실력과 결과의 확실한 증거입니다.',
        'icon'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>', // Building/Home
        'note'   => '최근 5년 시공 완료 건'
    ),
    array(
        'value'  => 24,
        'suffix' => '시간',
        'label'  => '상담응대 운영',
        'desc'   => '누수는 언제 발생할지 알 수 없습니다. 긴급 상황에 대비해 상시 상담 채널을 가동합니다.',
        'icon'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.91-1.14a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>', // Phone
        'note'   => '실시간 온라인 접수 가능'
    ),
    array(
        'value'  => 5,
        'suffix' => '종',
        'label'  => '주요서비스',
        'desc'   => '탐지부터 보험 처리, 사후관리까지 누수 해결에 필요한 모든 전문 서비스를 제공합니다.',
        'icon'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>', // Layout/Grid
        'note'   => '원스톱 솔루션 정식 품목'
    )
);
?>

<style>
/* Section Styles: Counter Infographic */
#knuCounter { 
    position: relative; 
    padding: 120px 0; 
    background: linear-gradient(180deg, #f7f9fc 0%, #eef3f7 100%);
    overflow: hidden;
}

/* Background Decoration */
#knuCounter::before {
    content: "";
    position: absolute;
    top: -100px;
    right: -100px;
    width: 400px;
    height: 400px;
    background: radial-gradient(circle, rgba(215, 179, 114, 0.05) 0%, rgba(255, 255, 255, 0) 70%);
    z-index: 1;
}

#knuCounter .knu-sec-head { margin-bottom: 70px; text-align: center; position: relative; z-index: 2; }
#knuCounter .knu-badge { display: inline-block; margin-bottom: 16px; padding: 6px 14px; background: #153B6D; color: #fff; border-radius: 4px; font-size: 12px; font-weight: 800; letter-spacing: 0.1em; }
#knuCounter .knu-sec-head h3 { font-size: 38px; font-weight: 900; color: #111; margin-bottom: 18px; }
#knuCounter .knu-sec-head p { font-size: 17px; color: #667085; max-width: 600px; margin: 0 auto; line-height: 1.6; }

#knuCounter .knu-counter-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; position: relative; z-index: 2; }

/* Card Styling */
#knuCounter .knu-counter-item { 
    position: relative;
    background: #fff; 
    border: 1px solid #e7eaf0; 
    border-radius: 20px; 
    padding: 46px 32px; 
    transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    height: 100%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}

/* Card Number Decor */
#knuCounter .knu-card-num-decor {
    position: absolute;
    top: 30px;
    right: 30px;
    font-size: 48px;
    font-weight: 900;
    color: #f3f5f9;
    line-height: 1;
    z-index: 1;
    user-select: none;
}

#knuCounter .knu-counter-item:hover {
    transform: translateY(-8px);
    box-shadow: 0 24px 48px rgba(21, 59, 109, 0.08);
    border-color: #d9b06b;
}

/* Icon Box */
#knuCounter .knu-icon-box {
    width: 52px;
    height: 52px;
    background: #f8fafc;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 28px;
    color: #153B6D;
    transition: all 0.3s ease;
    position: relative;
    z-index: 2;
}
#knuCounter .knu-icon-box svg { width: 26px; height: 26px; stroke-width: 2.2; transition: transform 0.4s ease; }
#knuCounter .knu-counter-item:hover .knu-icon-box { background: #153B6D; color: #fff; }
#knuCounter .knu-counter-item:hover .knu-icon-box svg { transform: scale(1.1) rotate(5deg); }

/* Value/Text Styling */
#knuCounter .knu-counter-num { 
    display: block; 
    font-size: 42px; 
    font-weight: 900; 
    color: #d9b06b; 
    line-height: 1.1; 
    margin-bottom: 8px;
    position: relative;
    z-index: 2;
}
#knuCounter .knu-counter-item label { 
    display: block; 
    font-size: 19px; 
    font-weight: 800; 
    color: #1a2433; 
    margin-bottom: 14px;
}
#knuCounter .knu-counter-desc {
    font-size: 14.5px;
    color: #667085;
    line-height: 1.6;
    margin-bottom: 20px;
    flex-grow: 1;
}
#knuCounter .knu-counter-note {
    font-size: 12px;
    font-weight: 700;
    color: #94a3b8;
    background: #f1f5f9;
    padding: 4px 10px;
    border-radius: 4px;
    align-self: flex-start;
}

/* Bottom Bar Decor */
#knuCounter .knu-item-bar {
    position: absolute;
    bottom: 0;
    left: 32px;
    right: 32px;
    height: 3px;
    background: #eee;
    overflow: hidden;
    border-radius: 3px 3px 0 0;
}
#knuCounter .knu-item-bar-inner {
    width: 0%;
    height: 100%;
    background: #d9b06b;
    transition: width 0.6s ease;
}
#knuCounter .knu-counter-item:hover .knu-item-bar-inner {
    width: 60%;
}

/* Responsive */
@media (max-width: 1199px) {
    #knuCounter .knu-counter-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; }
    #knuCounter { padding: 90px 0; }
}
@media (max-width: 767px) {
    #knuCounter .knu-counter-grid { grid-template-columns: 1fr; gap: 16px; }
    #knuCounter { padding: 70px 0; }
    #knuCounter .knu-sec-head { margin-bottom: 45px; }
    #knuCounter .knu-counter-item { padding: 36px 28px; }
    #knuCounter .knu-counter-num { font-size: 38px; }
}
</style>

<section class="knu-section" id="knuCounter">
    <div class="knu-container">
        <!-- Section Header -->
        <div class="knu-sec-head" data-knu-fade="data-knu-fade">
            <span class="knu-badge">02.TRUST DATA</span>
            <h3>신뢰를 만드는 수치</h3>
            <p>현장 대응 경험부터 시공 사례까지, 코리아누수가 쌓아온 전문성을 객관적인 지표로 투명하게 공개합니다.</p>
        </div>

        <!-- Grid -->
        <div class="knu-counter-grid">
            <?php foreach ($counters as $idx => $item) { 
                $num = sprintf("%02d", $idx + 1);
            ?>
            <div class="knu-counter-item" data-knu-fade="data-knu-fade">
                <!-- Card Decor Number -->
                <span class="knu-card-num-decor"><?php echo $num; ?></span>
                
                <!-- Icon Box -->
                <div class="knu-icon-box">
                    <?php echo $item['icon']; ?>
                </div>

                <!-- Number -->
                <span class="knu-counter-num" data-count="<?php echo (int)$item['value']; ?>" data-suffix="<?php echo $item['suffix']; ?>">0</span>
                
                <!-- Content -->
                <label><?php echo $item['label']; ?></label>
                <p class="knu-counter-desc"><?php echo $item['desc']; ?></p>
                <span class="knu-counter-note"><?php echo $item['note']; ?></span>

                <!-- Bottom Decoration Bar -->
                <div class="knu-item-bar">
                    <div class="knu-item-bar-inner"></div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</section>
