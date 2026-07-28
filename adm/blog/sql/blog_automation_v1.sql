-- BLOG_AUTOMATION_MVP_PHASE1
-- 서비스 중립 명칭 사용 (BLOG_AUTOMATION_DATA_MODEL.md 6절) — blog_ 접두어 금지
-- 실제 적용은 adm/blog/install.php 의 CREATE TABLE IF NOT EXISTS 로 수행됨(그누보드5 관례).
-- 이 파일은 검토용 DDL 원본이며 개발/운영 DB에 직접 실행해도 동일한 결과를 낸다.
-- 테이블 접두어({prefix})는 그누보드5 설정의 G5_TABLE_PREFIX 값으로 치환한다.

-- 1. 광고주 (공통, 최소 업체정보)
CREATE TABLE IF NOT EXISTS `{prefix}advertisers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `phone` VARCHAR(50) NOT NULL DEFAULT '',
  `sub_phone` VARCHAR(50) NOT NULL DEFAULT '',
  `address` VARCHAR(255) NOT NULL DEFAULT '',
  `domain` VARCHAR(255) NOT NULL DEFAULT '',
  `consult_url` VARCHAR(255) NOT NULL DEFAULT '',
  `kakao_channel` VARCHAR(255) NOT NULL DEFAULT '',
  `service_region` VARCHAR(255) NOT NULL DEFAULT '',
  `core_service` VARCHAR(255) NOT NULL DEFAULT '',
  `intro_text` TEXT NULL,
  `forbidden_words` TEXT NULL,
  `mandatory_notice` TEXT NULL,
  `status` CHAR(1) NOT NULL DEFAULT 'Y',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 발행 사이트 (공통)
CREATE TABLE IF NOT EXISTS `{prefix}sites` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `advertiser_id` INT UNSIGNED NOT NULL,
  `name` VARCHAR(255) NOT NULL DEFAULT '',
  `platform` VARCHAR(20) NOT NULL DEFAULT 'wordpress',
  `base_url` VARCHAR(255) NOT NULL DEFAULT '',
  `status` CHAR(1) NOT NULL DEFAULT 'Y',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_advertiser_id` (`advertiser_id`),
  KEY `idx_platform` (`platform`),
  CONSTRAINT `fk_sites_advertiser` FOREIGN KEY (`advertiser_id`) REFERENCES `{prefix}advertisers` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 사이트별 발행 채널 인증 정보 (WordPress Application Password 등) — 값은 암호화 저장, 화면엔 masked_hint만 노출
CREATE TABLE IF NOT EXISTS `{prefix}site_credentials` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `site_id` INT UNSIGNED NOT NULL,
  `cred_type` VARCHAR(30) NOT NULL DEFAULT 'wp_app_password',
  `cred_username` VARCHAR(255) NOT NULL DEFAULT '',
  `cred_value_enc` TEXT NULL,
  `masked_hint` VARCHAR(20) NOT NULL DEFAULT '',
  `updated_by` VARCHAR(20) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_site_credtype` (`site_id`, `cred_type`),
  CONSTRAINT `fk_credentials_site` FOREIGN KEY (`site_id`) REFERENCES `{prefix}sites` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. AI 공급자 설정 (최소 — API 키는 암호화 저장, masked_hint만 화면 노출)
CREATE TABLE IF NOT EXISTS `{prefix}ai_providers` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_code` VARCHAR(30) NOT NULL DEFAULT 'openai',
  `display_name` VARCHAR(100) NOT NULL DEFAULT '',
  `is_active` CHAR(1) NOT NULL DEFAULT 'N',
  `api_key_enc` TEXT NULL,
  `masked_hint` VARCHAR(20) NOT NULL DEFAULT '',
  `default_model` VARCHAR(50) NOT NULL DEFAULT '',
  `max_tokens` INT NOT NULL DEFAULT 2000,
  `temperature` DECIMAL(3,2) NOT NULL DEFAULT 0.70,
  `updated_by` VARCHAR(20) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_provider_code` (`provider_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. 콘텐츠 프로젝트 (오토블로그 전용 — 승인 상태머신의 상태를 갖는 핵심 엔티티)
--    status: draft -> generated -> review_required -> approved -> publish_pending -> publishing -> published|failed
--    contract_id 컬럼은 Phase 2(계약 차감)에서 추가 — 이번 단계는 광고주 단위까지만 최소 구현
CREATE TABLE IF NOT EXISTS `{prefix}content_projects` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_uuid` VARCHAR(36) NOT NULL DEFAULT '',
  `advertiser_id` INT UNSIGNED NOT NULL,
  `primary_site_id` INT UNSIGNED NULL,
  `topic` VARCHAR(255) NOT NULL DEFAULT '',
  `primary_keyword` VARCHAR(255) NOT NULL DEFAULT '',
  `content_type` VARCHAR(30) NOT NULL DEFAULT 'info',
  `target_audience` VARCHAR(255) NOT NULL DEFAULT '',
  `content_length` VARCHAR(20) NOT NULL DEFAULT 'normal',
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft',
  `quality_score` TINYINT UNSIGNED NULL,
  `duplicate_score` TINYINT UNSIGNED NULL,
  `created_by` VARCHAR(20) NOT NULL DEFAULT '',
  `reviewed_by` VARCHAR(20) NOT NULL DEFAULT '',
  `approved_at` DATETIME NULL,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_uuid` (`project_uuid`),
  KEY `idx_advertiser_id` (`advertiser_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_projects_advertiser` FOREIGN KEY (`advertiser_id`) REFERENCES `{prefix}advertisers` (`id`),
  CONSTRAINT `fk_projects_site` FOREIGN KEY (`primary_site_id`) REFERENCES `{prefix}sites` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. 프로젝트별 키워드 (그룹 분류 + 잠금)
CREATE TABLE IF NOT EXISTS `{prefix}content_keywords` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `keyword_group` VARCHAR(20) NOT NULL DEFAULT 'primary',
  `keyword` VARCHAR(100) NOT NULL DEFAULT '',
  `is_locked` CHAR(1) NOT NULL DEFAULT 'N',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_keyword_group` (`keyword_group`),
  CONSTRAINT `fk_keywords_project` FOREIGN KEY (`project_id`) REFERENCES `{prefix}content_projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. 포스팅 원본 (BLOG_AUTOMATION_DATA_MODEL.md 2절 — post_targets와 반드시 분리)
CREATE TABLE IF NOT EXISTS `{prefix}posts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `subtitle` VARCHAR(255) NOT NULL DEFAULT '',
  `body` LONGTEXT NULL,
  `version` INT UNSIGNED NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  CONSTRAINT `fk_posts_project` FOREIGN KEY (`project_id`) REFERENCES `{prefix}content_projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. 사이트별 발행 인스턴스 (1개 포스팅 : N개 사이트) — 제목/본문/이미지/예약/상태/재시도를 사이트마다 별도 저장
CREATE TABLE IF NOT EXISTS `{prefix}post_targets` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_id` INT UNSIGNED NOT NULL,
  `site_id` INT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL DEFAULT '',
  `body` LONGTEXT NULL,
  `featured_image` VARCHAR(255) NOT NULL DEFAULT '',
  `scheduled_at` DATETIME NULL,
  `publish_status` VARCHAR(20) NOT NULL DEFAULT 'draft',
  `external_post_id` VARCHAR(100) NOT NULL DEFAULT '',
  `published_url` VARCHAR(255) NOT NULL DEFAULT '',
  `last_error` TEXT NULL,
  `retry_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_post_id` (`post_id`),
  KEY `idx_site_id` (`site_id`),
  KEY `idx_publish_status` (`publish_status`),
  CONSTRAINT `fk_targets_post` FOREIGN KEY (`post_id`) REFERENCES `{prefix}posts` (`id`),
  CONSTRAINT `fk_targets_site` FOREIGN KEY (`site_id`) REFERENCES `{prefix}sites` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. 발행 작업 잠금 큐 (BLOG_AUTOMATION_DATA_MODEL.md 4절 — pending -> claimed -> processing -> published)
--    active_lock_key: 활성 작업(pending/claimed/processing) 동안만 값을 채우고, 종료(published/failed) 시 NULL로 되돌린다.
--    UNIQUE 인덱스는 NULL을 중복 허용하므로, 종료된 이력은 여러 건이어도 되지만 "활성 작업"은 target당 항상 1건으로 강제된다.
CREATE TABLE IF NOT EXISTS `{prefix}publish_jobs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `post_target_id` INT UNSIGNED NOT NULL,
  `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
  `active_lock_key` VARCHAR(20) NULL,
  `claimed_at` DATETIME NULL,
  `worker_id` VARCHAR(100) NOT NULL DEFAULT '',
  `attempt_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_active_lock` (`active_lock_key`),
  KEY `idx_post_target_id` (`post_target_id`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_jobs_target` FOREIGN KEY (`post_target_id`) REFERENCES `{prefix}post_targets` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. 발행 시도 이력 (성공/실패 각 시도 기록 — response_message에 절대 비밀정보 기록 금지)
CREATE TABLE IF NOT EXISTS `{prefix}publish_attempts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `publish_job_id` INT UNSIGNED NOT NULL,
  `attempt_no` INT UNSIGNED NOT NULL DEFAULT 1,
  `status` VARCHAR(20) NOT NULL DEFAULT 'failed',
  `response_code` VARCHAR(10) NOT NULL DEFAULT '',
  `response_message` VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_publish_job_id` (`publish_job_id`),
  CONSTRAINT `fk_attempts_job` FOREIGN KEY (`publish_job_id`) REFERENCES `{prefix}publish_jobs` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. 상태 변경 활동 로그 (승인 상태머신 감사 추적용)
CREATE TABLE IF NOT EXISTS `{prefix}content_activity_logs` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `action` VARCHAR(50) NOT NULL DEFAULT '',
  `actor` VARCHAR(20) NOT NULL DEFAULT '',
  `detail` VARCHAR(500) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  CONSTRAINT `fk_logs_project` FOREIGN KEY (`project_id`) REFERENCES `{prefix}content_projects` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
