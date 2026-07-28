<?php
include_once('./_common.php');

$sub_menu = '360300';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$table = bp_table('ai_providers');
$actor = bp_current_admin_id();

$is_active = isset($_POST['is_active']) && $_POST['is_active'] === 'Y' ? 'Y' : 'N';
$default_model = isset($_POST['default_model']) ? trim($_POST['default_model']) : '';
$max_tokens = isset($_POST['max_tokens']) ? (int) $_POST['max_tokens'] : 2000;
$temperature = isset($_POST['temperature']) ? (float) $_POST['temperature'] : 0.70;
$api_key = isset($_POST['api_key']) ? trim($_POST['api_key']) : '';

$key_sql = '';
if ($api_key !== '') {
    $enc = ai_encrypt($api_key);
    $hint = bp_mask_secret($api_key);
    $key_sql = ", api_key_enc = '" . sql_real_escape_string($enc) . "', masked_hint = '" . sql_real_escape_string($hint) . "'";
}

$sql = " insert into {$table}
            set provider_code = 'openai',
                display_name = 'OpenAI',
                is_active = '{$is_active}',
                default_model = '" . sql_real_escape_string($default_model) . "',
                max_tokens = '" . (int)$max_tokens . "',
                temperature = '" . (float)$temperature . "',
                updated_by = '" . sql_real_escape_string($actor) . "',
                created_at = '" . G5_TIME_YMDHIS . "',
                updated_at = '" . G5_TIME_YMDHIS . "'
                {$key_sql}
         on duplicate key update
                is_active = '{$is_active}',
                default_model = '" . sql_real_escape_string($default_model) . "',
                max_tokens = '" . (int)$max_tokens . "',
                temperature = '" . (float)$temperature . "',
                updated_by = '" . sql_real_escape_string($actor) . "',
                updated_at = '" . G5_TIME_YMDHIS . "'
                {$key_sql} ";
sql_query($sql);

alert('AI 공급자 설정이 저장되었습니다.', G5_ADMIN_URL . '/blog/ai_provider_form.php');
