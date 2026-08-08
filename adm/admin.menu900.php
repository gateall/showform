<?php
$menu['menu900'] = array(
    array('900000', '<span class="sf-menu-landing"><i class="fa-solid fa-file-lines"></i></span> 랜딩관리', G5_ADMIN_URL.'/landing/landing_list.php', 'sf_landing'),
    // 900100: 라이브목록(landing_list.php)이 대표 등록. template_list.php도 실제로는
    // 같은 900100 권한을 쓰지만(auth_check_menu 코드 불변), 같은 코드로 메뉴 행을 두 번
    // 등록하면 auth_list.php의 $auth_menu[900100] 라벨이 마지막 항목으로 덮어써지므로
    // 별도 메뉴 행은 만들지 않는다 - 900100 권한을 쓰는 하위 화면으로만 취급한다.
    array('900100', '<i class="fa-solid fa-list-check"></i> 라이브목록', G5_ADMIN_URL.'/landing/landing_list.php', 'landing_list'),
    array('900300', '<i class="fa-solid fa-wand-magic-sparkles"></i> AI 자동생성', G5_ADMIN_URL.'/landing/ai_generate.php', 'ai_generate'),
    array('900400', '<i class="fa-solid fa-images"></i> 갤러리관리', G5_ADMIN_URL.'/landing/gallery_list.php', 'gallery_list'),
    array('990050', '<i class="fa-solid fa-tags"></i> 업종관리', G5_ADMIN_URL.'/landing/category_list.php', 'category_list'),
    // 900060: 미니웹 설치/설정 화면 전용 코드. settings.php가 쓰던 900900은
    // adm/sms_admin/num_book_file*.php가 이미 쓰고 있어(= 권한을 SMS 번호북과 공유),
    // 여기에 등록하면 auth_list.php의 900900 라벨까지 "미니웹 설정"으로 덮어썼다.
    // 랜딩 설치 화면(install.php)이 쓰는 900050 바로 옆의 빈 코드를 새로 잡았다.
    array('900060', '<i class="fa-solid fa-cubes"></i> 미니웹 설정', G5_ADMIN_URL.'/landing/settings.php', 'miniweb_settings'),
);

$menu['menu910'] = array(
    // 그룹 헤더(인덱스 0)는 print_menu2()/bp_sf_build_content_verticals()가 절대 읽지 않는
    // 자리지만(둘 다 i=1부터 순회) 배열 구조상 비워둘 수 없다 - 실제 어떤 auth_check_menu()도
    // 안 쓰는 910000을 그대로 유지해 900200(문의목록)과 코드가 겹치지 않게 한다.
    array('910000', '<span class="sf-menu-inquiry"><i class="fa-solid fa-comments"></i></span> 문의관리', G5_ADMIN_URL.'/landing/inquiry_list.php', 'sf_inquiry'),
    array('900200', '<i class="fa-solid fa-inbox"></i> 문의목록', G5_ADMIN_URL.'/landing/inquiry_list.php', 'inquiry_list'),
    array('910200', '<i class="fa-solid fa-chart-column"></i> 문의통계', G5_ADMIN_URL.'/landing/inquiry_stats.php', 'inquiry_stats'),
);

$menu['menu920'] = array(
    array('920000', '<span class="sf-menu-ai"><i class="fa-solid fa-robot"></i></span> AI관리', G5_ADMIN_URL.'/landing/ai_prompt.php', 'sf_ai'),
    array('920100', '<i class="fa-solid fa-wand-magic-sparkles"></i> 프롬프트관리', G5_ADMIN_URL.'/landing/ai_prompt.php', 'ai_prompt'),
    array('920200', '<i class="fa-solid fa-clock-rotate-left"></i> 생성로그', G5_ADMIN_URL.'/landing/ai_log.php', 'ai_log'),
    array('920300', '<i class="fa-solid fa-plug"></i> API설정', G5_ADMIN_URL.'/landing/ai_setting.php', 'ai_setting'),
);
