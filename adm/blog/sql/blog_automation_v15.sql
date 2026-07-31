-- BLOG AUTOMATION: V15 포스팅 통합 제작 폼 상태 저장용 컬럼 추가

-- 원래 `AFTER `content`` 였으나 posts 테이블에 content 컬럼이 없어(실제 컬럼명은 body)
-- 신규 설치 시 항상 실패했다. 저장소 전체에서 builder_state를 컬럼 순서(숫자 인덱스)로
-- 읽는 코드가 없음을 확인했으므로(연관 배열 키로만 접근) AFTER 지정 자체를 제거한다.
ALTER TABLE `{prefix}blog_posts` ADD COLUMN IF NOT EXISTS `builder_state` LONGTEXT NULL COMMENT '통합 폼 블록(JSON) 저장소';
