-- BLOG AUTOMATION: V17 새 프로젝트 등록 화면 개편 (선택 중심 UI)
-- 광고주 상세 정보(대표자/업종) + 프로젝트 등록 시 목적/타깃독자/글유형을
-- 체크박스로 다중 선택해 저장할 수 있도록 컬럼을 추가한다.
-- 값은 기존 관례(hashtags, secondary_keywords 등)와 동일하게 콤마구분 텍스트로 저장한다.

-- 1. 광고주 - 대표자/업종 정보
ALTER TABLE `{prefix}blog_advertisers`
    ADD COLUMN IF NOT EXISTS `ceo_name` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '대표자명' AFTER `name`,
    ADD COLUMN IF NOT EXISTS `industry` VARCHAR(200) NOT NULL DEFAULT '' COMMENT '업종(콤마구분, 다중선택)' AFTER `core_service`,
    ADD COLUMN IF NOT EXISTS `industry_detail` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '세부 업종(직접입력)' AFTER `industry`,
    ADD COLUMN IF NOT EXISTS `memo` TEXT NULL COMMENT '기타 참고사항' AFTER `mandatory_notice`;

-- 2. 콘텐츠 프로젝트 - 프로젝트명 자동생성 + 목적/타깃/유형 다중선택
ALTER TABLE `{prefix}blog_content_projects`
    ADD COLUMN IF NOT EXISTS `project_name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '자동생성 프로젝트명(수정 가능)' AFTER `topic`,
    ADD COLUMN IF NOT EXISTS `purpose_tags` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '글 목적(콤마구분, 다중선택 + 기타)' AFTER `content_type`,
    ADD COLUMN IF NOT EXISTS `content_type_secondary` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '글 유형 중 대표(content_type) 제외 나머지(콤마구분)' AFTER `purpose_tags`,
    ADD COLUMN IF NOT EXISTS `target_reader_type` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '타깃 독자유형(콤마구분)' AFTER `target_audience`,
    ADD COLUMN IF NOT EXISTS `target_age_group` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '타깃 연령대(콤마구분)' AFTER `target_reader_type`,
    ADD COLUMN IF NOT EXISTS `target_customer_stage` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '타깃 고객상태(콤마구분)' AFTER `target_age_group`,
    ADD COLUMN IF NOT EXISTS `target_region` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '타깃 지역(콤마구분)' AFTER `target_customer_stage`,
    ADD COLUMN IF NOT EXISTS `target_audience_detail` VARCHAR(500) NOT NULL DEFAULT '' COMMENT '타깃 독자 상세설명(직접입력)' AFTER `target_region`,
    ADD COLUMN IF NOT EXISTS `project_notes` VARCHAR(1000) NOT NULL DEFAULT '' COMMENT '프로젝트별 추가 설명(직접입력)' AFTER `target_audience_detail`;
