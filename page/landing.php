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
if (!isset($row['is_active']) || (string)$row['is_active'] !== 'Y') {
    alert('현재 비공개 상태인 랜딩페이지입니다.', G5_URL);
}

$g5['title'] = !empty($row['company_name']) ? $row['company_name'] : 'ShowForm Landing';
$phone = isset($row['phone']) ? trim($row['phone']) : '';
$kakao_url = isset($row['kakao_url']) ? trim($row['kakao_url']) : '';
$main_image = isset($row['main_image']) ? trim($row['main_image']) : '';
$template_type = isset($row['template_type']) ? trim($row['template_type']) : 'service';
if (!in_array($template_type, array('service', 'hospital', 'local'), true)) {
    $template_type = 'service';
}
$theme_color = !empty($row['theme_color']) ? $row['theme_color'] : '#0f766e';
$area_name = isset($row['area_name']) ? $row['area_name'] : '';
$company_name = isset($row['company_name']) ? $row['company_name'] : '';
$intro_text = isset($row['intro_text']) ? $row['intro_text'] : '';
$main_copy = isset($row['main_copy']) ? trim($row['main_copy']) : '';
$sub_copy = isset($row['sub_copy']) ? trim($row['sub_copy']) : '';

$problem_text = isset($row['problem_text']) ? trim($row['problem_text']) : '';
$strength_text = isset($row['strength_text']) ? trim($row['strength_text']) : '';
$faq_text = isset($row['faq_text']) ? trim($row['faq_text']) : '';
$cta_text = isset($row['cta_text']) ? trim($row['cta_text']) : '';

// Preset fallback
$preset_file = G5_PATH . '/config/showform_preset.php';
if (is_file($preset_file)) {
    include_once($preset_file);
    $industry = isset($row['industry']) ? trim($row['industry']) : '';
    if ($industry && isset($sf_presets[$industry])) {
        $preset = $sf_presets[$industry];
        if (empty($main_copy)) $main_copy = $preset['main_copy'];
        if (empty($sub_copy)) $sub_copy = $preset['sub_copy'];
        if (empty($problem_text)) $problem_text = $preset['problem_text'];
        if (empty($strength_text)) $strength_text = $preset['strength_text'];
        if (empty($faq_text)) $faq_text = $preset['faq_text'];
        if (empty($cta_text)) $cta_text = $preset['cta_text'];
    }
}

$hero_kicker = $area_name && $company_name ? $area_name . ' ' . $company_name : $company_name;
$phone_href = $phone ? 'tel:' . preg_replace('/[^0-9\+]/', '', $phone) : '#contact';
$contact_label = $cta_text ? $cta_text : ($template_type === 'hospital' ? '빠른 예약 상담하기' : ($template_type === 'local' ? '무료 상담받기' : '지금 무료 상담받기'));

function sf_extract_youtube_id($url)
{
    $url = trim((string)$url);
    if ($url === '') return '';
    $patterns = array('~(?:https?://)?(?:www\.)?youtu\.be/([A-Za-z0-9_-]{11})~i','~(?:https?://)?(?:www\.)?youtube\.com/watch\?v=([A-Za-z0-9_-]{11})~i','~(?:https?://)?(?:www\.)?youtube\.com/embed/([A-Za-z0-9_-]{11})~i','~(?:https?://)?(?:www\.)?youtube\.com/shorts/([A-Za-z0-9_-]{11})~i');
    foreach ($patterns as $pattern) { if (preg_match($pattern, $url, $m)) return $m[1]; }
    if (preg_match('~[?&]v=([A-Za-z0-9_-]{11})~i', $url, $m)) return $m[1];
    return '';
}

include_once(G5_THEME_PATH . '/head.php');
?>
<style>
:root { --sf-primary: <?php echo get_text($theme_color); ?>; --sf-bg:#f7f7fb; --sf-text:#0f172a; --sf-muted:#64748b; --sf-card:#fff; --sf-border:#e2e8f0; }
body { background:var(--sf-bg); color:var(--sf-text); overflow-x:hidden; }
.sf-wrap { width:100%; overflow-x:hidden; }
.sf-container { width:min(1120px, calc(100% - 24px)); margin:0 auto; }
.sf-section { padding:28px 0; }
.sf-hero { background:linear-gradient(135deg, rgba(15,23,42,.94), rgba(15,118,110,.88)); color:#fff; padding:56px 0 34px; position:relative; overflow:hidden; }
.sf-hero::after { content:''; position:absolute; inset:auto -10% -40% auto; width:280px; height:280px; border-radius:50%; background:rgba(255,255,255,.08); }
.sf-kicker { display:inline-flex; padding:8px 14px; border-radius:999px; background:rgba(255,255,255,.12); font-size:14px; font-weight:700; margin-bottom:16px; }
.sf-hero h1 { margin:0; font-size:clamp(28px, 4vw, 48px); line-height:1.15; letter-spacing:-0.03em; }
.sf-hero p { margin:14px 0 0; color:rgba(255,255,255,.88); line-height:1.7; font-size:16px; }
.sf-card { background:var(--sf-card); border:1px solid var(--sf-border); border-radius:20px; padding:22px; box-shadow:0 10px 30px rgba(15,23,42,.05); }
.sf-btn { display:inline-flex; align-items:center; justify-content:center; min-height:48px; padding:0 18px; border-radius:14px; text-decoration:none; font-weight:800; transition:transform .15s ease; }
.sf-btn:hover { transform:translateY(-1px); }
.sf-btn-primary { background:#fff; color:#0f172a; }
.sf-btn-dark { background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.22); }
.sf-btn-solid { background:var(--sf-primary); color:#fff; }
.sf-hero-actions, .sf-cta-row { display:flex; gap:12px; flex-wrap:wrap; margin-top:22px; }
.sf-grid-2 { display:grid; grid-template-columns:1.1fr .9fr; gap:18px; }
.sf-image { border-radius:18px; overflow:hidden; background:#e2e8f0; min-height:280px; display:flex; align-items:center; justify-content:center; }
.sf-image img { width:100%; height:100%; object-fit:cover; display:block; }
.sf-image .placeholder { color:#94a3b8; font-weight:700; }
.sf-section-title { margin:0 0 14px; font-size:24px; letter-spacing:-.02em; }
.sf-section-desc { margin:0 0 18px; color:var(--sf-muted); line-height:1.7; }
.sf-pill-grid, .sf-review-grid, .sf-gallery-grid, .sf-video-grid, .sf-notice-grid, .sf-step-grid { display:grid; gap:12px; }
.sf-pill-grid { grid-template-columns:repeat(4,1fr); }
.sf-review-grid { grid-template-columns:repeat(3,1fr); }
.sf-gallery-grid { grid-template-columns:repeat(3,1fr); }
.sf-video-grid { grid-template-columns:repeat(2,1fr); }
.sf-step-grid { grid-template-columns:repeat(2,1fr); }
.sf-notice-grid { grid-template-columns:1fr; }
.sf-pill, .sf-step, .sf-review, .sf-notice { background:#f8fafc; border:1px solid var(--sf-border); border-radius:16px; padding:16px; line-height:1.7; }
.sf-step b, .sf-notice-title { display:block; margin-bottom:8px; color:var(--sf-primary); font-weight:800; }
.sf-gallery-item, .sf-video-card { border-radius:18px; overflow:hidden; background:#fff; border:1px solid var(--sf-border); box-shadow:0 8px 24px rgba(15,23,42,.04); }
.sf-gallery-item img { width:100%; height:220px; object-fit:cover; display:block; }
.sf-gallery-body, .sf-video-body { padding:14px; }
.sf-gallery-title, .sf-video-title { font-weight:800; margin-bottom:6px; }
.sf-gallery-desc, .sf-video-desc, .sf-notice-content { color:var(--sf-muted); line-height:1.6; font-size:14px; }
.sf-video-frame { position:relative; padding-top:56.25%; background:#0f172a; }
.sf-video-frame iframe { position:absolute; inset:0; width:100%; height:100%; border:0; }
.sf-form .sf-form-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; }
.sf-form label { display:block; }
.sf-form span { display:block; margin-bottom:6px; font-weight:700; }
.sf-form input, .sf-form textarea { width:100%; box-sizing:border-box; border:1px solid var(--sf-border); border-radius:14px; padding:14px; background:#fff; }
.sf-form .sf-full { grid-column:1/-1; }
.sf-mobile-bar { position:fixed; left:0; right:0; bottom:0; z-index:99; display:grid; grid-template-columns:1fr 1fr <?php echo $kakao_url ? '1fr' : '0'; ?>; gap:8px; padding:10px 12px; background:rgba(255,255,255,.94); backdrop-filter:blur(10px); border-top:1px solid var(--sf-border); }
.sf-mobile-bar a { text-align:center; border-radius:14px; padding:14px 10px; font-weight:800; text-decoration:none; }
.sf-mobile-bar .tel { background:var(--sf-primary); color:#fff; }
.sf-mobile-bar .inquiry { background:#0f172a; color:#fff; }
.sf-mobile-bar .kakao { background:#f7e600; color:#111827; }
.sf-spacer { height:88px; }
@media (max-width: 1200px) { .sf-container { width:min(1120px, calc(100% - 20px)); } }
@media (max-width: 768px) { .sf-grid-2, .sf-pill-grid, .sf-review-grid, .sf-gallery-grid, .sf-video-grid, .sf-step-grid, .sf-form .sf-form-grid { grid-template-columns:1fr; } .sf-section { padding:22px 0; } }
@media (max-width: 480px) { .sf-hero { padding:44px 0 28px; } .sf-hero h1 { font-size:28px; } .sf-card { padding:18px; } }
@media (max-width: 360px) { .sf-container { width:calc(100% - 16px); } .sf-btn { width:100%; } }
</style>
<div class="sf-wrap">
<?php
$template_file = __DIR__ . '/templates/' . $template_type . '.php';
if (!is_file($template_file)) {
    $template_file = __DIR__ . '/templates/service.php';
}
include $template_file;
?>
<?php if ($phone) { ?>
<div class="sf-mobile-bar">
    <a class="tel" href="<?php echo $phone_href; ?>">전화하기</a>
    <a class="inquiry" href="#contact"><?php echo get_text($contact_label); ?></a>
    <?php if ($kakao_url) { ?><a class="kakao" href="<?php echo get_text($kakao_url); ?>" target="_blank" rel="noopener">카카오</a><?php } ?>
</div>
<?php } ?>
<div class="sf-spacer"></div>
</div>
<?php include_once(G5_THEME_PATH . '/tail.php'); ?>