<?php
include_once('./_common.php');
include_once('./ai_crypto.php');

header('Content-Type: application/json; charset=utf-8');

if ($is_admin != 'super') {
    echo json_encode(array('error' => '권한이 없습니다.'));
    exit;
}

// 1. 로그 테이블 자동 생성
$log_table = G5_TABLE_PREFIX . 'landing_ai_log';
sql_query(" CREATE TABLE IF NOT EXISTS `{$log_table}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `created_at` datetime NOT NULL,
  `mb_id` varchar(20) NOT NULL,
  `action_type` varchar(50) NOT NULL,
  `model_name` varchar(50) NOT NULL,
  `prompt` text NOT NULL,
  `response` longtext NOT NULL,
  `tokens` int(11) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL,
  `error_msg` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `created_at` (`created_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ", false);

$original_text = isset($_POST['original_text']) ? trim($_POST['original_text']) : '';
$tone = isset($_POST['tone']) ? trim($_POST['tone']) : '고급스럽게';

if (!$original_text) {
    echo json_encode(array('error' => '텍스트가 없습니다.'));
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
    echo json_encode(array('error' => 'AI 기능이 비활성화되어 있습니다. 환경설정에서 API를 활성화해주세요.'));
    exit;
}

$api_key = ai_decrypt(isset($ai_cfg['openai_api_key']) ? $ai_cfg['openai_api_key'] : '');
$endpoint = isset($ai_cfg['openai_endpoint']) ? rtrim($ai_cfg['openai_endpoint'], '/') : 'https://api.openai.com/v1';
$model = isset($ai_cfg['copy_model_name']) ? $ai_cfg['copy_model_name'] : 'gpt-4o';
$temperature = isset($ai_cfg['temperature']) ? (float)$ai_cfg['temperature'] : 0.7;
$max_tokens = isset($ai_cfg['max_tokens']) ? (int)$ai_cfg['max_tokens'] : 1000;

$system_role = isset($ai_cfg['system_role']) ? $ai_cfg['system_role'] : "마케팅 전문가";
$system_forbidden = isset($ai_cfg['system_forbidden']) ? $ai_cfg['system_forbidden'] : "";

if (!$api_key) {
    echo json_encode(array('error' => 'API Key가 설정되지 않았습니다.'));
    exit;
}

// 프롬프트 구성
$system_prompt = "{$system_role}\n사용자가 제공한 원본 텍스트를 지정된 어조({$tone})에 맞게 마케팅 카피로 재작성해라. HTML 태그 없이 순수 텍스트만 1~2문장으로 간결하게 반환할 것.\n금지사항: {$system_forbidden}";
$user_prompt = "원본 텍스트:\n{$original_text}";

$data = array(
    'model' => $model,
    'messages' => array(
        array('role' => 'system', 'content' => $system_prompt),
        array('role' => 'user', 'content' => $user_prompt)
    ),
    'temperature' => $temperature,
    'max_tokens' => $max_tokens
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
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

$res_data = json_decode($response, true);

// 로깅 준비
$mb_id = $member['mb_id'] ? $member['mb_id'] : 'admin';
$created_at = G5_TIME_YMDHIS;
$safe_prompt = sql_real_escape_string($user_prompt);
$safe_response = sql_real_escape_string($response);
$tokens = isset($res_data['usage']['total_tokens']) ? (int)$res_data['usage']['total_tokens'] : 0;
$action_type = '카피라이팅 생성';
$log_status = 'success';
$error_msg = '';

if ($err) {
    $log_status = 'fail';
    $error_msg = 'cURL Error: ' . $err;
} else if (!isset($res_data['choices'][0]['message']['content'])) {
    $log_status = 'fail';
    $error_msg = isset($res_data['error']['message']) ? $res_data['error']['message'] : 'AI 응답 파싱 실패';
}

$safe_error_msg = sql_real_escape_string($error_msg);

$sql_log = " insert into {$log_table} 
                set created_at = '{$created_at}',
                    mb_id = '{$mb_id}',
                    action_type = '{$action_type}',
                    model_name = '{$model}',
                    prompt = '{$safe_prompt}',
                    response = '{$safe_response}',
                    tokens = '{$tokens}',
                    status = '{$log_status}',
                    error_msg = '{$safe_error_msg}' ";
sql_query($sql_log, false);

if ($log_status == 'success') {
    $improved_text = trim($res_data['choices'][0]['message']['content']);
    echo json_encode(array('success' => true, 'improved_text' => $improved_text));
} else {
    echo json_encode(array('error' => $error_msg));
}
?>
