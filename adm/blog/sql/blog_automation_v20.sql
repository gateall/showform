-- BLOG AUTOMATION: V20 AI 공급자 API 주소 오버라이드
-- 고급설정에서 기본 엔드포인트(OpenAI: https://api.openai.com/v1)를 바꿔 써야 하는
-- 경우(예: Azure OpenAI 프록시)를 위한 선택 필드. 비어있으면 기존과 동일하게 동작한다.

ALTER TABLE `{prefix}blog_ai_providers`
    ADD COLUMN IF NOT EXISTS `api_endpoint` VARCHAR(255) NOT NULL DEFAULT '' COMMENT 'API 엔드포인트 오버라이드(비어있으면 기본값 사용)' AFTER `default_model`;
