<?php
if (!defined('_GNUBOARD_')) exit;

$g5['title'] = 'ShowForm | AI 랜딩페이지 자동생성 플랫폼';
include_once(G5_THEME_PATH.'/head.php');
?>

<style>
.sf-page{font-family:'Arial','Noto Sans KR',sans-serif;color:#111;line-height:1.6;background:#fff}
.sf-wrap{max-width:1200px;margin:0 auto;padding:0 20px}
.sf-hero{position:relative;height:850px;overflow:hidden;background:#0f172a}
.sf-hero .swiper,.sf-hero .swiper-wrapper,.sf-hero .swiper-slide{height:100%}
.sf-hero-slide{position:relative;height:100%;display:flex;align-items:center;justify-content:center;text-align:center;color:#fff;background-size:cover;background-position:center}
.sf-hero-slide::before{content:"";position:absolute;inset:0;background:rgba(0,0,0,.45)}
.sf-hero-inner{position:relative;z-index:2;width:100%;padding:0 20px}
.sf-badge{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border:1px solid rgba(255,255,255,.25);border-radius:999px;background:rgba(255,255,255,.08);backdrop-filter:blur(10px);font-weight:700;margin-bottom:20px}
.sf-hero h1{margin:0 auto 18px;max-width:980px;font-size:58px;line-height:1.12;font-weight:900;letter-spacing:-2px;text-shadow:0 3px 12px rgba(0,0,0,.5)}
.sf-hero p{margin:0 auto 32px;max-width:780px;font-size:21px;color:#e5e7eb;text-shadow:0 3px 12px rgba(0,0,0,.5)}
.sf-actions{display:flex;gap:14px;justify-content:center;flex-wrap:wrap}
.sf-btn{display:inline-flex;align-items:center;justify-content:center;min-width:210px;height:56px;padding:0 28px;border-radius:50px;font-size:18px;font-weight:800;transition:.3s ease;box-shadow:0 12px 30px rgba(0,0,0,.15)}
.sf-btn:hover{transform:translateY(-5px);box-shadow:0 18px 36px rgba(0,0,0,.22)}
.sf-btn-primary{background:linear-gradient(135deg,#2563eb,#0ea5e9);color:#fff}
.sf-btn-line{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.35);color:#fff}
.sf-indicator{position:absolute;left:50%;bottom:30px;transform:translateX(-50%);z-index:3;display:flex;gap:8px}
.sf-indicator span{width:12px;height:12px;border-radius:999px;background:rgba(255,255,255,.35)}
.sf-indicator span.active{background:#fff}
.sf-section{padding:100px 0}
.sf-section h2{margin:0 0 16px;font-size:40px;line-height:1.2;letter-spacing:-1px;font-weight:900;text-align:center}
.sf-lead{max-width:820px;margin:0 auto 42px;text-align:center;font-size:19px;color:#475569}
.sf-gray{background:#f8fafc}
.sf-grid{display:grid;gap:20px}
.sf-grid-2{grid-template-columns:repeat(2,1fr)}
.sf-grid-3{grid-template-columns:repeat(3,1fr)}
.sf-grid-4{grid-template-columns:repeat(4,1fr)}
.sf-card{padding:30px 24px;border:1px solid rgba(226,232,240,.9);border-radius:20px;background:rgba(255,255,255,.72);backdrop-filter:blur(10px);box-shadow:0 10px 30px rgba(15,23,42,.06);transition:.3s ease}
.sf-card:hover{transform:translateY(-5px);box-shadow:0 18px 36px rgba(15,23,42,.12)}
.sf-card strong{display:block;margin-bottom:10px;font-size:22px;color:#0f172a}
.sf-card p{margin:0;color:#64748b}
.sf-step{display:flex;flex-direction:column;align-items:center;text-align:center;gap:10px}
.sf-step .num{width:66px;height:66px;border-radius:22px;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#1d4ed8,#2563eb);color:#fff;font-size:22px;font-weight:900}
.sf-tags{display:flex;flex-wrap:wrap;gap:12px;justify-content:center}
.sf-tags span{padding:12px 20px;border-radius:999px;background:#fff;border:1px solid #cbd5e1;font-weight:700;color:#334155}
.sf-sample{display:block;padding:24px;border-radius:18px;background:#fff;border:1px solid #e2e8f0;text-align:center;color:#0f172a;box-shadow:0 10px 30px rgba(15,23,42,.06);transition:.3s ease}
.sf-sample:hover{transform:translateY(-5px);box-shadow:0 18px 36px rgba(15,23,42,.12)}
.sf-sample i{display:block;font-size:40px;margin-bottom:14px;color:#2563eb}
.sf-review{display:flex;flex-direction:column;gap:12px}
.sf-review .stars{color:#f59e0b;font-size:18px}
.sf-faq details{background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:18px 20px;box-shadow:0 8px 24px rgba(15,23,42,.05)}
.sf-faq details+details{margin-top:14px}
.sf-faq summary{cursor:pointer;font-weight:800;font-size:18px;color:#111827}
.sf-faq p{margin:12px 0 0;color:#475569}
.sf-contact{background:linear-gradient(135deg,#0f172a,#1d4ed8);color:#fff}
.sf-contact .sf-lead{color:#dbeafe}
.sf-form{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;max-width:900px;margin:0 auto}
.sf-form input,.sf-form select,.sf-form textarea{width:100%;padding:16px 18px;border-radius:16px;border:1px solid rgba(255,255,255,.15);background:rgba(255,255,255,.08);color:#fff;outline:none}
.sf-form textarea{grid-column:1/-1;min-height:120px;resize:vertical}
.sf-form ::placeholder{color:#cbd5e1}
.sf-form .full{grid-column:1/-1}
.sf-submit{grid-column:1/-1;height:58px;border:none;border-radius:999px;background:linear-gradient(135deg,#f97316,#ef4444);color:#fff;font-size:18px;font-weight:900;box-shadow:0 16px 30px rgba(239,68,68,.28);transition:.3s ease}
.sf-submit:hover{transform:translateY(-5px)}
.sf-footer-cta{padding:24px 0 110px;text-align:center;color:#64748b}
@media (max-width:1200px){.sf-grid-4{grid-template-columns:repeat(2,1fr)}.sf-hero h1{font-size:48px}.sf-hero p{font-size:18px}}
@media (max-width:768px){.sf-section{padding:70px 0}.sf-grid-2,.sf-grid-3,.sf-grid-4,.sf-form{grid-template-columns:1fr}.sf-hero{height:650px}.sf-hero h1{font-size:34px}.sf-hero p{font-size:16px}.sf-btn{width:100%}}
</style>

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
                            <a href="#contact" class="sf-btn sf-btn-primary">무료로 랜딩페이지 만들기</a>
                            <a href="/adm/landing/landing_form.php" class="sf-btn sf-btn-line">상담 문의하기</a>
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
                        <h1>광고부터 상담까지 자동화</h1>
                        <p>랜딩, 문의, AI 문구, 관리 화면을 하나의 흐름으로 연결합니다.</p>
                        <div class="sf-actions">
                            <a href="#process" class="sf-btn sf-btn-primary">제작 프로세스 보기</a>
                            <a href="#contact" class="sf-btn sf-btn-line">무료 상담 신청</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="sf-indicator"><span class="active"></span><span></span><span></span></div>
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

    <section id="samples" class="sf-section">
        <div class="sf-wrap">
            <h2>샘플 랜딩페이지</h2>
            <p class="sf-lead">업종별 전환 구조를 미리 확인해보세요.</p>
            <div class="sf-grid sf-grid-4">
                <a href="/page/landing.php?id=1" class="sf-sample"><i class="fa-solid fa-droplet"></i>누수</a>
                <a href="/page/landing.php?id=2" class="sf-sample"><i class="fa-solid fa-umbrella"></i>이사</a>
                <a href="/page/landing.php?id=3" class="sf-sample"><i class="fa-solid fa-broom"></i>청소</a>
                <a href="/page/landing.php?id=1" class="sf-sample"><i class="fa-solid fa-hospital"></i>병원</a>
                <a href="/page/landing.php?id=1" class="sf-sample"><i class="fa-solid fa-house"></i>요양원</a>
                <a href="/page/landing.php?id=1" class="sf-sample"><i class="fa-solid fa-scale-balanced"></i>법률</a>
                <a href="/page/landing.php?id=1" class="sf-sample"><i class="fa-solid fa-paint-roller"></i>인테리어</a>
                <a href="/page/landing.php?id=1" class="sf-sample"><i class="fa-solid fa-sparkles"></i>기타</a>
            </div>
        </div>
    </section>

    <section class="sf-section sf-gray">
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

    <section id="faq" class="sf-section">
        <div class="sf-wrap">
            <h2>FAQ</h2>
            <p class="sf-lead">자주 묻는 질문을 빠르게 확인하세요.</p>
            <div class="sf-faq">
                <details open><summary>Q. 제작기간은?</summary><p>A. 입력 내용이 준비되면 즉시 생성 가능합니다.</p></details>
                <details><summary>Q. 수정 가능한가요?</summary><p>A. 관리자 화면에서 언제든지 수정할 수 있습니다.</p></details>
                <details><summary>Q. 문의는 어디서 확인하나요?</summary><p>A. 문의관리 메뉴에서 확인할 수 있습니다.</p></details>
                <details><summary>Q. 모바일 지원되나요?</summary><p>A. 예. 모바일 기준으로도 최적화되어 출력됩니다.</p></details>
            </div>
        </div>
    </section>

    <section id="contact" class="sf-section sf-contact">
        <div class="sf-wrap">
            <h2>지금 바로 랜딩페이지를 만들어보세요</h2>
            <p class="sf-lead">이름, 연락처, 업종만 입력해주시면 빠르게 상담 드리겠습니다.</p>
            <form class="sf-form" method="post" action="/page/landing_inquiry_update.php">
                <input type="text" name="name" placeholder="이름" required>
                <input type="tel" name="tel" placeholder="연락처" required>
                <input type="text" name="category" placeholder="업종" required>
                <input type="hidden" name="landing_id" value="1">
                <textarea name="content" placeholder="문의내용" required></textarea>
                <button type="submit" class="sf-submit">무료 상담 신청</button>
            </form>
        </div>
    </section>

    <div class="sf-footer-cta">ShowForm AI 랜딩페이지 자동생성 플랫폼</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    if (window.Swiper) {
        new Swiper('.sfHeroSwiper', { loop:true, autoplay:{ delay:4000, disableOnInteraction:false }, speed:800 });
        new Swiper('.sfReviewSwiper', { loop:true, autoplay:{ delay:3000, disableOnInteraction:false }, slidesPerView:1, spaceBetween:20, breakpoints:{ 768:{ slidesPerView:2 }, 1200:{ slidesPerView:3 } } });
    }
});
</script>

<?php include_once(G5_THEME_PATH.'/tail.php'); ?>