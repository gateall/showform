<?php
$sub_menu = '900050';
include_once('../../common.php');
include_once(G5_ADMIN_PATH . '/admin.lib.php');

if (!$is_admin) {
    alert('관리자만 접근 가능합니다.', G5_URL);
}

$landing_pages_table = G5_TABLE_PREFIX . 'landing_pages';
$landing_inquiries_table = G5_TABLE_PREFIX . 'landing_inquiries';
$landing_reviews_table = G5_TABLE_PREFIX . 'landing_reviews';
$landing_gallery_table = G5_TABLE_PREFIX . 'landing_gallery';
$landing_youtube_table = G5_TABLE_PREFIX . 'landing_youtube';
$landing_notices_table = G5_TABLE_PREFIX . 'landing_notices';

$sql = array();

$sql[] = "CREATE TABLE IF NOT EXISTS `{$landing_pages_table}` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `template_type` VARCHAR(30) NOT NULL DEFAULT '',
  `industry` VARCHAR(100) NOT NULL DEFAULT '',
  `company_name` VARCHAR(255) NOT NULL DEFAULT '',
  `ceo_name` VARCHAR(100) NOT NULL DEFAULT '',
  `phone` VARCHAR(50) NOT NULL DEFAULT '',
  `kakao_url` VARCHAR(255) NOT NULL DEFAULT '',
  `address` VARCHAR(255) NOT NULL DEFAULT '',
  `area_name` VARCHAR(100) NOT NULL DEFAULT '',
  `intro_text` TEXT NULL,
  `main_copy` VARCHAR(255) NOT NULL DEFAULT '',
  `sub_copy` VARCHAR(255) NOT NULL DEFAULT '',
  `theme_color` VARCHAR(20) NOT NULL DEFAULT '',
  `main_image` VARCHAR(255) NOT NULL DEFAULT '',
  `short_alias` VARCHAR(100) NOT NULL DEFAULT '',
  `short_url` VARCHAR(255) NOT NULL DEFAULT '',
  `qr_code_path` VARCHAR(255) NOT NULL DEFAULT '',
  `tracking_code` VARCHAR(255) NOT NULL DEFAULT '',
  `utm_source` VARCHAR(100) NOT NULL DEFAULT '',
  `utm_medium` VARCHAR(100) NOT NULL DEFAULT '',
  `utm_campaign` VARCHAR(100) NOT NULL DEFAULT '',
  `is_active` CHAR(1) NOT NULL DEFAULT 'Y',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_template_type` (`template_type`),
  KEY `idx_industry` (`industry`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS `{$landing_inquiries_table}` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `landing_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `name` VARCHAR(100) NOT NULL DEFAULT '',
  `phone` VARCHAR(50) NOT NULL DEFAULT '',
  `message` TEXT NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'new',
  `ip` VARCHAR(50) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_landing_id` (`landing_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS `{$landing_reviews_table}` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `landing_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `customer_name` VARCHAR(100) NOT NULL DEFAULT '',
  `rating` TINYINT UNSIGNED NOT NULL DEFAULT 0,
  `content` TEXT NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` CHAR(1) NOT NULL DEFAULT 'Y',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_landing_id` (`landing_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS `{$landing_gallery_table}` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `landing_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `image_path` VARCHAR(255) NOT NULL DEFAULT '',
  `description` TEXT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` CHAR(1) NOT NULL DEFAULT 'Y',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_landing_id` (`landing_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS `{$landing_youtube_table}` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `landing_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `youtube_url` VARCHAR(255) NOT NULL DEFAULT '',
  `description` TEXT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_active` CHAR(1) NOT NULL DEFAULT 'Y',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_landing_id` (`landing_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_sort_order` (`sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$sql[] = "CREATE TABLE IF NOT EXISTS `{$landing_notices_table}` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `landing_id` INT UNSIGNED NOT NULL DEFAULT 0,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `content` TEXT NOT NULL,
  `is_active` CHAR(1) NOT NULL DEFAULT 'Y',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_landing_id` (`landing_id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

foreach ($sql as $query) {
    sql_query($query, false);
}

$samples = array(
    array(1, 'service', '누수', '누수탐지 전문', '', '010-0000-0001', '', '대구', '누수·방수·설비 전문', '대구 누수탐지 전문, 정확한 진단과 빠른 해결', '24시간 긴급출동', '#0f766e', '', 'Y'),
    array(2, 'hospital', '병원', '정직한 진료', '', '010-0000-0002', '', '서울', '환자 중심 진료', '환자 중심의 정직한 진료', '빠른 예약 상담하기', '#1d4ed8', '', 'Y'),
    array(3, 'local', '지역업체', '지역상권 홍보', '', '010-0000-0003', '', '부산', '지역 고객 문의 증대', '지역 고객 문의를 빠르게 늘리는 랜딩페이지', '무료 상담받기', '#b45309', '', 'Y'),
);

foreach ($samples as $item) {
    list($id, $template_type, $industry, $company_name, $ceo_name, $phone, $kakao_url, $area_name, $intro_text, $main_copy, $sub_copy, $theme_color, $main_image, $is_active) = $item;
    $exists = sql_fetch(" select id from {$landing_pages_table} where id = '{$id}' limit 1 ");
    if (!$exists) {
        sql_query(" insert into {$landing_pages_table}
            set id = '{$id}',
                template_type = '" . sql_real_escape_string($template_type) . "',
                industry = '" . sql_real_escape_string($industry) . "',
                company_name = '" . sql_real_escape_string($company_name) . "',
                ceo_name = '" . sql_real_escape_string($ceo_name) . "',
                phone = '" . sql_real_escape_string($phone) . "',
                kakao_url = '" . sql_real_escape_string($kakao_url) . "',
                address = '',
                area_name = '" . sql_real_escape_string($area_name) . "',
                intro_text = '" . sql_real_escape_string($intro_text) . "',
                main_copy = '" . sql_real_escape_string($main_copy) . "',
                sub_copy = '" . sql_real_escape_string($sub_copy) . "',
                theme_color = '" . sql_real_escape_string($theme_color) . "',
                main_image = '" . sql_real_escape_string($main_image) . "',
                is_active = '" . sql_real_escape_string($is_active) . "',
                created_at = '" . G5_TIME_YMDHIS . "',
                updated_at = '" . G5_TIME_YMDHIS . "' ");
    }
}

$result_html = '<h2>ShowForm 1단계 설치 완료</h2>';
$result_html .= '<p>테이블 생성과 샘플 데이터 입력이 완료되었습니다.</p>';
$result_html .= '<ul>';
$result_html .= '<li>landing_pages</li>';
$result_html .= '<li>landing_inquiries</li>';
$result_html .= '<li>landing_reviews</li>';
$result_html .= '<li>landing_gallery</li>';
$result_html .= '<li>landing_youtube</li>';
$result_html .= '<li>landing_notices</li>';
$result_html .= '</ul>';

alert($result_html, G5_ADMIN_URL . '/landing/landing_list.php', false);