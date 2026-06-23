<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

// 임의의 고정 암호화 키 (실 서비스에서는 별도 config에 보관 권장)
define('AI_ENC_KEY', 'ShowForm_AI_Secret_Key_2026_!@#$');
define('AI_ENC_IV', '1234567890123456'); // 16 bytes IV

function ai_encrypt($plain_text) {
    if (!$plain_text) return '';
    $encrypted = openssl_encrypt($plain_text, 'AES-256-CBC', AI_ENC_KEY, 0, AI_ENC_IV);
    return base64_encode($encrypted);
}

function ai_decrypt($encrypted_text) {
    if (!$encrypted_text) return '';
    $decoded = base64_decode($encrypted_text);
    return openssl_decrypt($decoded, 'AES-256-CBC', AI_ENC_KEY, 0, AI_ENC_IV);
}
?>
