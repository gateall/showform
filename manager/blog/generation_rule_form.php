<?php
include_once('./_common.php');

$sub_menu = '360100';
auth_check_menu($auth, $sub_menu, 'w');

$rules_table = bp_table('generation_rules');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = array(
    'id' => 0, 'rule_type' => 'writing_rule', 'rule_name' => '', 'rule_instruction' => '',
    'is_default' => 'N', 'is_active' => 'Y', 'sort_order' => 0,
);

if ($id > 0) {
    $row = sql_fetch(" select * from {$rules_table} where id = '{$id}' ");
    if (!$row) {
        alert('생성 조건을 찾을 수 없습니다.', './content_management.php?tab=generation_rules');
    }
    $g5['title'] = 'AI 생성 조건 수정';
} else {
    $g5['title'] = 'AI 생성 조건 등록';
}

$type_labels = array(
    'writing_rule' => '작성 조건', 'technical_rule' => '기술 조건',
    'seo_rule' => 'SEO 조건', 'prohibited_rule' => '금지 조건', 'quality_rule' => '품질 조건',
);

include_once(__DIR__ . '/../layout/header.php');
?>
<div class="local_desc01 local_desc">
    <p>post_builder.php 1단계에서 체크박스로 노출되는 AI 글 생성 조건입니다. "조건 이름"은 화면에 보이는 라벨이고,
       "AI 전달용 상세 지시문"은 체크했을 때 실제로 AI 프롬프트에 그대로 실리는 문장입니다 - 이름만으로는
       AI가 정확히 이해하지 못하므로 상세 지시문을 구체적으로 작성해 주세요.</p>
</div>

<form name="fgenerationruleform" method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/generation_rule_update.php" onsubmit="return fgenerationruleform_submit(this);">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <?php if ($id > 0) { ?>
    <input type="hidden" name="w" value="u">
    <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
    <?php } ?>
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo get_text($g5['title']); ?></caption>
            <tbody>
                <tr><th scope="row"><label for="rule_type">조건 구분</label></th>
                    <td>
                        <select name="rule_type" id="rule_type" required>
                            <?php foreach ($type_labels as $code => $label) { ?>
                                <option value="<?php echo $code; ?>" <?php echo $row['rule_type'] === $code ? 'selected' : ''; ?>><?php echo get_text($label); ?></option>
                            <?php } ?>
                        </select>
                    </td></tr>
                <tr><th scope="row"><label for="rule_name">조건 이름</label></th>
                    <td><input type="text" name="rule_name" id="rule_name" class="frm_input" maxlength="150" required placeholder="예: 기승전결 구성" value="<?php echo get_text($row['rule_name']); ?>"></td></tr>
                <tr><th scope="row"><label for="rule_instruction">AI 전달용 상세 지시문</label></th>
                    <td><textarea name="rule_instruction" id="rule_instruction" rows="4" style="width:100%;" required placeholder="예: 글은 도입, 전개, 핵심 내용, 결론 순서로 구성한다."><?php echo get_text($row['rule_instruction']); ?></textarea></td></tr>
                <tr><th scope="row">기본 체크</th>
                    <td>
                        <label><input type="checkbox" name="is_default" value="Y" <?php echo $row['is_default'] === 'Y' ? 'checked' : ''; ?>> 새 프로젝트에서 기본으로 체크됨</label>
                    </td></tr>
                <tr><th scope="row">사용 상태</th>
                    <td>
                        <label><input type="radio" name="is_active" value="Y" <?php echo $row['is_active'] === 'Y' ? 'checked' : ''; ?>> 사용</label>
                        <label style="margin-left:12px;"><input type="radio" name="is_active" value="N" <?php echo $row['is_active'] === 'N' ? 'checked' : ''; ?>> 숨김(체크박스 목록에서 제외)</label>
                    </td></tr>
                <tr><th scope="row"><label for="sort_order">정렬 순서</label></th>
                    <td><input type="number" name="sort_order" id="sort_order" class="frm_input" style="width:100px;" value="<?php echo (int) $row['sort_order']; ?>"> <span class="help_txt">숫자가 작을수록 먼저 표시/전달됩니다.</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="<?php echo $id > 0 ? '수정 저장' : '등록'; ?>" class="btn_submit btn">
        <a href="./content_management.php?tab=generation_rules" class="btn btn_02">목록</a>
    </div>
</form>

<script>
function fgenerationruleform_submit(f) {
    if (!f.rule_name.value.trim()) { alert('조건 이름을 입력해 주세요.'); f.rule_name.focus(); return false; }
    if (!f.rule_instruction.value.trim()) { alert('AI 전달용 상세 지시문을 입력해 주세요.'); f.rule_instruction.focus(); return false; }
    return true;
}
</script>

<?php include_once(__DIR__ . '/../layout/footer.php');
