-- MINIWEB: V1 스키마
--
-- 미니웹은 기존 landing_page(단수) / landing_pages(복수) 어느 쪽에도 붙이지 않는다.
-- 두 테이블이 이미 혼선 상태라 그 구조를 상속하면 같은 문제를 물려받는다.
--
-- 핵심 분리 원칙:
--   block   = 디자인 (HTML/CSS/스키마)
--   section = 고객이 입력한 실제 콘텐츠
-- 이 둘을 섞지 않아야 Hero A -> Hero C로 디자인만 바꿔도 입력값이 살아남는다.
-- 그래서 고객 데이터는 절대 html_template 안에 저장하지 않는다.
--
-- 이 파일은 배포 후 수정하지 않는다. 스키마를 바꿔야 하면 miniweb_v2.sql을 새로 추가한다.
-- (로컬과 운영의 스키마 버전이 갈리면 추적이 불가능해진다.)

-- 1. 프로젝트 — 미니웹 한 건의 기본 정보
CREATE TABLE IF NOT EXISTS `{prefix}miniweb_project` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_name` VARCHAR(255) NOT NULL DEFAULT '',
  `customer_name` VARCHAR(100) NOT NULL DEFAULT '',
  `customer_phone` VARCHAR(50) NOT NULL DEFAULT '',
  `customer_email` VARCHAR(255) NOT NULL DEFAULT '',
  `template_id` INT UNSIGNED NULL COMMENT '업종 템플릿에서 시작한 경우',
  `status` VARCHAR(30) NOT NULL DEFAULT 'draft' COMMENT 'draft, submitted, reviewing, building, done, canceled',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  `submitted_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 블록 — 관리자가 등록하는 디자인 라이브러리. 고객 데이터는 들어가지 않는다.
CREATE TABLE IF NOT EXISTS `{prefix}miniweb_block` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `section_type` VARCHAR(30) NOT NULL COMMENT 'header, hero, visual, primary_cta, benefits, service, process, pricing, trust, faq, contact_form, footer, mobile_fixed_cta',
  `block_code` VARCHAR(60) NOT NULL COMMENT '고유 코드. hero_left, hero_center 등',
  `block_name` VARCHAR(120) NOT NULL DEFAULT '',
  `thumbnail_url` VARCHAR(255) NOT NULL DEFAULT '',
  `html_template` LONGTEXT NULL COMMENT '{{key}} 자리표시자를 content_json 값으로 치환한다',
  `css_code` LONGTEXT NULL,
  `js_code` LONGTEXT NULL,
  `schema_json` LONGTEXT NULL COMMENT '이 블록이 요구하는 입력 항목 정의. 편집 패널이 이걸 보고 폼을 그린다',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `sort_order` INT NOT NULL DEFAULT 0,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_block_code` (`block_code`),
  KEY `idx_section_type` (`section_type`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 섹션 — 프로젝트별 "어떤 블록을 골랐고 무엇을 입력했는가".
--    block_id만 바꾸면 디자인이 교체되고 content_json은 그대로 남는다. 이게 이 설계의 전부다.
CREATE TABLE IF NOT EXISTS `{prefix}miniweb_section` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` INT UNSIGNED NOT NULL,
  `section_type` VARCHAR(30) NOT NULL,
  `block_id` INT UNSIGNED NULL COMMENT '미선택 상태를 허용한다',
  `content_json` LONGTEXT NULL COMMENT '고객 입력값. 디자인을 바꿔도 유지된다',
  `style_json` LONGTEXT NULL COMMENT '정렬/배경 등 섹션 단위 설정',
  `sort_order` INT NOT NULL DEFAULT 0,
  `is_enabled` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_project_section` (`project_id`, `section_type`),
  KEY `idx_project` (`project_id`, `sort_order`),
  KEY `idx_block` (`block_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
