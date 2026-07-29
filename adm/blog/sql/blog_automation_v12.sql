-- 8단계 (보고서 완성) V12 업데이트
-- {prefix} 치환은 adm/blog/install.php가 수행(v1~v11과 동일 관례). 이전 버전은 {prefix}
-- 없이 테이블명을 하드코딩(g5_sf_blog_*)하고 있어 이 프로젝트의 실제 접두어 규칙과
-- 어긋나 설치·FK 참조가 모두 실패했다 — v1~v11과 동일하게 {prefix}blog_ 형식으로 수정.
-- 콘텐츠 성과 기록 (post_performance) 테이블
CREATE TABLE IF NOT EXISTS `{prefix}blog_post_performance` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `post_target_id` int(11) unsigned NOT NULL COMMENT '발행된 포스트 타겟 ID',
    `view_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '조회수',
    `like_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '공감(좋아요) 수',
    `comment_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '댓글 수',
    `share_count` int(11) unsigned NOT NULL DEFAULT 0 COMMENT '공유 수',
    `last_synced_at` datetime DEFAULT NULL COMMENT '마지막 통계 갱신 시각',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_post_target` (`post_target_id`),
    CONSTRAINT `fk_perf_post_target` FOREIGN KEY (`post_target_id`) REFERENCES `{prefix}blog_post_targets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 보고서 스냅샷 기록 (report_snapshots) 테이블 (향후 정기 메일/생성 이력 용도)
CREATE TABLE IF NOT EXISTS `{prefix}blog_report_snapshots` (
    `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
    `report_type` varchar(50) NOT NULL COMMENT '보고서 유형 (daily, weekly, monthly)',
    `report_date` date NOT NULL COMMENT '보고서 대상 일자 (주/월은 시작일)',
    `advertiser_id` int(11) unsigned DEFAULT NULL COMMENT '특정 광고주 보고서인 경우',
    `snapshot_data` json NOT NULL COMMENT '통계 요약 JSON',
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_report_type_date` (`report_type`, `report_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
