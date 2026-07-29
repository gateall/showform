<?php
if (!defined('_GNUBOARD_')) exit;

// admin.head.php가 사용하는 실제 $menu(admin.lib.php가 admin.menu*.php를 글롭으로 읽어
// 채운 원본 그누보드 메뉴 전역)를 "기본 관리자" 사이드바용 데이터와 "쇼폼·콘텐츠 운영"
// 상단 가로 메뉴용 데이터로 나눠 만든다. 항목 단위 권한 검사는 기존 print_menu2()와
// 완전히 동일한 방식을 유지한다 — 화면에서 숨기는 것과 별개로 각 페이지 자체의
// auth_check_menu() 서버 검사가 그대로 최종 방어선이다.
require_once __DIR__ . '/admin_area_map.php';

function bp_sf_build_menus(array $menu, array $auth, string $is_admin): array
{
    $area_map = bp_sf_admin_area_map();
    $bucket_map = bp_sf_content_bucket_map();
    $fallback_bucket = bp_sf_group_fallback_bucket();

    $core_menus = array();
    $content_items_by_bucket = array();

    foreach ($menu as $menu_key => $group) {
        if (!isset($group[0])) {
            continue;
        }
        $area = isset($area_map[$menu_key]) ? $area_map[$menu_key] : 'core';

        $subs = array();
        for ($i = 1; $i < count($group); $i++) {
            if (!isset($group[$i])) {
                continue;
            }
            $item = $group[$i];
            $auth_code = $item[0];
            // bucket_map은 4번째 값(각 파일이 auth_check_menu()에서 실제 쓰는 서술형 코드,
            // 예: 'blog_advertiser')을 키로 쓴다 — 권한 검사에 쓰는 1번째 숫자 코드와는 다른 값이다.
            $label_code = isset($item[3]) ? $item[3] : $auth_code;
            if ($is_admin != 'super' && (!array_key_exists($auth_code, $auth) || !strstr($auth[$auth_code], 'r'))) {
                continue;
            }
            $entry = array('code' => $auth_code, 'title' => $item[1], 'href' => $item[2]);
            $subs[] = $entry;

            if ($area === 'content') {
                $bucket = isset($bucket_map[$label_code])
                    ? $bucket_map[$label_code]
                    : (isset($fallback_bucket[$menu_key]) ? $fallback_bucket[$menu_key] : '기타');
                if (!isset($content_items_by_bucket[$bucket])) {
                    $content_items_by_bucket[$bucket] = array();
                }
                // 랜딩 재사용 링크처럼 같은 화면이 여러 그룹에 중복 등록된 경우 href 기준으로 한 번만.
                $dup = false;
                foreach ($content_items_by_bucket[$bucket] as $existing) {
                    if ($existing['href'] === $entry['href']) {
                        $dup = true;
                        break;
                    }
                }
                if (!$dup) {
                    $content_items_by_bucket[$bucket][] = $entry;
                }
            }
        }

        if ($area === 'core' && count($subs) > 0) {
            $core_menus[$menu_key] = array('title' => $group[0][1], 'subs' => $subs);
        }
    }

    $bucket_order = bp_sf_content_bucket_order();
    $content_buckets = array();
    foreach ($bucket_order as $bucket_label) {
        if (!empty($content_items_by_bucket[$bucket_label])) {
            $content_buckets[$bucket_label] = $content_items_by_bucket[$bucket_label];
        }
    }
    // 매핑표에 없는 새 항목이 실수로 조용히 사라지지 않도록 '기타'는 있으면 맨 뒤에 노출.
    if (!empty($content_items_by_bucket['기타'])) {
        $content_buckets['기타'] = $content_items_by_bucket['기타'];
    }

    return array('core' => $core_menus, 'content' => $content_buckets);
}

// 현재 페이지의 $sub_menu 코드가 어느 그룹에 속하는지로 현재 운영 영역을 판정한다.
// 별도 세션·쿠키 없이 페이지 자체의 등록 코드만으로 항상 정확하게 판정된다.
// 매칭되는 코드가 없으면 기존 그누보드 화면과의 호환을 위해 기본값은 'core'.
function bp_sf_resolve_current_area(array $menu, array $area_map, $sub_menu): string
{
    if ($sub_menu !== null && $sub_menu !== '') {
        foreach ($menu as $menu_key => $group) {
            for ($i = 1; $i < count($group); $i++) {
                if (isset($group[$i][0]) && $group[$i][0] == $sub_menu) {
                    return isset($area_map[$menu_key]) ? $area_map[$menu_key] : 'core';
                }
            }
        }
    }

    // 여러 blog/*.php, landing/*.php 파일이 스스로 선언한 $sub_menu 코드가 실제 등록된
    // 코드와 어긋나 있는 경우가 다수 확인됨(예: project_list.php는 '360500'을 선언하지만
    // admin.menu360.php에 등록된 코드는 '360400') — 코드 매칭이 실패하면 현재 스크립트가
    // 어느 디렉터리에서 실행 중인지로 한 번 더 판정한다. 신규 업무 파일은 전부
    // adm/blog/ 또는 adm/landing/ 아래에 있으므로 코드 숫자 드리프트와 무관하게 안정적이다.
    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
    if ($script !== '' && (strpos($script, '/blog/') !== false || strpos($script, '/landing/') !== false)) {
        return 'content';
    }

    return 'core';
}

// "쇼폼·콘텐츠 운영" 전환 버튼이 이동할 고정 대시보드 주소.
// blog_dashboard 코드가 실제로 등록되어 있으면 그 URL을 쓰고, 없으면 콘텐츠 프로젝트
// 목록으로 안전하게 대체한다 — 중복 대시보드를 새로 만들지 않는다.
function bp_sf_content_dashboard_url(array $menu): string
{
    foreach ($menu as $group) {
        for ($i = 1; $i < count($group); $i++) {
            if (isset($group[$i][0]) && $group[$i][0] === 'blog_dashboard') {
                return $group[$i][2];
            }
        }
    }
    return G5_ADMIN_URL . '/blog/project_list.php';
}
