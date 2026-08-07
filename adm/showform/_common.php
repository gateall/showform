<?php
define('G5_IS_ADMIN', true);
require_once '../../common.php';

if (!$is_member) {
    $return_url = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '/manager/';
    goto_url(G5_URL . '/manager/login.php?url=' . urlencode($return_url));
}

if (!$is_admin) {
    alert('관리자 권한이 필요합니다.', G5_URL . '/');
}

require_once G5_ADMIN_PATH . '/admin.lib.php';
// 외부 사이트 URL은 http/https만 허용한다 — javascript:/data: 등 위험한 스킴 차단.
function sf_is_safe_external_url(string $url): bool
{
    if ($url === '') {
        return true;
    }
    $scheme = parse_url($url, PHP_URL_SCHEME);
    return $scheme === 'http' || $scheme === 'https';
}

$method = isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '';
if ($method === 'GET' || $method === 'HEAD') {
    $current_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
    $new_uri = preg_replace('#/adm/showform/#', '/manager/showform/', $current_uri, 1);
    if ($new_uri !== null && $new_uri !== $current_uri) {
        header('Location: ' . $new_uri, true, 301);
        exit;
    }
}
