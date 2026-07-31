<?php
$sub_menu = '360100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');
require_once __DIR__ . '/../components/data_table.php';
require_once __DIR__ . '/../components/pagination.php';

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
    <?php else: ?>
    <!-- 본문 영역 뼈대 -->
    <div style="padding: 2rem 0; text-align: center; color: var(--mgr-text-muted);">
        <p><strong><?php echo $tabs[$tab]; ?></strong> 탭 콘텐츠가 이곳에 통합될 예정입니다. (Phase 3)</p>
    </div>
    <?php endif; ?>
</div>
<?php include_once(__DIR__ . '/../layout/footer.php'); ?>
