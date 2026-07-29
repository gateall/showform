CREATE TABLE IF NOT EXISTS `g5_blog_naver_packages` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_target_id` INT UNSIGNED NOT NULL,
  `job_id` INT UNSIGNED NOT NULL,
  `package_filename` VARCHAR(255) NOT NULL,
  `package_path` VARCHAR(255) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `download_count` INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `idx_post_target` (`post_target_id`),
  KEY `idx_job` (`job_id`),
  CONSTRAINT `fk_naver_pack_target` FOREIGN KEY (`post_target_id`) REFERENCES `g5_blog_post_targets` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_naver_pack_job` FOREIGN KEY (`job_id`) REFERENCES `g5_blog_publish_jobs` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='네이버 블로그 수동 발행 패키지 파일 정보';
