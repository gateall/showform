-- BLOG AUTOMATION: V23 감사로그 project_id NULL 허용
-- content_activity_logs.project_id가 NOT NULL 외래키였던 탓에, 프로젝트와 무관한
-- 시스템 단위 로그(AI 공급자 API 키 열람/삭제, 전역 스위치 변경, 마스터 키
-- 재암호화 등)가 project_id=0으로 기록을 시도할 때마다 존재하지 않는 프로젝트를
-- 참조하게 되어 외래키 위반으로 조용히 실패하고 있었다(실제 기능 자체는 정상
-- 동작하지만 감사로그만 비어있었다 - sql_query()가 G5_DISPLAY_SQL_ERROR=false라서
-- 예외를 삼키고 null을 반환할 뿐 화면에는 아무 오류도 안 남는다). NULL을 허용해
-- "프로젝트 없음"을 정상적으로 표현한다.

ALTER TABLE `{prefix}blog_content_activity_logs`
    MODIFY COLUMN `project_id` INT UNSIGNED NULL;
