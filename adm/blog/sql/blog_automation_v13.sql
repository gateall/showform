-- 9단계 (광고주 대시보드) V13 업데이트

-- 1. 기존 광고주 테이블(advertisers)에 계약 관련 컬럼 추가
ALTER TABLE `{prefix}blog_advertisers`
ADD COLUMN IF NOT EXISTS `contract_start_date` DATE NULL COMMENT '계약 시작일',
ADD COLUMN IF NOT EXISTS `contract_end_date` DATE NULL COMMENT '계약 종료일',
ADD COLUMN IF NOT EXISTS `contract_status` VARCHAR(20) NOT NULL DEFAULT '운영 중' COMMENT '계약상태(계약 예정, 운영 중, 종료 예정, 일시 중지, 계약 종료)',
ADD COLUMN IF NOT EXISTS `monthly_post_quota` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT '월간 약정 발행 횟수';

-- 2. 광고주 로그인 계정 테이블 신설
CREATE TABLE IF NOT EXISTS `{prefix}blog_advertiser_accounts` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `advertiser_id` INT UNSIGNED NOT NULL COMMENT '연결된 광고주 ID',
  `login_id` VARCHAR(50) NOT NULL COMMENT '로그인 아이디',
  `password_hash` VARCHAR(255) NOT NULL COMMENT '비밀번호 해시',
  `manager_name` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '담당자명',
  `manager_email` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '이메일',
  `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT '계정상태(active, inactive)',
  `last_login_at` DATETIME NULL COMMENT '마지막 로그인 일시',
  `login_fail_count` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '로그인 실패 횟수',
  `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_adv_login_id` (`login_id`),
  KEY `idx_adv_acc_advid` (`advertiser_id`),
  CONSTRAINT `fk_adv_account_adv` FOREIGN KEY (`advertiser_id`) REFERENCES `{prefix}blog_advertisers` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
