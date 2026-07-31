-- BLOG AUTOMATION: V19 프로젝트별 AI 공급자 선택 / AI 사용안함
-- 미리 등록해 둔 AI 공급자(ai_providers) 중 이 프로젝트에서 쓸 것을 지정하거나,
-- 이 프로젝트는 AI 호출 자체를 하지 않도록(수동 작성) 끌 수 있게 한다.
-- ai_provider_id가 비어있으면 기존과 동일하게 전역 활성 공급자(is_active='Y')를 그대로 쓴다.

ALTER TABLE `{prefix}blog_content_projects`
    ADD COLUMN IF NOT EXISTS `ai_provider_id` INT UNSIGNED NULL COMMENT '프로젝트 지정 AI 공급자(ai_providers.id, NULL=전역 활성 공급자 사용)' AFTER `quality_score`,
    ADD COLUMN IF NOT EXISTS `ai_disabled` CHAR(1) NOT NULL DEFAULT 'N' COMMENT 'Y면 이 프로젝트는 AI를 호출하지 않는다(수동 작성 전용)' AFTER `ai_provider_id`;
