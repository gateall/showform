<?php
// 랜딩페이지 본문 템플릿.
//
// landing.php가 templates/{template_type}.php를 include하고, 없으면 이 파일로
// 폴백한다(landing.php:128-132). 그런데 templates 폴더 자체가 없어서 include가
// 실패했고, 페이지는 <div class="sf-wrap">까지만 출력하고 500으로 끊겼다.
// service / hospital / local 세 유형 모두 이 폴백을 타므로 이 파일 하나로 복구된다.
//
// 마크업만 담당한다. 클래스와 색상은 landing.php의 <style>이 정의하고, 값은
// 이미 landing.php가 준비해 둔 변수를 그대로 쓴다(DB 조회·저장 형식 변경 없음).
if (!defined('_GNUBOARD_')) exit;

// faq_text는 "Q: 질문\nA: 답변" 줄 쌍으로 저장된다(config/showform_preset.php 참고).
$faq_items = array();
if ($faq_text !== '') {
    $q = '';
    foreach (preg_split('/\r\n|\r|\n/', $faq_text) as $line) {
        $line = trim($line);
        if ($line === '') continue;
        if (preg_match('/^Q\s*[:.]\s*(.+)$/u', $line, $m)) {
            $q = $m[1];
        } elseif (preg_match('/^A\s*[:.]\s*(.+)$/u', $line, $m)) {
            if ($q !== '') {
                $faq_items[] = array('q' => $q, 'a' => $m[1]);
                $q = '';
            }
        }
    }
}
?>

<section class="sf-hero">
    <div class="sf-container">
        <?php if ($hero_kicker) { ?>
            <span class="sf-kicker"><?php echo get_text($hero_kicker); ?></span>
        <?php } ?>
        <h1><?php echo get_text($main_copy ? $main_copy : $company_name); ?></h1>
        <?php if ($sub_copy) { ?>
            <p><?php echo nl2br(get_text($sub_copy)); ?></p>
        <?php } ?>

        <div class="sf-hero-actions">
            <?php if ($phone) { ?>
                <a class="sf-btn sf-btn-primary" href="<?php echo $phone_href; ?>">전화 상담 <?php echo get_text($phone); ?></a>
            <?php } ?>
            <a class="sf-btn sf-btn-dark" href="#contact"><?php echo get_text($contact_label); ?></a>
        </div>
    </div>
</section>

<?php if ($main_image || $intro_text) { ?>
<section class="sf-section">
    <div class="sf-container">
        <div class="sf-grid-2">
            <div class="sf-card">
                <h2 class="sf-section-title"><?php echo get_text($company_name ? $company_name : '서비스 소개'); ?></h2>
                <?php if ($intro_text) { ?>
                    <p class="sf-section-desc"><?php echo nl2br(get_text($intro_text)); ?></p>
                <?php } ?>
                <?php if ($phone) { ?>
                    <div class="sf-cta-row">
                        <a class="sf-btn sf-btn-solid" href="<?php echo $phone_href; ?>">지금 전화하기</a>
                    </div>
                <?php } ?>
            </div>
            <div class="sf-image">
                <?php if ($main_image) { ?>
                    <img src="<?php echo get_text($main_image); ?>" alt="<?php echo get_text($company_name ? $company_name . ' 대표 이미지' : '대표 이미지'); ?>" loading="lazy">
                <?php } else { ?>
                    <span class="placeholder">이미지 준비 중</span>
                <?php } ?>
            </div>
        </div>
    </div>
</section>
<?php } ?>

<?php if ($problem_text) { ?>
<section class="sf-section">
    <div class="sf-container">
        <div class="sf-card">
            <h2 class="sf-section-title">이런 고민이 있으신가요</h2>
            <p class="sf-section-desc"><?php echo nl2br(get_text($problem_text)); ?></p>
        </div>
    </div>
</section>
<?php } ?>

<?php if ($strength_text) { ?>
<section class="sf-section">
    <div class="sf-container">
        <h2 class="sf-section-title">이렇게 해결해 드립니다</h2>
        <div class="sf-pill-grid">
            <?php
            // 쉼표 또는 줄바꿈으로 나눠 카드로 만든다. 구분자가 없으면 한 덩어리로 둔다.
            $strengths = preg_split('/[,\r\n]+/u', $strength_text);
            $strengths = array_values(array_filter(array_map('trim', $strengths), 'strlen'));
            if (!$strengths) $strengths = array($strength_text);
            foreach ($strengths as $item) {
                echo '<div class="sf-pill">' . get_text($item) . '</div>';
            }
            ?>
        </div>
    </div>
</section>
<?php } ?>

<?php if ($faq_items) { ?>
<section class="sf-section">
    <div class="sf-container">
        <h2 class="sf-section-title">자주 묻는 질문</h2>
        <div class="sf-notice-grid">
            <?php foreach ($faq_items as $faq) { ?>
                <details class="sf-notice">
                    <summary class="sf-notice-title" style="cursor:pointer;list-style:none;"><?php echo get_text($faq['q']); ?></summary>
                    <div class="sf-notice-content"><?php echo nl2br(get_text($faq['a'])); ?></div>
                </details>
            <?php } ?>
        </div>
    </div>
</section>
<?php } ?>

<section class="sf-section" id="contact">
    <div class="sf-container">
        <div class="sf-card sf-form">
            <h2 class="sf-section-title"><?php echo get_text($contact_label); ?></h2>
            <p class="sf-section-desc">연락처를 남겨주시면 담당자가 빠르게 연락드립니다.</p>

            <form method="post" action="<?php echo G5_URL; ?>/page/landing_inquiry_update.php" onsubmit="return sfInquirySubmit(this);">
                <input type="hidden" name="landing_id" value="<?php echo (int) $id; ?>">
                <div class="sf-form-grid">
                    <label>
                        <span>이름 *</span>
                        <input type="text" name="name" required maxlength="30" autocomplete="name">
                    </label>
                    <label>
                        <span>연락처 *</span>
                        <input type="tel" name="phone" required maxlength="20" inputmode="numeric" autocomplete="tel" placeholder="010-0000-0000">
                    </label>
                    <label class="sf-full">
                        <span>문의 내용</span>
                        <textarea name="message" rows="4" maxlength="1000" placeholder="문의하실 내용을 남겨주세요."></textarea>
                    </label>
                    <label class="sf-full" style="display:flex;align-items:center;gap:8px;">
                        <input type="checkbox" name="agree" value="1" required style="width:auto;min-height:24px;">
                        <span style="margin:0;font-weight:600;">개인정보 수집 및 이용에 동의합니다. *</span>
                    </label>
                </div>
                <div class="sf-cta-row">
                    <button type="submit" class="sf-btn sf-btn-solid" style="border:0;cursor:pointer;width:100%;">상담 신청하기</button>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
// 연타로 같은 문의가 여러 건 접수되는 것을 막는다.
function sfInquirySubmit(form) {
    if (form.dataset.sending === '1') return false;
    form.dataset.sending = '1';
    var btn = form.querySelector('button[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = '접수 중...'; }
    return true;
}
</script>
