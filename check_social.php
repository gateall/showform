<?php
include_once('./_common.php');
include_once(G5_PLUGIN_PATH.'/social/includes/functions.php');

echo "<h2>[Social Skin Path Diagnostic]</h2>";
echo "<p><strong>G5_IS_MOBILE:</strong> " . (G5_IS_MOBILE ? 'True (Mobile)' : 'False (PC)') . "</p>";

$skin_path = get_social_skin_path();
echo "<p><strong>get_social_skin_path():</strong> " . $skin_path . "</p>";

$theme_social_path = G5_THEME_PATH . '/skin/social';
$theme_social_exists = is_dir($theme_social_path) ? "존재함 (Exist)" : "존재하지 않음 (Not Exist)";
echo "<p><strong>Theme PC Social Dir (" . $theme_social_path . "):</strong> " . $theme_social_exists . "</p>";

$theme_mobile_social_path = G5_THEME_PATH . '/mobile/skin/social';
$theme_mobile_social_exists = is_dir($theme_mobile_social_path) ? "존재함 (Exist)" : "존재하지 않음 (Not Exist)";
echo "<p><strong>Theme Mobile Social Dir (" . $theme_mobile_social_path . "):</strong> " . $theme_mobile_social_exists . "</p>";

// 파일 크기 및 정보 확인 함수
function check_file_info($title, $path) {
    if (file_exists($path)) {
        $size = filesize($path);
        $mtime = date("Y-m-d H:i:s", filemtime($path));
        echo "<p><strong>[{$title}]</strong> 존재함 - 크기: {$size} bytes / 수정시간: {$mtime} <br>경로: {$path}</p>";
    } else {
        echo "<p><strong>[{$title}]</strong> 존재하지 않음 <br>경로: {$path}</p>";
    }
}

echo "<h3>파일별 실제 정보</h3>";
check_file_info("PC 소셜 스킨 (루트)", G5_PATH . '/skin/social/social_register_member.skin.php');
check_file_info("모바일 소셜 스킨 (루트)", G5_PATH . '/mobile/skin/social/social_register_member.skin.php');
check_file_info("PC 소셜 스킨 (테마)", G5_THEME_PATH . '/skin/social/social_register_member.skin.php');
check_file_info("모바일 소셜 스킨 (테마)", G5_THEME_PATH . '/mobile/skin/social/social_register_member.skin.php');

echo "<h3>로컬 기준 정상 크기</h3>";
echo "<p>PC 소셜 스킨 (로컬): 11042 bytes</p>";
echo "<p>모바일 소셜 스킨 (로컬): 16393 bytes</p>";
?>
