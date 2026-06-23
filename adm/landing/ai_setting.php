<?php
$sub_menu = "900300"; // AI 환경설정
include_once('./_common.php');
include_once('./ai_crypto.php');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$g5['title'] = 'AI 환경설정';

// 테이블 자동 생성
$table = G5_TABLE_PREFIX . 'landing_ai_config';
$sql_create = "
CREATE TABLE IF NOT EXISTS `{$table}` (
  `config_key` varchar(50) NOT NULL,
  `config_value` text,
  `updated_at` datetime NOT NULL,
  `updated_by` varchar(20) NOT NULL,
  PRIMARY KEY (`config_key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
";
@sql_query($sql_create, false);

// 설정값 불러오기
$sql = " select * from {$table} ";
$result = sql_query($sql);
$ai_cfg = array();
while($row = sql_fetch_array($result)){
    $ai_cfg[$row['config_key']] = $row['config_value'];
}

// 기본값 세팅
$openai_use = isset($ai_cfg['openai_use']) ? $ai_cfg['openai_use'] : 'N';
$openai_endpoint = isset($ai_cfg['openai_endpoint']) ? $ai_cfg['openai_endpoint'] : 'https://api.openai.com/v1';
$openai_api_key_enc = isset($ai_cfg['openai_api_key']) ? $ai_cfg['openai_api_key'] : '';
$openai_api_key_plain = ai_decrypt($openai_api_key_enc);

$copy_model_name = isset($ai_cfg['copy_model_name']) ? $ai_cfg['copy_model_name'] : 'gpt-4o';
$image_model_name = isset($ai_cfg['image_model_name']) ? $ai_cfg['image_model_name'] : 'dall-e-3';
$max_tokens = isset($ai_cfg['max_tokens']) ? (int)$ai_cfg['max_tokens'] : 2000;
$temperature = isset($ai_cfg['temperature']) ? (float)$ai_cfg['temperature'] : 0.7;

$system_role = isset($ai_cfg['system_role']) ? $ai_cfg['system_role'] : "너는 프리미엄 마케팅 랜딩페이지 카피라이터 전문가이다. 전환율을 높이는 UI 구조와 헤드카피를 제안해라.";
$system_forbidden = isset($ai_cfg['system_forbidden']) ? $ai_cfg['system_forbidden'] : "어색한 번역투, 욕설, 경쟁사 비방 내용을 포함하지 말 것.";

include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<style>
.ai_tabs { display:flex; border-bottom:2px solid #1d4ed8; margin-bottom:20px; }
.ai_tab { padding:12px 24px; font-size:15px; font-weight:bold; color:#6b7280; background:#f3f4f6; border-radius:8px 8px 0 0; margin-right:5px; cursor:pointer; }
.ai_tab.active { background:#1d4ed8; color:#fff; }
.ai_tab_content { display:none; }
.ai_tab_content.active { display:block; }
.help_txt { font-size:12px; color:#6b7280; margin-top:5px; display:block; }
.pwd_wrap { position:relative; display:inline-block; width:100%; max-width:400px; }
.pwd_wrap input { width:100%; padding-right:40px; }
.pwd_wrap .eye_btn { position:absolute; right:10px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:16px; color:#6b7280; }
</style>

<form name="faisetting" method="post" action="./ai_setting_update.php">
    
<div class="ai_tabs">
    <div class="ai_tab active" data-target="tab1">API 연동 설정</div>
    <div class="ai_tab" data-target="tab2">모델/파라미터 설정</div>
    <div class="ai_tab" data-target="tab3">시스템 프롬프트 설정</div>
</div>

<!-- TAB 1: API 연동 설정 -->
<div id="tab1" class="ai_tab_content active tbl_frm01 tbl_wrap">
    <h2>OpenAI 플랫폼 설정</h2>
    <table>
    <caption>OpenAI 설정</caption>
    <tbody>
        <tr>
            <th scope="row">API 활성화 여부</th>
            <td>
                <label><input type="radio" name="openai_use" value="Y" <?php echo $openai_use == 'Y' ? 'checked' : ''; ?>> 사용함</label>
                &nbsp;&nbsp;
                <label><input type="radio" name="openai_use" value="N" <?php echo $openai_use == 'N' ? 'checked' : ''; ?>> 사용안함</label>
            </td>
        </tr>
        <tr>
            <th scope="row">API Endpoint URL</th>
            <td>
                <input type="text" name="openai_endpoint" value="<?php echo get_text($openai_endpoint); ?>" class="frm_input" size="50">
                <span class="help_txt">기본 주소: https://api.openai.com/v1</span>
            </td>
        </tr>
        <tr>
            <th scope="row">API Secret Key</th>
            <td>
                <div class="pwd_wrap">
                    <input type="password" name="openai_api_key" id="openai_api_key" value="<?php echo get_text($openai_api_key_plain); ?>" class="frm_input">
                    <span class="eye_btn" onclick="togglePwd('openai_api_key')">👁</span>
                </div>
                <button type="button" class="btn btn_03" id="btn_api_test" style="margin-left:10px;">연동 테스트</button>
                <span class="help_txt">저장 시 자체 보안 로직(AES-256)을 통해 DB에 안전하게 암호화되어 기록됩니다.</span>
            </td>
        </tr>
    </tbody>
    </table>
</div>

<!-- TAB 2: 모델/파라미터 설정 -->
<div id="tab2" class="ai_tab_content tbl_frm01 tbl_wrap">
    <h2>기능별 기본 모델 설정</h2>
    <table>
    <caption>파라미터 설정</caption>
    <tbody>
        <tr>
            <th scope="row">카피라이팅 생성 엔진</th>
            <td>
                <select name="copy_model_name">
                    <option value="gpt-4o" <?php echo get_selected($copy_model_name, 'gpt-4o'); ?>>gpt-4o (권장)</option>
                    <option value="gpt-4-turbo" <?php echo get_selected($copy_model_name, 'gpt-4-turbo'); ?>>gpt-4-turbo</option>
                    <option value="gpt-3.5-turbo" <?php echo get_selected($copy_model_name, 'gpt-3.5-turbo'); ?>>gpt-3.5-turbo (가성비)</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">이미지 편집/생성 엔진</th>
            <td>
                <select name="image_model_name">
                    <option value="dall-e-3" <?php echo get_selected($image_model_name, 'dall-e-3'); ?>>DALL-E 3 (권장)</option>
                    <option value="dall-e-2" <?php echo get_selected($image_model_name, 'dall-e-2'); ?>>DALL-E 2</option>
                </select>
            </td>
        </tr>
        <tr>
            <th scope="row">최대 토큰수 (Max Tokens)</th>
            <td>
                <input type="number" name="max_tokens" value="<?php echo $max_tokens; ?>" class="frm_input" size="10" min="100" max="8000">
                <span class="help_txt">1회 API 호출당 최대로 소모할 수 있는 토큰 제한 (비용 통제용)</span>
            </td>
        </tr>
        <tr>
            <th scope="row">창의성 수치 (Temperature)</th>
            <td>
                <input type="range" name="temperature_range" id="temperature_range" min="0" max="1" step="0.1" value="<?php echo $temperature; ?>" style="vertical-align:middle;">
                <input type="text" name="temperature" id="temperature_val" value="<?php echo $temperature; ?>" class="frm_input" size="5" readonly style="border:none; font-weight:bold; width:40px;">
                <span class="help_txt">0.0 (일관되고 정형화된 결과) ~ 1.0 (창의적이고 다양한 결과물)</span>
            </td>
        </tr>
    </tbody>
    </table>
</div>

<!-- TAB 3: 시스템 프롬프트 설정 -->
<div id="tab3" class="ai_tab_content tbl_frm01 tbl_wrap">
    <h2>시스템 프롬프트 (System Prompt Engineering)</h2>
    <table>
    <caption>프롬프트 설정</caption>
    <tbody>
        <tr>
            <th scope="row">기본 페르소나 설정<br>(System Role)</th>
            <td>
                <textarea name="system_role" style="width:100%; height:100px;"><?php echo get_text($system_role); ?></textarea>
                <span class="help_txt">AI가 결과물을 생성할 때 기본적으로 부여받는 역할과 핵심 목적을 기재합니다.</span>
            </td>
        </tr>
        <tr>
            <th scope="row">금지어 및 제약사항</th>
            <td>
                <textarea name="system_forbidden" style="width:100%; height:80px;"><?php echo get_text($system_forbidden); ?></textarea>
                <span class="help_txt">절대 포함하면 안 되는 단어나 문체 가이드라인을 입력합니다.</span>
            </td>
        </tr>
    </tbody>
    </table>
</div>

<div class="btn_confirm01 btn_confirm">
    <input type="submit" value="설정 저장" class="btn_submit btn" accesskey="s">
    <button type="button" class="btn btn_02" onclick="location.reload();">초기화</button>
</div>

</form>

<script>
// 탭 전환 기능
$('.ai_tab').on('click', function(){
    $('.ai_tab').removeClass('active');
    $(this).addClass('active');
    $('.ai_tab_content').removeClass('active');
    $('#' + $(this).data('target')).addClass('active');
});

// 비밀번호 마스킹 토글
function togglePwd(id) {
    var input = document.getElementById(id);
    if(input.type === "password"){
        input.type = "text";
    } else {
        input.type = "password";
    }
}

// Temperature Range 동기화
$('#temperature_range').on('input', function(){
    $('#temperature_val').val($(this).val());
});

// 연동 테스트 (Ajax)
$('#btn_api_test').on('click', function(){
    var endpoint = $('input[name="openai_endpoint"]').val();
    var key = $('#openai_api_key').val();
    
    if(!key) {
        alert('API Secret Key를 입력해주세요.');
        return;
    }
    
    $(this).text('테스트 중...').prop('disabled', true);
    
    $.post('./ai_api_test.php', { endpoint: endpoint, api_key: key }, function(res){
        $('#btn_api_test').text('연동 테스트').prop('disabled', false);
        if(res.success) {
            alert('🎉 연동 테스트 성공!\n모델 리스트를 정상적으로 불러왔습니다.');
        } else {
            alert('❌ 연동 실패:\n' + res.error);
        }
    }, 'json').fail(function(){
        $('#btn_api_test').text('연동 테스트').prop('disabled', false);
        alert('서버 통신 중 오류가 발생했습니다.');
    });
});
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
