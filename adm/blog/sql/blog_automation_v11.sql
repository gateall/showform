-- BLOG_AUTOMATION_MVP_PHASE2_STEP_3_7 (Scheduler and Retry)
ALTER TABLE `{prefix}blog_publish_jobs`
  ADD COLUMN IF NOT EXISTS `locked_at` DATETIME NULL COMMENT '작업 잠금 시작 시간',
  ADD COLUMN IF NOT EXISTS `lock_token` VARCHAR(100) NULL COMMENT '작업 선점 고유 토큰 (동시성 제어)',
  ADD COLUMN IF NOT EXISTS `idempotency_key` VARCHAR(100) NULL COMMENT '중복 발행 방지용 고유 키',
  ADD COLUMN IF NOT EXISTS `last_error_code` VARCHAR(50) NULL COMMENT '마지막 오류 코드 (HTTP status 등)',
  ADD COLUMN IF NOT EXISTS `last_error_message` TEXT NULL COMMENT '마지막 오류 상세',
  ADD COLUMN IF NOT EXISTS `completed_at` DATETIME NULL COMMENT '작업 완료(성공 또는 영구실패) 시각';

-- (Optional) worker_id 컬럼 길이를 늘리거나 lock_token 대신 쓸 수도 있지만 명확히 구분하기 위해 추가.
-- scheduled_at과 next_retry_at은 기존(v6, v2)에 이미 추가됨.
