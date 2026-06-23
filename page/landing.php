<?php
include_once('./_common.php');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;
if ($id < 1) {
    $id = 1;
}

$table = G5_TABLE_PREFIX . 'landing_page';
$row = sql_fetch(" select * from {$table} where id = '{$id}' ");

if ($row && isset($row['is_display']) && $row['is_display'] == 'N') {
    if ($is_admin != 'super') {
        alert('현재 일시적으로 접근이 차단된 페이지입니다.', G5_URL);
    }
}

if (!$row) {
    $row = array(
        'id' => 1,
        'subject' => '단 하나의 완벽한 골프 휴양, Hun Golf 프리미엄 필리핀 투어',
        'industry' => '골프투어',
        'phone' => '010-0000-0000',
        'hero_title' => '단 하나의 완벽한 골프 휴양, Hun Golf 프리미엄 필리핀 투어',
        'hero_text' => '필리핀 풀빌라/골프 패키지 상품! 명문 골프장 티오프 타임 전격 확보 및 단독 풀빌라 전용 숙박을 제공합니다.',
        'cta_text' => '무료 상담 신청하기',
        'cta_link' => '#contact',
        'problem_1' => '최고급 단독 풀빌라가 필요한가요?',
        'problem_2' => '명문 골프장 티오프 타임 확보가 어려우신가요?',
        'problem_3' => '단독 차량 및 전담 가이드 케어가 필요하신가요?',
        'service_1' => '최고급 단독 풀빌라 전용 숙박',
        'service_2' => '명문 골프장 티오프 타임 전격 확보',
        'service_3' => '단독 차량 및 전담 가이드 케어',
        'service_4' => 'VIP 맞춤형 밀착 서비스 제공',
        'privacy_text' => '개인정보 수집 및 이용에 동의합니다.',
        'kakao_link' => 'http://pf.kakao.com/test'
    );
}

$g5['title'] = $row['subject'] ?: $row['hero_title'];

// PV 자동 집계 로직
$stat_table = G5_TABLE_PREFIX . 'landing_page_stats';
$today = G5_TIME_YMD;
sql_query(" CREATE TABLE IF NOT EXISTS `{$stat_table}` (
  `stat_date` date NOT NULL,
  `landing_id` int(11) NOT NULL,
  `pv_count` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`stat_date`, `landing_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ", false);

sql_query(" insert into {$stat_table} (stat_date, landing_id, pv_count) values ('{$today}', '{$id}', 1) on duplicate key update pv_count = pv_count + 1 ", false);

// GrapesJS 커스텀 CSS가 없으면 기본 테마 CSS 로드
if (empty($row['custom_css'])) {
    add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_URL.'/css/landing_view.css?ver='.G5_CSS_VER.'">', 20);
    add_javascript('<script>
    document.addEventListener("DOMContentLoaded", function() {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = 1;
                    entry.target.style.transform = "translateY(0)";
                }
            });
        });
        document.querySelectorAll(".lv-section h2, .lv-card, .lv-review-card, .lv-steps div").forEach(el => {
            el.style.opacity = 0;
            el.style.transform = "translateY(20px)";
            el.style.transition = "opacity 0.6s ease-out, transform 0.6s ease-out";
            observer.observe(el);
        });
    });
    </script>', 20);
}

include_once(G5_THEME_PATH.'/head.php');

if (!empty($row['custom_html'])) {
    // 빌더 에디터로 제작한 커스텀 템플릿 렌더링
    echo "<style>{$row['custom_css']}</style>";
    echo "<div class='grapesjs-custom-wrap'>";
    echo $row['custom_html'];
    echo "</div>";
} else {
    // 기존 설정 기반 템플릿 렌더링
?>
<div class="lv-stickybar">
    <div class="lv-stickybar__inner">
        <div class="lv-stickybar__copy">
            <strong><?php echo get_text($row['cta_text']); ?></strong>
            <span>무료 현장 상담 신청</span>
        </div>
        <div class="lv-stickybar__actions">
            <a href="<?php echo get_text($row['cta_link']); ?>" class="lv-stickybar__btn lv-stickybar__btn--primary">예약하기</a>
            <a href="tel:<?php echo get_text($row['phone']); ?>" class="lv-stickybar__btn lv-stickybar__btn--ghost">전화하기</a>
        </div>
    </div>
</div>

<main class="lv-page">
    <section class="lv-hero" id="hero">
        <div class="lv-container">
            <div class="lv-badge"><?php echo get_text($row['industry']); ?> 프리미엄 투어</div>
            <h1><?php echo get_text($row['hero_title']); ?></h1>
            <p class="lv-hero-desc"><?php echo nl2br(get_text($row['hero_text'])); ?></p>
            <div class="lv-hero-actions">
                <a href="<?php echo get_text($row['cta_link']); ?>" class="lv-btn lv-btn--primary"><?php echo get_text($row['cta_text']); ?></a>
                <a href="tel:<?php echo get_text($row['phone']); ?>" class="lv-btn lv-btn--line">전화 문의하기</a>
            </div>
        </div>
    </section>

    <section class="lv-section lv-service">
        <div class="lv-container">
            <h2>타사 대비 차별화된 핵심 서비스</h2>
            <div class="lv-card-grid lv-card-grid--six lv-card-grid--accent">
                <?php if($row['service_1']) { ?><div class="lv-card"><?php echo get_text($row['service_1']); ?></div><?php } ?>
                <?php if($row['service_2']) { ?><div class="lv-card"><?php echo get_text($row['service_2']); ?></div><?php } ?>
                <?php if($row['service_3']) { ?><div class="lv-card"><?php echo get_text($row['service_3']); ?></div><?php } ?>
                <?php if($row['service_4']) { ?><div class="lv-card"><?php echo get_text($row['service_4']); ?></div><?php } ?>
                <div class="lv-card">프리미엄 라운지 이용권 제공</div>
                <div class="lv-card">고품격 조식 & 다이닝 서비스</div>
            </div>
        </div>
    </section>

    <section class="lv-section lv-problem">
        <div class="lv-container">
            <h2>이런 분들께 완벽한 솔루션을 제공합니다</h2>
            <div class="lv-card-grid lv-card-grid--six">
                <?php if($row['problem_1']) { ?><div class="lv-card"><?php echo get_text($row['problem_1']); ?></div><?php } ?>
                <?php if($row['problem_2']) { ?><div class="lv-card"><?php echo get_text($row['problem_2']); ?></div><?php } ?>
                <?php if($row['problem_3']) { ?><div class="lv-card"><?php echo get_text($row['problem_3']); ?></div><?php } ?>
                <div class="lv-card">비즈니스 접대용 럭셔리 투어가 필요하신 분</div>
                <div class="lv-card">언어 장벽 없이 편안한 일정을 원하시는 분</div>
                <div class="lv-card">가족/지인과 프라이빗한 시간을 보내고 싶으신 분</div>
            </div>
        </div>
    </section>

    <section class="lv-section lv-gallery">
        <div class="lv-container">
            <h2>프라이빗 갤러리</h2>
            <div class="lv-gallery-grid">
                <div class="lv-gallery-item lv-gallery-item--placeholder" style="background:#09130b; color:#D4AF37;">단독 풀빌라 전경</div>
                <div class="lv-gallery-item lv-gallery-item--placeholder" style="background:#09130b; color:#D4AF37;">프리미엄 골프장</div>
                <div class="lv-gallery-item lv-gallery-item--placeholder" style="background:#09130b; color:#D4AF37;">VIP 의전 차량</div>
                <div class="lv-gallery-item lv-gallery-item--placeholder" style="background:#09130b; color:#D4AF37;">고급 다이닝</div>
            </div>
        </div>
    </section>

    <section class="lv-section lv-process">
        <div class="lv-container">
            <h2>투어 예약 프로세스</h2>
            <div class="lv-steps">
                <div><span>1</span>상담 신청</div>
                <div><span>2</span>일정 및 견적 확인</div>
                <div><span>3</span>예약금 결제</div>
                <div><span>4</span>바우처 발송</div>
                <div><span>5</span>현지 의전 미팅</div>
                <div><span>6</span>투어 시작</div>
            </div>
        </div>
    </section>

    <section class="lv-section lv-review">
        <div class="lv-container">
            <h2>실제 고객 만족 리뷰</h2>
            <div class="lv-review-grid">
                <div class="lv-review-card">"풀빌라 퀄리티가 정말 압도적이었습니다. 비즈니스 파트너들과 함께했는데 모두가 만족했던 최고의 투어였어요."</div>
                <div class="lv-review-card">"골프장 티오프 타임을 황금시간대로 잡아주셔서 너무 편했습니다. 가이드분도 친절하고 완벽했습니다."</div>
                <div class="lv-review-card">"전담 차량으로 이동하니 피로감이 전혀 없었고, 음식부터 숙박까지 모든 것이 프리미엄이었습니다."</div>
            </div>
        </div>
    </section>

    <section class="lv-section lv-contact" id="contact">
        <div class="lv-container">
            <h2>상담 예약 및 문의하기</h2>
            <p class="lv-section-desc">아래 폼으로 접수하시면 담당자가 확인 후 신속하게 예약 확정 안내를 드립니다.</p>
            <form id="landingInquiryForm" class="lv-form" method="post" action="/adm/landing/inquiry_update.php">
                <input type="hidden" name="landing_id" value="<?php echo (int) $row['id']; ?>">
                <div class="lv-form-grid">
                    <label>
                        <span>예약자 성함</span>
                        <input type="text" name="name" required>
                    </label>
                    <label>
                        <span>연락처</span>
                        <input type="tel" name="tel" required>
                    </label>
                    <label>
                        <span>희망 일정</span>
                        <input type="text" name="schedule" placeholder="예: 2026.08.15 ~ 08.18" required>
                    </label>
                    <label>
                        <span>참여 인원</span>
                        <input type="text" name="people" placeholder="예: 4명">
                    </label>
                    <label class="lv-form-full">
                        <span>요청 및 문의 사항</span>
                        <textarea name="content" rows="5" required></textarea>
                    </label>
                    <label class="lv-form-agree lv-form-full">
                        <input type="checkbox" name="agree" value="1" required> <?php echo get_text($row['privacy_text'] ?: '개인정보 수집 및 이용에 동의합니다.'); ?>
                    </label>
                </div>
                <button type="submit" class="lv-form-submit"><?php echo get_text($row['cta_text'] ?: '예약 신청하기'); ?></button>
            </form>
        </div>
    </section>

    <div class="lv-bottom-spacer"></div>
</main>

<div class="lv-mobile-bottom">
    <a href="tel:<?php echo get_text($row['phone']); ?>">전화상담</a>
    <a href="<?php echo get_text($row['cta_link']); ?>">상담예약</a>
</div>

<?php 
}
include_once(G5_THEME_PATH.'/tail.php'); 
?>