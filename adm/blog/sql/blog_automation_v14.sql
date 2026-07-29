-- 10단계 (SNS 및 추가 채널 연동) V14 업데이트
-- {prefix} 치환은 adm/blog/install.php가 수행(v1~v13과 동일 관례).

-- 채널 앱(OAuth 클라이언트) 설정 — 사이트별 개별 인증정보(blog_site_credentials)와는 성격이
-- 다르다: 네이버 블로그 같은 OAuth 채널은 애플리케이션(클라이언트 ID/시크릿)을 서비스 전체에서
-- 한 번만 등록하고, 각 블로그 소유자는 그 앱에 개별적으로 로그인·동의해 자신의 access/refresh
-- 토큰을 발급받는다. 이 테이블은 그 "앱 레벨" 설정만 담고, 사이트별 발급 토큰은 기존
-- blog_site_credentials(cred_type='naver_oauth')에 암호화 저장한다(신규 컬럼 없이 재사용).
CREATE TABLE IF NOT EXISTS `{prefix}blog_channel_apps` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `channel_code` VARCHAR(30) NOT NULL COMMENT '예: naver_blog',
  `display_name` VARCHAR(100) NOT NULL DEFAULT '',
  `client_id` VARCHAR(255) NOT NULL DEFAULT '',
  `client_secret_enc` TEXT NULL,
  `redirect_uri` VARCHAR(255) NOT NULL DEFAULT '',
  `is_active` CHAR(1) NOT NULL DEFAULT 'N',
  `updated_by` VARCHAR(20) NOT NULL DEFAULT '',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_channel_code` (`channel_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
