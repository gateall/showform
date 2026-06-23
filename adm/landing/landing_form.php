<?php
include_once('./_common.php');

auth_check_menu($auth, '900100', 'r');

$g5['title'] = '?? ??/??';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$table = G5_TABLE_PREFIX . 'landing_pages';
$row = array();

if ($id) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' limit 1 ");
    if (!$row) {
        alert('?????? ?? ? ????.', './landing_list.php');
    }
    $g5['title'] = '?? ??';
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
            <label>??? ??</label>
            <select name="template_type">
                <option value="service" <?php echo $row['template_type'] === 'service' ? 'selected' : ''; ?>>??? ???</option>
                <option value="hospital" <?php echo $row['template_type'] === 'hospital' ? 'selected' : ''; ?>>???</option>
                <option value="local" <?php echo $row['template_type'] === 'local' ? 'selected' : ''; ?>>?????</option>
            </select>
        </div>
        <div class="sf-field">
            <label>?? <button type="button" class="btn btn_03 btn_xs sf-ai-btn" data-action="all" onclick="sfAiGenerate('all', this)">?? AI ??</button></label>
            <input type="text" name="industry" id="industry" value="<?php echo get_text($row['industry']); ?>" class="frm_input" placeholder="?: ??, ??, ??">
        </div>
        <div class="sf-field">
            <label>???</label>
            <input type="text" name="company_name" value="<?php echo get_text($row['company_name']); ?>" class="frm_input">
        </div>
        <div class="sf-field">
            <label>???</label>
            <input type="text" name="ceo_name" value="<?php echo get_text($row['ceo_name']); ?>" class="frm_input">
        </div>
        <div class="sf-field">
            <label>????</label>
            <input type="text" name="phone" value="<?php echo get_text($row['phone']); ?>" class="frm_input">
        </div>
        <div class="sf-field">
            <label>????? URL</label>
            <input type="text" name="kakao_url" value="<?php echo get_text($row['kakao_url']); ?>" class="frm_input">
        </div>
        <div class="sf-field">
            <label>??</label>
            <input type="text" name="address" value="<?php echo get_text($row['address']); ?>" class="frm_input">
        </div>
        <div class="sf-field">
            <label>???</label>
            <input type="text" name="area_name" value="<?php echo get_text($row['area_name']); ?>" class="frm_input">
        </div>
        <div class="sf-field" style="grid-column:1/-1;">
            <label>???</label>
            <textarea name="intro_text" rows="4" class="frm_input" style="width:100%;"><?php echo get_text($row['intro_text']); ?></textarea>
        </div>
        <div class="sf-field">
            <label>?? ?? <button type="button" class="btn btn_03 btn_xs sf-ai-btn" data-action="main" onclick="sfAiGenerate('main', this)">AI ???? ??</button></label>
            <input type="text" name="main_copy" value="<?php echo get_text($row['main_copy']); ?>" class="frm_input">
        </div>
        <div class="sf-field">
            <label>?? ??</label>
            <input type="text" name="sub_copy" value="<?php echo get_text($row['sub_copy']); ?>" class="frm_input">
        </div>
        <div class="sf-field" style="grid-column:1/-1;">
            <label>???/?? ??? <button type="button" class="btn btn_03 btn_xs sf-ai-btn" data-action="problem" onclick="sfAiGenerate('problem', this)">AI ???? ??</button></label>
            <textarea name="problem_text" rows="3" class="frm_input" style="width:100%;"><?php echo get_text($row['problem_text']); ?></textarea>
        </div>
        <div class="sf-field" style="grid-column:1/-1;">
            <label>??? ??? <button type="button" class="btn btn_03 btn_xs sf-ai-btn" data-action="strength" onclick="sfAiGenerate('strength', this)">AI ?? ??</button></label>
            <textarea name="strength_text" rows="3" class="frm_input" style="width:100%;"><?php echo get_text($row['strength_text']); ?></textarea>
        </div>
        <div class="sf-field" style="grid-column:1/-1;">
            <label>FAQ ??? <button type="button" class="btn btn_03 btn_xs sf-ai-btn" data-action="faq" onclick="sfAiGenerate('faq', this)">AI FAQ ??</button></label>
            <textarea name="faq_text" rows="4" class="frm_input" style="width:100%;"><?php echo get_text($row['faq_text']); ?></textarea>
        </div>
        <div class="sf-field">
            <label>CTA ?? ??? <button type="button" class="btn btn_03 btn_xs sf-ai-btn" data-action="cta" onclick="sfAiGenerate('cta', this)">AI CTA ??</button></label>
            <input type="text" name="cta_text" value="<?php echo get_text($row['cta_text']); ?>" class="frm_input" placeholder="?: ?? ????">
        </div>
        <div class="sf-field">
            <label>?? ??</label>
            <input type="text" name="theme_color" value="<?php echo get_text($row['theme_color']); ?>" class="frm_input" placeholder="#0f766e">
        </div>
        <div class="sf-field">
            <label>?? ???</label>
            <input type="file" name="main_image_file" class="frm_input">
            <?php if (!empty($row['main_image'])) { ?><p style="margin-top:8px;"><a href="<?php echo get_text($row['main_image']); ?>" target="_blank"><?php echo get_text($row['main_image']); ?></a></p><?php } ?>
        </div>
        <div class="sf-field">
            <label>?? ??</label>
            <select name="is_active">
                <option value="Y" <?php echo $row['is_active'] === 'Y' ? 'selected' : ''; ?>>??</option>
                <option value="N" <?php echo $row['is_active'] === 'N' ? 'selected' : ''; ?>>???</option>
            </select>
        </div>
    </div>
    <div class="sf-actions">
        <input type="submit" value="??" class="btn_submit btn">
        <a href="./landing_list.php" class="btn btn_02">??</a>
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
        $btn.prop('disabled', true).text('???...');
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
        alert('action ?? ?????.');
        return;
    }
    if (!industry) {
        alert('??? ??????.');
        $('#industry').focus();
        return;
    }
    if (!companyName) {
        alert('???? ??????.');
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
                alert('AI ??? ???????.');
            } else {
                alert(res && res.error ? res.error : 'AI ?? ??? ??????.');
            }
        },
        error: function() {
            alert('AI ?? ?? ? ??? ??????.');
        },
        complete: function() {
            sfAiSetBusy(btn, false);
        }
    });
}
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>
