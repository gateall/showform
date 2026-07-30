-- BLOG AUTOMATION: V16 프론트 스튜디오 (Phase 1)
-- 프론트 전용 넓은 작성 화면에서 사용되는 단락형 데이터 저장을 위한 신규 테이블 추가

-- 1. 본문 단락 (포스팅을 여러 블록으로 분할하여 저장)
CREATE TABLE IF NOT EXISTS `{prefix}blog_post_sections` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `sort_order` INT NOT NULL DEFAULT 0,
  `section_type` VARCHAR(30) NOT NULL DEFAULT 'body' COMMENT 'basic, title, subtitle, body 등',
  `section_title` VARCHAR(255) NOT NULL DEFAULT '',
  `content` LONGTEXT NULL,
  `purpose` VARCHAR(255) NOT NULL DEFAULT '',
  `target_length` INT UNSIGNED NOT NULL DEFAULT 0,
  `status` VARCHAR(30) NOT NULL DEFAULT 'ready' COMMENT 'ready, writing, completed, review',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_sort_order` (`sort_order`),
  CONSTRAINT `fk_sections_post` FOREIGN KEY (`post_id`) REFERENCES `{prefix}blog_posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 프롬프트 이력 (AI 생성 요청 시 사용된 프롬프트 보관)
CREATE TABLE IF NOT EXISTS `{prefix}blog_ai_prompts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `section_id` INT UNSIGNED NULL,
  `prompt_text` LONGTEXT NULL,
  `ai_provider_id` INT UNSIGNED NULL,
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft' COMMENT 'draft, modified, executed, final',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_section_id` (`section_id`),
  CONSTRAINT `fk_prompts_post` FOREIGN KEY (`post_id`) REFERENCES `{prefix}blog_posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_prompts_section` FOREIGN KEY (`section_id`) REFERENCES `{prefix}blog_post_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 콘텐츠 꼬리표 카드 (우측 AI 도구 결과물이 본문 옆에 귀속됨)
CREATE TABLE IF NOT EXISTS `{prefix}blog_content_tags` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `section_id` INT UNSIGNED NOT NULL,
  `tag_type` VARCHAR(30) NOT NULL DEFAULT 'keyword' COMMENT 'keyword, place, url, image, video 등',
  `tag_data` LONGTEXT NULL COMMENT 'JSON 형태로 실제 데이터 저장',
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_section_id` (`section_id`),
  CONSTRAINT `fk_tags_post` FOREIGN KEY (`post_id`) REFERENCES `{prefix}blog_posts` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tags_section` FOREIGN KEY (`section_id`) REFERENCES `{prefix}blog_post_sections` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
