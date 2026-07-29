-- BLOG_AUTOMATION_MVP_PHASE3_STAGE4 (PHP 블로그 자체 발행)
-- blog_ 네임스페이스, {prefix} 치환은 adm/blog/install.php가 수행, 전 문장 재실행 안전.

CREATE TABLE IF NOT EXISTS `{prefix}blog_category_mappings` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` INT UNSIGNED NOT NULL COMMENT '대상 발행 사이트 ID',
  `internal_category_name` VARCHAR(100) NOT NULL COMMENT '내부 카테고리명',
  `remote_category_id` VARCHAR(50) NOT NULL COMMENT '원격 카테고리 ID',
  `remote_category_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '원격 카테고리명',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_site_internal` (`site_id`, `internal_category_name`),
  CONSTRAINT `fk_catmap_site` FOREIGN KEY (`site_id`) REFERENCES `{prefix}blog_sites` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='사이트별 카테고리 매핑';
