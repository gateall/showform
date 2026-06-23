<?php
if (!defined('_GNUBOARD_')) exit;

$g5['title'] = 'ShowForm | AI 랜딩페이지 자동생성 플랫폼';
include_once(G5_THEME_PATH.'/head.php');
?>

<div class="sf-page">
    <section class="sf-hero">
        <div class="swiper sfHeroSwiper">
            <div class="swiper-wrapper">
                <div class="swiper-slide sf-hero-slide" style="background-image:url('<?php echo G5_THEME_URL; ?>/img/slider/hero1.jpg');">
                    <div class="sf-hero-inner sf-wrap">
                        <div class="sf-badge">AI 마케팅 자동화</div>
                        <h1>AI가 랜딩페이지를 자동 생성합니다</h1>
                        <p>업종만 입력하면 랜딩페이지 제작, 문의폼, 고객관리까지 한 번에</p>
                        <div class="sf-actions">
                            <a href="/adm/landing/landing_form.php" class="sf-btn sf-btn-primary">무료로 랜딩페이지 만들기</a>
                            <a href="#contact" class="sf-btn sf-btn-line">상담 문의하기</a>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide sf-hero-slide" style="background-image:url('<?php echo G5_THEME_URL; ?>/img/slider/hero2.jpg');">
                    <div class="sf-hero-inner sf-wrap">
                        <div class="sf-badge">고객 문의 자동화</div>
                        <h1>문의가 들어오는 랜딩페이지 자동 구축</h1>
                        <p>광고부터 상담까지 연결되는 전환 중심 랜딩 구조를 빠르게 만듭니다.</p>
                        <div class="sf-actions">
                            <a href="#samples" class="sf-btn sf-btn-primary">샘플 랜딩 보기</a>
                            <a href="#faq" class="sf-btn sf-btn-line">자주 묻는 질문</a>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide sf-hero-slide" style="background-image:url('<?php echo G5_THEME_URL; ?>/img/slider/hero3.jpg');">
                    <div class="sf-hero-inner sf-wrap">
                        <div class="sf-badge">광고 성과 향상</div>
                        <h1>광고부터 고객관리까지 자동화</h1>
                        <p>랜딩, 문의, AI 문구, 관리 화면을 하나의 흐름으로 연결합니다.</p>
                        <div class="sf-actions">
                            <a href="#process" class="sf-btn sf-btn-primary">제작 프로세스 보기</a>
                            <a href="#contact" class="sf-btn sf-btn-line">무료 상담 신청</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sf-indicator"></div>
        </div>
    </section>

    <section class="sf-section sf-gray">
        <div class="sf-wrap">
            <h2>이런 고민 있으신가요?</h2>
            <p class="sf-lead">ShowForm은 랜딩이 필요한 순간부터 문의가 들어오는 순간까지의 과정을 단순화합니다.</p>
            <div class="sf-grid sf-grid-4">
                <div class="sf-card"><strong>홈페이지 만들기 어렵다</strong><p>복잡한 제작 과정을 줄이고 바로 시작할 수 있습니다.</p></div>
                <div class="sf-card"><strong>광고는 하는데 문의가 없다</strong><p>전환 중심 구조로 문의 흐름을 강화합니다.</p></div>
                <div class="sf-card"><strong>제작비가 부담된다</strong><p>템플릿 기반 자동 생성으로 부담을 낮춥니다.</p></div>
                <div class="sf-card"><strong>관리자가 없다</strong><p>관리자 화면에서 쉽게 등록과 수정이 가능합니다.</p></div>
            </div>
            <p style="text-align:center;margin:28px 0 0;font-weight:800;color:#111827;">ShowForm이 해결합니다</p>
        </div>
    </section>

    <section class="sf-section">
        <div class="sf-wrap">
            <h2>서비스 소개</h2>
            <p class="sf-lead">업종 입력, AI 생성, 문의 저장, 광고 연동까지 하나의 플랫폼으로 연결합니다.</p>
            <div class="sf-grid sf-grid-3">
                <div class="sf-card"><strong>랜딩페이지 자동생성</strong><p>업종 입력 → AI 생성 → 즉시 공개까지 연결합니다.</p></div>
                <div class="sf-card"><strong>문의관리</strong><p>고객 문의가 자동 저장되어 관리가 쉬워집니다.</p></div>
                <div class="sf-card"><strong>광고 연동</strong><p>구글, 네이버, 카카오, 유튜브 광고와 연결 가능합니다.</p></div>
            </div>
        </div>
    </section>

    <section id="process" class="sf-section sf-gray">
        <div class="sf-wrap">
            <h2>제작 프로세스</h2>
            <p class="sf-lead">네 단계만 거치면 바로 랜딩 운영이 가능합니다.</p>
            <div class="sf-grid sf-grid-4">
                <div class="sf-card sf-step"><div class="num">01</div><strong>업종 선택</strong><p>필요한 업종을 먼저 선택합니다.</p></div>
                <div class="sf-card sf-step"><div class="num">02</div><strong>정보 입력</strong><p>업체 정보와 문구를 입력합니다.</p></div>
                <div class="sf-card sf-step"><div class="num">03</div><strong>AI 생성</strong><p>프롬프트 기반 문구와 페이지를 생성합니다.</p></div>
                <div class="sf-card sf-step"><div class="num">04</div><strong>즉시 배포</strong><p>랜딩 주소를 바로 연결해 운영할 수 있습니다.</p></div>
            </div>
        </div>
    </section>

    <section id="samples" class="sf-section sf-samples-section">
        <div class="sf-wrap">
            <div class="sf-section-head">
                <span class="sf-eyebrow">Sample Landing Pages</span>
                <h2>업종별 샘플 랜딩페이지</h2>
                <p class="sf-lead">누수·방수·청소·병원·요양원까지 전환 구조를 미리 확인해보세요.</p>
            </div>

            <div class="sf-sample-grid">
                <a href="/page/landing.php?id=1" class="sf-sample-card">
                    <span class="sf-sample-icon"><i class="fa-solid fa-droplet"></i></span>
                    <strong>누수</strong>
                    <em>누수탐지 · 긴급출동</em>
                </a>

                <a href="/page/landing.php?id=2" class="sf-sample-card">
                    <span class="sf-sample-icon"><i class="fa-solid fa-umbrella"></i></span>
                    <strong>방수</strong>
                    <em>옥상방수 · 외벽방수</em>
                </a>

                <a href="/page/landing.php?id=3" class="sf-sample-card">
                    <span class="sf-sample-icon"><i class="fa-solid fa-broom"></i></span>
                    <strong>청소</strong>
                    <em>입주청소 · 사무실청소</em>
                </a>

                <a href="/page/landing.php?id=4" class="sf-sample-card">
                    <span class="sf-sample-icon"><i class="fa-solid fa-hospital"></i></span>
                    <strong>병원</strong>
                    <em>진료안내 · 예약문의</em>
                </a>

                <a href="/page/landing.php?id=5" class="sf-sample-card">
                    <span class="sf-sample-icon"><i class="fa-solid fa-house-medical"></i></span>
                    <strong>요양원</strong>
                    <em>입소상담 · 시설안내</em>
                </a>

                <a href="/page/landing.php?id=6" class="sf-sample-card">
                    <span class="sf-sample-icon"><i class="fa-solid fa-scale-balanced"></i></span>
                    <strong>법률</strong>
                    <em>상담예약 · 사건문의</em>
                </a>

                <a href="/page/landing.php?id=7" class="sf-sample-card">
                    <span class="sf-sample-icon"><i class="fa-solid fa-paint-roller"></i></span>
                    <strong>인테리어</strong>
                    <em>견적문의 · 시공사례</em>
                </a>

                <a href="/page/landing.php?id=8" class="sf-sample-card">
                    <span class="sf-sample-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></span>
                    <strong>기타 업종</strong>
                    <em>맞춤 랜딩 제작</em>
                </a>
            </div>
        </div>
    </section>

    <section class="sf-section sf-gray sf-review-section">
        <div class="sf-wrap">
            <h2>고객 후기</h2>
            <p class="sf-lead">실제 운영 관점에서 전환 흐름을 높이는 구조로 설계합니다.</p>
            <div class="swiper sfReviewSwiper">
                <div class="swiper-wrapper">
                    <div class="swiper-slide">
                        <div class="sf-card sf-review"><div class="stars">★★★★★</div><strong>문의가 바로 들어오기 시작했어요</strong><p>랜딩 문구가 명확해서 광고 효율이 좋아졌습니다.</p></div>
                    </div>
                    <div class="swiper-slide">
                        <div class="sf-card sf-review"><div class="stars">★★★★★</div><strong>관리자에서 수정이 쉬워요</strong><p>업종과 문구를 바꿔도 바로 반영돼서 편합니다.</p></div>
                    </div>
                    <div class="swiper-slide">
                        <div class="sf-card sf-review"><div class="stars">★★★★★</div><strong>모바일에서도 보기 좋습니다</strong><p>상담 버튼이 명확해서 전환율이 올랐습니다.</p></div>
                    </div>
                    <div class="swiper-slide">
                        <div class="sf-card sf-review"><div class="stars">★★★★★</div><strong>광고 연결이 쉬웠습니다</strong><p>샘플부터 운영까지 흐름이 잘 잡혀 있습니다.</p></div>
                    </div>
                    <div class="swiper-slide">
                        <div class="sf-card sf-review"><div class="stars">★★★★★</div><strong>랜딩 생성 속도가 빠릅니다</strong><p>업무 시간을 크게 줄일 수 있었습니다.</p></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="faq" class="sf-section sf-faq-section">
        <div class="sf-wrap">
            <h2>자주 묻는 질문</h2>
            <p class="sf-lead">자주 묻는 질문을 빠르게 확인하세요.</p>
            <div class="sf-faq">
                <details open><summary>Q. 제작기간은?</summary><p>A. 입력 내용이 준비되면 즉시 생성 가능합니다.</p></details>
                <details><summary>Q. 수정 가능한가요?</summary><p>A. 관리자 화면에서 언제든지 수정할 수 있습니다.</p></details>
                <details><summary>Q. 문의는 어디서 확인하나요?</summary><p>A. 문의관리 메뉴에서 확인할 수 있습니다.</p></details>
                <details><summary>Q. 모바일 지원되나요?</summary><p>A. 예. 모바일 기준으로도 최적화되어 출력됩니다.</p></details>
            </div>
        </div>
    </section>

    <section id="contact" class="sf-section sf-contact-premium">
        <div class="sf-wrap">
            <div class="sf-contact-box">
                <div class="sf-contact-copy">
                    <span class="sf-contact-badge">무료 상담 신청</span>
                    <h2>지금 바로 랜딩페이지를 만들어보세요</h2>
                    <p>
                        이름, 연락처, 업종만 입력해주시면<br>
                        ShowForm이 빠르게 상담 드리겠습니다.
                    </p>

                    <ul class="sf-contact-points">
                        <li><i class="fa-solid fa-check"></i> 업종별 랜딩페이지 구조 제안</li>
                        <li><i class="fa-solid fa-check"></i> 문의폼 · 고객관리 연동 가능</li>
                        <li><i class="fa-solid fa-check"></i> 모바일 반응형 기본 적용</li>
                    </ul>
                </div>

                <form class="sf-form-premium" method="post" action="/page/landing_inquiry_update.php">
                    <input type="text" name="name" placeholder="이름" required>
                    <input type="tel" name="hp" placeholder="연락처 (010-1234-5678)" required>
                    <input type="text" name="email" placeholder="업종" required>
                    <input type="hidden" name="landing_id" value="0">
                    <textarea name="memo" placeholder="문의내용" required></textarea>
                    <button type="submit" class="sf-submit-premium">
                        무료 상담 신청 <i class="fa-solid fa-arrow-right"></i>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <div class="sf-footer-cta">ShowForm AI 랜딩페이지 자동생성 플랫폼</div>
</div>

<?php include_once(G5_THEME_PATH.'/tail.php'); ?>