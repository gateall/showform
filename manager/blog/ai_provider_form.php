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
        'masked_hint' => '', 'default_model' => '', 'api_endpoint' => '', 'max_tokens' => 2000, 'temperature' => 0.70,
    );
}

$g5['title'] = $id > 0 ? 'AI 공급자 수정' : 'AI 공급자 등록';

// get_admin_token()은 호출할 때마다 세션 값을 새로 덮어쓴다 - 한 페이지 안에서 두 번
// 부르면 먼저 화면에 찍힌 값(폼)은 세션과 어긋난 채로 남아 저장 시 "올바른 방법으로
// 이용해 주십시오" 오류가 난다. 반드시 한 번만 호출해서 재사용해야 한다.
$admin_token = get_admin_token();

// AI 종류 => [표시 라벨, 실제 호출 지원 여부, 모델 목록]. 모델 목록은 자주 바뀌므로
// 여기 배열만 고치면 화면에 바로 반영된다. 실제 API 호출은 openai만 지원한다
// (blog_ai_service.lib.php의 BlogOpenAiProvider) - 나머지는 등록·키 저장은 되지만
// 실제 생성 시 안전한 템플릿 생성기로 자동 전환된다(가짜로 "연동됨"처럼 보이게 하지 않기 위함).
$ai_types = array(
    'openai' => array('label' => '챗GPT', 'live' => true, 'models' => array('gpt-4o', 'gpt-4o-mini', 'gpt-4.1', 'gpt-4.1-mini', 'gpt-5')),
    'gemini' => array('label' => '제미나이', 'live' => false, 'models' => array('gemini-2.5-pro', 'gemini-2.5-flash')),
    'anthropic' => array('label' => '클로드', 'live' => false, 'models' => array('claude-opus-4', 'claude-sonnet-4', 'claude-haiku-4')),
    'deepseek' => array('label' => '딥시크', 'live' => false, 'models' => array('deepseek-chat', 'deepseek-reasoner')),
    'xai' => array('label' => '그록', 'live' => false, 'models' => array('grok-4', 'grok-3')),
    'custom' => array('label' => '직접 설정', 'live' => false, 'models' => array()),
);
$current_type = isset($ai_types[$row['provider_code']]) ? $row['provider_code'] : ($id > 0 ? 'custom' : 'openai');

include_once(__DIR__ . '/../layout/header.php');
?>
<style>
.ai-type-grid{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:8px;}
.ai-type-btn{padding:12px 18px;border:2px solid #cbd5e1;border-radius:8px;background:#fff;cursor:pointer;font-size:0.95rem;text-align:center;min-width:90px;}
.ai-type-btn.active{border-color:#4f46e5;background:#eef2ff;color:#4338ca;font-weight:bold;}
.ai-type-btn .live-badge{display:block;font-size:0.7rem;color:#10b981;margin-top:2px;}
.ai-type-btn .fallback-badge{display:block;font-size:0.7rem;color:#94a3b8;margin-top:2px;}
.ai-toggle-grid{display:flex;gap:10px;}
.ai-toggle-btn{padding:14px 30px;border:2px solid #cbd5e1;border-radius:8px;background:#fff;cursor:pointer;font-size:1rem;font-weight:bold;}
.ai-toggle-btn.on.active{border-color:#10b981;background:#ecfdf5;color:#047857;}
.ai-toggle-btn.off.active{border-color:#dc2626;background:#fef2f2;color:#b91c1c;}
</style>
<div class="local_desc01 local_desc">
    <p>실제 외부 API 호출은 현재 <strong>챗GPT(OpenAI)</strong>만 지원합니다. 다른 종류는 등록·키 저장은 되지만, 실제 생성 요청 시 안전한 템플릿 생성기로 자동 전환됩니다(연동 예정).</p>
</div>

<form name="faiproviderform" method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/ai_provider_update.php">
    <input type="hidden" name="token" value="<?php echo get_text($admin_token); ?>">
    <?php if ($id > 0) { ?>
    <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
    <?php } ?>
    <input type="hidden" name="provider_code" id="provider_code_hidden" value="<?php echo get_text($row['provider_code']); ?>">

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo get_text($g5['title']); ?></caption>
            <tbody>
                <tr><th scope="row">AI 종류</th>
                    <td>
                        <?php if ($id > 0) { ?>
                            <div class="ai-type-btn active" style="display:inline-block;"><?php echo get_text($ai_types[$current_type]['label']); ?></div>
                            <span class="help_txt">등록 후에는 AI 종류를 변경할 수 없습니다(새로 등록해 주세요).</span>
                        <?php } else { ?>
                            <div class="ai-type-grid" id="ai_type_grid">
                                <?php foreach ($ai_types as $code => $info) { ?>
                                    <div class="ai-type-btn" data-code="<?php echo $code; ?>" onclick="selectAiType('<?php echo $code; ?>')">
                                        <?php echo get_text($info['label']); ?>
                                        <?php echo $info['live'] ? '<span class="live-badge">연동됨</span>' : '<span class="fallback-badge">준비 중</span>'; ?>
                                    </div>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    </td></tr>
                <tr><th scope="row"><label for="display_name">공급자명</label></th>
                    <td>
                        <input type="text" name="display_name" id="display_name" value="<?php echo get_text($row['display_name']); ?>" class="frm_input" maxlength="100" required <?php echo ($id === 0 && $current_type !== 'custom') ? 'readonly style="background:#f5f5f5;"' : ''; ?>>
                        <span class="help_txt" id="display_name_help" style="<?php echo ($current_type === 'custom') ? 'display:none;' : ''; ?>">AI 종류를 선택하면 자동으로 채워집니다. "직접 설정"을 고르면 직접 입력할 수 있습니다.</span>
                    </td></tr>
                <tr id="row_custom_code" style="<?php echo $current_type === 'custom' ? '' : 'display:none;'; ?>">
                    <th scope="row"><label for="custom_provider_code">공급자 코드(직접 설정)</label></th>
                    <td>
                        <?php if ($id > 0) { ?>
                            <input type="text" value="<?php echo get_text($row['provider_code']); ?>" class="frm_input" readonly style="background:#f5f5f5;">
                        <?php } else { ?>
                            <input type="text" id="custom_provider_code" value="" class="frm_input" maxlength="30" placeholder="예: my_custom_ai" oninput="document.getElementById('provider_code_hidden').value=this.value.toLowerCase();">
                            <span class="help_txt">영문 소문자·숫자·밑줄만 사용하세요.</span>
                        <?php } ?>
                    </td></tr>
                <tr><th scope="row">세부 AI 모델</th>
                    <td>
                        <select id="model_select" class="frm_input" onchange="onModelSelectChange()"></select>
                        <div style="margin-top:8px;">
                            <label><input type="radio" name="model_mode" id="model_mode_list" value="list" checked> 목록에서 선택</label>
                            <label style="margin-left:15px;"><input type="radio" name="model_mode" id="model_mode_custom" value="custom"> 모델명 직접 입력</label>
                        </div>
                        <input type="text" id="model_custom_input" class="frm_input" style="margin-top:6px; display:none;" placeholder="직접 입력할 모델명">
                        <input type="hidden" name="default_model" id="default_model_hidden" value="<?php echo get_text($row['default_model']); ?>">
                    </td></tr>
                <tr><th scope="row"><label for="api_key">API Key</label></th>
                    <td>
                        <input type="password" name="api_key" id="api_key" value="" class="frm_input" autocomplete="new-password" placeholder="새 값을 입력할 때만 교체됩니다">
                        <span class="help_txt"><?php echo $row['masked_hint'] ? '현재 저장된 값: ' . get_text($row['masked_hint']) : '저장된 값 없음'; ?></span>
                    </td></tr>
                <tr><th scope="row">사용 상태</th>
                    <td>
                        <div class="ai-toggle-grid">
                            <div class="ai-toggle-btn on <?php echo $row['is_active'] === 'Y' ? 'active' : ''; ?>" onclick="setActive('Y')">사용함</div>
                            <div class="ai-toggle-btn off <?php echo $row['is_active'] !== 'Y' ? 'active' : ''; ?>" onclick="setActive('N')">사용 안 함</div>
                        </div>
                        <input type="hidden" name="is_active" id="is_active_hidden" value="<?php echo $row['is_active'] === 'Y' ? 'Y' : 'N'; ?>">
                        <span class="help_txt">여러 공급자를 동시에 "사용함" 상태로 둘 수 있습니다. 프로젝트마다 그중 하나를 골라 씁니다.</span>
                    </td></tr>
            </tbody>
        </table>
    </div>

    <details class="tbl_frm01 tbl_wrap" style="margin-top:15px; padding:15px;">
        <summary style="cursor:pointer; font-weight:bold;">▶ 고급 설정</summary>
        <table style="margin-top:10px;">
            <tbody>
                <tr><th scope="row"><label for="max_tokens">최대 토큰</label></th>
                    <td><input type="number" name="max_tokens" id="max_tokens" value="<?php echo (int) $row['max_tokens']; ?>" class="frm_input" min="100" max="8000"></td></tr>
                <tr><th scope="row"><label for="temperature">Temperature</label></th>
                    <td><input type="number" name="temperature" id="temperature" value="<?php echo (float) $row['temperature']; ?>" class="frm_input" min="0" max="1" step="0.1"></td></tr>
                <tr><th scope="row"><label for="api_endpoint">API 주소</label></th>
                    <td>
                        <input type="text" name="api_endpoint" id="api_endpoint" value="<?php echo get_text($row['api_endpoint']); ?>" class="frm_input" placeholder="비워두면 기본 주소 사용 (예: https://api.openai.com/v1)">
                        <span class="help_txt">Azure OpenAI 등 프록시를 쓸 때만 입력하세요.</span>
                    </td></tr>
            </tbody>
        </table>
    </details>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="<?php echo G5_ADMIN_URL; ?>/blog/ai_provider_delete.php?id=<?php echo (int)$id; ?>&amp;token=<?php echo get_text($admin_token); ?>" class="btn btn_01" style="<?php echo $id > 0 ? '' : 'display:none;'; ?>" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
        <a href="./settings.php?tab=ai" class="btn btn_02">목록</a>
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
            url: '<?php echo G5_ADMIN_URL; ?>/blog/ai_provider_test.php',
            type: 'POST',
            dataType: 'json',
            data: {
                token: '<?php echo get_text($admin_token); ?>',
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
var AI_TYPES = <?php
    $ai_types_js = array();
    foreach ($ai_types as $code => $info) {
        $ai_types_js[$code] = array('label' => $info['label'], 'models' => $info['models']);
    }
    echo json_encode($ai_types_js);
?>;
var CURRENT_TYPE = <?php echo json_encode($current_type); ?>;
var CURRENT_MODEL = <?php echo json_encode($row['default_model']); ?>;
var IS_EDIT = <?php echo $id > 0 ? 'true' : 'false'; ?>;

function selectAiType(code) {
    document.querySelectorAll('#ai_type_grid .ai-type-btn').forEach(function(el) {
        el.classList.toggle('active', el.getAttribute('data-code') === code);
    });
    document.getElementById('provider_code_hidden').value = code;
    document.getElementById('row_custom_code').style.display = (code === 'custom') ? '' : 'none';

    // 공급자명은 AI 종류에서 그대로 가져온다 - "직접 설정"일 때만 사용자가 입력한다.
    var nameInput = document.getElementById('display_name');
    var nameHelp = document.getElementById('display_name_help');
    if (code === 'custom') {
        nameInput.readOnly = false;
        nameInput.style.background = '';
        nameHelp.style.display = 'none';
    } else {
        nameInput.value = AI_TYPES[code].label;
        nameInput.readOnly = true;
        nameInput.style.background = '#f5f5f5';
        nameHelp.style.display = '';
    }
    populateModelSelect(code);
}

function populateModelSelect(code) {
    var sel = document.getElementById('model_select');
    sel.innerHTML = '';
    var models = AI_TYPES[code] ? AI_TYPES[code].models : [];
    if (models.length === 0) {
        sel.innerHTML = '<option value="">(직접 입력을 이용하세요)</option>';
        sel.disabled = true;
        document.getElementById('model_mode_custom').checked = true;
        toggleModelMode();
        return;
    }
    sel.disabled = false;
    models.forEach(function(m) {
        var opt = document.createElement('option');
        opt.value = m;
        opt.text = m;
        sel.appendChild(opt);
    });
    if (models.indexOf(CURRENT_MODEL) >= 0) {
        sel.value = CURRENT_MODEL;
    } else if (CURRENT_MODEL) {
        document.getElementById('model_mode_custom').checked = true;
        document.getElementById('model_custom_input').value = CURRENT_MODEL;
        toggleModelMode();
    }
    onModelSelectChange();
}

function toggleModelMode() {
    var custom = document.getElementById('model_mode_custom').checked;
    document.getElementById('model_select').style.display = custom ? 'none' : '';
    document.getElementById('model_custom_input').style.display = custom ? '' : 'none';
    onModelSelectChange();
}

function onModelSelectChange() {
    var custom = document.getElementById('model_mode_custom').checked;
    var value = custom ? document.getElementById('model_custom_input').value.trim() : document.getElementById('model_select').value;
    document.getElementById('default_model_hidden').value = value;
}

function setActive(v) {
    document.getElementById('is_active_hidden').value = v;
    document.querySelectorAll('.ai-toggle-btn.on').forEach(function(el) { el.classList.toggle('active', v === 'Y'); });
    document.querySelectorAll('.ai-toggle-btn.off').forEach(function(el) { el.classList.toggle('active', v === 'N'); });
}

document.getElementById('model_mode_list').addEventListener('change', toggleModelMode);
document.getElementById('model_mode_custom').addEventListener('change', toggleModelMode);
document.getElementById('model_custom_input').addEventListener('input', onModelSelectChange);

if (!IS_EDIT) {
    selectAiType(CURRENT_TYPE);
} else {
    populateModelSelect(CURRENT_TYPE);
}

function faiproviderform_submit(f) {
    if (!f.provider_code_hidden.value.trim()) {
        alert('AI 종류를 선택해 주세요.');
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

<?php include_once(__DIR__ . '/../layout/footer.php');
