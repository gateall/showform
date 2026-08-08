<?php
if (!defined('_GNUBOARD_')) exit;

// 관리자 메뉴를 "기본 관리자"(그누보드/영카트 원본 네이티브 메뉴)와
// "쇼폼·콘텐츠 운영"(신규 업무, 별도의 상단 전환 버튼) 두 갈래로 명시적으로 분류하기
// 위한 설정. 메뉴 코드 숫자나 파일명 패턴으로 추측하지 않고, admin.menu*.php가
// 등록하는 $menu 그룹 키 단위로 지정한다. core로 분류된 그룹만 네이티브
// <nav id="gnb"> 메뉴(admin.head.php의 원본 구조, 화면 그대로 유지)에 남는다.
function bp_sf_admin_area_map(): array
{
    return array(
        'menu100' => 'core',    // 환경설정
        'menu200' => 'core',    // 회원관리
        'menu300' => 'core',    // 게시판관리
        'menu400' => 'core',    // 쇼핑몰관리 1/2
        'menu500' => 'core',    // 쇼핑몰관리 2/2
        'menu350' => 'content', // 랜딩관리(레거시 플랫 경로)
        'menu360' => 'content', // 블로그자동화 + 광고주 + 발행/보고서/설정 + 랜딩 재사용 링크
        'menu700' => 'content', // 쇼폼(제작 사례) — 실제 CRUD 화면 있음
        'menu900' => 'content', // 랜딩관리
        'menu910' => 'content', // 문의관리
        'menu920' => 'content', // 랜딩 AI관리
        'menu370' => 'content', // 쇼폼 통합관리(/manager/) 권한 코드 전용 — vertical_map에 없어 vswitch에도 노출 안 됨
    );
}

// content 영역 항목을 신규 업무 상단 메뉴("쇼폼"/"블로그 자동화"/"랜딩페이지" 3개)로
// 재분류한다. 각 항목의 auth 코드(등록 배열의 4번째 값)를 명시적으로 매핑한다 —
// 코드 숫자 규칙에 기대지 않는다. 매핑에 없는 항목은 그룹별 기본값으로 떨어진다.
function bp_sf_content_vertical_map(): array
{
    return array(
        'showform' => '쇼폼',
        'showform_portfolio_list' => '쇼폼',
        'showform_portfolio_form' => '쇼폼',
        'showform_install' => '쇼폼',
        'blog' => '블로그 자동화',
        'blog_dashboard' => '블로그 자동화',
        'blog_post_builder' => '블로그 자동화',
        'blog_project' => '블로그 자동화',
        'blog_site' => '블로그 자동화',
        'blog_image' => '블로그 자동화',
        'blog_advertiser' => '블로그 자동화',
        'blog_advertiser_account' => '블로그 자동화',
        'blog_publish_job' => '블로그 자동화',
        'blog_report' => '블로그 자동화',
        'blog_report_daily' => '블로그 자동화',
        'blog_report_weekly' => '블로그 자동화',
        'blog_report_monthly' => '블로그 자동화',
        'blog_report_site' => '블로그 자동화',
        'blog_report_perf' => '블로그 자동화',
        'blog_report_stats' => '블로그 자동화',
        'blog_ai_provider' => '블로그 자동화',
        'blog_channel_app' => '블로그 자동화',
        'blog_install' => '블로그 자동화',
        'landing_list' => '랜딩페이지',
        'landing_form' => '랜딩페이지',
        'inquiry_list' => '랜딩페이지',
        'inquiry_stats' => '랜딩페이지',
        'landing' => '랜딩페이지',
        'landing_category' => '랜딩페이지',
        'landing_template' => '랜딩페이지',
        'landing_inquiry' => '랜딩페이지',
        'landing_ai' => '랜딩페이지',
        'sf_landing' => '랜딩페이지',
        'template_list' => '랜딩페이지',
        'ai_generate' => '랜딩페이지',
        'category_list' => '랜딩페이지',
        'sf_inquiry' => '랜딩페이지',
        'sf_ai' => '랜딩페이지',
        'miniweb_settings' => '랜딩페이지',
        'ai_prompt' => '랜딩페이지',
        'ai_log' => '랜딩페이지',
        'ai_setting' => '랜딩페이지',
    );
}

// 화면에 표시할 상단 메뉴(수직 영역) 순서 고정 — 요청된 3개.
// 항목이 없는 영역은 렌더링 단계에서 자동으로 생략된다.
function bp_sf_content_vertical_order(): array
{
    return array('쇼폼', '블로그 자동화', '랜딩페이지');
}

// $menu 그룹의 그룹별 기본 수직영역 — vertical_map에 없는 항목이 나오면 이 값으로 떨어진다.
function bp_sf_group_fallback_vertical(): array
{
    return array(
        'menu360' => '블로그 자동화',
        'menu350' => '랜딩페이지',
        'menu900' => '랜딩페이지',
        'menu910' => '랜딩페이지',
        'menu920' => '랜딩페이지',
    );
}

// 각 수직영역의 "대시보드" 링크로 쓸 auth 코드(4번째 값). 목록에서 대시보드
// 항목은 제외하고 별도로 상단에 고정 표시한다.
function bp_sf_vertical_dashboard_code(): array
{
    return array(
        '쇼폼' => 'showform_portfolio_list',
        '블로그 자동화' => 'blog_dashboard',
        '랜딩페이지' => 'landing_list',
    );
}
