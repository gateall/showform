<?php
if (!defined('_GNUBOARD_')) exit;

// 관리자 메뉴를 "기본 관리자"(그누보드/영카트 원본)와 "쇼폼·콘텐츠 운영"(신규 업무)
// 두 영역으로 명시적으로 분류하기 위한 설정. 메뉴 코드 숫자나 파일명 패턴으로 추측하지
// 않고, admin.menu*.php가 등록하는 $menu 그룹 키 단위로 영역을 지정한다.
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
        'menu900' => 'content', // 랜딩관리
        'menu910' => 'content', // 문의관리
        'menu920' => 'content', // 랜딩 AI관리
    );
}

// content 영역으로 분류된 그룹들 안에 뒤섞인 항목을 업무 성격별 1차 메뉴(버킷)로 재분류.
// 각 항목의 auth 코드(등록 배열의 4번째 값)를 명시적으로 매핑한다 — 코드 숫자 규칙에 기대지 않는다.
// 매핑에 없는 항목은 '기타'로 떨어지며, 화면에는 그 항목이 속한 그룹의 fallback 버킷으로 노출된다.
function bp_sf_content_bucket_map(): array
{
    return array(
        // 대시보드
        'blog' => '대시보드',
        'blog_dashboard' => '대시보드',
        // 블로그 자동화
        'blog_post_builder' => '블로그 자동화',
        'blog_project' => '블로그 자동화',
        'blog_site' => '블로그 자동화',
        'blog_image' => '블로그 자동화',
        // 광고주
        'blog_advertiser' => '광고주',
        'blog_advertiser_account' => '광고주',
        // 발행 관리
        'blog_publish_job' => '발행 관리',
        // 보고서
        'blog_report' => '보고서',
        'blog_report_daily' => '보고서',
        'blog_report_weekly' => '보고서',
        'blog_report_monthly' => '보고서',
        'blog_report_site' => '보고서',
        'blog_report_perf' => '보고서',
        'blog_report_stats' => '보고서',
        // 설정
        'blog_ai_provider' => '설정',
        'blog_channel_app' => '설정',
        'blog_install' => '설정',
        // 랜딩페이지 (menu360에 재사용으로 걸어둔 링크 + 랜딩 모듈 자체 등록분)
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
        'ai_prompt' => '랜딩페이지',
        'ai_log' => '랜딩페이지',
        'ai_setting' => '랜딩페이지',
    );
}

// 화면에 표시할 1차 메뉴(버킷) 순서 고정 — 8개 안팎 요구사항.
// 항목이 없는 버킷은 렌더링 단계에서 자동으로 생략된다(없는 화면을 링크로 만들지 않는다).
function bp_sf_content_bucket_order(): array
{
    return array('대시보드', '쇼폼', '랜딩페이지', '블로그 자동화', '광고주', '발행 관리', '보고서', '설정');
}

// $menu 그룹의 각 그룹별 fallback 버킷 — bucket_map에 없는 항목이 나오면 이 값으로 떨어진다.
function bp_sf_group_fallback_bucket(): array
{
    return array(
        'menu360' => '블로그 자동화',
        'menu350' => '랜딩페이지',
        'menu900' => '랜딩페이지',
        'menu910' => '랜딩페이지',
        'menu920' => '랜딩페이지',
    );
}
