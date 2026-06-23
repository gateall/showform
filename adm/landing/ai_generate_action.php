<?php
include_once('./_common.php');
include_once('./ai_crypto.php');
header('Content-Type: application/json; charset=utf-8');

if ($is_admin != 'super') {
    echo json_encode(array('error' => '권한이 없습니다.'));
    exit;
}

$industry = isset($_POST['industry']) ? trim($_POST['industry']) : '';
$region = isset($_POST['region']) ? trim($_POST['region']) : '';
$service_name = isset($_POST['service_name']) ? trim($_POST['service_name']) : '';
$phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';

if (!$industry || !$service_name || !$phone) {
    echo json_encode(array('error' => '필수 값이 누락되었습니다.'));
    exit;
}

// AI 설정 로드
$config_table = G5_TABLE_PREFIX . 'landing_ai_config';
$res = sql_query(" select * from {$config_table} ");
$ai_cfg = array();
while($r = sql_fetch_array($res)){
    $ai_cfg[$r['config_key']] = $r['config_value'];
}

$openai_use = isset($ai_cfg['openai_use']) ? $ai_cfg['openai_use'] : 'N';
if ($openai_use != 'Y') {
    echo json_encode(array('error' => 'AI 기능이 비활성화되어 있습니다. 환경설정에서 활성화해주세요.'));
    exit;
}

$api_key = ai_decrypt(isset($ai_cfg['openai_api_key']) ? $ai_cfg['openai_api_key'] : '');
$endpoint = isset($ai_cfg['openai_endpoint']) ? rtrim($ai_cfg['openai_endpoint'], '/') : 'https://api.openai.com/v1';
// 최신 gpt-4o 계열은 JSON 응답을 완벽히 지원함
$model = isset($ai_cfg['copy_model_name']) ? $ai_cfg['copy_model_name'] : 'gpt-4o'; 
$temperature = 0.7;

if (!$api_key) {
    echo json_encode(array('error' => 'API Key가 설정되지 않았습니다.'));
    exit;
}

// 프롬프트 작성 (JSON 강제)
$region_txt = $region ? "지역: {$region}" : "지역 제한 없음(전국)";
$system_prompt = "너는 15년 차 베테랑 퍼포먼스 마케터이자 전문 카피라이터야.
사용자가 제공한 정보를 바탕으로 전환율이 극대화된 랜딩페이지 기획안을 JSON 포맷으로 출력해라.

[입력 정보]
업종: {$industry}
{$region_txt}
상호(서비스명): {$service_name}

[작성 규칙]
반드시 아래의 정확한 JSON 키값을 유지하여 응답할 것:
{
    \"subject\": \"관리용 랜딩페이지 타이틀 (예: [{$region}] {$industry} 전문 {$service_name})\",
    \"hero_title\": \"방문자를 3초 만에 사로잡는 강력한 메인 헤드라인 1줄\",
    \"hero_text\": \"메인 헤드라인을 뒷받침하는 신뢰감 있는 서브 카피 2줄\",
    \"cta_text\": \"전환율을 높이는 행동 유도 버튼(CTA) 텍스트 (예: 지금 바로 무료 견적 받기)\",
    \"problem_1\": \"고객이 겪고 있는 페인포인트(문제점) 1\",
    \"problem_2\": \"고객이 겪고 있는 페인포인트 2\",
    \"problem_3\": \"타 업체에 대한 고객의 불만이나 불안요소 3\",
    \"service_1\": \"우리가 제공하는 핵심 해결책 및 차별점 1\",
    \"service_2\": \"우리가 제공하는 핵심 해결책 및 차별점 2\",
    \"service_3\": \"고객이 우리를 선택해야 하는 압도적 장점 3\",
    \"service_4\": \"신뢰도를 높이는 보증/A/S 또는 혜택 4\"
}";

$data = array(
    'model' => $model,
    'response_format' => array('type' => 'json_object'),
    'messages' => array(
        array('role' => 'system', 'content' => $system_prompt),
        array('role' => 'user', 'content' => '지금 바로 마케팅 카피 JSON을 생성해줘.')
    ),
    'temperature' => $temperature,
    'max_tokens' => 1500
);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $endpoint . '/chat/completions');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
    'Authorization: Bearer ' . $api_key
));
curl_setopt($ch, CURLOPT_TIMEOUT, 40);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

$res_data = json_decode($response, true);
$log_status = 'fail';
$error_msg = '';

if ($err) {
    $error_msg = 'cURL Error: ' . $err;
} else if (!isset($res_data['choices'][0]['message']['content'])) {
    $error_msg = isset($res_data['error']['message']) ? $res_data['error']['message'] : 'AI 응답 파싱 실패';
} else {
    $content = $res_data['choices'][0]['message']['content'];
    $json_result = json_decode($content, true);
    
    if (!$json_result || !isset($json_result['hero_title'])) {
        $error_msg = 'AI가 올바른 JSON 형식을 반환하지 않았습니다.';
    } else {
        $log_status = 'success';
    }
}

// 1. AI 호출 로깅 (landing_ai_log)
$log_table = G5_TABLE_PREFIX . 'landing_ai_log';
$tokens = isset($res_data['usage']['total_tokens']) ? (int)$res_data['usage']['total_tokens'] : 0;
$safe_prompt = sql_real_escape_string($system_prompt);
$safe_response = sql_real_escape_string($response);
$safe_error_msg = sql_real_escape_string($error_msg);

sql_query(" insert into {$log_table} 
            set created_at = '".G5_TIME_YMDHIS."',
                mb_id = '{$member['mb_id']}',
                action_type = '랜딩페이지 전체 자동생성',
                model_name = '{$model}',
                prompt = '{$safe_prompt}',
                response = '{$safe_response}',
                tokens = '{$tokens}',
                status = '{$log_status}',
                error_msg = '{$safe_error_msg}' ", false);

if ($log_status != 'success') {
    echo json_encode(array('error' => $error_msg));
    exit;
}

// 2. 파싱 성공 시 DB에 마스터 템플릿으로 인서트
$table = G5_TABLE_PREFIX . 'landing_page';

$subject = sql_real_escape_string($json_result['subject']);
$hero_title = sql_real_escape_string($json_result['hero_title']);
$hero_text = sql_real_escape_string($json_result['hero_text']);
$cta_text = sql_real_escape_string($json_result['cta_text']);
$p1 = sql_real_escape_string($json_result['problem_1']);
$p2 = sql_real_escape_string($json_result['problem_2']);
$p3 = sql_real_escape_string($json_result['problem_3']);
$s1 = sql_real_escape_string($json_result['service_1']);
$s2 = sql_real_escape_string($json_result['service_2']);
$s3 = sql_real_escape_string($json_result['service_3']);
$s4 = sql_real_escape_string($json_result['service_4']);
$safe_phone = sql_real_escape_string($phone);
$safe_industry = sql_real_escape_string($industry);

$sql_insert = "
    insert into {$table}
       set subject = '{$subject}',
           category_id = 0,
           industry = '{$safe_industry}',
           use_yn = 'Y',
           is_template = 'Y',
           parent_id = 0,
           is_display = 'Y',
           hero_title = '{$hero_title}',
           hero_text = '{$hero_text}',
           cta_text = '{$cta_text}',
           cta_link = '#contact',
           problem_1 = '{$p1}',
           problem_2 = '{$p2}',
           problem_3 = '{$p3}',
           service_1 = '{$s1}',
           service_2 = '{$s2}',
           service_3 = '{$s3}',
           service_4 = '{$s4}',
           phone = '{$safe_phone}',
           kakao_link = '',
           privacy_text = '상담을 위해 입력하신 정보 수집에 동의합니다.',
           created_at = '".G5_TIME_YMDHIS."',
           updated_at = '".G5_TIME_YMDHIS."'
";

$res_insert = sql_query($sql_insert, false);

if ($res_insert) {
    echo json_encode(array('success' => true));
} else {
    echo json_encode(array('error' => 'DB 인서트 과정에서 오류가 발생했습니다.'));
}
?>
