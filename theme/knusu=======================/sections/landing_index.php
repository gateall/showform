<?php
if (!defined('_GNUBOARD_')) exit;

$landing_nav = isset($knu_landing_nav) ? $knu_landing_nav : array();
?>
<section class="knu-landing">
    <div class="knu-container">
        <div class="knu-landing__nav">
            <?php foreach ((array) $landing_nav as $nav) { ?>
                <a href="<?php echo $nav['url']; ?>"><?php echo $nav['text']; ?></a>
            <?php } ?>
        </div>

        <div class="knu-hero">
            <div class="knu-hero__copy">
                <span class="knu-kicker">AI 랜딩페이지 자동화 시스템</span>
                <h2>사진 몇 장과 업체 정보만 입력하면 랜딩페이지, 블로그 원고, 숏폼 광고소재까지 자동으로 생성됩니다.</h2>
                <p>ShowForm은 지역업체와 소상공인을 위한 AI 기반 랜딩페이지 자동 생성 시스템입니다. 업체 정보, 사진, 연락처만 입력하면 업종별 템플릿을 기반으로 홍보용 랜딩페이지를 빠르게 구성할 수 있습니다.</p>
                <div class="knu-hero__actions">
                    <a class="knu-btn knu-btn--primary" href="#contact-form">무료 상담 신청</a>
                    <a class="knu-btn knu-btn--ghost" href="#plans">샘플 랜딩 보기</a>
                    <a class="knu-btn knu-btn--ghost" href="#key-features">자동화 기능 보기</a>
                </div>
            </div>
            <div class="knu-hero__panel">
                <strong>AI 랜딩페이지 자동화 시스템</strong>
                <p>랜딩페이지 제작, 블로그 원고, 숏폼 광고소재를 한 번에 묶어 운영하는 구조를 준비합니다.</p>
                <ul>
                    <li>업체 정보 입력</li>
                    <li>사진 업로드</li>
                    <li>AI 문구 생성</li>
                    <li>상담폼 연결</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<section class="knu-section bg-light" id="service-intro">
    <div class="knu-container knu-grid-2">
        <div class="knu-card knu-card--accent">
            <span class="knu-section-label">문제 제기</span>
            <p>홈페이지 제작은 오래 걸리고 비쌉니다. 광고 문구 작성은 어렵고, 블로그와 숏폼 제작은 계속 손이 갑니다.</p>
        </div>
        <div class="knu-card">
            <span class="knu-section-label">해결 방향</span>
            <p>이제 반복 작업은 자동화하고 고객 상담과 영업에 집중하세요.</p>
        </div>
    </div>
</section>

<section class="knu-section bg-alt" id="automation-flow">
    <div class="knu-container">
        <div class="knu-sec-head">
            <h3>자동화 흐름</h3>
        </div>
        <div class="knu-steps">
            <span>1. 업체 정보 입력</span><span>2. 사진 업로드</span><span>3. AI 문구 생성</span><span>4. 랜딩페이지 자동 구성</span><span>5. 블로그 글 생성</span><span>6. 숏폼 광고소재 제작</span><span>7. 상담폼 연결</span>
        </div>
    </div>
</section>

<section class="knu-section bg-light" id="key-features">
    <div class="knu-container">
        <div class="knu-sec-head"><h3>주요 기능</h3></div>
        <div class="knu-feature-list">
            <span>업종별 랜딩페이지 템플릿</span><span>AI 홍보 문구 자동 생성</span><span>사진 자동 배치</span><span>상담 신청폼 연결</span><span>블로그 원고 자동 생성</span><span>숏폼 광고소재 생성</span><span>모바일 최적화</span>
        </div>
    </div>
</section>

<section class="knu-section bg-alt" id="industries">
    <div class="knu-container">
        <div class="knu-sec-head"><h3>적용 업종</h3></div>
        <p class="knu-industry-line">누수 · 방수 · 이사 · 청소 · 설비 · 인테리어 · 병원 · 요양원 · 학원 · 법무 · 세무 · 지역상점</p>
    </div>
</section>

<section class="knu-section bg-light" id="plans">
    <div class="knu-container">
        <div class="knu-sec-head"><h3>상품 구성</h3></div>
        <div class="knu-plan-grid">
            <div class="knu-plan-box"><strong>기본형 월 19만원</strong><p>랜딩페이지 1개 + 숏폼 10개</p></div>
            <div class="knu-plan-box"><strong>표준형 월 39만원</strong><p>랜딩페이지 3개 + 숏폼 20개 + 블로그 4건</p></div>
            <div class="knu-plan-box"><strong>프리미엄 월 69만원</strong><p>랜딩페이지 다수 + 숏폼 30개 + 블로그 10건 + 채널 배포</p></div>
        </div>
    </div>
</section>

<section class="knu-section bg-dark" id="contact-form">
    <div class="knu-container">
        <div class="knu-sec-head">
            <h3>상담 신청</h3>
            <p>업체 정보를 남겨주시면 안내드리겠습니다.</p>
        </div>
        <form class="knu-contact-form" action="#" method="post">
            <div class="knu-form-grid">
                <label><span>업체명</span><input type="text" name="company_name"></label>
                <label><span>담당자명</span><input type="text" name="manager_name"></label>
                <label><span>연락처</span><input type="text" name="phone"></label>
                <label><span>업종</span><input type="text" name="business_type"></label>
                <label class="knu-form-full"><span>희망 서비스</span><input type="text" name="service"></label>
                <label class="knu-form-full"><span>문의 내용</span><textarea name="message" rows="5"></textarea></label>
                <label class="knu-form-full knu-form-check"><input type="checkbox" name="agree" value="1"> 개인정보 동의</label>
            </div>
            <button type="submit" class="knu-btn knu-btn--primary">무료 상담 신청</button>
        </form>
    </div>
</section>