-- BLOG_AUTOMATION_MVP_KEYWORD_OPERATIONS (Phase 2 / Step 3-5)
-- Phase 1~3에 이어 실행하는 증분 스키마.
-- blog_ 네임스페이스, {prefix} 치환은 adm/blog/install.php가 수행, 전 문장 재실행 안전.

-- 1. 키워드 사용 상태 (활성/비활성)
ALTER TABLE `{prefix}blog_content_keywords` ADD COLUMN IF NOT EXISTS `status` CHAR(1) NOT NULL DEFAULT 'Y';

-- 2. 키워드 수정일
ALTER TABLE `{prefix}blog_content_keywords` ADD COLUMN IF NOT EXISTS `updated_at` DATETIME NULL;
