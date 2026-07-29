-- BLOG_AUTOMATION_MVP_PHASE2
-- Phase 1(blog_automation_v1.sql)에 이어 실행하는 증분 스키마. v1과 동일한 관례를 따른다:
-- 테이블명은 blog_ 네임스페이스로 통일(2026-07-29 PM 지시), {prefix} 치환은 adm/blog/install.php가 수행.
-- 모든 문장이 재실행 안전(idempotent)하도록 IF NOT EXISTS를 사용한다(MariaDB 10.x 확장 문법).

-- 1. 제목 후보 (프로젝트당 여러 건 — AI 생성 또는 관리자 직접 추가)
CREATE TABLE IF NOT EXISTS `{prefix}blog_content_title_candidates` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `source` VARCHAR(10) NOT NULL DEFAULT 'ai',
  `is_selected` CHAR(1) NOT NULL DEFAULT 'N',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  CONSTRAINT `fk_title_candidates_project` FOREIGN KEY (`project_id`) REFERENCES `{prefix}blog_content_projects` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 품질 검사 결과 (생성 직후 자동 실행 — 승인 게이트가 이 테이블의 최신 결과를 참조)
CREATE TABLE IF NOT EXISTS `{prefix}blog_content_quality_checks` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `post_id` INT UNSIGNED NOT NULL,
  `check_key` VARCHAR(40) NOT NULL DEFAULT '',
  `status` VARCHAR(10) NOT NULL DEFAULT 'fail',
  `detail` VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_quality_checks_project` FOREIGN KEY (`project_id`) REFERENCES `{prefix}blog_content_projects` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_quality_checks_post` FOREIGN KEY (`post_id`) REFERENCES `{prefix}blog_posts` (`id`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 발행 재시도 대기 시각 (재시도 백오프 게이트 — project_action.php mode=retry가 참조)
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `next_retry_at` DATETIME NULL;

-- 4. 직전 본문 스냅샷 (승인 화면의 원문·수정본 비교용 — 전체 버전이력이 아닌 1단계 이전본만 보관)
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `previous_body` LONGTEXT NULL;
