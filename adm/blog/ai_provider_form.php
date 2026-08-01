<?php
include_once('./_common.php');

$sub_menu = '360400';
auth_check_menu($auth, $sub_menu, 'w');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$table = bp_table('ai_providers');

if ($id > 0) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' ");
    if (!$row) {
        alert('AI 공급자를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/ai_provider_list.php');
    }
} else {
    $row = array(
        'provider_code' => '', 'display_name' => '', 'is_active' => 'N',
        'masked_hint' => '', 'default_model' => 'gpt-4o', 'max_tokens' => 2000, 'temperature' => 0.70,
    );
}

$g5['title'] = $id > 0 ? 'AI 공급자 수정' : 'AI 공급자 등록';

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>실제 외부 API 호출은 <code>provider_code</code>가 <code>openai</code>인 활성 레코드에서만 지원됩니다. 그 외 코드는 레코드로 등록·관리는 되지만, 포스팅 화면에서 실제 생성을 시도하면 조용히 대체되지 않고 챗GPT로 변경할지 템플릿으로 생성할지 먼저 선택하게 됩니다(추후 단계에서 라우팅 확장 예정).</p>
</div>

<form name="faiproviderform" method="post" action="./ai_provider_update.php">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <?php if ($id > 0) { ?>
    <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
    <?php } ?>
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo get_text($g5['title']); ?></caption>
            <tbody>
                <tr><th scope="row"><label for="provider_code">공급자 코드</label></th>
                    <td>
                        <?php if ($id > 0) { ?>
                            <input type="text" value="<?php echo get_text($row['provider_code']); ?>" class="frm_input" readonly style="background:#f5f5f5;">
                            <input type="hidden" name="provider_code" value="<?php echo get_text($row['provider_code']); ?>">
                            <span class="help_txt">등록 후에는 코드를 변경할 수 없습니다.</span>
                        <?php } else { ?>
                            <input type="text" name="provider_code" id="provider_code" value="" class="frm_input" maxlength="30" placeholder="예: openai" required>
                            <span class="help_txt">영문 소문자·숫자·밑줄만 사용하세요. 등록 후 변경 불가.</span>
                        <?php } ?>
                    </td></tr>
                <tr><th scope="row"><label for="display_name">공급자명</label></th>
                    <td><input type="text" name="display_name" id="display_name" value="<?php echo get_text($row['display_name']); ?>" class="frm_input" maxlength="100" required></td></tr>
                <tr><th scope="row">사용 여부</th>
                    <td>
                        <label><input type="radio" name="is_active" value="Y" <?php echo $row['is_active'] === 'Y' ? 'checked' : ''; ?>> 사용함</label>
                        <label style="margin-left:15px;"><input type="radio" name="is_active" value="N" <?php echo $row['is_active'] === 'N' ? 'checked' : ''; ?>> 사용안함</label>
                        <span class="help_txt">"사용함"으로 저장하면 다른 공급자는 자동으로 "사용안함"으로 전환됩니다(활성 공급자는 항상 1개).</span>
                    </td></tr>
                <tr><th scope="row"><label for="api_key">API Key</label></th>
                    <td>
                        <input type="password" name="api_key" id="api_key" value="" class="frm_input" autocomplete="new-password" placeholder="새 값을 입력할 때만 교체됩니다">
                        <span class="help_txt"><?php echo $row['masked_hint'] ? '현재 저장된 값: ' . get_text($row['masked_hint']) : '저장된 값 없음'; ?></span>
                    </td></tr>
                <tr><th scope="row"><label for="default_model">기본 모델</label></th>
                    <td><input type="text" name="default_model" id="default_model" value="<?php echo get_text($row['default_model']); ?>" class="frm_input"></td></tr>
                <tr><th scope="row"><label for="max_tokens">최대 토큰</label></th>
                    <td><input type="number" name="max_tokens" id="max_tokens" value="<?php echo (int) $row['max_tokens']; ?>" class="frm_input" min="100" max="8000"></td></tr>
                <tr><th scope="row"><label for="temperature">Temperature</label></th>
                    <td><input type="number" name="temperature" id="temperature" value="<?php echo (float) $row['temperature']; ?>" class="frm_input" min="0" max="1" step="0.1"></td></tr>
            </tbody>
        </table>
    </div>
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="./ai_provider_list.php" class="btn btn_02">목록</a>
    </div>
</form>

<?php if ($id > 0 && $row['masked_hint']) { ?>
<div class="ai-test-wrap" style="margin: 20px 0; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 5px;">
    <h3 style="font-size: 16px; font-weight: bold; margin-bottom: 10px;">연결 테스트</h3>
    <p style="margin-bottom: 15px; color: #666;">저장된 API 키를 사용하여 제공자와의 연결 및 인증 상태를 확인합니다. 과금이 큰 생성 요청 대신 안전한 최소 확인을 수행합니다.</p>
    <button type="button" id="btn_test_connection" class="btn btn_03" style="min-height: 48px; font-size: 16px; padding: 0 20px;">연결 테스트 실행</button>
    <div id="test_result_area" style="display: none; margin-top: 15px; padding: 15px; border-radius: 4px; border: 1px solid #ccc; background: #fafafa; word-break: break-all;">
        <!-- 결과 출력 영역 -->
    </div>
</div>

<script>
$(function() {
    $('#btn_test_connection').on('click', function() {
        var $btn = $(this);
        var $result = $('#test_result_area');
        
        $btn.prop('disabled', true).text('테스트 진행 중...');
        $result.hide().html('');
        
        $.ajax({
            url: SF_MANAGER_URL . '/blog/ai_provider_test.php',
            type: 'POST',
            dataType: 'json',
            data: {
                token: '<?php echo get_admin_token(); ?>',
                id: <?php echo $id; ?>
            },
            success: function(res) {
                $result.show();
                if (res.ok) {
                    $result.css({'border-color': '#28a745', 'background-color': '#eaf9ed', 'color': '#155724'}).html(
                        '<div><strong style="font-size:16px;">[연결 성공]</strong> ' + res.provider + ' 서버와 정상 연결되었습니다.</div>' +
                        '<div style="margin-top:8px;"><strong>테스트 모델:</strong> ' + res.model + ' <em>(' + res.model_status + ')</em></div>' +
                        '<div style="margin-top:4px;"><strong>응답 상태:</strong> HTTP ' + res.http_code + ' (' + res.elapsed_ms + 'ms)</div>' +
                        '<div style="margin-top:8px; font-size:12px; color:#555;">테스트 일시: ' + res.timestamp + '</div>'
                    );
                } else {
                    $result.css({'border-color': '#dc3545', 'background-color': '#fceeed', 'color': '#721c24'}).html(
                        '<div><strong style="font-size:16px;">[연결 실패]</strong></div>' +
                        '<div style="margin-top:8px;">' + res.error + '</div>'
                    );
                }
            },
            error: function(xhr, status, error) {
                $result.show().css({'border-color': '#dc3545', 'background-color': '#fceeed', 'color': '#721c24'}).html(
                    '<div><strong style="font-size:16px;">[통신 오류]</strong></div>' +
                    '<div style="margin-top:8px;">서버와 통신 중 문제가 발생했습니다. (' + error + ')</div>'
                );
            },
            complete: function() {
                $btn.prop('disabled', false).text('연결 테스트 실행');
            }
        });
    });
});
</script>
<?php } ?>

<script>
function faiproviderform_submit(f) {
    if (f.provider_code && !f.provider_code.readOnly && !f.provider_code.value.trim()) {
        alert('공급자 코드를 입력해 주세요.');
        return false;
    }
    if (!f.display_name.value.trim()) {
        alert('공급자명을 입력해 주세요.');
        return false;
    }
    return true;
}
document.forms['faiproviderform'].onsubmit = function() { return faiproviderform_submit(this); };
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
