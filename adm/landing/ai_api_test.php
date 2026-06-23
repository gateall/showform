<?php
include_once('./_common.php');

header('Content-Type: application/json; charset=utf-8');

if ($is_admin != 'super') {
    echo json_encode(array('error' => '권한이 없습니다.'));
    exit;
}

$endpoint = isset($_POST['endpoint']) ? rtrim($_POST['endpoint'], '/') : 'https://api.openai.com/v1';
$api_key = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';

if (!$api_key) {
    echo json_encode(array('error' => 'API Key가 없습니다.'));
    exit;
}

// 간단한 Ping 테스트를 위해 모델 목록 요청
$url = $endpoint . '/models';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $api_key
));
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 개발 환경용

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    echo json_encode(array('error' => 'cURL Error: ' . $err));
    exit;
}

$res_data = json_decode($response, true);

if ($http_code == 200 && isset($res_data['data'])) {
    echo json_encode(array('success' => true));
} else {
    $msg = isset($res_data['error']['message']) ? $res_data['error']['message'] : 'Status: '.$http_code;
    echo json_encode(array('error' => $msg));
}
?>
