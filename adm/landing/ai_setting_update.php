<?php
$sub_menu = "900300";
include_once('./_common.php');
include_once('./ai_crypto.php');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$table = G5_TABLE_PREFIX . 'landing_ai_config';

$post_keys = array(
    'openai_use', 'openai_endpoint', 'copy_model_name', 'image_model_name',
    'max_tokens', 'temperature', 'system_role', 'system_forbidden'
);

$mb_id = $member['mb_id'] ? $member['mb_id'] : 'admin';
$updated_at = G5_TIME_YMDHIS;

// 일반 텍스트 변수 저장
foreach ($post_keys as $key) {
    if (isset($_POST[$key])) {
        $val = sql_real_escape_string(trim($_POST[$key]));
        $sql = " insert into {$table} 
                    set config_key = '{$key}', config_value = '{$val}', updated_at = '{$updated_at}', updated_by = '{$mb_id}'
                 on duplicate key update 
                    config_value = '{$val}', updated_at = '{$updated_at}', updated_by = '{$mb_id}' ";
        sql_query($sql);
    }
}

// API Key 암호화 저장
if (isset($_POST['openai_api_key'])) {
    $plain_key = trim($_POST['openai_api_key']);
    // 값이 비어있지 않으면 암호화, 비어있으면 빈값 그대로
    $enc_key = $plain_key ? ai_encrypt($plain_key) : '';
    $enc_key = sql_real_escape_string($enc_key);
    
    $sql = " insert into {$table} 
                set config_key = 'openai_api_key', config_value = '{$enc_key}', updated_at = '{$updated_at}', updated_by = '{$mb_id}'
             on duplicate key update 
                config_value = '{$enc_key}', updated_at = '{$updated_at}', updated_by = '{$mb_id}' ";
    sql_query($sql);
}

alert('AI 환경설정이 성공적으로 변경되었습니다.', './ai_setting.php');
?>
