<?php
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
.sf-sticky-actions { position:sticky; bottom:12px; z-index:30; display:flex; gap:10px; justify-content:flex-end; margin-top:16px; padding-top:12px; }
.sf-note { padding:12px 14px; border-radius:12px; background:#f8fafc; border:1px solid #e2e8f0; color:#475569; line-height:1.6; font-size:13px; }
@media (max-width: 768px) { .sf-form-grid { grid-template-columns:1fr; } .sf-sticky-actions { justify-content:stretch; } .sf-sticky-actions .btn, .sf-sticky-actions .btn_submit { flex:1; } }
</style>

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
                <?php if (!empty($row['main_image'])) { ?><div class="sf-preview" style="margin-top:10px;"><img src="<?php echo get_text($row['main_image']); ?>" alt="메인 이미지"><div><a href="<?php echo get_text($row['main_image']); ?>" target="_blank"><?php echo get_text($row['main_image']); ?></a></div></div><?php } ?>
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

    <div class="sf-sticky-actions">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="<?php echo SF_MANAGER_URL; ?>/landing/landing_list.php" class="btn btn_02">목록</a>
    </div>
</div>
</form>

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
        if (text) {
            $btn.text(text);
        }
        $btn.prop('disabled', false);
    }
}

function sfAiGenerate(action, btn) {
    var industry = $.trim($('#industry').val());
    var companyName = $.trim($('[name="company_name"]').val());
    var areaName = $.trim($('[name="area_name"]').val());
    var introText = $.trim($('[name="intro_text"]').val());

    if (!action) {
        alert('action 값이 없습니다.');
        return;
    }
    if (!industry) {
        alert('업종을 입력하세요.');
        $('#industry').focus();
        return;
    }
    if (!companyName) {
        alert('회사명을 입력하세요.');
        $('[name="company_name"]').focus();
        return;
    }

    sfAiSetBusy(btn, true);

    $.ajax({
        url: './ai_generate.php',
        type: 'POST',
        dataType: 'json',
        data: {
            action: action,
            industry: industry,
            company_name: companyName,
            area_name: areaName,
            intro_text: introText
        },
        success: function(res) {
            if (res && res.success) {
                sfAiFill(res.data || {});
                alert('AI 생성이 완료되었습니다.');
            } else {
                alert(res && res.error ? res.error : 'AI 생성 오류가 발생했습니다.');
            }
        },
        error: function() {
            alert('AI 통신 중 오류가 발생했습니다.');
        },
        complete: function() {
            sfAiSetBusy(btn, false);
        }
    });
}
</script>

<?php include_once(dirname(__FILE__) . '/../layout/footer.php'); ?>
