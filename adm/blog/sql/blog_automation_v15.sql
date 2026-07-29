-- BLOG AUTOMATION: V15 포스팅 통합 제작 폼 상태 저장용 컬럼 추가

ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `builder_state` LONGTEXT NULL COMMENT '통합 폼 블록(JSON) 저장소' AFTER `content`;
