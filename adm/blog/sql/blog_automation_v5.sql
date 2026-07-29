-- BLOG_AUTOMATION_MVP_POST_MANAGEMENT (Phase 2 / Step 3-5 — 포스트 목록·상세·수정·삭제)
-- Phase 1~4에 이어 실행하는 증분 스키마.
-- blog_ 네임스페이스, {prefix} 치환은 adm/blog/install.php가 수행, 전 문장 재실행 안전.

-- 1. 포스트 SEO/편집 메타데이터 (project_view.php 기본정보 수정과 별개로 포스트 콘텐츠에 종속)
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `slug` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `excerpt` VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `meta_title` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `meta_description` VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `secondary_keywords` VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `category` VARCHAR(100) NOT NULL DEFAULT '';
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `tags` VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `featured_image_url` VARCHAR(500) NOT NULL DEFAULT '';
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `internal_memo` VARCHAR(1000) NOT NULL DEFAULT '';
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `review_comment` VARCHAR(1000) NOT NULL DEFAULT '';

-- 2. 콘텐츠 프로젝트 소프트 삭제 (물리 삭제 대신 상태 보존 — project_action.php의 delete 모드에서만 채움)
ALTER TABLE `{prefix}blog_content_projects` ADD COLUMN IF NOT EXISTS `deleted_at` DATETIME NULL;
ALTER TABLE `{prefix}blog_content_projects` ADD COLUMN IF NOT EXISTS `deleted_by` VARCHAR(20) NOT NULL DEFAULT '';
