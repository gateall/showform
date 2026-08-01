-- BLOG AUTOMATION: V25 AI 글 생성 조건(생성조건/기술조건/SEO조건/금지조건 등) 프리셋
-- 관리자가 미리 등록해두는 "체크형 지시문" 라이브러리. post_builder.php 1단계에서
-- rule_type별로 그룹핑해 체크박스로 보여주고, 체크된 항목의 rule_instruction만
-- 골라 AI 프롬프트에 그대로 얹는다. 프로젝트별 선택 상태·추가 지시는 별도 테이블 없이
-- posts.builder_state JSON에 실어 보존한다(이미 hashtag_enabled 등에 쓰는 것과 동일한 방식).

CREATE TABLE IF NOT EXISTS `{prefix}blog_generation_rules` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `rule_type` VARCHAR(30) NOT NULL COMMENT 'writing_rule/technical_rule/seo_rule/prohibited_rule/quality_rule',
    `rule_name` VARCHAR(150) NOT NULL COMMENT '체크박스에 보이는 이름',
    `rule_instruction` TEXT NOT NULL COMMENT 'AI에 실제로 전달되는 상세 지시문',
    `is_default` CHAR(1) NOT NULL DEFAULT 'N' COMMENT '새 프로젝트에서 기본 체크 여부',
    `is_active` CHAR(1) NOT NULL DEFAULT 'Y' COMMENT 'N이면 목록/체크박스에서 숨김(삭제 대신 비활성화)',
    `sort_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL,
    `updated_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_rule_type` (`rule_type`, `is_active`, `sort_order`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
