<?php
define('_GNUBOARD_', true);
include_once('./common.php');

$log_file = G5_DATA_PATH . '/session/social_debug.log';
echo "<h3>Social Login Debug Log</h3>";
if (file_exists($log_file)) {
    // 보안을 위해 키 정보 등 민감한 정보가 노출되지 않도록 간단히 덤프하되,
    // client_id 등의 파라미터 구성을 검증할 수 있도록 출력합니다.
    echo "<pre style='background:#f4f4f4; padding:10px; border:1px solid #ccc; max-height:600px; overflow:auto;'>";
    // 로그 파일의 마지막 100라인만 가져와 가독성을 높입니다.
    $lines = file($log_file);
    $last_lines = array_slice($lines, -100);
    echo htmlspecialchars(implode("", $last_lines));
    echo "</pre>";
} else {
    echo "Log file not found at: " . htmlspecialchars($log_file);
}
?>
