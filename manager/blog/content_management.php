<?php
$sub_menu = '360100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');
require_once __DIR__ . '/../components/data_table.php';
require_once __DIR__ . '/../components/pagination.php';
require_once __DIR__ . '/../components/status_badge.php';
require_once __DIR__ . '/../components/stat_card.php';

$page_title = '광고주·포스팅 관리';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'advertisers';

$tabs = array(
    'advertisers' => '광고주',
    'posts' => '포스팅',
    'keywords' => '키워드',
    'generation_rules' => '생성조건',
    'publishing' => '발행 관리',
    'reports' => '보고서'
);

if (!isset($tabs[$tab])) {
    $tab = 'advertisers';
}

// ---- 광고주 탭: manager/blog/advertiser_list.php의 조회 로직(검색/상태필터/페이징)을 그대로
// 재사용한다 - 그 파일은 이제 이 화면(?tab=advertisers)으로 리다이렉트만 하는 상태였다.
if ($tab === 'advertisers') {
    $adv_search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $adv_status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $adv_rows_per_page = 20;
    $adv_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($adv_page < 1) $adv_page = 1;

    $adv_table = bp_table('advertisers');
    $adv_where = array('1=1');
    if ($adv_search !== '') {
        $adv_safe_search = sql_real_escape_string($adv_search);
        $adv_where[] = "(name like '%{$adv_safe_search}%' or domain like '%{$adv_safe_search}%')";
    }
    if ($adv_status === 'Y' || $adv_status === 'N') {
        $adv_where[] = "status = '{$adv_status}'";
    }
    $adv_where_sql = ' where ' . implode(' and ', $adv_where);

    $adv_total_row = sql_fetch("select count(*) as cnt from {$adv_table} {$adv_where_sql}");
    $adv_total_count = isset($adv_total_row['cnt']) ? (int) $adv_total_row['cnt'] : 0;
    $adv_total_pages = $adv_rows_per_page > 0 ? (int) ceil($adv_total_count / $adv_rows_per_page) : 1;
    if ($adv_total_pages < 1) $adv_total_pages = 1;
    if ($adv_page > $adv_total_pages) $adv_page = $adv_total_pages;
    $adv_from = ($adv_page - 1) * $adv_rows_per_page;

    $adv_result = sql_query("select * from {$adv_table} {$adv_where_sql} order by id desc limit {$adv_from}, {$adv_rows_per_page}");
    $adv_list = array();
    while ($adv_row = sql_fetch_array($adv_result)) {
        $adv_list[] = $adv_row;
    }
}

// ---- 포스팅 탭: manager/blog/project_list.php의 조회 로직(제목/광고주/사이트 검색, 상태필터,
// AI제공자 필터, 발행상태 GROUP_CONCAT)을 그대로 재사용한다 - 그 파일도 이제 이 화면
// (?tab=posts)으로 리다이렉트만 하는 상태였다. 페이징만 새로 추가(원본은 flat limit 100).
if ($tab === 'posts') {
    $proj_projects_table = bp_table('content_projects');
    $proj_adv_table = bp_table('advertisers');
    $proj_sites_table = bp_table('sites');
    $proj_posts_table = bp_table('posts');
    $proj_targets_table = bp_table('post_targets');
    $proj_jobs_table = bp_table('publish_jobs');
    $proj_gen_logs_table = bp_table('content_generation_logs');

    $proj_status_label = array(
        'draft' => '초안', 'pending_approval' => '승인대기', 'approved' => '승인됨',
        'publish_pending' => '발행대기', 'publishing' => '발행중', 'published' => '발행완료', 'failed' => '발행실패',
    );
    $proj_job_status_label = array(
        'pending' => '대기', 'claimed' => '할당됨', 'processing' => '처리중', 'published' => '완료', 'failed' => '실패',
    );
    $proj_valid_status = array_keys($proj_status_label);
    $proj_valid_job_status = array_keys($proj_job_status_label);

    $proj_stx_title = isset($_GET['stx_title']) ? trim($_GET['stx_title']) : '';
    $proj_stx_advertiser = isset($_GET['stx_advertiser']) ? trim($_GET['stx_advertiser']) : '';
    $proj_status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $proj_job_status = isset($_GET['job_status']) ? trim($_GET['job_status']) : '';
    $proj_rows_per_page = 20;
    $proj_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($proj_page < 1) $proj_page = 1;

    $proj_where = array('p.deleted_at is null');
    if ($proj_stx_title !== '') {
        $proj_safe_title = sql_real_escape_string($proj_stx_title);
        $proj_where[] = "(po.title like '%{$proj_safe_title}%' or p.topic like '%{$proj_safe_title}%')";
    }
    if ($proj_stx_advertiser !== '') {
        $proj_where[] = "a.name like '%" . sql_real_escape_string($proj_stx_advertiser) . "%'";
    }
    if (in_array($proj_status, $proj_valid_status, true)) {
        $proj_where[] = "p.status = '" . sql_real_escape_string($proj_status) . "'";
    }
    $proj_where_sql = ' where ' . implode(' and ', $proj_where);

    $proj_having_sql = '';
    if (in_array($proj_job_status, $proj_valid_job_status, true)) {
        $proj_having_sql = " having find_in_set('" . sql_real_escape_string($proj_job_status) . "', job_statuses) > 0 ";
    }

    // COUNT는 페이징 계산용 - LEFT JOIN posts로 인한 행 중복을 피하려 DISTINCT p.id를 센다.
    // HAVING(발행상태 필터)이 걸리면 GROUP_CONCAT 결과 기준으로 걸러야 하므로 서브쿼리로 감싼다.
    $proj_count_sql = "select count(*) as cnt from (
            select p.id,
                (select group_concat(distinct pj.status) from {$proj_targets_table} pt2
                    join {$proj_jobs_table} pj on pj.post_target_id = pt2.id where pt2.post_id = po.id) as job_statuses
            from {$proj_projects_table} p
            left join {$proj_adv_table} a on a.id = p.advertiser_id
            left join {$proj_posts_table} po on po.project_id = p.id
            {$proj_where_sql}
            group by p.id
            {$proj_having_sql}
        ) t";
    $proj_total_row = sql_fetch($proj_count_sql);
    $proj_total_count = isset($proj_total_row['cnt']) ? (int) $proj_total_row['cnt'] : 0;
    $proj_total_pages = $proj_rows_per_page > 0 ? (int) ceil($proj_total_count / $proj_rows_per_page) : 1;
    if ($proj_total_pages < 1) $proj_total_pages = 1;
    if ($proj_page > $proj_total_pages) $proj_page = $proj_total_pages;
    $proj_from = ($proj_page - 1) * $proj_rows_per_page;

    $proj_result = sql_query(" select p.*, a.name as advertiser_name, s.name as site_name, po.id as post_id, po.title as post_title,
                          (select count(*) from {$proj_targets_table} pt where pt.post_id = po.id) as target_count,
                          (select group_concat(distinct pj.status) from {$proj_targets_table} pt2
                            join {$proj_jobs_table} pj on pj.post_target_id = pt2.id where pt2.post_id = po.id) as job_statuses,
                          (select min(pj3.scheduled_at) from {$proj_targets_table} pt3
                            join {$proj_jobs_table} pj3 on pj3.post_target_id = pt3.id
                            where pt3.post_id = po.id and pj3.status = 'pending' and pj3.scheduled_at is not null) as next_scheduled_at
                          from {$proj_projects_table} p
                          left join {$proj_adv_table} a on a.id = p.advertiser_id
                          left join {$proj_sites_table} s on s.id = p.primary_site_id
                          left join {$proj_posts_table} po on po.project_id = p.id
                          {$proj_where_sql}
                          {$proj_having_sql}
                          order by p.id desc limit {$proj_from}, {$proj_rows_per_page} ");
    $proj_list = array();
    while ($proj_row = sql_fetch_array($proj_result)) {
        $proj_list[] = $proj_row;
    }
}

// ---- 키워드 탭: manager/blog/keyword_list.php의 조회 로직(프로젝트/상태/키워드 검색)을
// 기반으로 하되, 지시대로 검색 대상을 키워드명/광고주명/프로젝트명 3종 텍스트 검색으로 바꿨다.
// content_keywords 테이블에 "유형"/"우선순위" 컬럼은 존재하지 않는다(SHOW COLUMNS로 확인) -
// keyword_group은 코드 전체에서 'primary' 값만 쓰여 사실상 구분 의미가 없어 정보성으로만 표시하고,
// "우선순위"는 대응 컬럼이 아예 없어 실제 존재하는 is_locked(잠금 상태)로 대체했다.
if ($tab === 'keywords') {
    $kw_table = bp_table('content_keywords');
    $kw_projects_table = bp_table('content_projects');
    $kw_adv_table = bp_table('advertisers');

    $kw_stx_keyword = isset($_GET['stx_keyword']) ? trim($_GET['stx_keyword']) : '';
    $kw_stx_advertiser = isset($_GET['stx_advertiser']) ? trim($_GET['stx_advertiser']) : '';
    $kw_stx_project = isset($_GET['stx_project']) ? trim($_GET['stx_project']) : '';
    $kw_status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $kw_locked = isset($_GET['locked']) ? trim($_GET['locked']) : '';
    $kw_rows_per_page = 20;
    $kw_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($kw_page < 1) $kw_page = 1;

    $kw_where = array('1=1');
    if ($kw_stx_keyword !== '') {
        $kw_where[] = "k.keyword like '%" . sql_real_escape_string($kw_stx_keyword) . "%'";
    }
    if ($kw_stx_advertiser !== '') {
        $kw_where[] = "a.name like '%" . sql_real_escape_string($kw_stx_advertiser) . "%'";
    }
    if ($kw_stx_project !== '') {
        $kw_where[] = "p.topic like '%" . sql_real_escape_string($kw_stx_project) . "%'";
    }
    if ($kw_status === 'Y' || $kw_status === 'N') {
        $kw_where[] = "k.status = '{$kw_status}'";
    }
    if ($kw_locked === 'Y' || $kw_locked === 'N') {
        $kw_where[] = "k.is_locked = '{$kw_locked}'";
    }
    $kw_where_sql = ' where ' . implode(' and ', $kw_where);

    $kw_count_sql = "select count(*) as cnt from {$kw_table} k
        left join {$kw_projects_table} p on p.id = k.project_id
        left join {$kw_adv_table} a on a.id = p.advertiser_id
        {$kw_where_sql}";
    $kw_total_row = sql_fetch($kw_count_sql);
    $kw_total_count = isset($kw_total_row['cnt']) ? (int) $kw_total_row['cnt'] : 0;
    $kw_total_pages = $kw_rows_per_page > 0 ? (int) ceil($kw_total_count / $kw_rows_per_page) : 1;
    if ($kw_total_pages < 1) $kw_total_pages = 1;
    if ($kw_page > $kw_total_pages) $kw_page = $kw_total_pages;
    $kw_from = ($kw_page - 1) * $kw_rows_per_page;

    $kw_result = sql_query("select k.*, p.topic as project_topic, a.name as advertiser_name
        from {$kw_table} k
        left join {$kw_projects_table} p on p.id = k.project_id
        left join {$kw_adv_table} a on a.id = p.advertiser_id
        {$kw_where_sql}
        order by k.id desc
        limit {$kw_from}, {$kw_rows_per_page}");
    $kw_list = array();
    while ($kw_row = sql_fetch_array($kw_result)) {
        $kw_list[] = $kw_row;
    }
}

// ---- 생성조건 탭: post_builder.php 1단계에서 체크박스로 노출되는 AI 글 생성 조건
// (blog_generation_rules) 프리셋을 관리한다. 신규 화면 - 다른 탭처럼 옛 화면을 흡수한 게
// 아니라 처음부터 이 탭으로 만들었다.
if ($tab === 'generation_rules') {
    $gr_table = bp_table('generation_rules');
    $gr_type_labels = array(
        'writing_rule' => '작성 조건', 'technical_rule' => '기술 조건',
        'seo_rule' => 'SEO 조건', 'prohibited_rule' => '금지 조건', 'quality_rule' => '품질 조건',
    );
    $gr_result = sql_query("select * from {$gr_table} order by rule_type, sort_order, id");
    $gr_list = array();
    while ($gr_row = sql_fetch_array($gr_result)) {
        $gr_list[] = $gr_row;
    }
}

// ---- 발행 관리 탭: manager/blog/publish_job_list.php의 조회 로직(상태/사이트/기간/제목검색)을
// 기반으로 재사용한다 - 그 파일도 이제 이 화면(?tab=publishing)으로 리다이렉트만 하는 상태였다.
// 광고주 join과 완료시각(completed_at)은 원본 쿼리에 없어서 추가했다(둘 다 실제 컬럼).
// 상태값은 원본의 select box(scheduled/pending/processing/succeeded/failed/cancelled)를 그대로
// 안 쓰고 adm/blog/publish_job_update.php·blog_scheduler.lib.php의 실제 상태 전이를 확인해
// 'succeeded'를 실제 값인 'published'로 정정했다.
if ($tab === 'publishing') {
    $pj_jobs_table = bp_table('publish_jobs');
    $pj_targets_table = bp_table('post_targets');
    $pj_posts_table = bp_table('posts');
    $pj_sites_table = bp_table('sites');
    $pj_projects_table = bp_table('content_projects');
    $pj_adv_table = bp_table('advertisers');

    $pj_status_label = array(
        'scheduled' => '예약됨', 'pending' => '대기중', 'processing' => '처리중',
        'published' => '완료', 'failed' => '실패', 'cancelled' => '취소',
    );

    $pj_stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
    $pj_status = isset($_GET['sfl_status']) ? trim($_GET['sfl_status']) : '';
    $pj_sdate = isset($_GET['sdate']) ? trim($_GET['sdate']) : '';
    $pj_edate = isset($_GET['edate']) ? trim($_GET['edate']) : '';
    $pj_rows_per_page = 20;
    $pj_page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($pj_page < 1) $pj_page = 1;

    $pj_where = array('1=1');
    if ($pj_stx !== '') {
        $pj_where[] = "p.title like '%" . sql_real_escape_string($pj_stx) . "%'";
    }
    if (isset($pj_status_label[$pj_status])) {
        $pj_where[] = "j.status = '" . sql_real_escape_string($pj_status) . "'";
    }
    if ($pj_sdate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $pj_sdate) && $pj_edate !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $pj_edate)) {
        $pj_where[] = "j.scheduled_at between '" . sql_real_escape_string($pj_sdate) . " 00:00:00' and '" . sql_real_escape_string($pj_edate) . " 23:59:59'";
    }
    $pj_where_sql = ' where ' . implode(' and ', $pj_where);

    $pj_common_sql = " from {$pj_jobs_table} j
        join {$pj_targets_table} t on j.post_target_id = t.id
        join {$pj_posts_table} p on t.post_id = p.id
        join {$pj_sites_table} s on t.site_id = s.id
        join {$pj_projects_table} prj on p.project_id = prj.id
        left join {$pj_adv_table} adv on adv.id = prj.advertiser_id
        {$pj_where_sql} ";

    $pj_total_row = sql_fetch("select count(*) as cnt {$pj_common_sql}");
    $pj_total_count = isset($pj_total_row['cnt']) ? (int) $pj_total_row['cnt'] : 0;
    $pj_total_pages = $pj_rows_per_page > 0 ? (int) ceil($pj_total_count / $pj_rows_per_page) : 1;
    if ($pj_total_pages < 1) $pj_total_pages = 1;
    if ($pj_page > $pj_total_pages) $pj_page = $pj_total_pages;
    $pj_from = ($pj_page - 1) * $pj_rows_per_page;

    $pj_result = sql_query(" select j.*, p.title as post_title, s.name as site_name, prj.topic as project_topic,
            adv.name as advertiser_name
        {$pj_common_sql}
        order by j.id desc
        limit {$pj_from}, {$pj_rows_per_page} ");
    $pj_list = array();
    while ($pj_row = sql_fetch_array($pj_result)) {
        $pj_list[] = $pj_row;
    }
}

// ---- 보고서 탭: manager/blog/report_dashboard.php의 집계 로직(bp_report_* 헬퍼)을 그대로
// 재사용한다 - 그 파일도 이제 이 화면(?tab=reports)으로 리다이렉트만 하는 상태였다.
// 하위 상세 보고서 6개(report_daily/weekly/monthly_adv/stats/site/performance.php)는
// manager/blog/에 이미 실제로 존재해서(리다이렉트 아님) 그쪽으로 링크만 건다.
if ($tab === 'reports') {
    $rpt_today = bp_report_period_range('today');
    $rpt_week = bp_report_period_range('this_week');
    $rpt_month = bp_report_period_range('this_month');

    $rpt_today_attempts = bp_report_attempt_counts($rpt_today['from'], $rpt_today['to']);
    $rpt_week_jobs = bp_report_job_counts($rpt_week['from'], $rpt_week['to']);
    $rpt_month_attempts = bp_report_attempt_counts($rpt_month['from'], $rpt_month['to']);

    $rpt_adv_table = bp_table('advertisers');
    $rpt_sites_table = bp_table('sites');
    $rpt_active_advertisers = sql_fetch("select count(*) as cnt from {$rpt_adv_table} where status = 'Y'");
    $rpt_active_sites = sql_fetch("select count(*) as cnt from {$rpt_sites_table} where status = 'Y'");

    $rpt_jobs_table = bp_table('publish_jobs');
    $rpt_targets_table = bp_table('post_targets');
    $rpt_posts_table = bp_table('posts');
    $rpt_projects_table = bp_table('content_projects');

    $rpt_recent_failures = sql_query(" select j.id, j.completed_at, j.last_error, po.title, prj.topic
        from {$rpt_jobs_table} j
        inner join {$rpt_targets_table} t on t.id = j.post_target_id
        inner join {$rpt_posts_table} po on po.id = t.post_id
        inner join {$rpt_projects_table} prj on prj.id = po.project_id
        where j.status = 'failed'
        order by j.completed_at desc limit 10 ");

    $rpt_stale_pending = sql_query(" select j.id, j.status, j.created_at, po.title
        from {$rpt_jobs_table} j
        inner join {$rpt_targets_table} t on t.id = j.post_target_id
        inner join {$rpt_posts_table} po on po.id = t.post_id
        where j.status in ('pending','claimed','processing')
          and j.created_at < date_sub(now(), interval 24 hour)
        order by j.created_at asc limit 10 ");

    $rpt_maxed_out = sql_query(" select j.id, j.attempt_count, j.max_retries, po.title
        from {$rpt_jobs_table} j
        inner join {$rpt_targets_table} t on t.id = j.post_target_id
        inner join {$rpt_posts_table} po on po.id = t.post_id
        where j.status = 'failed' and j.attempt_count >= j.max_retries
        order by j.completed_at desc limit 10 ");

    $rpt_missing_url = sql_query(" select j.id, j.completed_at, po.title
        from {$rpt_jobs_table} j
        inner join {$rpt_targets_table} t on t.id = j.post_target_id
        inner join {$rpt_posts_table} po on po.id = t.post_id
        where j.status = 'published' and (t.published_url is null or t.published_url = '')
        order by j.completed_at desc limit 10 ");

    bp_log_activity(0, 'report_viewed', bp_current_admin_id(), 'content_management_tab');
}

include_once(__DIR__ . '/../layout/header.php');
?>
<div class="mgr-card" style="padding:1.25rem;margin-bottom:1.5rem;">
    <h2 style="margin:0 0 1rem;font-size:1.25rem;"><?php echo $page_title; ?></h2>
    
    <!-- 탭 UI -->
    <div style="border-bottom:1px solid var(--mgr-border);margin-bottom:1.5rem;display:flex;gap:1rem;">
        <?php foreach ($tabs as $k => $v): ?>
            <a href="?tab=<?php echo $k; ?>" style="padding:0.5rem 1rem;text-decoration:none;color:<?php echo $tab === $k ? 'var(--mgr-primary)' : 'var(--mgr-text-muted)'; ?>;border-bottom:2px solid <?php echo $tab === $k ? 'var(--mgr-primary)' : 'transparent'; ?>;font-weight:<?php echo $tab === $k ? 'bold' : 'normal'; ?>;">
                <?php echo $v; ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <?php if ($tab === 'advertisers'): ?>
    <form method="get" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;margin-bottom:1rem;">
        <input type="hidden" name="tab" value="advertisers">
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">상태</label>
            <select name="status" class="mgr-input">
                <option value="">전체</option>
                <option value="Y" <?php echo $adv_status === 'Y' ? 'selected' : ''; ?>>사용</option>
                <option value="N" <?php echo $adv_status === 'N' ? 'selected' : ''; ?>>중지</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">검색어</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($adv_search, ENT_QUOTES, 'UTF-8'); ?>" class="mgr-input" placeholder="상호 또는 도메인">
        </div>
        <button type="submit" class="mgr-btn mgr-btn-primary">검색</button>
        <a href="?tab=advertisers" class="mgr-btn">초기화</a>
        <a href="<?php echo SF_MANAGER_URL; ?>/blog/advertiser_form.php" class="mgr-btn mgr-btn-primary" style="margin-left:auto;">+ 광고주 등록</a>
    </form>

    <?php
    // get_admin_token()은 호출할 때마다 세션 값을 새로 덮어쓴다 - 행마다 반복 호출하면
    // 마지막 행 말고는 전부 페이지에 찍힌 토큰과 세션 값이 어긋나 삭제 시 "올바른 방법으로
    // 이용해 주십시오" 오류가 난다. 루프 밖에서 한 번만 호출해 모든 행이 재사용해야 한다.
    $adv_admin_token = get_admin_token();
    $adv_table_rows = array();
    foreach ($adv_list as $row) {
        $adv_table_rows[] = array(
            'name' => '<a href="' . SF_MANAGER_URL . '/blog/advertiser_form.php?id=' . (int) $row['id'] . '">' . htmlspecialchars($row['name']) . '</a>',
            'phone' => htmlspecialchars($row['phone']),
            'region' => htmlspecialchars($row['service_region']),
            'contract_status' => htmlspecialchars($row['contract_status']),
            'quota' => number_format((int) $row['monthly_post_quota']) . '건',
            'status' => $row['status'] === 'Y' ? '사용' : '중지',
            'created_at' => htmlspecialchars($row['created_at']),
            'manage' => '<a href="' . SF_MANAGER_URL . '/blog/advertiser_form.php?id=' . (int) $row['id'] . '" class="mgr-btn">수정</a> '
                . '<a href="' . G5_ADMIN_URL . '/blog/advertiser_delete.php?id=' . (int) $row['id'] . '&token=' . $adv_admin_token . '" class="mgr-btn" style="color:var(--mgr-danger);" onclick="return confirm(\'정말 삭제하시겠습니까?\');">삭제</a>',
        );
    }
    echo mgr_data_table(
        array(
            array('key' => 'name', 'label' => '상호'),
            array('key' => 'phone', 'label' => '전화'),
            array('key' => 'region', 'label' => '서비스 지역'),
            array('key' => 'contract_status', 'label' => '계약 상태'),
            array('key' => 'quota', 'label' => '월간 계약 수량'),
            array('key' => 'status', 'label' => '상태'),
            array('key' => 'created_at', 'label' => '등록일'),
            array('key' => 'manage', 'label' => '관리'),
        ),
        $adv_table_rows,
        array('empty_title' => '등록된 광고주가 없습니다')
    );

    $adv_qs = array('tab' => 'advertisers', 'search' => $adv_search, 'status' => $adv_status);
    echo mgr_pagination($adv_page, $adv_total_pages, '?' . http_build_query($adv_qs) . '&page=');
    ?>
    <?php elseif ($tab === 'posts'): ?>
    <form method="get" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;margin-bottom:1rem;">
        <input type="hidden" name="tab" value="posts">
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">제목 검색</label>
            <input type="text" name="stx_title" value="<?php echo htmlspecialchars($proj_stx_title, ENT_QUOTES, 'UTF-8'); ?>" class="mgr-input">
        </div>
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">광고주</label>
            <input type="text" name="stx_advertiser" value="<?php echo htmlspecialchars($proj_stx_advertiser, ENT_QUOTES, 'UTF-8'); ?>" class="mgr-input">
        </div>
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">작성 상태</label>
            <select name="status" class="mgr-input">
                <option value="">전체</option>
                <?php foreach ($proj_status_label as $code => $label): ?>
                <option value="<?php echo $code; ?>" <?php echo $proj_status === $code ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">발행 상태</label>
            <select name="job_status" class="mgr-input">
                <option value="">전체</option>
                <?php foreach ($proj_job_status_label as $code => $label): ?>
                <option value="<?php echo $code; ?>" <?php echo $proj_job_status === $code ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="mgr-btn mgr-btn-primary">검색</button>
        <a href="?tab=posts" class="mgr-btn">초기화</a>
        <a href="<?php echo SF_MANAGER_URL; ?>/blog/post_builder.php" class="mgr-btn mgr-btn-primary" style="margin-left:auto;">+ 새 포스팅 작성</a>
    </form>

    <?php
    // 광고주/키워드 탭과 같은 이유로 루프 밖에서 한 번만 호출한다(get_admin_token() 반복 호출 방지).
    $proj_admin_token = get_admin_token();
    $proj_table_rows = array();
    foreach ($proj_list as $row) {
        $st = $row['status'];
        $st_label = isset($proj_status_label[$st]) ? $proj_status_label[$st] : htmlspecialchars($st);
        $job_summary = '-';
        if (!empty($row['job_statuses'])) {
            $parts = array();
            foreach (explode(',', $row['job_statuses']) as $js) {
                $parts[] = isset($proj_job_status_label[$js]) ? $proj_job_status_label[$js] : htmlspecialchars($js);
            }
            $job_summary = implode(', ', $parts);
        }
        // 삭제는 project_action.php가 초안 상태 + 발행 이력 없음일 때만 허용한다(같은 규칙을
        // 여기서 미리 판단하지 않고, 그 파일 자신의 검사에 맡긴다 - 다른 탭의 삭제 링크와
        // 동일하게 "조건에 안 맞으면 목적지가 알림으로 알려준다" 패턴을 따름).
        // 개별 삭제를 <form>으로 감싸면 아래 "선택 삭제" 폼 안에 폼이 중첩되어(잘못된 HTML,
        // 브라우저마다 동작이 달라짐) - 이 파일의 발행관리 탭이 이미 쓰는 JS 임시폼 생성
        // 패턴(mgrPublishJobAction)과 동일한 방식으로 처리한다.
        $manage = '<a href="' . SF_MANAGER_URL . '/blog/project_form.php?id=' . (int) $row['id'] . '" class="mgr-btn">수정</a> '
            . '<a href="' . SF_MANAGER_URL . '/blog/project_view.php?id=' . (int) $row['id'] . '" class="mgr-btn">열기</a> '
            . '<button type="button" class="mgr-btn" style="color:var(--mgr-danger);" onclick="mgrDeletePost(' . (int) $row['id'] . ')">삭제</button>';

        $proj_table_rows[] = array(
            'chk' => '<input type="checkbox" class="mgr-row-check" name="ids[]" value="' . (int) $row['id'] . '">',
            'title' => '<a href="' . SF_MANAGER_URL . '/blog/project_view.php?id=' . (int) $row['id'] . '">'
                . htmlspecialchars($row['post_title'] ? $row['post_title'] : $row['topic']) . '</a>',
            'advertiser' => htmlspecialchars($row['advertiser_name'] ? $row['advertiser_name'] : '-'),
            'site' => htmlspecialchars($row['site_name'] ? $row['site_name'] : '-'),
            'status' => mgr_status_badge($st_label, $st === 'published' ? 'success' : ($st === 'failed' ? 'danger' : ($st === 'pending_approval' ? 'warning' : 'muted'))),
            'job_status' => $job_summary,
            'scheduled_at' => $row['next_scheduled_at'] ? htmlspecialchars($row['next_scheduled_at']) : '-',
            'updated_at' => $row['updated_at'] ? htmlspecialchars($row['updated_at']) : htmlspecialchars($row['created_at']),
            'manage' => $manage,
        );
    }
    ?>
    <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_bulk_delete.php" onsubmit="return mgrConfirmPostBulkDelete();">
        <input type="hidden" name="token" value="<?php echo $proj_admin_token; ?>">
        <div style="margin-bottom:.5rem;">
            <button type="submit" class="mgr-btn" style="color:var(--mgr-danger);">선택 삭제</button>
        </div>
        <?php
        echo mgr_data_table(
            array(
                array('key' => 'chk', 'label' => '<input type="checkbox" id="mgr_post_check_all" onclick="mgrToggleAllPostChecks(this)">', 'raw' => true),
                array('key' => 'title', 'label' => '프로젝트/포스팅'),
                array('key' => 'advertiser', 'label' => '광고주'),
                array('key' => 'site', 'label' => '대상 사이트'),
                array('key' => 'status', 'label' => '작성 상태'),
                array('key' => 'job_status', 'label' => '발행 상태'),
                array('key' => 'scheduled_at', 'label' => '예약 일시'),
                array('key' => 'updated_at', 'label' => '최근 수정일'),
                array('key' => 'manage', 'label' => '관리'),
            ),
            $proj_table_rows,
            array('empty_title' => '조건에 맞는 포스팅 프로젝트가 없습니다')
        );
        ?>
    </form>
    <script>
    function mgrToggleAllPostChecks(cb) {
        document.querySelectorAll('.mgr-row-check').forEach(function(el) { el.checked = cb.checked; });
    }
    function mgrConfirmPostBulkDelete() {
        var checked = document.querySelectorAll('.mgr-row-check:checked');
        if (checked.length === 0) {
            alert('삭제할 항목을 선택해 주세요.');
            return false;
        }
        return confirm(checked.length + '건을 삭제하시겠습니까? 초안 상태이면서 발행 이력이 없는 항목만 실제로 삭제되고, 나머지는 건너뜁니다.');
    }
    // 개별 삭제 - 테이블을 감싼 "선택 삭제" <form> 안에 또 다른 <form>을 중첩할 수 없어서
    // (유효하지 않은 HTML) 발행관리 탭의 mgrPublishJobAction()과 동일하게 임시 폼을 만들어 제출한다.
    function mgrDeletePost(id) {
        if (!confirm('이 콘텐츠 프로젝트를 삭제하시겠습니까?')) return;
        var form = document.createElement('form');
        form.method = 'post';
        form.action = '<?php echo G5_ADMIN_URL; ?>/blog/project_action.php';
        var fields = { id: id, mode: 'delete', token: '<?php echo $proj_admin_token; ?>' };
        for (var key in fields) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
    }
    </script>
    <?php

    $proj_qs = array('tab' => 'posts', 'stx_title' => $proj_stx_title, 'stx_advertiser' => $proj_stx_advertiser, 'status' => $proj_status, 'job_status' => $proj_job_status);
    echo mgr_pagination($proj_page, $proj_total_pages, '?' . http_build_query($proj_qs) . '&page=');
    ?>
    <?php elseif ($tab === 'keywords'): ?>
    <form method="get" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;margin-bottom:1rem;">
        <input type="hidden" name="tab" value="keywords">
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">키워드</label>
            <input type="text" name="stx_keyword" value="<?php echo htmlspecialchars($kw_stx_keyword, ENT_QUOTES, 'UTF-8'); ?>" class="mgr-input">
        </div>
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">광고주</label>
            <input type="text" name="stx_advertiser" value="<?php echo htmlspecialchars($kw_stx_advertiser, ENT_QUOTES, 'UTF-8'); ?>" class="mgr-input">
        </div>
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">프로젝트명</label>
            <input type="text" name="stx_project" value="<?php echo htmlspecialchars($kw_stx_project, ENT_QUOTES, 'UTF-8'); ?>" class="mgr-input">
        </div>
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">사용 상태</label>
            <select name="status" class="mgr-input">
                <option value="">전체</option>
                <option value="Y" <?php echo $kw_status === 'Y' ? 'selected' : ''; ?>>활성</option>
                <option value="N" <?php echo $kw_status === 'N' ? 'selected' : ''; ?>>비활성</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">잠금 상태</label>
            <select name="locked" class="mgr-input">
                <option value="">전체</option>
                <option value="Y" <?php echo $kw_locked === 'Y' ? 'selected' : ''; ?>>잠김</option>
                <option value="N" <?php echo $kw_locked === 'N' ? 'selected' : ''; ?>>일반</option>
            </select>
        </div>
        <button type="submit" class="mgr-btn mgr-btn-primary">검색</button>
        <a href="?tab=keywords" class="mgr-btn">초기화</a>
        <a href="<?php echo G5_ADMIN_URL; ?>/blog/keyword_form.php" class="mgr-btn mgr-btn-primary" style="margin-left:auto;">+ 키워드 신규 등록</a>
    </form>

    <?php
    // 광고주 탭과 같은 이유로 루프 밖에서 한 번만 호출한다(get_admin_token() 반복 호출 방지).
    $kw_admin_token = get_admin_token();
    $kw_table_rows = array();
    foreach ($kw_list as $row) {
        $kw_table_rows[] = array(
            'keyword' => '<strong>' . htmlspecialchars($row['keyword']) . '</strong>',
            'advertiser' => htmlspecialchars($row['advertiser_name'] ? $row['advertiser_name'] : '-'),
            'project' => htmlspecialchars($row['project_topic'] ? $row['project_topic'] : '-'),
            'group' => htmlspecialchars($row['keyword_group']),
            'locked' => $row['is_locked'] === 'Y' ? '잠김' : '일반',
            'status' => mgr_status_badge($row['status'] === 'Y' ? '활성' : '비활성', $row['status'] === 'Y' ? 'success' : 'muted'),
            'created_at' => htmlspecialchars($row['created_at']),
            'manage' => '<a href="' . G5_ADMIN_URL . '/blog/keyword_form.php?w=u&id=' . (int) $row['id'] . '" class="mgr-btn">수정</a> '
                . '<a href="' . G5_ADMIN_URL . '/blog/keyword_update.php?mode=delete&id=' . (int) $row['id'] . '&token=' . $kw_admin_token . '" class="mgr-btn" style="color:var(--mgr-danger);" onclick="return confirm(\'이 키워드를 삭제하시겠습니까?\');">삭제</a>',
        );
    }
    echo mgr_data_table(
        array(
            array('key' => 'keyword', 'label' => '키워드'),
            array('key' => 'advertiser', 'label' => '광고주'),
            array('key' => 'project', 'label' => '연결 프로젝트'),
            array('key' => 'group', 'label' => '유형'),
            array('key' => 'locked', 'label' => '잠금'),
            array('key' => 'status', 'label' => '상태'),
            array('key' => 'created_at', 'label' => '등록일'),
            array('key' => 'manage', 'label' => '관리'),
        ),
        $kw_table_rows,
        array('empty_title' => '등록된 키워드가 없습니다')
    );

    $kw_qs = array('tab' => 'keywords', 'stx_keyword' => $kw_stx_keyword, 'stx_advertiser' => $kw_stx_advertiser, 'stx_project' => $kw_stx_project, 'status' => $kw_status, 'locked' => $kw_locked);
    echo mgr_pagination($kw_page, $kw_total_pages, '?' . http_build_query($kw_qs) . '&page=');
    ?>
    <?php elseif ($tab === 'generation_rules'): ?>
    <p style="color:var(--mgr-text-muted);font-size:.875rem;margin:0 0 1rem;">post_builder.php 1단계 "AI 글 생성 조건"에 체크박스로 노출되는 지시문 프리셋입니다. 체크된 조건의 "AI 전달용 상세 지시문"만 실제 프롬프트에 실립니다.</p>
    <div style="margin-bottom:1rem;">
        <a href="./generation_rule_form.php" class="mgr-btn mgr-btn-primary">+ 조건 등록</a>
    </div>
    <?php
    $gr_admin_token = get_admin_token();
    $gr_table_rows = array();
    foreach ($gr_list as $row) {
        $gr_table_rows[] = array(
            'rule_type' => isset($gr_type_labels[$row['rule_type']]) ? $gr_type_labels[$row['rule_type']] : htmlspecialchars($row['rule_type']),
            'rule_name' => htmlspecialchars($row['rule_name']),
            'rule_instruction' => htmlspecialchars(mb_substr($row['rule_instruction'], 0, 60)) . (mb_strlen($row['rule_instruction']) > 60 ? '…' : ''),
            'is_default' => $row['is_default'] === 'Y' ? '기본체크' : '-',
            'is_active' => mgr_status_badge($row['is_active'] === 'Y' ? '사용' : '숨김', $row['is_active'] === 'Y' ? 'success' : 'muted'),
            'sort_order' => (int) $row['sort_order'],
            'manage' => '<a href="./generation_rule_form.php?id=' . (int) $row['id'] . '" class="mgr-btn">수정</a> '
                . '<a href="' . G5_ADMIN_URL . '/blog/generation_rule_update.php?mode=delete&id=' . (int) $row['id'] . '&token=' . $gr_admin_token . '" class="mgr-btn" style="color:var(--mgr-danger);" onclick="return confirm(\'이 생성 조건을 삭제하시겠습니까?\');">삭제</a>',
        );
    }
    echo mgr_data_table(
        array(
            array('key' => 'rule_type', 'label' => '구분'),
            array('key' => 'rule_name', 'label' => '조건 이름'),
            array('key' => 'rule_instruction', 'label' => 'AI 전달용 지시문(요약)'),
            array('key' => 'is_default', 'label' => '기본 체크'),
            array('key' => 'is_active', 'label' => '상태'),
            array('key' => 'sort_order', 'label' => '순서'),
            array('key' => 'manage', 'label' => '관리'),
        ),
        $gr_table_rows,
        array('empty_title' => '등록된 생성 조건이 없습니다')
    );
    ?>
    <?php elseif ($tab === 'publishing'): ?>
    <form method="get" style="display:flex;gap:.75rem;flex-wrap:wrap;align-items:flex-end;margin-bottom:1rem;">
        <input type="hidden" name="tab" value="publishing">
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">포스트 제목 검색</label>
            <input type="text" name="stx" value="<?php echo htmlspecialchars($pj_stx, ENT_QUOTES, 'UTF-8'); ?>" class="mgr-input">
        </div>
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">상태</label>
            <select name="sfl_status" class="mgr-input">
                <option value="">전체</option>
                <?php foreach ($pj_status_label as $code => $label): ?>
                <option value="<?php echo $code; ?>" <?php echo $pj_status === $code ? 'selected' : ''; ?>><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">예약일 시작</label>
            <input type="date" name="sdate" value="<?php echo htmlspecialchars($pj_sdate, ENT_QUOTES, 'UTF-8'); ?>" class="mgr-input">
        </div>
        <div>
            <label style="display:block;font-size:.8125rem;color:var(--mgr-text-muted);margin-bottom:.25rem;">예약일 종료</label>
            <input type="date" name="edate" value="<?php echo htmlspecialchars($pj_edate, ENT_QUOTES, 'UTF-8'); ?>" class="mgr-input">
        </div>
        <button type="submit" class="mgr-btn mgr-btn-primary">검색</button>
        <a href="?tab=publishing" class="mgr-btn">초기화</a>
        <a href="<?php echo SF_MANAGER_URL; ?>/blog/publish_job_form.php" class="mgr-btn mgr-btn-primary" style="margin-left:auto;">+ 예약 등록</a>
    </form>

    <?php
    $pj_table_rows = array();
    foreach ($pj_list as $row) {
        $st = $row['status'];
        $st_label = isset($pj_status_label[$st]) ? $pj_status_label[$st] : htmlspecialchars($st);
        $st_tone = $st === 'published' ? 'success' : ($st === 'failed' ? 'danger' : (in_array($st, array('scheduled', 'pending'), true) ? 'primary' : 'muted'));
        $can_edit = in_array($st, array('scheduled', 'pending'), true);
        $can_retry = ($st === 'failed');

        $manage = '<a href="' . G5_ADMIN_URL . '/blog/publish_job_view.php?id=' . (int) $row['id'] . '" class="mgr-btn">상세</a> ';
        if ($can_edit) {
            $manage .= '<a href="' . SF_MANAGER_URL . '/blog/publish_job_form.php?id=' . (int) $row['id'] . '&w=u" class="mgr-btn">수정</a> ';
            $manage .= '<button type="button" class="mgr-btn" style="color:var(--mgr-danger);" onclick="mgrPublishJobAction(\'cancel\', ' . (int) $row['id'] . ')">취소</button> ';
        }
        if ($can_retry) {
            $manage .= '<button type="button" class="mgr-btn" onclick="mgrPublishJobAction(\'retry\', ' . (int) $row['id'] . ')">재시도</button>';
        }

        $pj_table_rows[] = array(
            'advertiser' => htmlspecialchars($row['advertiser_name'] ? $row['advertiser_name'] : '-'),
            'post_title' => htmlspecialchars($row['post_title']) . '<br><span style="font-size:.8125rem;color:var(--mgr-text-muted);">' . htmlspecialchars($row['project_topic']) . '</span>',
            'site' => htmlspecialchars($row['site_name']),
            'scheduled_at' => $row['scheduled_at'] ? htmlspecialchars($row['scheduled_at']) : '-',
            'status' => mgr_status_badge($st_label, $st_tone),
            'attempts' => (int) $row['attempt_count'] . ' / ' . (int) $row['max_retries'],
            'last_error' => $row['last_error'] ? '<span style="color:var(--mgr-danger);font-size:.8125rem;">' . htmlspecialchars(mb_substr($row['last_error'], 0, 60)) . '</span>' : '-',
            'completed_at' => $row['completed_at'] ? htmlspecialchars($row['completed_at']) : '-',
            'manage' => $manage,
        );
    }
    echo mgr_data_table(
        array(
            array('key' => 'advertiser', 'label' => '광고주'),
            array('key' => 'post_title', 'label' => '포스팅 / 프로젝트'),
            array('key' => 'site', 'label' => '발행 채널'),
            array('key' => 'scheduled_at', 'label' => '예약 일시'),
            array('key' => 'status', 'label' => '현재 상태'),
            array('key' => 'attempts', 'label' => '시도 횟수'),
            array('key' => 'last_error', 'label' => '최근 오류'),
            array('key' => 'completed_at', 'label' => '완료 시각'),
            array('key' => 'manage', 'label' => '관리'),
        ),
        $pj_table_rows,
        array('empty_title' => '조건에 맞는 발행 작업이 없습니다')
    );

    $pj_qs = array('tab' => 'publishing', 'stx' => $pj_stx, 'sfl_status' => $pj_status, 'sdate' => $pj_sdate, 'edate' => $pj_edate);
    echo mgr_pagination($pj_page, $pj_total_pages, '?' . http_build_query($pj_qs) . '&page=');
    ?>
    <script>
    // adm/blog/publish_job_update.php가 실제 처리 파일이다 - 원래 SF_MANAGER_URL(manager 쪽,
    // 대응 파일 없음)로 폼을 만들어 제출하고 있어서 취소/재시도 버튼이 항상 404였다.
    function mgrPublishJobAction(action, id) {
        var label = action === 'cancel' ? '이 예약 작업을 취소하시겠습니까?' : '이 실패 작업을 재시도 상태로 변경하시겠습니까?';
        if (!confirm(label)) return;
        var form = document.createElement('form');
        form.method = 'post';
        form.action = '<?php echo G5_ADMIN_URL; ?>/blog/publish_job_update.php';
        var fields = { w: action, id: id, token: '<?php echo get_admin_token(); ?>' };
        for (var key in fields) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = fields[key];
            form.appendChild(input);
        }
        document.body.appendChild(form);
        form.submit();
    }
    </script>
    <?php elseif ($tab === 'reports'): ?>
    <p style="color:var(--mgr-text-muted);font-size:.875rem;margin:0 0 1rem;">발행 작업·시도 데이터를 기준으로 집계한 요약입니다. 차트 없이 표로 확인할 수 있습니다.</p>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;">
        <?php
        echo mgr_stat_card('오늘 발행 시도', number_format((int) $rpt_today_attempts['total']), array('tone' => 'primary'));
        echo mgr_stat_card('오늘 성공', number_format((int) $rpt_today_attempts['success']), array('tone' => 'success'));
        echo mgr_stat_card('오늘 실패', number_format((int) $rpt_today_attempts['failed']), array('tone' => 'danger'));
        echo mgr_stat_card('이번 주 발행(예약+대기+처리중)', number_format((int) ($rpt_week_jobs['scheduled'] + $rpt_week_jobs['pending'] + $rpt_week_jobs['processing'])), array('tone' => 'primary'));
        echo mgr_stat_card('이번 달 발행 시도', number_format((int) $rpt_month_attempts['total']), array('tone' => 'primary'));
        echo mgr_stat_card('이번 달 성공률', bp_report_success_rate($rpt_month_attempts['success'], $rpt_month_attempts['total']) . '%', array('tone' => 'success'));
        echo mgr_stat_card('운영 사이트', number_format((int) $rpt_active_sites['cnt']), array('tone' => 'muted'));
        echo mgr_stat_card('활성 광고주', number_format((int) $rpt_active_advertisers['cnt']), array('tone' => 'muted'));
        ?>
    </div>

    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1.5rem;">
        <a href="<?php echo SF_MANAGER_URL; ?>/blog/report_daily.php" class="mgr-btn">일간 발행 보고서</a>
        <a href="<?php echo SF_MANAGER_URL; ?>/blog/report_weekly.php" class="mgr-btn">주간 운영 보고서</a>
        <a href="<?php echo SF_MANAGER_URL; ?>/blog/report_monthly_adv.php" class="mgr-btn">월간 광고주 보고서</a>
        <a href="<?php echo SF_MANAGER_URL; ?>/blog/report_stats.php" class="mgr-btn">성공·실패 통계</a>
        <a href="<?php echo SF_MANAGER_URL; ?>/blog/report_site.php" class="mgr-btn">사이트별 발행 통계</a>
        <a href="<?php echo SF_MANAGER_URL; ?>/blog/report_performance.php" class="mgr-btn">콘텐츠 성과</a>
    </div>

    <?php
    $rpt_fail_rows = array();
    while ($r = sql_fetch_array($rpt_recent_failures)) {
        $rpt_fail_rows[] = array(
            'id' => (int) $r['id'],
            'completed_at' => $r['completed_at'] ? htmlspecialchars($r['completed_at']) : '-',
            'project' => htmlspecialchars($r['topic']),
            'title' => htmlspecialchars($r['title']),
            'error' => '<span style="color:var(--mgr-danger);">' . htmlspecialchars(mb_substr((string) $r['last_error'], 0, 60)) . '</span>',
        );
    }
    echo '<h3 style="font-size:1rem;margin:0 0 .5rem;">최근 실패한 발행 작업(최대 10건)</h3>';
    echo mgr_data_table(
        array(
            array('key' => 'id', 'label' => '작업 ID'), array('key' => 'completed_at', 'label' => '완료 시각'),
            array('key' => 'project', 'label' => '프로젝트'), array('key' => 'title', 'label' => '포스트 제목'),
            array('key' => 'error', 'label' => '오류'),
        ),
        $rpt_fail_rows,
        array('empty_title' => '최근 실패한 작업이 없습니다')
    );

    $rpt_stale_rows = array();
    while ($r = sql_fetch_array($rpt_stale_pending)) {
        $rpt_stale_rows[] = array(
            'id' => (int) $r['id'], 'status' => htmlspecialchars($r['status']),
            'created_at' => htmlspecialchars($r['created_at']), 'title' => htmlspecialchars($r['title']),
        );
    }
    echo '<h3 style="font-size:1rem;margin:1.5rem 0 .5rem;">24시간 이상 대기·처리중인 작업(최대 10건)</h3>';
    echo mgr_data_table(
        array(
            array('key' => 'id', 'label' => '작업 ID'), array('key' => 'status', 'label' => '상태'),
            array('key' => 'created_at', 'label' => '생성일'), array('key' => 'title', 'label' => '포스트 제목'),
        ),
        $rpt_stale_rows,
        array('empty_title' => '장기 대기 작업이 없습니다')
    );

    $rpt_maxed_rows = array();
    while ($r = sql_fetch_array($rpt_maxed_out)) {
        $rpt_maxed_rows[] = array(
            'id' => (int) $r['id'], 'attempts' => (int) $r['attempt_count'] . ' / ' . (int) $r['max_retries'],
            'title' => htmlspecialchars($r['title']),
        );
    }
    echo '<h3 style="font-size:1rem;margin:1.5rem 0 .5rem;">최대 재시도 도달 후 실패(최대 10건)</h3>';
    echo mgr_data_table(
        array(
            array('key' => 'id', 'label' => '작업 ID'), array('key' => 'attempts', 'label' => '시도/최대'),
            array('key' => 'title', 'label' => '포스트 제목'),
        ),
        $rpt_maxed_rows,
        array('empty_title' => '해당 작업이 없습니다')
    );

    $rpt_missing_rows = array();
    while ($r = sql_fetch_array($rpt_missing_url)) {
        $rpt_missing_rows[] = array(
            'id' => (int) $r['id'], 'completed_at' => htmlspecialchars($r['completed_at']),
            'title' => htmlspecialchars($r['title']),
        );
    }
    echo '<h3 style="font-size:1rem;margin:1.5rem 0 .5rem;">발행 성공했지만 외부 URL이 누락된 작업(최대 10건)</h3>';
    echo mgr_data_table(
        array(
            array('key' => 'id', 'label' => '작업 ID'), array('key' => 'completed_at', 'label' => '완료 시각'),
            array('key' => 'title', 'label' => '포스트 제목'),
        ),
        $rpt_missing_rows,
        array('empty_title' => '해당 작업이 없습니다')
    );
    ?>
    <?php else: ?>
    <!-- 본문 영역 뼈대 -->
    <div style="padding: 2rem 0; text-align: center; color: var(--mgr-text-muted);">
        <p><strong><?php echo $tabs[$tab]; ?></strong> 탭 콘텐츠가 이곳에 통합될 예정입니다. (Phase 3)</p>
    </div>
    <?php endif; ?>
</div>
<?php include_once(__DIR__ . '/../layout/footer.php'); ?>
