<?php
$sub_menu = '360100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');
require_once __DIR__ . '/../components/data_table.php';
require_once __DIR__ . '/../components/pagination.php';
require_once __DIR__ . '/../components/status_badge.php';

$page_title = '광고주·포스팅 관리';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'advertisers';

$tabs = array(
    'advertisers' => '광고주',
    'posts' => '포스팅',
    'keywords' => '키워드',
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
                . '<a href="' . G5_ADMIN_URL . '/blog/advertiser_delete.php?id=' . (int) $row['id'] . '&token=' . get_admin_token() . '" class="mgr-btn" style="color:var(--mgr-danger);" onclick="return confirm(\'정말 삭제하시겠습니까?\');">삭제</a>',
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
        $proj_table_rows[] = array(
            'title' => '<a href="' . SF_MANAGER_URL . '/blog/project_view.php?id=' . (int) $row['id'] . '">'
                . htmlspecialchars($row['post_title'] ? $row['post_title'] : $row['topic']) . '</a>',
            'advertiser' => htmlspecialchars($row['advertiser_name'] ? $row['advertiser_name'] : '-'),
            'site' => htmlspecialchars($row['site_name'] ? $row['site_name'] : '-'),
            'status' => mgr_status_badge($st_label, $st === 'published' ? 'success' : ($st === 'failed' ? 'danger' : ($st === 'pending_approval' ? 'warning' : 'muted'))),
            'job_status' => $job_summary,
            'scheduled_at' => $row['next_scheduled_at'] ? htmlspecialchars($row['next_scheduled_at']) : '-',
            'updated_at' => $row['updated_at'] ? htmlspecialchars($row['updated_at']) : htmlspecialchars($row['created_at']),
            'manage' => '<a href="' . SF_MANAGER_URL . '/blog/project_view.php?id=' . (int) $row['id'] . '" class="mgr-btn">열기</a>',
        );
    }
    echo mgr_data_table(
        array(
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

    $proj_qs = array('tab' => 'posts', 'stx_title' => $proj_stx_title, 'stx_advertiser' => $proj_stx_advertiser, 'status' => $proj_status, 'job_status' => $proj_job_status);
    echo mgr_pagination($proj_page, $proj_total_pages, '?' . http_build_query($proj_qs) . '&page=');
    ?>
    <?php else: ?>
    <!-- 본문 영역 뼈대 -->
    <div style="padding: 2rem 0; text-align: center; color: var(--mgr-text-muted);">
        <p><strong><?php echo $tabs[$tab]; ?></strong> 탭 콘텐츠가 이곳에 통합될 예정입니다. (Phase 3)</p>
    </div>
    <?php endif; ?>
</div>
<?php include_once(__DIR__ . '/../layout/footer.php'); ?>
