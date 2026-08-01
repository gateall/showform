<?php
// 이 5개(대시보드/포스팅 만들기/광고주·포스팅 관리/운영 설정) 화면의 실제 파일은 전부
// /manager/blog/ 아래에 있다 - /adm/blog/의 옛 화면들은 대부분 이 화면들로의 리다이렉트
// 껍데기만 남아있거나(advertiser_list.php, project_list.php, keyword_list.php,
// ai_provider_list.php, site_list.php, image_list.php, publish_job_list.php,
// report_dashboard.php 등) 이미 기능이 흡수됐다. G5_ADMIN_URL(=/adm)로 걸면 해당 경로에
// 파일이 없어 404가 난다 - G5_URL.'/manager'로 걸어야 한다.
$menu['menu360'] = array(
    array('360000', '블로그 관리', G5_URL . '/manager/blog/dashboard.php', 'blog'),
    array('360000', '블로그 대시보드', G5_URL . '/manager/blog/dashboard.php', 'blog_dashboard'),
    array('360050', '포스팅 만들기', G5_URL . '/manager/blog/post_builder.php', 'blog_post_builder'),
    array('360100', '광고주·포스팅 관리', G5_URL . '/manager/blog/content_management.php', 'blog_content_management'),
    array('360150', '운영 설정', G5_URL . '/manager/blog/settings.php', 'blog_settings'),
    // 테이블 설치는 DB 마이그레이션 도구라 관리자 쪽(/adm/)에 그대로 둔다 - manager 쪽엔
    // 대응 화면이 없다.
    array('360900', '테이블 설치', G5_ADMIN_URL . '/blog/install_form.php', 'blog_install'),
    // 아래 4개는 랜딩 모듈(adm/landing/)의 기존 화면을 그대로 재사용한다 - 블로그 자동화와
    // 무관한 별개 기능이라 위 5개 통합과는 상관없이 계속 필요하다(각 파일이 자체적으로
    // 검사하는 실제 권한 코드를 그대로 쓰도록 auth_check_menu 호출값도 그대로 둔다).
    array('900100', '제작 사례 대시보드(랜딩 재사용)', G5_ADMIN_URL . '/landing/landing_list.php', 'landing_list'),
    array('900100', '제작 사례 등록(랜딩 재사용)', G5_ADMIN_URL . '/landing/landing_form.php', 'landing_form'),
    array('900200', '상담 신청 관리(랜딩 재사용)', G5_ADMIN_URL . '/landing/inquiry_list.php', 'inquiry_list'),
    array('910200', '방문·전환 통계(랜딩 재사용)', G5_ADMIN_URL . '/landing/inquiry_stats.php', 'inquiry_stats'),
);
