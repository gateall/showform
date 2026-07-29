<?php
if (!defined('_GNUBOARD_')) exit;

// admin.head.php의 네이티브 <nav id="gnb"> 메뉴(화면 그대로 유지)와, 그 아래 별도로
// 붙는 "쇼폼/블로그 자동화/랜딩페이지" 상단 전환 메뉴에 쓸 데이터를 실제 $menu/$amenu
// (admin.lib.php가 admin.menu*.php를 글롭으로 읽어 채운 원본)에서 만든다.
// 항목 단위 권한 검사는 기존 print_menu2()와 완전히 동일한 방식을 유지한다.
require_once __DIR__ . '/admin_area_map.php';

// 네이티브 <nav id="gnb"> 루프가 도는 $amenu를 core로 분류된 그룹만 남도록 거른다.
// $amenu/$menu 전역 자체는 건드리지 않는다(다른 코드가 원본에 의존할 수 있음).
function bp_sf_filter_core_amenu(array $amenu, array $area_map): array
{
    $filtered = array();
    foreach ($amenu as $key => $file) {
        $area = isset($area_map['menu' . $key]) ? $area_map['menu' . $key] : 'core';
        if ($area === 'core') {
            $filtered[$key] = $file;
        }
    }
    return $filtered;
}

// "쇼폼/블로그 자동화/랜딩페이지" 각 수직영역의 대시보드 링크 + 사이트맵형 목록을 만든다.
function bp_sf_build_content_verticals(array $menu, array $auth, string $is_admin): array
{
    $area_map = bp_sf_admin_area_map();
    $vertical_map = bp_sf_content_vertical_map();
    $fallback_vertical = bp_sf_group_fallback_vertical();
    $dashboard_code = bp_sf_vertical_dashboard_code();

    $items_by_vertical = array();

    foreach ($menu as $menu_key => $group) {
        $area = isset($area_map[$menu_key]) ? $area_map[$menu_key] : 'core';
        if ($area !== 'content' || !isset($group[0])) {
            continue;
        }
        for ($i = 1; $i < count($group); $i++) {
            if (!isset($group[$i])) {
                continue;
            }
            $item = $group[$i];
            $auth_code = $item[0];
            $label_code = isset($item[3]) ? $item[3] : $auth_code;
            if ($is_admin != 'super' && (!array_key_exists($auth_code, $auth) || !strstr($auth[$auth_code], 'r'))) {
                continue;
            }

            $vertical = isset($vertical_map[$label_code])
                ? $vertical_map[$label_code]
                : (isset($fallback_vertical[$menu_key]) ? $fallback_vertical[$menu_key] : null);
            if ($vertical === null) {
                continue;
            }

            $entry = array('code' => $label_code, 'title' => $item[1], 'href' => $item[2]);
            if (!isset($items_by_vertical[$vertical])) {
                $items_by_vertical[$vertical] = array();
            }
            $dup = false;
            foreach ($items_by_vertical[$vertical] as $existing) {
                if ($existing['href'] === $entry['href']) {
                    $dup = true;
                    break;
                }
            }
            if (!$dup) {
                $items_by_vertical[$vertical][] = $entry;
            }
        }
    }

    $verticals = array();
    foreach (bp_sf_content_vertical_order() as $label) {
        if (empty($items_by_vertical[$label])) {
            continue; // 화면이 없는 영역(예: 쇼폼)은 만들지 않는다.
        }
        $all_items = $items_by_vertical[$label];
        $dash_code = isset($dashboard_code[$label]) ? $dashboard_code[$label] : null;
        $dashboard = null;
        $items = array();
        foreach ($all_items as $entry) {
            if ($dash_code !== null && $entry['code'] === $dash_code && $dashboard === null) {
                $dashboard = $entry;
                continue;
            }
            $items[] = $entry;
        }
        $verticals[$label] = array('dashboard' => $dashboard, 'items' => $items);
    }

    return $verticals;
}

// 현재 페이지가 어느 수직영역(쇼폼/블로그 자동화/랜딩페이지)에 속하는지 판정 —
// 상단 전환 메뉴에서 현재 위치를 강조 표시하는 용도. 실제 접근 권한과는 무관하다.
// $sub_menu 코드 매칭을 우선 시도하고, 여러 blog/landing 파일이 실제 등록 코드와
// 다른 $sub_menu를 선언하는 경우(확인된 기존 드리프트)를 위해 스크립트 경로를
// 보조 신호로 사용한다.
function bp_sf_resolve_current_vertical(array $menu, $sub_menu): ?string
{
    $area_map = bp_sf_admin_area_map();
    $vertical_map = bp_sf_content_vertical_map();
    $fallback_vertical = bp_sf_group_fallback_vertical();

    if ($sub_menu !== null && $sub_menu !== '') {
        foreach ($menu as $menu_key => $group) {
            if (!isset($area_map[$menu_key]) || $area_map[$menu_key] !== 'content') {
                continue;
            }
            for ($i = 1; $i < count($group); $i++) {
                if (!isset($group[$i][0]) || $group[$i][0] != $sub_menu) {
                    continue;
                }
                $label_code = isset($group[$i][3]) ? $group[$i][3] : $group[$i][0];
                if (isset($vertical_map[$label_code])) {
                    return $vertical_map[$label_code];
                }
                return isset($fallback_vertical[$menu_key]) ? $fallback_vertical[$menu_key] : null;
            }
        }
    }

    $script = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
    if ($script !== '') {
        if (strpos($script, '/blog/') !== false) {
            return '블로그 자동화';
        }
        if (strpos($script, '/landing/') !== false) {
            return '랜딩페이지';
        }
    }

    return null;
}
