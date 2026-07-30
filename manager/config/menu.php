<?php
// 쇼폼 통합관리자(/manager/) 좌측/모바일 메뉴 데이터.
// 각 href의 permission 코드는 admin.menuXXX.php에 "등록된" 코드가 아니라,
// 목적지 파일이 자기 안에서 실제로 auth_check_menu()에 넘기는 진짜 코드다
// (조사 결과 admin.menu360.php 등록 코드와 4곳이 어긋나 있었다 - KNOWN ISSUE 참고).
// 화면이 없는 항목(쇼폼 카테고리 관리 등)은 넣지 않는다.
if (!defined('_GNUBOARD_')) exit;

$manager_menu = array(
    array(
        'id' => 'dashboard',
        'title' => '대시보드',
        'icon' => 'gauge',
        'items' => array(
            array('title' => '통합 대시보드', 'url' => SF_MANAGER_URL . '/index.php', 'permission' => '370000'),
            array('title' => '운영 상태', 'url' => SF_MANAGER_URL . '/pages/system_status.php', 'permission' => '370100'),
        ),
    ),
    array(
        'id' => 'showform',
        'title' => '쇼폼 관리',
        'icon' => 'showform',
        'items' => array(
            array('title' => '쇼폼 목록', 'url' => SF_MANAGER_URL . '/showform/list.php', 'permission' => '700100'),
            array('title' => '쇼폼 신규 등록', 'url' => SF_MANAGER_URL . '/showform/form.php', 'permission' => '700100'),
        ),
    ),
    array(
        'id' => 'landing',
        'title' => '랜딩페이지 관리',
        'icon' => 'landing',
        'items' => array(
            array('title' => '랜딩페이지 목록', 'url' => SF_MANAGER_URL . '/landing/landing_list.php', 'permission' => '900100'),
            array('title' => '랜딩페이지 등록', 'url' => SF_MANAGER_URL . '/landing/landing_form.php', 'permission' => '900100'),
            array('title' => '상담 문의 관리', 'url' => SF_MANAGER_URL . '/landing/inquiry_list.php', 'permission' => '900200'),
            array('title' => '방문·전환 통계', 'url' => SF_MANAGER_URL . '/landing/inquiry_stats.php', 'permission' => '910200'),
        ),
    ),
    array(
        'id' => 'blog',
        'title' => '블로그 자동화',
        'icon' => 'blog',
        'items' => array(
            array('title' => '블로그 대시보드', 'url' => SF_MANAGER_URL . '/blog/blog_dashboard.php', 'permission' => '360000'),
            array('title' => '통합 포스팅 제작', 'url' => SF_MANAGER_URL . '/blog/post_builder.php', 'permission' => '360050'),
            array('title' => '콘텐츠 프로젝트', 'url' => SF_MANAGER_URL . '/blog/project_list.php', 'permission' => '360500'),
            array('title' => '키워드 관리', 'url' => SF_MANAGER_URL . '/blog/keyword_list.php', 'permission' => '360600'),
            array('title' => '광고주 관리', 'url' => SF_MANAGER_URL . '/blog/advertiser_list.php', 'permission' => '360100'),
            array('title' => '광고주 계정관리', 'url' => SF_MANAGER_URL . '/blog/advertiser_account_list.php', 'permission' => '361100'),
            array('title' => 'SNS/채널 앱 설정', 'url' => SF_MANAGER_URL . '/blog/channel_app_list.php', 'permission' => '361110'),
            array('title' => '발행사이트관리', 'url' => SF_MANAGER_URL . '/blog/site_list.php', 'permission' => '360300'),
            array('title' => 'AI 공급자설정', 'url' => SF_MANAGER_URL . '/blog/ai_provider_list.php', 'permission' => '360400'),
            array('title' => '예약 발행 관리', 'url' => SF_MANAGER_URL . '/blog/publish_job_list.php', 'permission' => '360700'),
            array('title' => '이미지 라이브러리', 'url' => SF_MANAGER_URL . '/blog/image_list.php', 'permission' => '360800'),
            array('title' => '보고서 대시보드', 'url' => SF_MANAGER_URL . '/blog/report_dashboard.php', 'permission' => '361000'),
        ),
    ),
    array(
        'id' => 'system',
        'title' => '시스템 관리',
        'icon' => 'system',
        'is_system' => true, // 사이드바 하단에 별도 스타일로 표시, 아코디언 접힘 대상 아님
        'items' => array(
            array('title' => '그누보드 관리자', 'url' => G5_ADMIN_URL . '/', 'permission' => '', 'external' => true),
            array('title' => '홈페이지 보기', 'url' => G5_URL . '/', 'permission' => '', 'external' => true),
            array('title' => '관리자 로그아웃', 'url' => SF_MANAGER_URL . '/logout.php', 'permission' => ''),
        ),
    ),
);

// 모바일 하단 고정 5개(홈/쇼폼/랜딩/블로그/전체) — 각 그룹의 대표(첫 번째) 링크를 그대로 재사용한다.
$manager_bottom_nav = array(
    array('id' => 'dashboard', 'label' => '홈', 'icon' => 'gauge', 'url' => SF_MANAGER_URL . '/index.php'),
    array('id' => 'showform', 'label' => '쇼폼', 'icon' => 'showform', 'url' => G5_ADMIN_URL . '/showform/portfolio_list.php'),
    array('id' => 'landing', 'label' => '랜딩', 'icon' => 'landing', 'url' => SF_MANAGER_URL . '/landing/landing_list.php'),
    array('id' => 'blog', 'label' => '블로그', 'icon' => 'blog', 'url' => SF_MANAGER_URL . '/blog/blog_dashboard.php'),
    array('id' => 'more', 'label' => '전체', 'icon' => 'menu', 'url' => '#', 'action' => 'open-mobile-nav'),
);

// 각 메뉴 그룹의 URL이 어느 디렉터리 prefix에 속하는지 - 현재 페이지 활성 판정용.
$manager_menu_prefix = array(
    'dashboard' => SF_MANAGER_URL . '/',
    'showform' => G5_ADMIN_URL . '/showform/',
    'landing' => G5_ADMIN_URL . '/landing/',
    'blog' => G5_ADMIN_URL . '/blog/',
);
