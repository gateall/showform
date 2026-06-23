<?php
if (!defined('_GNUBOARD_')) exit;

if (!function_exists('showform_openai_config')) {
    function showform_openai_config()
    {
        $cfg_file = G5_DATA_PATH . '/config/openai.php';
        $config = array('api_key' => '', 'model' => 'gpt-4.1');
        if (is_file($cfg_file)) {
            include $cfg_file;
            if (isset($openai_config) && is_array($openai_config)) {
                $config = array_merge($config, $openai_config);
            }
        }
        return $config;
    }
}

if (!function_exists('showform_openai_generate')) {
    function showform_openai_generate($prompt, $model = '')
    {
        $config = showform_openai_config();
        $api_key = isset($config['api_key']) ? trim($config['api_key']) : '';
        $model = $model ? $model : (isset($config['model']) ? $config['model'] : 'gpt-4.1');

        if ($api_key === '') {
            return array('ok' => false, 'error' => 'OpenAI API 키가 설정되지 않았습니다.');
        }

        $payload = array(
            'model' => $model,
            'input' => $prompt,
            'temperature' => 0.7,
        );

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Bearer ' . $api_key));
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        $response = curl_exec($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $http_code < 200 || $http_code >= 300) {
            return array('ok' => false, 'error' => $error ? $error : 'OpenAI API 호출에 실패했습니다.', 'raw' => $response);
        }

        return array('ok' => true, 'raw' => $response, 'json' => json_decode($response, true));
    }
}
