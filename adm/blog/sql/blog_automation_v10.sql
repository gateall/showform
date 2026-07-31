-- BLOG_AUTOMATION_MVP_PHASE2_STEP_3_8 (이미지 생성 및 최적화 파이프라인)

ALTER TABLE `{prefix}blog_ai_providers` ADD COLUMN IF NOT EXISTS `supports_image` CHAR(1) NOT NULL DEFAULT 'N' COMMENT '이미지 생성 지원 여부';

CREATE TABLE IF NOT EXISTS `{prefix}blog_image_presets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(100) NOT NULL,
  `prompt_template` TEXT NOT NULL,
  `provider_code` VARCHAR(30) NOT NULL,
  `width` INT NOT NULL DEFAULT 1024,
  `height` INT NOT NULL DEFAULT 1024,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='AI 이미지 생성 프리셋';

-- 중복 제거를 위한 파일 해시 컬럼 추가
ALTER TABLE `{prefix}blog_images` ADD COLUMN IF NOT EXISTS `file_hash` VARCHAR(64) NOT NULL DEFAULT '' COMMENT '파일 MD5/SHA 해시 (중복 업로드 방지용)' AFTER `file_size`;
ALTER TABLE `{prefix}blog_images` ADD INDEX IF NOT EXISTS `idx_file_hash` (`file_hash`);
