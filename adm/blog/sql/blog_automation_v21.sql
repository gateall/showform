-- BLOG AUTOMATION: V21 AI 기능 전체 사용 여부(전역 스위치)
-- 프로젝트별/공급자별 설정과 별개로, 블로그 자동화 전체에서 AI 호출 자체를
-- 껐다 켰다 할 수 있는 단일 행 설정 테이블.

CREATE TABLE IF NOT EXISTS `{prefix}blog_ai_global_settings` (
    `id` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `is_enabled` CHAR(1) NOT NULL DEFAULT 'Y' COMMENT 'N이면 전체 AI 호출 차단(수동 작성만 가능)',
    `updated_by` VARCHAR(20) NOT NULL DEFAULT '',
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `{prefix}blog_ai_global_settings` (`id`, `is_enabled`) VALUES (1, 'Y');
