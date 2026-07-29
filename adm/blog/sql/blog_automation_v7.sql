-- BLOG_AUTOMATION_MVP_PHASE2_STEP_3_7 (이미지 생성·보관·재사용 관리)

-- 1. 이미지 메타데이터 보관 테이블
CREATE TABLE IF NOT EXISTS `{prefix}blog_images` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL DEFAULT 0 COMMENT '연관된 프로젝트 ID (0이면 공용)',
  `filename` varchar(255) NOT NULL COMMENT '서버에 저장된 실제 파일명',
  `original_name` varchar(255) NOT NULL COMMENT '업로드 원본 파일명',
  `file_path` varchar(255) NOT NULL COMMENT '저장 상대 경로 (data/blog_images 등)',
  `file_size` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '파일 크기(Byte)',
  `mime_type` varchar(100) NOT NULL DEFAULT '' COMMENT '파일 MIME 타입',
  `is_ai_generated` tinyint(4) NOT NULL DEFAULT 0 COMMENT 'AI 생성 이미지 여부 (1: AI, 0: 일반업로드)',
  `ai_prompt` text NULL COMMENT 'AI 생성에 사용된 프롬프트',
  `created_by` varchar(50) NOT NULL DEFAULT '' COMMENT '업로더/생성자 ID',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='블로그 포스트 이미지 보관함';

-- 2. 포스트-이미지 매핑 테이블 (고아 이미지 판별 및 본문/썸네일 사용 추적용)
CREATE TABLE IF NOT EXISTS `{prefix}blog_post_images` (
  `post_id` bigint(20) unsigned NOT NULL COMMENT 'blog_posts.id',
  `image_id` bigint(20) unsigned NOT NULL COMMENT 'blog_images.id',
  `usage_type` varchar(50) NOT NULL DEFAULT 'body' COMMENT '사용처 (thumbnail, body 등)',
  `display_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`post_id`, `image_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='포스트와 이미지의 연결 정보';
