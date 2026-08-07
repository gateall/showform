<?php
header('Content-Type:text/html; charset=UTF-8');
include_once('./_common.php');

auth_check_menu($auth, '900100', 'r');

$g5['title'] = '랜딩 생성/수정';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$table = G5_TABLE_PREFIX . 'landing_pages';
$row = array();

if ($id) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' limit 1 ");
    if (!$row) {
        alert('데이터를 찾을 수 없습니다.', './landing_list.php');
    }
    $g5['title'] = '랜딩 수정';
}

$defaults = array(
    'template_type' => 'service',
    'industry' => '',
    'company_name' => '',
    'ceo_name' => '',
    'phone' => '',
    'kakao_url' => '',
    'address' => '',
    'area_name' => '',
    'intro_text' => '',
    'main_copy' => '',
    'sub_copy' => '',
    'problem_text' => '',
    'strength_text' => '',
    'faq_text' => '',
    'cta_text' => '',
    'theme_color' => '#0f766e',
    'main_image' => '',
    'is_active' => 'Y'
);

foreach ($defaults as $key => $value) {
    if (!isset($row[$key])) {
        $row[$key] = $value;
    }
}

include_once(dirname(__FILE__) . '/../layout/header.php');
?>
<link rel="stylesheet" href="<?php echo SF_MANAGER_URL; ?>/landing/landing_admin.css">
<style>
/* 3단 레이아웃 */
.sf-3col-wrapper {
    display: grid;
    grid-template-columns: 200px 1fr 250px;
    gap: 16px;
    margin-top: 20px;
}
.sf-left-sidebar {
    background:#f9fafb;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:12px;
    height: fit-content;
}
.sf-main-content { /* 기존 폼 레이아웃 유지 */ }
.sf-right-panel {
    background:#f9fafb;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:12px;
    height: fit-content;
}
.sf-form-layout { display:grid; gap:16px; }
.sf-form-section { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:18px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
.sf-form-section h2 { margin:0 0 6px; font-size:18px; color:#0f172a; }
.sf-form-section .section-desc { margin:0 0 16px; color:#64748b; line-height:1.6; }
.sf-form-grid { display:grid; grid-template-columns:repeat(2, minmax(0, 1fr)); gap:14px; }
.sf-field label { display:block; margin-bottom:6px; font-weight:800; color:#334155; }
.sf-field input, .sf-field select, .sf-field textarea { width:100%; box-sizing:border-box; }
.sf-field textarea { min-height:120px; }
.sf-help { margin-top:6px; font-size:12px; color:#64748b; line-height:1.5; }
.sf-required { color:#dc2626; margin-left:4px; }
.sf-preview { display:flex; gap:12px; align-items:center; flex-wrap:wrap; }
.sf-preview img { width:96px; height:96px; object-fit:cover; border-radius:12px; border:1px solid #e5e7eb; background:#f8fafc; }
.sf-sticky-actions { display:flex; flex-direction:column; gap:8px; }
.sf-btn-primary { background:#4f46e5; border-color:#4338ca; color:#fff; width:100%; }
@media (max-width: 768px) {
    .sf-3col-wrapper { grid-template-columns:1fr; }
    .sf-sticky-actions { justify-content:stretch; }
    .sf-sticky-actions .btn, .sf-sticky-actions .btn_submit { flex:1; }
}
</style>

<div class="sf-3col-wrapper">
    <!-- 좌측 네비게이션 (플레이스홀더) -->
    <aside class="sf-left-sidebar">
        <h3 style="margin-top:0; font-size:1rem; color:#0f172a;">메뉴</h3>
        <ul style="list-style:none; padding:0; margin:0;">
            <li><a href="<?php echo SF_MANAGER_URL; ?>/landing/landing_list.php" style="color:#334155; text-decoration:none;">목록</a></li>
            <li><a href="<?php echo SF_MANAGER_URL; ?>/landing/landing_form.php" style="color:#334155; text-decoration:none;">새 랜딩</a></li>
        </ul>
    </aside>

    <!-- 중앙 폼 영역 -->
    <div class="sf-main-content">
        <form name="flandingform" method="post" action="<?php echo SF_MANAGER_URL; ?>/landing/landing_update.php" enctype="multipart/form-data">
            <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
            <div class="sf-form-layout">
                <section class="sf-form-section">
                    <h2>기본정보</h2>
                    <p class="section-desc">랜딩의 기본 정보를 정확히 입력해주세요.</p>
                    <div class="sf-form-grid">
                        <div class="sf-field">
                            <label>템플릿 유형 <span class="sf-required">*</span></label>
                            <select name="template_type">
                                <option value="service" <?php echo $row['template_type'] === 'service' ? 'selected' : ''; ?>>일반 서비스</option>
                                <option value="hospital" <?php echo $row['template_type'] === 'hospital' ? 'selected' : ''; ?>>병의원</option>
                                <option value="local" <?php echo $row['template_type'] === 'local' ? 'selected' : ''; ?>>지역 서비스</option>
                            </select>
                            <div class="sf-help">업종 특성에 맞는 템플릿을 선택하세요.</div>
                        </div>
                        <div class="sf-field">
                            <label>업종 <span class="sf-required">*</span> <button type="button" class="btn btn_03 btn_xs sf-ai-btn" data-action="all" onclick="sfAiGenerate('all', this)">전체 AI 생성</button></label>
                            <input type="text" name="industry" id="industry" value="<?php echo get_text($row['industry']); ?>" class="frm_input" placeholder="예: 치과, 식당, 학원">
                            <div class="sf-help">AI 자동 생성을 위해 정확한 업종을 입력하세요.</div>
                        </div>
                        <div class="sf-field">
                            <label>회사명 <span class="sf-required">*</span></label>
                            <input type="text" name="company_name" value="<?php echo get_text($row['company_name']); ?>" class="frm_input">
                            <div class="sf-help">실제 노출될 상호 또는 업체명을 입력하세요.</div>
                        </div>
                        <div class="sf-field">
                            <label>대표자</label>
                            <input type="text" name="ceo_name" value="<?php echo get_text($row['ceo_name']); ?>" class="frm_input">
                            <div class="sf-help">하단에 노출하기 위한 대표자 이름을 입력하세요.</div>
                        </div>
                        <div class="sf-field">
                            <label>연락처 <span class="sf-required">*</span></label>
                            <input type="text" name="phone" value="<?php echo get_text($row['phone']); ?>" class="frm_input">
                            <div class="sf-help">고객 문의를 받을 CTA용 연락처입니다.</div>
                        </div>
                        <div class="sf-field">
                            <label>카카오톡 URL</label>
                            <input type="text" name="kakao_url" value="<?php echo get_text($row['kakao_url']); ?>" class="frm_input">
                            <div class="sf-help">채팅 상담을 위한 오픈채팅 링크를 입력하세요.</div>
                        </div>
                        <div class="sf-field">
                            <label>주소</label>
                            <input type="text" name="address" value="<?php echo get_text($row['address']); ?>" class="frm_input">
                            <div class="sf-help">오시는길, 하단 주소에 표시됩니다.</div>
                        </div>
                        <div class="sf-field">
                            <label>지역명</label>
                            <input type="text" name="area_name" value="<?php echo get_text($row['area_name']); ?>" class="frm_input">
                            <div class="sf-help">AI 카피를 작성할 타겟지역을 입력하세요.</div>
                        </div>
                    </div>
                </section>

                <section class="sf-form-section">
                    <h2>콘텐츠 설정</h2>
                    <p class="section-desc">페이지 구성요소를 직접 또는 AI 생성으로 채워주세요.</p>
                    <div class="sf-form-grid">
                        <div class="sf-field" style="grid-column:1/-1;">
                            <label>소개글</label>
                            <textarea name="intro_text" rows="4" class="frm_input" style="width:100%;"><?php echo get_text($row['intro_text']); ?></textarea>
                            <div class="sf-help">자사의 특징, 주요 업무, 핵심 장점 등 상세 정보를 입력하세요.</div>
                        </div>
                        <div class="sf-field">
                            <label>메인 카피 <button type="button" class="btn btn_03 btn_xs sf-ai-btn" data-action="main" onclick="sfAiGenerate('main', this)">AI 메인카피 생성</button></label>
                            <input type="text" name="main_copy" value="<?php echo get_text($row['main_copy']); ?>" class="frm_input">
                            <div class="sf-help">고객의 시선을 끄는 첫 문구입니다.</div>
                        </div>
                        <div class="sf-field">
                            <label>서브 카피</label>
                            <input type="text" name="sub_copy" value="<?php echo get_text($row['sub_copy']); ?>" class="frm_input">
                            <div class="sf-help">메인 카피를 뒷받침할 짧은 문구입니다.</div>
                        </div>
                        <div class="sf-field" style="grid-column:1/-1;">
                            <label>문제점/공감 텍스트 <button type="button" class="btn btn_03 btn_xs sf-ai-btn" data-action="problem" onclick="sfAiGenerate('problem', this)">AI 공감문구 생성</button></label>
                            <textarea name="problem_text" rows="4" class="frm_input" style="width:100%;"><?php echo get_text($row['problem_text']); ?></textarea>
                            <div class="sf-help">고객의 문제를 짚어주고 해결책 제안을 준비하는 문단입니다.</div>
                        </div>
                        <div class="sf-field" style="grid-column:1/-1;">
                            <label>장점및 해결책 <button type="button" class="btn btn_03 btn_xs sf-ai-btn" data-action="strength" onclick="sfAiGenerate('strength', this)">AI 장점 생성</button></label>
                            <textarea name="strength_text" rows="4" class="frm_input" style="width:100%;"><?php echo get_text($row['strength_text']); ?></textarea>
                            <div class="sf-help">실적, 강점, 혜택, 해결방법 등 우리 서비스 장점을 입력하세요.</div>
                        </div>
                        <div class="sf-field" style="grid-column:1/-1;">
                            <label>FAQ 텍스트 <button type="button" class="btn btn_03 btn_xs sf-ai-btn" data-action="faq" onclick="sfAiGenerate('faq', this)">AI FAQ 생성</button></label>
                            <textarea name="faq_text" rows="5" class="frm_input" style="width:100%;"><?php echo get_text($row['faq_text']); ?></textarea>
                            <div class="sf-help">고객이 자주 묻는 질문과 답변을 질의응답 형 텍스트로 적어주세요.</div>
                        </div>
                        <div class="sf-field">
                            <label>CTA 버튼 <button type="button" class="btn btn_03 btn_xs sf-ai-btn" data-action="cta" onclick="sfAiGenerate('cta', this)">AI CTA 생성</button></label>
                            <input type="text" name="cta_text" value="<?php echo get_text($row['cta_text']); ?>" class="frm_input" placeholder="예: 무료 상담하기">
                            <div class="sf-help">상담 신청 버튼에 사용할 짧은 문구입니다.</div>
                        </div>
                    </div>
                </section>

                <section class="sf-form-section">
                    <h2>디자인 설정</h2>
                    <p class="section-desc">랜딩 페이지의 시각 요소들을 지정하세요.</p>
                    <div class="sf-form-grid">
                        <div class="sf-field">
                            <label>테마 컬러</label>
                            <input type="text" name="theme_color" value="<?php echo get_text($row['theme_color']); ?>" class="frm_input" placeholder="#0f766e">
                            <div class="sf-help">버튼, 핵심 문구, 포인트 배경에 쓰일 색상코드입니다.</div>
                        </div>
                        <div class="sf-field">
                            <label>메인 이미지</label>
                            <input type="file" name="main_image_file" class="frm_input">
                            <div class="sf-help">최상단 히어로존의 배경에 사용될 이미지입니다.</div>
                            <?php if (!empty($row['main_image'])) { ?>
                            <div class="sf-preview" style="margin-top:10px;">
                                <img src="<?php echo get_text($row['main_image']); ?>" alt="메인 이미지">
                                <div><a href="<?php echo get_text($row['main_image']); ?>" target="_blank"><?php echo get_text($row['main_image']); ?></a></div>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </section>

                <section class="sf-form-section">
                    <h2>상태 설정</h2>
                    <p class="section-desc">현재 페이지의 공개 여부 상태를 변경합니다.</p>
                    <div class="sf-form-grid">
                        <div class="sf-field">
                            <label>공개 여부</label>
                            <select name="is_active">
                                <option value="Y" <?php echo $row['is_active'] === 'Y' ? 'selected' : ''; ?>>공개</option>
                                <option value="N" <?php echo $row['is_active'] === 'N' ? 'selected' : ''; ?>>비공개</option>
                            </select>
                            <div class="sf-help">활성화하면 실제 랜딩 링크로 접속이 가능해집니다.</div>
                        </div>
                    </div>
                </section>

                <section class="sf-form-section">
                    <h2>AI / 데이터 안내</h2>
                    <p class="section-desc">업종 정보 및 AI 자동완성을 활용해 빠르게 콘텐츠를 채울 수 있습니다.</p>
                    <div class="sf-note">업종, 회사명, 지역명, 소개글을 입력하면 더욱 AI 정확도가 높게 생성됩니다. 현재 OpenAI 모델 기반으로 컨텍스트 맞춤 작성이 진행 중 입니다.</div>
                </section>
<section class="sf-form-section">
    <h2>권장 제작 방식</h2>
    <p class="section-desc">검증된 디자인 블록을 먼저 구축하고 AI가 콘텐츠를 보완합니다.</p>
    <ul class="sf-list">
        <li>헤더 10종</li>
        <li>메인 비주얼 20종</li>
        <li>서비스 소개 15종</li>
        <li>장점·특징 15종</li>
        <li>상품·가격표 10종</li>
        <li>진행 절차 10종</li>
        <li>포트폴리오 10종</li>
        <li>고객 후기 10종</li>
        <li>FAQ 10종</li>
        <li>상담 신청폼 10종</li>
        <li>하단 고정 상담 버튼</li>
        <li>푸터 5종</li>
    </ul>
    <p class="sf-help">AI는 선택된 블록에 맞춰 텍스트와 이미지 배치를 자동 생성합니다.</p>
</section>
<section class="sf-form-section">
    <h2>디자인 블록 선택</h2>
    <p class="section-desc">아래 블록 중 필요한 것을 선택하면 해당 디자인 블록이 자동으로 적용됩니다.</p>
    <ul class="sf-list sf-checkbox-list" style="columns:2;">
        <li><label><input type="checkbox" name="design_blocks[]" value="header" checked> 헤더</label></li>
        <li><label><input type="checkbox" name="design_blocks[]" value="visual" checked> 메인 비주얼</label></li>
        <li><label><input type="checkbox" name="design_blocks[]" value="service" checked> 서비스 소개</label></li>
        <li><label><input type="checkbox" name="design_blocks[]" value="strength" checked> 장점·특징</label></li>
        <li><label><input type="checkbox" name="design_blocks[]" value="price" checked> 상품·가격표</label></li>
        <li><label><input type="checkbox" name="design_blocks[]" value="process" checked> 진행 절차</label></li>
        <li><label><input type="checkbox" name="design_blocks[]" value="portfolio" checked> 포트폴리오</label></li>
        <li><label><input type="checkbox" name="design_blocks[]" value="review" checked> 고객 후기</label></li>
        <li><label><input type="checkbox" name="design_blocks[]" value="faq" checked> FAQ</label></li>
        <li><label><input type="checkbox" name="design_blocks[]" value="contact" checked> 상담 신청폼</label></li>
        <li><label><input type="checkbox" name="design_blocks[]" value="fixed_contact" checked> 하단 고정 상담 버튼</label></li>
        <li><label><input type="checkbox" name="design_blocks[]" value="footer" checked> 푸터</label></li>
    </ul>
    <p class="sf-help">선택된 블록은 AI가 텍스트와 이미지 배치를 자동 생성하는 기반이 됩니다.</p>
</section>
            </div>
        </form>
    </div>

    <!-- 우측 컨트롤 패널 -->
    <aside class="sf-right-panel">
        <div class="sf-sticky-actions">
            <input type="submit" form="flandingform" value="저장" class="btn_submit btn btn-btn-primary">
            <a href="<?php echo SF_MANAGER_URL; ?>/landing/landing_list.php" class="btn btn_02">목록</a>
        </div>
    </aside>
</div>

<script>
function sfAiFill(data) {
    var fields = ['main_copy', 'sub_copy', 'problem_text', 'strength_text', 'faq_text', 'cta_text'];
    for (var i = 0; i < fields.length; i++) {
        var name = fields[i];
        if (typeof data[name] !== 'undefined') {
            $('[name="' + name + '"]').val(data[name]);
        }
    }
}
function sfAiSetBusy(btn, busy) {
    if (!btn) return;
    var $btn = $(btn);
    if (busy) {
        $btn.data('original-text', $btn.text());
        $btn.prop('disabled', true).text('생성중...');
    } else {
        var text = $btn.data('original-text');
        if (text) { $btn.text(text); }
        $btn.prop('disabled', false);
    }
}
function sfAiGenerate(action, btn) {
    var industry = $.trim($('#industry').val());
    var companyName = $.trim($('[name="company_name"]').val());
    var areaName = $.trim($('[name="area_name"]').val());
    var introText = $.trim($('[name="intro_text"]').val());
    if (!action) { alert('action 값이 없습니다.'); return; }
    if (!industry) { alert('업종을 입력하세요.'); $('#industry').focus(); return; }
    if (!companyName) { alert('회사명을 입력하세요.'); $('[name="company_name"]').focus(); return; }
    sfAiSetBusy(btn, true);
    $.ajax({
        url: './ai_generate.php',
        type: 'POST',
        dataType: 'json',
        data: { action: action, industry: industry, company_name: companyName, area_name: areaName, intro_text: introText },
        success: function(res) { if (res && res.success) { sfAiFill(res.data || {}); alert('AI 생성이 완료되었습니다.'); } else { alert(res && res.error ? res.error : 'AI 생성 오류가 발생했습니다.'); } },
        error: function() { alert('AI 통신 중 오류가 발생했습니다.'); },
        complete: function() { sfAiSetBusy(btn, false); }
    });
}
</script>
<?php
// ─────────────────────────────────────────────────────────────────────────
// 미니웹 빌더 (PoC · Hero 섹션 한정)
//
// 진입점을 넷으로 늘리지 않기 위해 이 화면에 붙인다. 다만 기존 landing_pages 폼과는
// 데이터가 완전히 분리돼 있다 - 미니웹은 miniweb_project / miniweb_section 을 쓰고
// landing_pages 를 읽거나 쓰지 않는다. 위쪽 폼 코드는 건드리지 않았다.
//
// 프로젝트는 ?mwpid=N 로 따라다닌다. 없으면 첫 저장 시 만들어진다.
// ─────────────────────────────────────────────────────────────────────────
$mw_prefix = G5_TABLE_PREFIX;
$mw_ready = false;
$mw_blocks = array();
$mw_section = array('block_id' => 0, 'content' => array());
$mw_pid = isset($_GET['mwpid']) ? (int) $_GET['mwpid'] : 0;

// 설치 전이면 안내만 하고 UI를 그리지 않는다(없는 테이블을 조회하지 않기 위해).
$mw_chk = sql_query(" show tables like '" . sql_real_escape_string($mw_prefix . 'miniweb_block') . "' ", false);
if ($mw_chk && sql_num_rows($mw_chk) > 0) {
    $mw_ready = true;

    $mw_res = sql_query(" select id, block_code, block_name, thumbnail_url
                          from {$mw_prefix}miniweb_block
                          where section_type = 'hero' and is_active = 1
                          order by sort_order asc, id asc ", false);
    if ($mw_res) {
        while ($mw_r = sql_fetch_array($mw_res)) { $mw_blocks[] = $mw_r; }
    }

    if ($mw_pid > 0) {
        $mw_s = sql_fetch(" select block_id, content_json from {$mw_prefix}miniweb_section
                            where project_id = '{$mw_pid}' and section_type = 'hero' limit 1 ", false);
        if ($mw_s) {
            $mw_section['block_id'] = (int) $mw_s['block_id'];
            $mw_decoded = json_decode((string) $mw_s['content_json'], true);
            if (is_array($mw_decoded)) { $mw_section['content'] = $mw_decoded; }
        }
    }
}
$mw_val = function ($k) use ($mw_section) {
    return isset($mw_section['content'][$k]) ? $mw_section['content'][$k] : '';
};
?>

<div class="mgr-card" style="margin-top:2rem;padding:1.25rem;">
    <h2 style="margin:0 0 .25rem;font-size:1.05rem;">미니웹 빌더 <span style="font-weight:400;color:var(--mgr-text-muted);font-size:.8125rem;">· Hero 섹션 (PoC)</span></h2>
    <p style="margin:0 0 1rem;color:var(--mgr-text-muted);font-size:.875rem;line-height:1.6;">
        디자인을 바꿔도 입력한 내용은 그대로 유지됩니다. 위쪽 랜딩페이지 폼과는 별개 데이터입니다.
    </p>

<?php if (!$mw_ready) { ?>
    <div style="padding:1rem;background:#fef3c7;color:#92400e;border-radius:10px;font-size:.875rem;line-height:1.7;">
        미니웹 테이블이 아직 설치되지 않았습니다.<br>
        <a href="<?php echo G5_ADMIN_URL; ?>/landing/settings.php?tab=install" style="font-weight:700;color:#92400e;">설치 화면으로 이동</a>해
        <strong>미니웹 DB 설치</strong> → <strong>Hero 기본 샘플 설치</strong>를 차례로 실행해 주세요.
    </div>
<?php } else { ?>

    <style>
    .mw-b { display:grid; grid-template-columns:1fr; gap:1rem; }
    .mw-b__panel { min-width:0; }
    .mw-b__label { font-size:.8125rem; font-weight:700; color:var(--mgr-text-muted); margin:0 0 .5rem; }

    .mw-b__cards { display:grid; grid-template-columns:1fr; gap:.5rem; }
    .mw-b__card { display:flex; align-items:center; gap:.625rem; width:100%; min-height:56px;
                  padding:.625rem .75rem; text-align:left; cursor:pointer;
                  background:#fff; border:1px solid var(--mgr-border); border-radius:10px; }
    .mw-b__card.is-current { border-color:var(--mgr-primary); box-shadow:0 0 0 2px rgba(37,99,235,.15); }
    .mw-b__thumb { flex:0 0 auto; width:44px; height:32px; border-radius:6px; background:#e2e8f0;
                   display:flex; align-items:center; justify-content:center; font-size:10px; color:#64748b; }
    .mw-b__name { flex:1 1 auto; min-width:0; font-size:.875rem; font-weight:600; }
    .mw-b__use { flex:0 0 auto; font-size:.75rem; font-weight:700; color:var(--mgr-primary); }

    .mw-b__devices { display:flex; gap:.25rem; overflow-x:auto; margin-bottom:.5rem; }
    .mw-b__dev { flex:0 0 auto; min-height:40px; padding:.375rem .75rem; font-size:.8125rem; font-weight:600;
                 background:#fff; border:1px solid var(--mgr-border); border-radius:8px; cursor:pointer; }
    .mw-b__dev.is-current { background:var(--mgr-primary); border-color:var(--mgr-primary); color:#fff; }

    /* 미리보기는 iframe이어야 한다. div 폭만 줄이면 미디어쿼리가 바깥 창 폭을 보기 때문에
       390px 상자 안에 PC 레이아웃이 그려진다. */
    .mw-b__stage { background:#f1f5f9; border:1px solid var(--mgr-border); border-radius:10px;
                   padding:.75rem; overflow-x:auto; }
    .mw-b__frame { display:block; margin:0 auto; width:100%; max-width:none; height:460px;
                   background:#fff; border:1px solid var(--mgr-border); border-radius:8px; }

    .mw-b__field { margin-bottom:.75rem; }
    .mw-b__field label { display:block; font-size:.8125rem; font-weight:700; margin-bottom:.25rem; }
    .mw-b__field input, .mw-b__field textarea { width:100%; min-height:44px; padding:.5rem .75rem;
        font-size:1rem; border:1px solid var(--mgr-border); border-radius:8px; }
    .mw-b__field textarea { min-height:80px; }
    .mw-b__save { display:flex; align-items:center; gap:.75rem; flex-wrap:wrap; }
    .mw-b__save button { min-height:48px; padding:0 1.25rem; }
    .mw-b__state { font-size:.8125rem; color:var(--mgr-text-muted); }

    @media (min-width: 1024px) {
        .mw-b { grid-template-columns: 1fr 300px; align-items:start; }
        .mw-b__cards { grid-template-columns:1fr; }
    }
    </style>

    <div class="mw-b">
        <div class="mw-b__panel">
            <p class="mw-b__label">미리보기</p>
            <div class="mw-b__devices" id="mwDevices">
                <button type="button" class="mw-b__dev is-current" data-w="390">모바일 390</button>
                <button type="button" class="mw-b__dev" data-w="768">태블릿 768</button>
                <button type="button" class="mw-b__dev" data-w="1440">PC 1440</button>
                <button type="button" class="mw-b__dev" id="mwFull">전체화면</button>
            </div>
            <div class="mw-b__stage">
                <iframe id="mwFrame" class="mw-b__frame" title="미니웹 미리보기"></iframe>
            </div>
        </div>

        <div class="mw-b__panel">
            <p class="mw-b__label">Hero 디자인</p>
            <div class="mw-b__cards" id="mwCards">
                <?php foreach ($mw_blocks as $b) {
                    $cur = ((int) $b['id'] === $mw_section['block_id']); ?>
                    <button type="button" class="mw-b__card<?php echo $cur ? ' is-current' : ''; ?>"
                            data-block="<?php echo (int) $b['id']; ?>">
                        <span class="mw-b__thumb">
                            <?php if ($b['thumbnail_url']) { ?>
                                <img src="<?php echo get_text($b['thumbnail_url']); ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:6px;">
                            <?php } else { echo '샘플'; } ?>
                        </span>
                        <span class="mw-b__name"><?php echo get_text($b['block_name']); ?></span>
                        <span class="mw-b__use"><?php echo $cur ? '사용중' : '선택'; ?></span>
                    </button>
                <?php } ?>
            </div>

            <p class="mw-b__label" style="margin-top:1rem;">내용</p>
            <div class="mw-b__field">
                <label for="mw_title">메인 제목 *</label>
                <input type="text" id="mw_title" maxlength="60" value="<?php echo get_text($mw_val('title')); ?>">
            </div>
            <div class="mw-b__field">
                <label for="mw_description">보조 설명</label>
                <textarea id="mw_description" maxlength="200"><?php echo get_text($mw_val('description')); ?></textarea>
            </div>
            <div class="mw-b__field">
                <label for="mw_phone">대표 전화번호</label>
                <input type="tel" id="mw_phone" inputmode="numeric" value="<?php echo get_text($mw_val('phone')); ?>">
            </div>
            <div class="mw-b__field">
                <label for="mw_cta_text">CTA 문구</label>
                <input type="text" id="mw_cta_text" maxlength="20" value="<?php echo get_text($mw_val('cta_text')); ?>">
            </div>
            <div class="mw-b__field">
                <label for="mw_cta_url">CTA 링크</label>
                <input type="text" id="mw_cta_url" value="<?php echo get_text($mw_val('cta_url') !== '' ? $mw_val('cta_url') : '#contact'); ?>">
            </div>

            <div class="mw-b__save">
                <button type="button" class="mgr-btn mgr-btn-primary" id="mwSave">내용 저장</button>
                <span class="mw-b__state" id="mwState"></span>
            </div>
        </div>
    </div>

    <script>
    (function () {
        var AJAX = <?php echo json_encode(SF_MANAGER_URL . '/landing/miniweb_ajax.php', JSON_UNESCAPED_SLASHES); ?>;
        var PREVIEW = <?php echo json_encode(SF_MANAGER_URL . '/landing/miniweb_preview.php', JSON_UNESCAPED_SLASHES); ?>;
        var TOKEN = <?php echo json_encode(get_token()); ?>;
        var pid = <?php echo (int) $mw_pid; ?>;

        var frame = document.getElementById('mwFrame');
        var stage = frame.parentNode;
        var state = document.getElementById('mwState');
        var deviceWidth = 390;

        function say(msg, tone) {
            state.textContent = msg || '';
            state.style.color = tone === 'error' ? '#b91c1c' : (tone === 'ok' ? '#166534' : '');
        }

        function fields() {
            return {
                title: document.getElementById('mw_title').value,
                description: document.getElementById('mw_description').value,
                phone: document.getElementById('mw_phone').value,
                cta_text: document.getElementById('mw_cta_text').value,
                cta_url: document.getElementById('mw_cta_url').value
            };
        }

        // iframe 폭을 실제로 바꾼다. 편집 영역보다 넓으면 축소해 보여주되 폭 자체는 유지해야
        // 그 안의 미디어쿼리가 해당 기기 기준으로 평가된다.
        function applyDevice() {
            var avail = stage.clientWidth - 24;
            frame.style.width = deviceWidth + 'px';
            if (deviceWidth > avail) {
                var scale = avail / deviceWidth;
                frame.style.transformOrigin = 'top left';
                frame.style.transform = 'scale(' + scale + ')';
                frame.style.marginBottom = (460 * scale - 460) + 'px';
            } else {
                frame.style.transform = '';
                frame.style.marginBottom = '';
            }
        }

        function reloadPreview() {
            frame.src = PREVIEW + '?pid=' + pid + '&t=' + Date.now();
        }

        function post(data, done) {
            data.token = TOKEN;
            var body = new URLSearchParams(data).toString();
            fetch(AJAX, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body
            }).then(function (r) {
                return r.text().then(function (t) {
                    try { return JSON.parse(t); }
                    catch (e) {
                        // 세션이 풀리면 JSON 대신 로그인 HTML이 온다 - 원인을 구분해 알린다.
                        throw new Error(/<!doctype|<html/i.test(t)
                            ? '로그인이 풀렸습니다. 새로고침 후 다시 시도해 주세요.'
                            : '서버 응답을 해석할 수 없습니다.');
                    }
                });
            }).then(function (res) {
                if (!res.success) { say(res.error || '처리하지 못했습니다.', 'error'); return; }
                done(res);
            }).catch(function (err) { say(err.message || '통신 오류', 'error'); });
        }

        // 프로젝트가 없으면 만들고 주소에 mwpid를 남긴다(새로고침해도 이어지도록).
        function ensureProject(done) {
            if (pid > 0) { done(); return; }
            post({ action: 'ensure_project', pid: 0, section_type: 'hero', project_name: '미니웹 PoC' }, function (res) {
                pid = res.pid;
                var u = new URL(location.href);
                u.searchParams.set('mwpid', pid);
                history.replaceState(null, '', u.toString());
                done();
            });
        }

        document.getElementById('mwCards').addEventListener('click', function (e) {
            var card = e.target.closest('.mw-b__card');
            if (!card) return;
            ensureProject(function () {
                post({ action: 'change_block', pid: pid, section_type: 'hero', block_id: card.dataset.block }, function () {
                    var all = document.querySelectorAll('.mw-b__card');
                    for (var i = 0; i < all.length; i++) {
                        all[i].classList.remove('is-current');
                        all[i].querySelector('.mw-b__use').textContent = '선택';
                    }
                    card.classList.add('is-current');
                    card.querySelector('.mw-b__use').textContent = '사용중';
                    say('디자인을 바꿨습니다. 입력한 내용은 그대로입니다.', 'ok');
                    reloadPreview();
                });
            });
        });

        document.getElementById('mwSave').addEventListener('click', function () {
            ensureProject(function () {
                post({ action: 'save_content', pid: pid, section_type: 'hero',
                       content: JSON.stringify(fields()) }, function (res) {
                    say('저장됨 ' + res.saved_at, 'ok');
                    reloadPreview();
                });
            });
        });

        document.getElementById('mwDevices').addEventListener('click', function (e) {
            var btn = e.target.closest('.mw-b__dev');
            if (!btn || btn.id === 'mwFull') return;
            var all = document.querySelectorAll('.mw-b__dev');
            for (var i = 0; i < all.length; i++) all[i].classList.remove('is-current');
            btn.classList.add('is-current');
            deviceWidth = parseInt(btn.dataset.w, 10);
            applyDevice();
        });

        // 전체화면은 새 탭이 가장 정확하다 - 브라우저 창 자체가 뷰포트가 된다.
        document.getElementById('mwFull').addEventListener('click', function () {
            if (pid < 1) { say('먼저 디자인을 선택하거나 내용을 저장해 주세요.'); return; }
            window.open(PREVIEW + '?pid=' + pid, '_blank', 'noopener');
        });

        window.addEventListener('resize', applyDevice);
        applyDevice();
        reloadPreview();
    })();
    </script>
<?php } ?>
</div>

<?php include_once(dirname(__FILE__) . '/../layout/footer.php'); ?>
