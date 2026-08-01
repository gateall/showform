-- BLOG AUTOMATION: V22 AI 공급자 암호화 키 버전 추적
-- 마스터 암호화 키(BP_CRYPTO_KEY, data/dbconfig.php)를 회전할 때, 어떤 행이 아직 옛 키로
-- 암호화된 상태인지 빠르게 조회하기 위한 컬럼. 실제 판단 근거는 api_key_enc 안에 내장된
-- "{version}:{algo}:..." 프리픽스이고(blog_crypto.lib.php), 이 컬럼은 그 값을 그대로
-- 복사해 둔 조회 최적화용 캐시다 - 평소 API 키 추가/수정/삭제 작업에서는 이 컬럼이나
-- dbconfig.php를 전혀 건드릴 필요가 없다.

ALTER TABLE `{prefix}blog_ai_providers`
    ADD COLUMN IF NOT EXISTS `encryption_key_version` SMALLINT UNSIGNED NULL DEFAULT NULL COMMENT '암호문에 쓰인 BP_CRYPTO_KEY 버전(회전 대상 조회용 캐시)' AFTER `api_key_enc`;
