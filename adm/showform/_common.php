<?php
define('G5_IS_ADMIN', true);
require_once '../../common.php';
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
