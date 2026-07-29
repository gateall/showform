-- BLOG_AUTOMATION_MVP_CONTENT_PIPELINE (Stage 2)
-- Phase 1(v1) + Phase 2(v2)에 이어 실행하는 증분 스키마. 동일 관례를 따른다:
-- blog_ 네임스페이스, {prefix} 치환은 adm/blog/install.php가 수행, 전 문장 재실행 안전.

-- 1. 해시태그 (posts — 제목/본문과 함께 생성되는 세 번째 콘텐츠 필드)
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `hashtags` VARCHAR(500) NULL;

-- 2. AI 생성 시도 로그 (제목/본문 생성 성공·실패, 토큰 사용량, 비용 추정치)
--    cost_estimate는 실제 청구 금액이 아니라 모델별 단가표 기반 추정치다 — 청구서와
--    다를 수 있으며 참고용이다.
CREATE TABLE IF NOT EXISTS `{prefix}blog_content_generation_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(20) NOT NULL DEFAULT '',
  `provider` VARCHAR(30) NOT NULL DEFAULT '',
  `model` VARCHAR(50) NOT NULL DEFAULT '',
  `status` VARCHAR(10) NOT NULL DEFAULT 'fail',
  `tokens_prompt` INT UNSIGNED NULL,
  `tokens_completion` INT UNSIGNED NULL,
  `cost_estimate` DECIMAL(10,4) NULL,
  `error_message` VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_generation_logs_project` FOREIGN KEY (`project_id`) REFERENCES `{prefix}blog_content_projects` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
