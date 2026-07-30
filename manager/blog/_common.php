<?php
$sub_menu = "360000";
require_once '../_common.php';
require_once G5_ADMIN_PATH . '/blog/lib/blog_common.lib.php';
require_once G5_ADMIN_PATH . '/blog/lib/blog_crypto.lib.php';
require_once G5_ADMIN_PATH . '/blog/lib/blog_state_machine.lib.php';
require_once G5_ADMIN_PATH . '/blog/lib/blog_ai_service.lib.php';
require_once G5_ADMIN_PATH . '/blog/lib/blog_quality.lib.php';
require_once G5_ADMIN_PATH . '/blog/lib/blog_publisher.lib.php';
require_once G5_ADMIN_PATH . '/blog/lib/blog_report.lib.php';
require_once G5_ADMIN_PATH . '/blog/lib/blog_naver.lib.php';

bp_ensure_utf8mb4_connection();

if (isset($token)) {
    $token = @htmlspecialchars(strip_tags($token), ENT_QUOTES);
}
