-- BLOG AUTOMATION: V24 연결 테스트 결과 저장
-- 공급자별 상태 표시(사용 중/테스트 필요/연결 오류/사용 안 함/API 키 필요)를 provider_code
-- 기반의 하드코딩된 "라이브 여부" 플래그가 아니라, 실제로 연결 테스트를 돌려본 이력으로
-- 판단하기 위한 컬럼. ai_provider_test.php가 테스트를 실행할 때마다 이 값을 갱신한다.

ALTER TABLE `{prefix}blog_ai_providers`
    ADD COLUMN IF NOT EXISTS `last_test_status` VARCHAR(10) NOT NULL DEFAULT '' COMMENT 'success|error, 빈 값=한 번도 테스트 안 함' AFTER `encryption_key_version`,
    ADD COLUMN IF NOT EXISTS `last_test_message` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '마지막 연결 테스트 결과 메시지(성공 시 모델 확인 등, 실패 시 오류 요약)' AFTER `last_test_status`,
    ADD COLUMN IF NOT EXISTS `last_test_at` DATETIME NULL AFTER `last_test_message`;
