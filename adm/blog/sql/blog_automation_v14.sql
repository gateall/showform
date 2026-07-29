CREATE TABLE IF NOT EXISTS `{prefix}blog_channel_apps` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `channel_code` VARCHAR(30) NOT NULL COMMENT 'oauth_channel_code',
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
