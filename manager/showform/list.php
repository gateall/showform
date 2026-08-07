<?php
include_once(__DIR__ . '/../_common.php');
auth_check_menu($auth, '700100', 'r');

require_once __DIR__ . '/../components/search_filter.php';
require_once __DIR__ . '/../components/data_table.php';
require_once __DIR__ . '/../components/pagination.php';
require_once __DIR__ . '/../components/status_badge.php';

$table = G5_TABLE_PREFIX . 'sf_portfolio';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$display = isset($_GET['display']) ? trim($_GET['display']) : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}
$rows_per_page = 20;

$where = array('deleted_at is null');
if ($search !== '') {
    $safe_search = sql_real_escape_string($search);
    $where[] = "(title like '%{$safe_search}%' or industry like '%{$safe_search}%')";
}
if ($display === 'Y' || $display === 'N') {
    $where[] = "is_display = '{$display}'";
}
$where_sql = ' where ' . implode(' and ', $where);

$total_row = sql_fetch(" select count(*) as cnt from {$table} {$where_sql} ");
$total_count = isset($total_row['cnt']) ? (int) $total_row['cnt'] : 0;
$total_page = $rows_per_page > 0 ? (int) ceil($total_count / $rows_per_page) : 1;
if ($total_page < 1) {
    $total_page = 1;
}
if ($page > $total_page) {
    $page = $total_page;
}
$offset = ($page - 1) * $rows_per_page;

$result = sql_query(" select * from {$table} {$where_sql} order by sort_order asc, id desc limit {$offset}, {$rows_per_page} ");

$page_title = '쇼폼 관리';
include __DIR__ . '/../layout/header.php';
?>

<div style="display:flex;justify-content:flex-end;margin-bottom:1rem;">
    <a href="<?php echo SF_MANAGER_URL ?>/showform/form.php" class="mgr-btn mgr-btn-primary">쇼폼 신규 등록</a>
</div>

<?php
echo mgr_search_filter(
    SF_MANAGER_URL . '/showform/list.php',
    array(
        array('type' => 'text', 'name' => 'search', 'label' => '검색', 'value' => $search, 'placeholder' => '프로젝트명·업종'),
        array('type' => 'select', 'name' => 'display', 'label' => '공개 여부', 'value' => $display, 'options' => array('' => '전체', 'Y' => '공개', 'N' => '비공개')),
    )
);

$table_rows = array();
while ($row = sql_fetch_array($result)) {
    $thumb = $row['thumbnail']
        ? '<img src="' . htmlspecialchars($row['thumbnail']) . '" alt="" style="width:56px;height:38px;object-fit:cover;border-radius:6px;">'
        : '<span style="color:#94a3b8;">-</span>';
    $edit_url = SF_MANAGER_URL . '/showform/form.php?id=' . (int) $row['id'];
    $token = get_admin_token();
    $table_rows[] = array(
        'thumb' => $thumb,
        'title' => '<a href="' . $edit_url . '"><strong>' . htmlspecialchars($row['title']) . '</strong></a>',
        'industry' => htmlspecialchars($row['industry']),
        'display' => $row['is_display'] === 'Y' ? mgr_status_badge('공개', 'success') : mgr_status_badge('비공개', 'muted'),
        'featured' => $row['is_featured'] === 'Y' ? mgr_status_badge('추천', 'ai') : '',
        'sort' => (int) $row['sort_order'],
        'updated' => htmlspecialchars($row['updated_at'] ? $row['updated_at'] : $row['created_at']),
        'action' => '<a href="' . $edit_url . '" class="mgr-btn">수정</a> '
            . '<a href="' . SF_MANAGER_URL . '/showform/portfolio_duplicate.php?id=' . (int) $row['id'] . '&return=manager&token=' . $token . '" class="mgr-btn">복제</a> '
            . '<a href="' . SF_MANAGER_URL . '/showform/portfolio_delete.php?id=' . (int) $row['id'] . '&return=manager&token=' . $token . '" class="mgr-btn" style="color:var(--mgr-danger);" onclick="return confirm(\'삭제하시겠습니까?\');">삭제</a>',
    );
}

echo mgr_data_table(
    array(
        array('key' => 'thumb', 'label' => '이미지'),
        array('key' => 'title', 'label' => '프로젝트명'),
        array('key' => 'industry', 'label' => '업종'),
        array('key' => 'display', 'label' => '공개'),
        array('key' => 'featured', 'label' => '추천'),
        array('key' => 'sort', 'label' => '순서'),
        array('key' => 'updated', 'label' => '수정일'),
        array('key' => 'action', 'label' => '관리'),
    ),
    $table_rows,
    array('empty_title' => '등록된 쇼폼이 없습니다', 'empty_desc' => '"쇼폼 신규 등록" 버튼으로 첫 제작 사례를 등록해 보세요.')
);

$qs = array();
if ($search !== '') {
    $qs['search'] = $search;
}
if ($display !== '') {
    $qs['display'] = $display;
}
$qs_string = http_build_query($qs);
$base_url = SF_MANAGER_URL . '/showform/list.php?' . ($qs_string !== '' ? $qs_string . '&' : '') . 'page=';
echo mgr_pagination($page, $total_page, $base_url);

include __DIR__ . '/../layout/footer.php';
