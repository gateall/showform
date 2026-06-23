<?php
include_once('./_common.php');

auth_check_menu($auth, '900300', 'r');

$g5['title'] = '후기관리';
$review_table = G5_TABLE_PREFIX . 'landing_reviews';
$page_table = G5_TABLE_PREFIX . 'landing_pages';

$landing_id = isset($_GET['landing_id']) ? (int)$_GET['landing_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
$sfl = isset($_GET['sfl']) ? trim($_GET['sfl']) : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rows = 20;
$offset = ($page - 1) * $rows;

$sql_search = ' where 1=1 ';
if ($landing_id > 0) {
    $sql_search .= " and a.landing_id = '" . (int)$landing_id . "' ";
}
if ($status === 'Y' || $status === 'N') {
    $sql_search .= " and a.is_active = '" . sql_real_escape_string($status) . "' ";
}
if ($stx !== '') {
    $safe = sql_real_escape_string($stx);
    if ($sfl === 'company_name') {
        $sql_search .= " and p.company_name like '%{$safe}%' ";
    } elseif ($sfl === 'customer_name') {
        $sql_search .= " and a.customer_name like '%{$safe}%' ";
    } else {
        $sql_search .= " and (p.company_name like '%{$safe}%' or a.customer_name like '%{$safe}%' or a.content like '%{$safe}%') ";
    }
}

$row_count = sql_fetch(" select count(*) as cnt from {$review_table} a left join {$page_table} p on p.id = a.landing_id {$sql_search} ");
$total_count = isset($row_count['cnt']) ? (int)$row_count['cnt'] : 0;
$total_page = $rows > 0 ? ceil($total_count / $rows) : 1;

$sql = " select a.*, p.company_name from {$review_table} a left join {$page_table} p on p.id = a.landing_id {$sql_search} order by a.sort_order asc, a.id desc limit {$offset}, {$rows} ";
$result = sql_query($sql, false);

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<link rel="stylesheet" href="<?php echo G5_ADMIN_URL; ?>/landing/landing_admin.css">
<?php
?>
<style>
.sf-top { display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
.sf-search { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.sf-search select, .sf-search input { padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; }
.sf-status { display:inline-flex; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700; }
.sf-on { background:#dcfce7; color:#166534; }
.sf-off { background:#fee2e2; color:#991b1b; }
.sf-actions a { margin-right:8px; text-decoration:none; font-weight:700; }
</style>

<div class="sf-admin-shell">
    <div class="sf-admin-top">
    <form method="get" class="sf-search">
        <select name="landing_id">
            <option value="0">전체 랜딩</option>
            <?php
            $pages = sql_query(" select id, company_name from {$page_table} order by id desc ");
            while ($p = sql_fetch_array($pages)) {
            ?>
            <option value="<?php echo (int)$p['id']; ?>" <?php echo get_selected($landing_id, $p['id']); ?>><?php echo get_text($p['company_name']); ?></option>
            <?php } ?>
        </select>
        <select name="status">
            <option value="">전체 노출</option>
            <option value="Y" <?php echo get_selected($status, 'Y'); ?>>노출</option>
            <option value="N" <?php echo get_selected($status, 'N'); ?>>숨김</option>
        </select>
        <select name="sfl">
            <option value="all" <?php echo get_selected($sfl, 'all'); ?>>통합 검색</option>
            <option value="company_name" <?php echo get_selected($sfl, 'company_name'); ?>>회사명</option>
            <option value="customer_name" <?php echo get_selected($sfl, 'customer_name'); ?>>고객명</option>
        </select>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" placeholder="검색어">
        <button type="submit" class="btn_submit btn">검색</button>
    </form>
    <div><span class="btn_ov01"><span class="ov_txt">총 후기</span><span class="ov_num"> <?php echo number_format($total_count); ?>건</span></span></div>
</div>

<div class="sf-table-wrap">
    <table>
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">랜딩회사명</th>
                <th scope="col">고객명</th>
                <th scope="col">평점</th>
                <th scope="col">후기내용</th>
                <th scope="col">정렬</th>
                <th scope="col">노출</th>
                <th scope="col">등록일</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $count = 0;
        while ($row = sql_fetch_array($result)) {
            $count++;
        ?>
            <tr>
                <td class="td_num"><?php echo (int)$row['id']; ?></td>
                <td><a href="/page/landing.php?id=<?php echo (int)$row['landing_id']; ?>" target="_blank"><?php echo get_text($row['company_name'] ? $row['company_name'] : '랜딩 #' . (int)$row['landing_id']); ?></a></td>
                <td><?php echo get_text($row['customer_name']); ?></td>
                <td class="td_num"><?php echo str_repeat('★', (int)$row['rating']); ?></td>
                <td><?php echo get_text(cut_str($row['content'], 80, '...')); ?></td>
                <td class="td_num"><?php echo (int)$row['sort_order']; ?></td>
                <td>
                    <span class="sf-status <?php echo $row['is_active'] === 'Y' ? 'sf-on' : 'sf-off'; ?>"><?php echo $row['is_active'] === 'Y' ? '노출' : '숨김'; ?></span>
                </td>
                <td><?php echo get_text($row['created_at']); ?></td>
                <td class="sf-actions">
                    <a href="./review_form.php?id=<?php echo (int)$row['id']; ?>">수정</a>
                    <a href="./review_delete.php?id=<?php echo (int)$row['id']; ?>" onclick="return confirm('삭제하시겠습니까?');" style="color:#dc2626;">삭제</a>
                </td>
            </tr>
        <?php }
        if ($count === 0) {
            echo '<tr><td colspan="9" class="empty_table">등록된 후기가 없습니다.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?landing_id='.urlencode((string)$landing_id).'&status='.urlencode($status).'&sfl='.urlencode($sfl).'&stx='.urlencode($stx).'&page='); ?>

</div>
<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>