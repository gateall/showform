<?php
$sub_menu = "700100";
require_once '../_common.php';

// 외부 사이트 URL은 http/https만 허용한다 — javascript:/data: 등 위험한 스킴 차단.
function sf_is_safe_external_url(string $url): bool
{
    if ($url === '') {
        return true;
    }
    $scheme = parse_url($url, PHP_URL_SCHEME);
    return $scheme === 'http' || $scheme === 'https';
}
