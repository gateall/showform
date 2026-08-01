<?php
if (!defined('_GNUBOARD_')) exit;
// 공용 폼 조각 - manager/blog/ai_provider_form.php(단독 페이지)와
// manager/blog/settings.php(설정 > AI 설정 탭, 같은 화면 안 인라인 폼) 양쪽이
// 이 파일 하나를 그대로 include한다. 저장·암호화·연결테스트 로직은 여기서 만들지
// 않고 기존 처리 파일(ai_provider_update.php/ai_provider_test.php)을 그대로 재사용한다.
//
// 호출부가 미리 준비해야 하는 변수: $row, $id, $admin_token
// 선택: $is_inline (true면 settings.php 안에 삽입되는 인라인 모드 - 취소 버튼이 페이지
//       이동 대신 닫기 동작을 하고, 저장/삭제 후 항상 설정 탭으로 돌아오게 return 값을 심는다)
$is_inline = isset($is_inline) ? (bool) $is_inline : false;

// AI 종류 => [표시 라벨, 모델 목록]. 모델 목록은 자주 바뀌므로 여기 배열만 고치면
// 화면에 바로 반영된다. "실제 호출 지원 여부"는 더 이상 이 배열에 하드코딩하지 않고
// bp_ai_provider_is_live($code)를 그대로 물어본다(openai, gemini) - blog_ai_service.lib.php의
// BlogOpenAiProvider/BlogGeminiProvider가 실제 구현이다. 그 외(웹 검색 포함)는 등록·키
// 저장은 되지만 실제 생성/조회 시 안전한 템플릿 생성기로 자동 전환된다(가짜로 "연동됨"처럼
// 보이게 하지 않기 위함 - 실제 연동은 추후 단계). 새 공급자를 실제로 연동하면 이 배열이
// 아니라 bp_ai_provider_is_live()에 코드를 추가해야 여기 배지도 자동으로 맞게 바뀐다.
$ai_types = array(
    'openai' => array('label' => '챗GPT', 'models' => array('gpt-4o', 'gpt-4o-mini', 'gpt-4.1', 'gpt-4.1-mini', 'gpt-5')),
    'gemini' => array('label' => '제미나이', 'models' => array('gemini-2.5-pro', 'gemini-2.5-flash')),
    'anthropic' => array('label' => '클로드', 'models' => array('claude-opus-4', 'claude-sonnet-4', 'claude-haiku-4')),
    'deepseek' => array('label' => '딥시크', 'models' => array('deepseek-chat', 'deepseek-reasoner')),
    'xai' => array('label' => '그록', 'models' => array('grok-4', 'grok-3')),
    'websearch' => array('label' => '웹 검색', 'models' => array()),
    'custom' => array('label' => '직접 설정', 'models' => array()),
);
foreach ($ai_types as $ai_type_code => &$ai_type_info) {
    $ai_type_info['live'] = bp_ai_provider_is_live($ai_type_code);
}
unset($ai_type_info);
$current_type = isset($ai_types[$row['provider_code']]) ? $row['provider_code'] : ($id > 0 ? 'custom' : 'openai');
$return_field = $is_inline ? 'manager' : '';
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
    <p>실제 외부 API 호출은 현재 <strong>챗GPT(OpenAI)</strong>와 <strong>제미나이(Gemini)</strong>만 지원합니다. 다른 종류는 등록·키 저장은 되지만, 포스팅 화면에서 실제 생성을 시도하면 조용히 대체되지 않고 챗GPT로 변경할지 템플릿으로 생성할지 먼저 선택하게 됩니다(연동 예정).</p>
</div>

<form name="faiproviderform" method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/ai_provider_update.php">
    <input type="hidden" name="token" value="<?php echo get_text($admin_token); ?>">
    <input type="hidden" name="return" value="<?php echo get_text($return_field); ?>">
    <?php if ($id > 0) { ?>
    <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
    <?php } ?>
    <?php if ($id > 0) { ?>
    <input type="hidden" name="provider_code" id="provider_code_hidden" value="<?php echo get_text($row['provider_code']); ?>">
<?php } else { ?>
    <!-- New provider: show input for provider code -->
    <div class="sf-field" style="margin-bottom:0.75rem;">
        <label for="provider_code_input"><strong>공급자 코드</strong> (영문 소문자·숫자·밑줄, 30자 이하)</label>
        <input type="text" name="provider_code" id="provider_code_input" class="frm_input" maxlength="30" placeholder="예: my_custom_ai" required style="width:100%;">
        <span class="help_txt">새 공급자를 등록할 때만 입력합니다.</span>
    </div>
<?php } ?>


    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo $id > 0 ? 'AI 공급자 수정' : 'AI 공급자 등록'; ?></caption>
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
                        <?php if ($row['masked_hint']) { ?>
                        <div id="api_key_current_wrap" style="display:flex; gap:6px; align-items:center; margin-bottom:10px; flex-wrap:wrap;">
                            <code id="api_key_current_display" data-masked="<?php echo get_text($row['masked_hint']); ?>" style="flex:1; padding:8px 10px; background:#f5f5f5; border:1px solid #ddd; border-radius:4px; letter-spacing:1px;"><?php echo get_text($row['masked_hint']); ?></code>
                            <button type="button" class="btn btn_02" id="btn_reveal_api_key" onclick="revealApiKey(<?php echo (int) $id; ?>)">보기</button>
                            <span id="api_key_reveal_prompt" style="display:none; gap:6px; align-items:center;">
                                <input type="password" id="api_key_reveal_password" class="frm_input" autocomplete="off" placeholder="관리자 비밀번호" style="width:160px;" onkeydown="if(event.key==='Enter'){event.preventDefault();confirmRevealApiKey(<?php echo (int) $id; ?>);}">
                                <button type="button" class="btn btn_02" id="btn_confirm_reveal_api_key" onclick="confirmRevealApiKey(<?php echo (int) $id; ?>)">확인</button>
                                <button type="button" class="btn btn_02" onclick="cancelRevealApiKey()">취소</button>
                            </span>
                            <a href="<?php echo G5_ADMIN_URL; ?>/blog/ai_provider_key_delete.php?id=<?php echo (int) $id; ?>&amp;token=<?php echo get_text($admin_token); ?><?php echo $is_inline ? '&amp;return=manager' : ''; ?>" class="btn btn_01" onclick="return confirm('저장된 API 키를 삭제하시겠습니까? 이후 AI 호출 시 키를 다시 입력해야 합니다.');">키 삭제</a>
                        </div>
                        <?php } ?>
                        <div style="display:flex; gap:6px; align-items:center;">
                            <input type="password" name="api_key" id="api_key" value="" class="frm_input" autocomplete="new-password" placeholder="<?php echo $row['masked_hint'] ? '새 값을 입력할 때만 교체됩니다' : 'API 키를 입력하세요'; ?>" style="flex:1;">
                            <button type="button" class="btn btn_02" id="btn_toggle_api_key" onclick="toggleApiKeyVisible()">입력값 보기</button>
                        </div>
                        <span class="help_txt"><?php echo $row['masked_hint'] ? '위 마스킹된 값이 현재 저장된 키입니다. "보기"를 누르면 관리자 비밀번호 확인 후 실제 값을 보여주며, 20초 후 자동으로 다시 가려집니다. 아래 입력칸은 교체할 새 값을 넣을 때만 사용하세요.' : '저장된 값 없음 · 붙여넣기가 안 되면 "입력값 보기"를 눌러 직접 확인하며 입력해 보세요.'; ?></span>
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
        <a href="<?php echo G5_ADMIN_URL; ?>/blog/ai_provider_delete.php?id=<?php echo (int)$id; ?>&amp;token=<?php echo get_text($admin_token); ?><?php echo $is_inline ? '&amp;return=manager' : ''; ?>" class="btn btn_01" style="<?php echo $id > 0 ? '' : 'display:none;'; ?>" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
        <?php if ($is_inline) { ?>
            <button type="button" class="btn btn_02" onclick="if(window.closeAiProviderForm) window.closeAiProviderForm();">취소</button>
        <?php } else { ?>
            <a href="./settings.php?tab=ai" class="btn btn_02">목록</a>
        <?php } ?>
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
(function() {
    var btn = document.getElementById('btn_test_connection');
    if (!btn) return;
    btn.addEventListener('click', function() {
        var result = document.getElementById('test_result_area');

        btn.disabled = true;
        btn.textContent = '테스트 진행 중...';
        result.style.display = 'none';
        result.innerHTML = '';

        var params = new URLSearchParams();
        // 저장 폼이 쓰는 관리자 토큰과는 별개인 테스트 전용 토큰 - 테스트를 실행해도
        // 저장 버튼이 계속 정상 작동한다(저장용 토큰을 건드리지 않음).
        params.set('test_token', <?php echo json_encode(bp_get_test_token()); ?>);
        params.set('id', <?php echo (int) $id; ?>);

        fetch(<?php echo json_encode(G5_ADMIN_URL . '/blog/ai_provider_test.php'); ?>, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(function(res) { return res.json(); })
        .then(function(res) {
            result.style.display = '';
            if (res.ok) {
                result.style.borderColor = '#28a745';
                result.style.backgroundColor = '#eaf9ed';
                result.style.color = '#155724';
                result.innerHTML =
                    '<div><strong style="font-size:16px;">[연결 성공]</strong> ' + res.provider + ' 서버와 정상 연결되었습니다.</div>' +
                    '<div style="margin-top:8px;"><strong>테스트 모델:</strong> ' + res.model + ' <em>(' + res.model_status + ')</em></div>' +
                    '<div style="margin-top:4px;"><strong>응답 상태:</strong> HTTP ' + res.http_code + ' (' + res.elapsed_ms + 'ms)</div>' +
                    '<div style="margin-top:8px; font-size:12px; color:#555;">테스트 일시: ' + res.timestamp + '</div>';
            } else {
                result.style.borderColor = '#dc3545';
                result.style.backgroundColor = '#fceeed';
                result.style.color = '#721c24';
                result.innerHTML =
                    '<div><strong style="font-size:16px;">[연결 실패]</strong></div>' +
                    '<div style="margin-top:8px;">' + res.error + '</div>';
            }
        })
        .catch(function() {
            result.style.display = '';
            result.style.borderColor = '#dc3545';
            result.style.backgroundColor = '#fceeed';
            result.style.color = '#721c24';
            result.innerHTML =
                '<div><strong style="font-size:16px;">[통신 오류]</strong></div>' +
                '<div style="margin-top:8px;">서버와 통신 중 문제가 발생했습니다.</div>';
        })
        .finally(function() {
            btn.disabled = false;
            btn.textContent = '연결 테스트 실행';
        });
    });
})();
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

function toggleApiKeyVisible() {
    var input = document.getElementById('api_key');
    var btn = document.getElementById('btn_toggle_api_key');
    var showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    btn.innerText = showing ? '입력값 보기' : '숨기기';
}

var _bpRevealTimer = null;

function _bpRemaskApiKey() {
    var display = document.getElementById('api_key_current_display');
    var btn = document.getElementById('btn_reveal_api_key');
    if (_bpRevealTimer) {
        clearTimeout(_bpRevealTimer);
        _bpRevealTimer = null;
    }
    if (!display || !btn || btn.dataset.revealed !== '1') return;
    display.textContent = display.getAttribute('data-masked');
    btn.textContent = '보기';
    btn.dataset.revealed = '0';
}

// 화면에 평문 키를 오래 띄워두지 않도록, 보여준 뒤 일정 시간이 지나면 자동으로 다시 마스킹한다.
var BP_REVEAL_TIMEOUT_MS = 20000;

// "보기"는 매번 관리자 비밀번호를 다시 물어본다(클라이언트에 평문을 캐싱해 재사용하지
// 않음) - 세션이 살아있다는 것만으로 바로 열람되지 않게 하기 위함.
function revealApiKey(id) {
    var btn = document.getElementById('btn_reveal_api_key');
    if (!btn) return;

    if (btn.dataset.revealed === '1') {
        _bpRemaskApiKey();
        return;
    }

    var prompt = document.getElementById('api_key_reveal_prompt');
    var pwInput = document.getElementById('api_key_reveal_password');
    btn.style.display = 'none';
    prompt.style.display = 'inline-flex';
    pwInput.value = '';
    pwInput.focus();
}

function cancelRevealApiKey() {
    var btn = document.getElementById('btn_reveal_api_key');
    var prompt = document.getElementById('api_key_reveal_prompt');
    var pwInput = document.getElementById('api_key_reveal_password');
    pwInput.value = '';
    prompt.style.display = 'none';
    btn.style.display = '';
}

function confirmRevealApiKey(id) {
    var pwInput = document.getElementById('api_key_reveal_password');
    var okBtn = document.getElementById('btn_confirm_reveal_api_key');
    var password = pwInput.value;
    if (!password) {
        if (window.mgrToast) { window.mgrToast('비밀번호를 입력해 주세요.', 'warning'); }
        return;
    }

    okBtn.disabled = true;
    okBtn.textContent = '확인 중...';

    var params = new URLSearchParams();
    params.set('test_token', <?php echo json_encode(bp_get_test_token()); ?>);
    params.set('id', String(id));
    params.set('admin_password', password);

    fetch(<?php echo json_encode(G5_ADMIN_URL . '/blog/ai_provider_key_reveal.php'); ?>, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params.toString()
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        // 성공/실패와 무관하게 입력했던 비밀번호는 필드에 남기지 않는다.
        pwInput.value = '';
        okBtn.disabled = false;
        okBtn.textContent = '확인';

        if (data.ok) {
            cancelRevealApiKey();
            var display = document.getElementById('api_key_current_display');
            var btn = document.getElementById('btn_reveal_api_key');
            display.textContent = data.api_key;
            btn.style.display = '';
            btn.textContent = '숨기기';
            btn.dataset.revealed = '1';
            if (_bpRevealTimer) clearTimeout(_bpRevealTimer);
            _bpRevealTimer = setTimeout(_bpRemaskApiKey, BP_REVEAL_TIMEOUT_MS);
        } else {
            var msg = data.error || 'API 키 확인에 실패했습니다.';
            if (window.mgrToast) { window.mgrToast(msg, 'danger'); } else { alert(msg); }
        }
    })
    .catch(function() {
        pwInput.value = '';
        okBtn.disabled = false;
        okBtn.textContent = '확인';
        var msg = '서버와 통신 중 문제가 발생했습니다.';
        if (window.mgrToast) { window.mgrToast(msg, 'danger'); } else { alert(msg); }
    });
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
