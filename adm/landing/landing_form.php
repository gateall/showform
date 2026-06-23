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
        alert('랜딩페이지를 찾을 수 없습니다.', './landing_list.php');
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

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<style>
.sf-admin-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:20px; box-shadow:0 8px 24px rgba(15,23,42,.05); }
.sf-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:16px; }
@media (max-width: 768px) { .sf-grid { grid-template-columns:1fr; } }
.sf-field label { display:block; margin-bottom:6px; font-weight:700; color:#334155; }
.sf-field input, .sf-field select, .sf-field textarea { width:100%; box-sizing:border-box; }
.sf-actions { display:flex; gap:10px; margin-top:20px; }
.sf-actions .btn_submit { background:#0f766e; border-color:#0f766e; }
</style>

<form name="flandingform" method="post" action="./landing_update.php" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?php echo (int)$id; ?>">
<div class="sf-admin-card">
    <div class="sf-grid">
        <div class="sf-field">
            <label>템플릿 선택</label>
            <select name="template_type">
                <option value="service" <?php echo $row['template_type'] === 'service' ? 'selected' : ''; ?>>서비스 판매형</option>
                <option value="hospital" <?php echo $row['template_type'] === 'hospital' ? 'selected' : ''; ?>>병원형</option>
                <option value="local" <?php echo $row['template_type'] === 'local' ? 'selected' : ''; ?>>지역업체형</option>
            </select>
        </div>
        <div class="sf-field">
            <label>업종 <button type="button" class="btn btn_03 btn_xs" onclick="applyPreset(false)">프리셋 적용</button> <button type="button" class="btn btn_03 btn_xs" onclick="applyPreset(true)">프리셋 다시 적용</button></label>
            <input type="text" name="industry" id="industry" value="<?php echo get_text($row['industry']); ?>" class="frm_input" placeholder="예: 누수, 병원, 식당">
        </div>
        <div class="sf-field">
            <label>회사명</label>
            <input type="text" name="company_name" value="<?php echo get_text($row['company_name']); ?>" class="frm_input">
        </div>
        <div class="sf-field">
            <label>대표자</label>
            <input type="text" name="ceo_name" value="<?php echo get_text($row['ceo_name']); ?>" class="frm_input">
        </div>
        <div class="sf-field">
            <label>전화번호</label>
            <input type="text" name="phone" value="<?php echo get_text($row['phone']); ?>" class="frm_input">
        </div>
        <div class="sf-field">
            <label>카카오채널 URL</label>
            <input type="text" name="kakao_url" value="<?php echo get_text($row['kakao_url']); ?>" class="frm_input">
        </div>
        <div class="sf-field">
            <label>주소</label>
            <input type="text" name="address" value="<?php echo get_text($row['address']); ?>" class="frm_input">
        </div>
        <div class="sf-field">
            <label>지역명</label>
            <input type="text" name="area_name" value="<?php echo get_text($row['area_name']); ?>" class="frm_input">
        </div>
        <div class="sf-field" style="grid-column:1/-1;">
            <label>소개글</label>
            <textarea name="intro_text" rows="4" class="frm_input" style="width:100%;"><?php echo get_text($row['intro_text']); ?></textarea>
        </div>
        <div class="sf-field">
            <label>메인 문구</label>
            <input type="text" name="main_copy" value="<?php echo get_text($row['main_copy']); ?>" class="frm_input">
        </div>
        <div class="sf-field">
            <label>서브 문구</label>
            <input type="text" name="sub_copy" value="<?php echo get_text($row['sub_copy']); ?>" class="frm_input">
        </div>
        <div class="sf-field" style="grid-column:1/-1;">
            <label>문제점/공감 텍스트</label>
            <textarea name="problem_text" rows="3" class="frm_input" style="width:100%;"><?php echo get_text($row['problem_text']); ?></textarea>
        </div>
        <div class="sf-field" style="grid-column:1/-1;">
            <label>특장점 텍스트</label>
            <textarea name="strength_text" rows="3" class="frm_input" style="width:100%;"><?php echo get_text($row['strength_text']); ?></textarea>
        </div>
        <div class="sf-field" style="grid-column:1/-1;">
            <label>FAQ 텍스트</label>
            <textarea name="faq_text" rows="4" class="frm_input" style="width:100%;"><?php echo get_text($row['faq_text']); ?></textarea>
        </div>
        <div class="sf-field">
            <label>CTA 버튼 텍스트</label>
            <input type="text" name="cta_text" value="<?php echo get_text($row['cta_text']); ?>" class="frm_input" placeholder="예: 무료 상담받기">
        </div>
        <div class="sf-field">
            <label>테마 색상</label>
            <input type="text" name="theme_color" value="<?php echo get_text($row['theme_color']); ?>" class="frm_input" placeholder="#0f766e">
        </div>
        <div class="sf-field">
            <label>대표 이미지</label>
            <input type="file" name="main_image_file" class="frm_input">
            <?php if (!empty($row['main_image'])) { ?><p style="margin-top:8px;"><a href="<?php echo get_text($row['main_image']); ?>" target="_blank"><?php echo get_text($row['main_image']); ?></a></p><?php } ?>
        </div>
        <div class="sf-field">
            <label>공개 여부</label>
            <select name="is_active">
                <option value="Y" <?php echo $row['is_active'] === 'Y' ? 'selected' : ''; ?>>공개</option>
                <option value="N" <?php echo $row['is_active'] === 'N' ? 'selected' : ''; ?>>비공개</option>
            </select>
        </div>
    </div>
    <div class="sf-actions">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="./landing_list.php" class="btn btn_02">목록</a>
    </div>
</div>
</form>

<script>
function applyPreset(force) {
    var industry = $('#industry').val();
    if (!industry) {
        alert('업종을 입력해주세요.');
        return;
    }
    $.ajax({
        url: './preset_get.php',
        type: 'POST',
        data: { industry: industry },
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                var d = res.data;
                var keys = ['main_copy', 'sub_copy', 'problem_text', 'strength_text', 'faq_text', 'cta_text'];
                for (var i = 0; i < keys.length; i++) {
                    var k = keys[i];
                    var el = $('[name="' + k + '"]');
                    if (el.length > 0) {
                        if (force || $.trim(el.val()) === '') {
                            el.val(d[k]);
                        }
                    }
                }
                alert('프리셋이 적용되었습니다.');
            } else {
                alert('해당 업종의 프리셋을 찾을 수 없습니다.');
            }
        },
        error: function() {
            alert('프리셋을 불러오는 중 오류가 발생했습니다.');
        }
    });
}
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>