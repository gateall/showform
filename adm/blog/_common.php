<?php
define('G5_IS_ADMIN', true);
require_once '../../common.php';
require_once G5_ADMIN_PATH . '/admin.lib.php';
require_once __DIR__ . '/lib/blog_common.lib.php';
require_once __DIR__ . '/lib/blog_crypto.lib.php';
require_once __DIR__ . '/lib/blog_state_machine.lib.php';
require_once __DIR__ . '/lib/blog_ai_service.lib.php';
require_once __DIR__ . '/lib/blog_quality.lib.php';
require_once __DIR__ . '/lib/blog_publisher.lib.php';

bp_ensure_utf8mb4_connection();

if (isset($token)) {
    $token = @htmlspecialchars(strip_tags($token), ENT_QUOTES);
}

run_event('admin_common');
