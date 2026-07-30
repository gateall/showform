<?php
$menu['menu360'] = array(
    array('360000', '블로그', G5_ADMIN_URL . '/blog/blog_dashboard.php', 'blog'),
    array('360000', '블로그 대시보드 (통합)', G5_ADMIN_URL . '/blog/blog_dashboard.php', 'blog_dashboard'),
    array('360050', '통합 포스팅 제작', G5_ADMIN_URL . '/blog/post_builder.php', 'blog_post_builder'),
    array('360100', '광고주 관리', G5_ADMIN_URL . '/blog/advertiser_list.php', 'blog_advertiser'),
    array('361100', '광고주 계정관리', G5_ADMIN_URL . '/blog/advertiser_account_list.php', 'blog_advertiser_account'),
    array('361110', 'SNS/채널 앱 설정', G5_ADMIN_URL . '/blog/channel_app_list.php', 'blog_channel_app'),
    array('360300', '발행사이트관리', G5_ADMIN_URL . '/blog/site_list.php', 'blog_site'),
    array('360400', 'AI 공급자설정', G5_ADMIN_URL . '/blog/ai_provider_list.php', 'blog_ai_provider'),
    array('360500', '콘텐츠 프로젝트', G5_ADMIN_URL . '/blog/project_list.php', 'blog_project'),
    array('360600', '키워드 관리', G5_ADMIN_URL . '/blog/keyword_list.php', 'blog_keyword'),
    // 아래 4개는 랜딩 모듈(adm/landing/)의 기존 화면을 그대로 재사용 — 새 코드를 붙이지 않고
    // 각 파일이 자체적으로 검사하는 실제 권한 코드(auth_check_menu 호출값)를 그대로 사용해야
    // 이 메뉴로 들어가도 랜딩 쪽과 동일하게 권한이 적용된다.
    array('900100', '제작 사례 대시보드(랜딩 재사용)', G5_ADMIN_URL . '/landing/landing_list.php', 'landing_list'),
    array('900100', '제작 사례 등록(랜딩 재사용)', G5_ADMIN_URL . '/landing/landing_form.php', 'landing_form'),
    array('900200', '상담 신청 관리(랜딩 재사용)', G5_ADMIN_URL . '/landing/inquiry_list.php', 'inquiry_list'),
    array('910200', '방문·전환 통계(랜딩 재사용)', G5_ADMIN_URL . '/landing/inquiry_stats.php', 'inquiry_stats'),
    array('360700', '예약 발행 관리', G5_ADMIN_URL . '/blog/publish_job_list.php', 'blog_publish_job'),
    array('360800', '이미지 라이브러리', G5_ADMIN_URL . '/blog/image_list.php', 'blog_image'),
    array('360900', '테이블 설치', G5_ADMIN_URL . '/blog/install_form.php', 'blog_install'),
    array('361000', '보고서 대시보드', G5_ADMIN_URL . '/blog/report_dashboard.php', 'blog_report'),
    array('361000', '일간 발행 보고서', G5_ADMIN_URL . '/blog/report_daily.php', 'blog_report_daily'),
    array('361010', '주간 운영 보고서', G5_ADMIN_URL . '/blog/report_weekly.php', 'blog_report_weekly'),
    array('361020', '월간 광고주 보고서', G5_ADMIN_URL . '/blog/report_monthly_adv.php', 'blog_report_monthly'),
    array('361000', '사이트별 발행 통계', G5_ADMIN_URL . '/blog/report_site.php', 'blog_report_site'),
    array('361030', '콘텐츠 성과 기록', G5_ADMIN_URL . '/blog/report_performance.php', 'blog_report_perf'),
    array('361000', '성공·실패 통계', G5_ADMIN_URL . '/blog/report_stats.php', 'blog_report_stats'),
);
