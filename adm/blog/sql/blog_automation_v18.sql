-- BLOG AUTOMATION: V18 광고주 이메일 컬럼 추가
-- 새 광고주 등록 폼에 이메일 입력이 추가되면서 저장할 컬럼이 필요해졌다.

ALTER TABLE `{prefix}blog_advertisers`
    ADD COLUMN `email` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '담당자 이메일' AFTER `phone`;
