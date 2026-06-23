<?php
include_once('./_common.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) {
    alert('잘못된 접근입니다.', G5_URL);
}

$table = G5_TABLE_PREFIX . 'landing_pages';
$row = sql_fetch(" select * from {$table} where id = '{$id}' limit 1 ");

if (!$row) {
    alert('랜딩페이지를 찾을 수 없습니다.', G5_URL);
}

if (isset($row['is_active']) && (string)$row['is_active'] !== 'Y') {
    alert('현재 비공개 상태인 랜딩페이지입니다.', G5_URL);
}

$g5['title'] = !empty($row['company_name']) ? $row['company_name'] : 'ShowForm Landing';

$phone = isset($row['phone']) ? trim($row['phone']) : '';
$kakao_url = isset($row['kakao_url']) ? trim($row['kakao_url']) : '';
$main_image = isset($row['main_image']) ? trim($row['main_image']) : '';
$template_type = isset($row['template_type']) ? trim($row['template_type']) : 'service';
$theme_color = !empty($row['theme_color']) ? $row['theme_color'] : '#0f766e';
$area_name = isset($row['area_name']) ? $row['area_name'] : '';
$company_name = isset($row['company_name']) ? $row['company_name'] : '';
$intro_text = isset($row['intro_text']) ? $row['intro_text'] : '';
$main_copy = isset($row['main_copy']) ? $row['main_copy'] : '';
$sub_copy = isset($row['sub_copy']) ? $row['sub_copy'] : '';
$privacy_text = '개인정보 수집 및 이용에 동의합니다.';

$hero_kicker = $area_name && $company_name ? $area_name . ' ' . $company_name : $company_name;
$phone_href = $phone ? 'tel:' . preg_replace('/[^0-9\+]/', '', $phone) : '#contact';
$contact_label = $template_type === 'hospital' ? '빠른 예약 상담하기' : ($template_type === 'local' ? '무료 상담받기' : '지금 무료 상담받기');

include_once(G5_THEME_PATH . '/head.php');
?>
<style>
:root {
    --sf-primary: <?php echo get_text($theme_color); ?>;
    --sf-bg: #f7f7fb;
    --sf-text: #0f172a;
    --sf-muted: #64748b;
    --sf-card: #ffffff;
    --sf-border: #e2e8f0;
}
body { background: var(--sf-bg); color: var(--sf-text); }
.sf-wrap { width:100%; }
.sf-container { width:min(1120px, calc(100% - 32px)); margin:0 auto; }
.sf-hero { background: linear-gradient(135deg, rgba(15,23,42,.94), rgba(15,118,110,.88)); color:#fff; padding:56px 0 34px; position:relative; overflow:hidden; }
.sf-hero::after { content:''; position:absolute; inset:auto -10% -40% auto; width:280px; height:280px; border-radius:50%; background:rgba(255,255,255,.08); }
.sf-kicker { display:inline-flex; padding:8px 14px; border-radius:999px; background:rgba(255,255,255,.12); font-size:14px; font-weight:700; margin-bottom:16px; }
.sf-hero h1 { margin:0; font-size:clamp(28px, 4vw, 48px); line-height:1.15; letter-spacing:-0.03em; }
.sf-hero p { margin:14px 0 0; color:rgba(255,255,255,.88); line-height:1.7; font-size:16px; }
.sf-hero-actions, .sf-cta-row { display:flex; gap:12px; flex-wrap:wrap; margin-top:22px; }
.sf-btn { display:inline-flex; align-items:center; justify-content:center; min-height:48px; padding:0 18px; border-radius:14px; text-decoration:none; font-weight:800; transition:transform .15s ease, opacity .15s ease; }
.sf-btn:hover { transform:translateY(-1px); }
.sf-btn-primary { background:#fff; color:#0f172a; }
.sf-btn-dark { background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.22); }
.sf-btn-solid { background:var(--sf-primary); color:#fff; }
.sf-section { padding:28px 0; }
.sf-card { background:var(--sf-card); border:1px solid var(--sf-border); border-radius:20px; padding:22px; box-shadow:0 10px 30px rgba(15,23,42,.05); }
.sf-grid-2 { display:grid; grid-template-columns:1.1fr .9fr; gap:18px; }
@media (max-width: 900px) { .sf-grid-2 { grid-template-columns:1fr; } }
.sf-image { border-radius:18px; overflow:hidden; background:#e2e8f0; min-height:280px; display:flex; align-items:center; justify-content:center; }
.sf-image img { width:100%; height:100%; object-fit:cover; display:block; }
.sf-image .placeholder { color:#94a3b8; font-weight:700; }
.sf-section-title { margin:0 0 14px; font-size:24px; letter-spacing:-0.02em; }
.sf-section-desc { margin:0 0 18px; color:var(--sf-muted); line-height:1.7; }
.sf-pill-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; }
@media (max-width: 900px) { .sf-pill-grid { grid-template-columns:repeat(2, 1fr); } }
@media (max-width: 520px) { .sf-pill-grid { grid-template-columns:1fr; } }
.sf-pill { background:#f8fafc; border:1px solid var(--sf-border); border-radius:16px; padding:16px; font-weight:700; color:#0f172a; }
.sf-step-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:12px; }
@media (max-width: 640px) { .sf-step-grid { grid-template-columns:1fr; } }
.sf-step { padding:16px; border-radius:16px; background:#f8fafc; border:1px solid var(--sf-border); }
.sf-step b { display:block; margin-bottom:8px; color:var(--sf-primary); }
.sf-faq details { border:1px solid var(--sf-border); border-radius:16px; background:#fff; padding:14px 16px; }
.sf-faq details + details { margin-top:10px; }
.sf-faq summary { cursor:pointer; font-weight:800; }
.sf-faq p { margin:10px 0 0; color:var(--sf-muted); line-height:1.7; }
.sf-form .sf-form-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:14px; }
@media (max-width: 640px) { .sf-form .sf-form-grid { grid-template-columns:1fr; } }
.sf-form label { display:block; }
.sf-form span { display:block; margin-bottom:6px; font-weight:700; }
.sf-form input, .sf-form textarea { width:100%; box-sizing:border-box; border:1px solid var(--sf-border); border-radius:14px; padding:14px; background:#fff; }
.sf-form .sf-full { grid-column:1/-1; }
.sf-form-note { margin-top:10px; color:var(--sf-muted); font-size:14px; }
.sf-mobile-bar { position:fixed; left:0; right:0; bottom:0; z-index:99; display:grid; grid-template-columns:1fr 1fr <?php echo $kakao_url ? '1fr' : '0'; ?>; gap:8px; padding:10px 12px; background:rgba(255,255,255,.94); backdrop-filter:blur(10px); border-top:1px solid var(--sf-border); }
.sf-mobile-bar a { text-align:center; border-radius:14px; padding:14px 10px; font-weight:800; text-decoration:none; }
.sf-mobile-bar .tel { background:var(--sf-primary); color:#fff; }
.sf-mobile-bar .inquiry { background:#0f172a; color:#fff; }
.sf-mobile-bar .kakao { background:#f7e600; color:#111827; }
.sf-spacer { height:88px; }
</style>

<div class="sf-wrap">
    <section class="sf-hero">
        <div class="sf-container">
            <div class="sf-kicker"><?php echo get_text($hero_kicker); ?></div>
            <h1><?php echo get_text($main_copy); ?></h1>
            <p><?php echo nl2br(get_text($sub_copy)); ?></p>
            <?php if (!empty($intro_text)) { ?><p><?php echo nl2br(get_text($intro_text)); ?></p><?php } ?>
            <div class="sf-hero-actions">
                <?php if ($phone) { ?><a class="sf-btn sf-btn-primary" href="<?php echo $phone_href; ?>">전화하기</a><?php } ?>
                <?php if ($kakao_url) { ?><a class="sf-btn sf-btn-dark" href="<?php echo get_text($kakao_url); ?>" target="_blank" rel="noopener">카카오상담</a><?php } ?>
                <a class="sf-btn sf-btn-solid" href="#contact"><?php echo get_text($contact_label); ?></a>
            </div>
        </div>
    </section>

    <main class="sf-container sf-section">
        <div class="sf-grid-2">
            <div class="sf-card">
                <h2 class="sf-section-title"><?php echo get_text($main_copy); ?></h2>
                <p class="sf-section-desc"><?php echo nl2br(get_text($sub_copy)); ?></p>
                <div class="sf-cta-row">
                    <?php if ($phone) { ?><a class="sf-btn sf-btn-solid" href="<?php echo $phone_href; ?>">전화 상담</a><?php } ?>
                    <?php if ($kakao_url) { ?><a class="sf-btn sf-btn-dark" style="background:#f7e600;color:#111827;border-color:#f7e600;" href="<?php echo get_text($kakao_url); ?>" target="_blank" rel="noopener">카카오 문의</a><?php } ?>
                    <a class="sf-btn sf-btn-primary" href="#contact">문의 남기기</a>
                </div>
            </div>
            <div class="sf-image">
                <?php if ($main_image) { ?><img src="<?php echo get_text($main_image); ?>" alt="<?php echo get_text($company_name); ?>"><?php } else { ?><div class="placeholder">대표 이미지 준비중</div><?php } ?>
            </div>
        </div>
    </main>

    <section class="sf-container sf-section">
        <div class="sf-card">
            <h2 class="sf-section-title">주요 강점</h2>
            <div class="sf-pill-grid">
                <div class="sf-pill">빠른 상담 응대</div>
                <div class="sf-pill">지역 맞춤 안내</div>
                <div class="sf-pill">전환 중심 랜딩</div>
                <div class="sf-pill">모바일 최적화</div>
            </div>
        </div>
    </section>

    <section class="sf-container sf-section">
        <div class="sf-card">
            <h2 class="sf-section-title">업종별 안내</h2>
            <div class="sf-step-grid">
                <?php if ($template_type === 'hospital') { ?>
                <div class="sf-step"><b>진료 안내</b>환자 중심 상담 흐름을 구성합니다.</div>
                <div class="sf-step"><b>예약 문의</b>빠른 예약과 전화 연결을 강조합니다.</div>
                <div class="sf-step"><b>시설 소개</b>신뢰를 높이는 정보 배치를 준비합니다.</div>
                <div class="sf-step"><b>후기 영역</b>고객 신뢰를 확보하는 구조로 확장합니다.</div>
                <?php } elseif ($template_type === 'local') { ?>
                <div class="sf-step"><b>지역 안내</b><?php echo get_text($area_name); ?>에서 쉽게 찾는 구조입니다.</div>
                <div class="sf-step"><b>방문 유도</b>상담과 방문 예약을 강조합니다.</div>
                <div class="sf-step"><b>메뉴/서비스</b>핵심 서비스를 빠르게 안내합니다.</div>
                <div class="sf-step"><b>문의 연결</b>전화와 카카오 문의를 분리 노출합니다.</div>
                <?php } else { ?>
                <div class="sf-step"><b>긴급 문의</b>빠른 접수가 필요한 상황을 강조합니다.</div>
                <div class="sf-step"><b>현장 대응</b>현장 출동과 진단 프로세스를 보여줍니다.</div>
                <div class="sf-step"><b>서비스 소개</b>업종별 핵심 해결책을 배치합니다.</div>
                <div class="sf-step"><b>신뢰 확보</b>문의 전환을 높이는 구조를 유지합니다.</div>
                <?php } ?>
            </div>
        </div>
    </section>

    <section class="sf-container sf-section sf-faq">
        <div class="sf-card">
            <h2 class="sf-section-title">FAQ</h2>
            <details open>
                <summary>문의는 어떻게 접수되나요?</summary>
                <p>아래 문의폼을 통해 접수되며, Stage 04에서 DB 저장을 연결할 예정입니다.</p>
            </details>
            <details>
                <summary>카카오 상담 버튼은 항상 보이나요?</summary>
                <p>아니요. `kakao_url` 값이 있을 때만 노출됩니다.</p>
            </details>
            <details>
                <summary>모바일에서도 잘 보이나요?</summary>
                <p>모바일 우선 기준으로 구성되어 있습니다.</p>
            </details>
        </div>
    </section>

    <section class="sf-container sf-section" id="contact">
        <div class="sf-card sf-form">
            <h2 class="sf-section-title">문의하기</h2>
            <p class="sf-section-desc">Stage 04에서 실제 문의 저장이 연결됩니다. 현재는 화면 구조만 준비되어 있습니다.</p>
            <form method="post" action="/page/landing_inquiry_update.php">
                <input type="hidden" name="landing_id" value="<?php echo (int)$row['id']; ?>">
                <div class="sf-form-grid">
                    <label><span>이름</span><input type="text" name="name" placeholder="이름" required></label>
                    <label><span>연락처</span><input type="tel" name="phone" placeholder="연락처" required></label>
                    <label class="sf-full"><span>문의내용</span><textarea name="message" rows="5" placeholder="문의 내용을 입력하세요"></textarea></label>
                    <label class="sf-full"><span><input type="checkbox" name="agree" value="1" required> <?php echo get_text($privacy_text); ?></span></label>
                </div>
                <button type="submit" class="sf-btn sf-btn-solid" style="margin-top:14px;">문의 보내기</button>
            </form>
        </div>
    </section>

    <div class="sf-spacer"></div>
</div>

<?php if ($phone) { ?>
<div class="sf-mobile-bar">
    <a class="tel" href="<?php echo $phone_href; ?>">전화하기</a>
    <a class="inquiry" href="#contact"><?php echo get_text($contact_label); ?></a>
    <?php if ($kakao_url) { ?><a class="kakao" href="<?php echo get_text($kakao_url); ?>" target="_blank" rel="noopener">카카오</a><?php } ?>
</div>
<?php } ?>

<?php include_once(G5_THEME_PATH . '/tail.php'); ?>