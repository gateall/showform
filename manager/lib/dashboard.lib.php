<?php
if (!defined('_GNUBOARD_')) exit;

// 테이블이 아직 설치되지 않은 상태(사이트 최초 세팅 직후 등)에서도 대시보드가
// 죽지 않도록 하는 방어 헬퍼. lib/common.lib.php의 sql_query($sql, false)는
// 실패해도 die하지 않고 결과 객체 또는 false/null을 그대로 반환한다.
function mgr_table_exists($table_name)
{
    static $cache = array();
    if (isset($cache[$table_name])) {
        return $cache[$table_name];
    }
    $rs = sql_query("SHOW TABLES LIKE '{$table_name}'", false);
    $exists = ($rs && $rs->num_rows > 0);
    $cache[$table_name] = $exists;
    return $exists;
}

function mgr_count($table_name, $where = '')
{
    if (!mgr_table_exists($table_name)) {
        return 0;
    }
    $sql = "SELECT COUNT(*) AS cnt FROM `{$table_name}`";
    if ($where !== '') {
        $sql .= ' WHERE ' . $where;
    }
    $row = sql_fetch($sql);
    return $row ? (int) $row['cnt'] : 0;
}

// 요약 카드 8개 값을 한 번에 반환한다.
function mgr_dashboard_stats()
{
    $sf_table = G5_TABLE_PREFIX . 'sf_portfolio';
    $landing_table = G5_TABLE_PREFIX . 'landing_pages';
    $inquiry_table = G5_TABLE_PREFIX . 'landing_inquiries';
    $project_table = G5_TABLE_PREFIX . 'blog_content_projects';
    $target_table = G5_TABLE_PREFIX . 'blog_post_targets';

    return array(
        'showform_total' => mgr_count($sf_table),
        'showform_public' => mgr_count($sf_table, "is_display = 'Y'"),
        'landing_total' => mgr_count($landing_table),
        'inquiry_total' => mgr_count($inquiry_table),
        'blog_project_total' => mgr_count($project_table),
        'publish_pending' => mgr_count($target_table, "publish_status IN ('draft','publish_pending')"),
        'publish_done' => mgr_count($target_table, "publish_status = 'published'"),
        'publish_failed' => mgr_count($target_table, "publish_status = 'failed'"),
    );
}

// 최근 문의 5건 (adm/landing/inquiry_list.php와 동일한 테이블/컬럼을 그대로 재사용)
function mgr_recent_inquiries($limit = 5)
{
    $inq_table = G5_TABLE_PREFIX . 'landing_inquiries';
    $page_table = G5_TABLE_PREFIX . 'landing_pages';
    if (!mgr_table_exists($inq_table)) {
        return array();
    }
    $limit = (int) $limit;
    $sql = "SELECT a.id, a.name, a.phone, a.status, a.created_at, a.landing_id, p.company_name
            FROM {$inq_table} a
            LEFT JOIN {$page_table} p ON p.id = a.landing_id
            ORDER BY a.id DESC LIMIT {$limit}";
    $result = sql_query($sql, false);
    $rows = array();
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// 최근 포스팅 작업 5건
function mgr_recent_posts($limit = 5)
{
    $target_table = G5_TABLE_PREFIX . 'blog_post_targets';
    $post_table = G5_TABLE_PREFIX . 'blog_posts';
    $project_table = G5_TABLE_PREFIX . 'blog_content_projects';
    $advertiser_table = G5_TABLE_PREFIX . 'blog_advertisers';
    if (!mgr_table_exists($target_table)) {
        return array();
    }
    $limit = (int) $limit;
    $sql = "SELECT t.id, t.title, t.publish_status, t.scheduled_at, t.updated_at, t.post_id,
                   po.project_id, po.title AS post_title, adv.name AS advertiser_name
            FROM {$target_table} t
            LEFT JOIN {$post_table} po ON po.id = t.post_id
            LEFT JOIN {$project_table} pr ON pr.id = po.project_id
            LEFT JOIN {$advertiser_table} adv ON adv.id = pr.advertiser_id
            ORDER BY t.updated_at DESC LIMIT {$limit}";
    $result = sql_query($sql, false);
    $rows = array();
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// 최근 발행 실패 5건
function mgr_recent_publish_failures($limit = 5)
{
    $target_table = G5_TABLE_PREFIX . 'blog_post_targets';
    $post_table = G5_TABLE_PREFIX . 'blog_posts';
    $site_table = G5_TABLE_PREFIX . 'blog_sites';
    if (!mgr_table_exists($target_table)) {
        return array();
    }
    $limit = (int) $limit;
    $sql = "SELECT t.id, t.title, t.last_error, t.updated_at, t.post_id, t.site_id,
                   po.title AS post_title, po.project_id, s.name AS site_name
            FROM {$target_table} t
            LEFT JOIN {$post_table} po ON po.id = t.post_id
            LEFT JOIN {$site_table} s ON s.id = t.site_id
            WHERE t.publish_status = 'failed'
            ORDER BY t.updated_at DESC LIMIT {$limit}";
    $result = sql_query($sql, false);
    $rows = array();
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}
