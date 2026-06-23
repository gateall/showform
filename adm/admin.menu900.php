<?php
$menu['menu900'] = array(
    array('900000', '<span class="sf-menu-landing"><i class="fa-solid fa-file-lines"></i></span> 랜딩관리', G5_ADMIN_URL.'/landing/landing_list.php', 'sf_landing'),
    array('900100', '<i class="fa-solid fa-list-check"></i> 라이브목록', G5_ADMIN_URL.'/landing/landing_list.php', 'landing_list'),
    array('900200', '<i class="fa-solid fa-layer-group"></i> 템플릿관리', G5_ADMIN_URL.'/landing/template_list.php', 'template_list'),
    array('900300', '<i class="fa-solid fa-wand-magic-sparkles"></i> AI 자동생성', G5_ADMIN_URL.'/landing/ai_generate.php', 'ai_generate'),
    array('900400', '<i class="fa-solid fa-tags"></i> 업종관리', G5_ADMIN_URL.'/landing/category_list.php', 'category_list'),
);

$menu['menu910'] = array(
    array('910000', '<span class="sf-menu-inquiry"><i class="fa-solid fa-comments"></i></span> 문의관리', G5_ADMIN_URL.'/landing/inquiry_list.php', 'sf_inquiry'),
    array('910100', '<i class="fa-solid fa-inbox"></i> 문의목록', G5_ADMIN_URL.'/landing/inquiry_list.php', 'inquiry_list'),
    array('910200', '<i class="fa-solid fa-chart-column"></i> 문의통계', G5_ADMIN_URL.'/landing/inquiry_stats.php', 'inquiry_stats'),
);

$menu['menu920'] = array(
    array('920000', '<span class="sf-menu-ai"><i class="fa-solid fa-robot"></i></span> AI관리', G5_ADMIN_URL.'/landing/ai_prompt.php', 'sf_ai'),
    array('920100', '<i class="fa-solid fa-wand-magic-sparkles"></i> 프롬프트관리', G5_ADMIN_URL.'/landing/ai_prompt.php', 'ai_prompt'),
    array('920200', '<i class="fa-solid fa-clock-rotate-left"></i> 생성로그', G5_ADMIN_URL.'/landing/ai_log.php', 'ai_log'),
    array('920300', '<i class="fa-solid fa-plug"></i> API설정', G5_ADMIN_URL.'/landing/ai_setting.php', 'ai_setting'),
);
