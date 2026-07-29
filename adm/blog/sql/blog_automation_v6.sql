-- BLOG_AUTOMATION_MVP_PHASE2_STEP_3_6 (예약 발행 데이터 및 일정 관리)
-- Phase 1~5에 이어 실행하는 증분 스키마.
-- blog_ 네임스페이스, {prefix} 치환은 adm/blog/install.php가 수행, 전 문장 재실행 안전.

-- 1. 기존 잠금 큐 중심이었던 `blog_publish_jobs` 테이블에 예약 일정(Schedule) 관련 메타데이터 증분
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `schedule_type` VARCHAR(20) NOT NULL DEFAULT 'fixed' COMMENT '예약 방식 (fixed: 고정 시각, random: 기간 내 랜덤)';
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `scheduled_at` DATETIME NULL COMMENT '실제 발행 실행 예정 시각';
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `schedule_start_at` DATETIME NULL COMMENT '랜덤 방식일 때의 시작 시각';
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `schedule_end_at` DATETIME NULL COMMENT '랜덤 방식일 때의 종료 시각';
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `priority` TINYINT NOT NULL DEFAULT 0 COMMENT '발행 우선순위 (높을수록 먼저)';
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `max_retries` INT UNSIGNED NOT NULL DEFAULT 3 COMMENT '실패 시 최대 재시도 허용 횟수';
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `last_error` TEXT NULL COMMENT '최근 오류 메시지';
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `internal_memo` TEXT NULL COMMENT '관리자 메모';

-- 2. 등록 및 완료/취소 관련 메타데이터 증분
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `created_by` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '최초 예약 등록 관리자 ID';
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `updated_by` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '최종 수정 관리자 ID';
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `cancelled_by` VARCHAR(20) NOT NULL DEFAULT '' COMMENT '취소 처리 관리자 ID';
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `cancelled_at` DATETIME NULL COMMENT '취소 일시';
ALTER TABLE `{prefix}blog_publish_jobs` ADD COLUMN IF NOT EXISTS `completed_at` DATETIME NULL COMMENT '성공 처리 완료 일시';

-- 3. 빠른 조회를 위한 인덱스 증분
-- 주의: DROP INDEX IF EXISTS는 MariaDB 버전에 따라 제약이 있으므로 생략하거나, PHP 스키마 관리 스크립트에서 동적 처리 권장.
-- 여기서는 가장 기본적인 쿼리에 사용되는 scheduled_at 인덱스만 추가 (이미 존재하는 경우 에러가 날 수 있으므로 예외 처리 필요)
-- (해당 부분은 php의 sql_query 에러를 무시하도록 처리하는 방향으로 우회)
-- CREATE INDEX `idx_scheduled_at` ON `{prefix}blog_publish_jobs` (`scheduled_at`);
