<?php
include_once('./_common.php');

$sub_menu = '360400';
auth_check_menu($auth, $sub_menu, 'w');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$g5['title'] = 'AI 공급자 설정';

$table = bp_table('ai_providers');
$row = sql_fetch(" select * from {$table} where provider_code = 'openai' ");
if (!$row) {
    $row = array(
        'provider_code' => 'openai', 'display_name' => 'OpenAI', 'is_active' => 'N',
        'masked_hint' => '', 'default_model' => 'gpt-4o', 'max_tokens' => 2000, 'temperature' => 0.70,
    );
}

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>Phase 1에서는 저장된 값이 있어도 실제 외부 API를 호출하지 않고 템플릿 생성기를 사용합니다(BLOG_AUTOMATION_MVP_PHASE1 작업지시서). 이 화면은 Phase 2 실연동을 위한 설정 구조만 미리 마련합니다.</p>
</div>

<form name="faiproviderform" method="post" action="./ai_provider_update.php">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>OpenAI 설정</caption>
            <tbody>
                <tr><th scope="row">사용 여부</th>
                    <td>
                        <label><input type="radio" name="is_active" value="Y" <?php echo $row['is_active'] === 'Y' ? 'checked' : ''; ?>> 사용함</label>
                        <label style="margin-left:15px;"><input type="radio" name="is_active" value="N" <?php echo $row['is_active'] === 'N' ? 'checked' : ''; ?>> 사용안함(Phase 1 기본값)</label>
                    </td></tr>
                <tr><th scope="row"><label for="api_key">API Key</label></th>
                    <td>
                        <input type="password" name="api_key" id="api_key" value="" class="frm_input" autocomplete="new-password" placeholder="새 값을 입력할 때만 교체됩니다">
                        <span class="help_txt"><?php echo $row['masked_hint'] ? '현재 저장된 값: ' . get_text($row['masked_hint']) : '저장된 값 없음'; ?></span>
                    </td></tr>
                <tr><th scope="row"><label for="default_model">기본 모델</label></th>
                    <td><input type="text" name="default_model" id="default_model" value="<?php echo get_text($row['default_model']); ?>" class="frm_input"></td></tr>
                <tr><th scope="row"><label for="max_tokens">최대 토큰</label></th>
                    <td><input type="number" name="max_tokens" id="max_tokens" value="<?php echo (int)$row['max_tokens']; ?>" class="frm_input" min="100" max="8000"></td></tr>
                <tr><th scope="row"><label for="temperature">Temperature</label></th>
                    <td><input type="number" name="temperature" id="temperature" value="<?php echo (float)$row['temperature']; ?>" class="frm_input" min="0" max="1" step="0.1"></td></tr>
            </tbody>
        </table>
    </div>
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="./ai_provider_list.php" class="btn btn_02">목록</a>
    </div>
</form>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
