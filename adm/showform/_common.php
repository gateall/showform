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

// 구주소 호환 계층. 제작 사례 CRUD의 유일한 진입점은 /manager/showform/ 이고,
// /adm/showform/ 는 예전 북마크·링크를 받아 넘겨주는 역할만 남긴다.
$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
$current_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
$new_uri = preg_replace('#/adm/showform/#', '/manager/showform/', $current_uri, 1);

if ($method === 'GET' || $method === 'HEAD') {
    if ($new_uri !== null && $new_uri !== $current_uri) {
        header('Location: ' . $new_uri, true, 301);
        exit;
    }
} else {
    // 변경 요청은 구 경로에서 처리하지 않는다. 301로 넘기면 브라우저가 GET으로
    // 바꿔 재요청하면서 본문이 통째로 사라져 "저장했는데 아무 일도 안 일어남"이
    // 되므로, 조용히 넘기지 말고 405로 끊는다.
    header('Allow: GET, HEAD');
    if ($new_uri !== null && $new_uri !== $current_uri) {
        header('Content-Location: ' . $new_uri);
    }
    http_response_code(405);
    exit('Method Not Allowed');
}
