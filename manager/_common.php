<?php
// 쇼폼 통합관리자(/manager/) 세션 부트스트랩.
// blog-studio/_common.php와 동일한 패턴: 그누보드 세션 판단(common.php)과 관리자
// 권한 로직(admin.lib.php)만 불러오고, 프론트 화면(head.sub.php)은 불러오지 않는다.
// 이 방식으로 /adm/ 로그인 세션을 그대로 재사용해 재로그인이 발생하지 않는다.
$g5_path = '..';
include_once(__DIR__ . '/../common.php');
require_once G5_ADMIN_PATH . '/admin.lib.php';

define('SF_MANAGER_URL', G5_URL . '/manager');

require_once __DIR__ . '/config/menu.php';

// 메뉴 항목 표시 여부: permission이 빈 값이면 로그인만 되어 있으면 보이고,
// super는 전부 통과, 그 외에는 실제 목적지 파일이 쓰는 진짜 auth 코드로 판정한다
// (숨김은 UX일 뿐 실제 차단은 목적지 파일 자신의 auth_check_menu()가 한다).
function mgr_menu_visible($item)
{
    global $is_admin, $auth;
    $code = isset($item['permission']) ? $item['permission'] : '';
    if ($code === '') {
        return true;
    }
    if ($is_admin === 'super') {
        return true;
    }
    return isset($auth[$code]) && strstr($auth[$code], 'r');
}

function mgr_current_path()
{
    static $path = null;
    if ($path === null) {
        $path = strtok($_SERVER['REQUEST_URI'], '?');
    }
    return $path;
}

// 그룹 활성 판정: 현재 요청 경로가 그룹의 디렉터리 prefix로 시작하는지(문자열 단순비교 아님).
function mgr_group_is_active($group_id)
{
    global $manager_menu_prefix;
    if (!isset($manager_menu_prefix[$group_id])) {
        return false;
    }
    $prefix_path = parse_url($manager_menu_prefix[$group_id], PHP_URL_PATH);
    return $prefix_path && strpos(mgr_current_path(), $prefix_path) === 0;
}

// 개별 항목 활성 판정(새 창/외부 링크는 대상에서 제외).
function mgr_item_is_active($item)
{
    if (!empty($item['external'])) {
        return false;
    }
    $item_path = parse_url($item['url'], PHP_URL_PATH);
    return $item_path && mgr_current_path() === $item_path;
}
