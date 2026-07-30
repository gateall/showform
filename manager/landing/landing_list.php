<?php
include_once('./_common.php');
auth_check_menu($auth, '900100', 'r');
$g5['title'] = '랜딩 관리';
$table = G5_TABLE_PREFIX . 'landing_pages';
$inq_table = G5_TABLE_PREFIX . 'landing_inquiries';
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$rows = 20;
$offset = ($page - 1) * $rows;
$sql_search = ' where 1=1 ';
if ($stx !== '') {
    $safe = sql_real_escape_string($stx);
    $sql_search .= " and (company_name like '%{$safe}%' or industry like '%{$safe}%' or area_name like '%{$safe}%') ";
}
$summary_total = sql_fetch(" select count(*) as cnt from {$table} ");
$summary_public = sql_fetch(" select count(*) as cnt from {$table} where is_active = 'Y' ");
$summary_private = sql_fetch(" select count(*) as cnt from {$table} where is_active <> 'Y' ");
$summary_inquiry = sql_fetch(" select count(*) as cnt from {$inq_table} ");
$row_count = sql_fetch(" select count(*) as cnt from {$table} {$sql_search} ");
$total_count = isset($row_count['cnt']) ? (int)$row_count['cnt'] : 0;
$total_page = $rows > 0 ? ceil($total_count / $rows) : 1;
$sql = " select a.*, (select count(*) from {$inq_table} where landing_id = a.id) as inq_cnt from {$table} a {$sql_search} order by a.id desc limit {$offset}, {$rows} ";
$result = sql_query($sql, false);
include_once(__DIR__ . '/../layout/header.php');
?>
<link rel="stylesheet" href="<?php echo SF_MANAGER_URL; ?>/landing/landing_admin.css">
<div class="sf-admin-shell">
    <div class="sf-summary-grid">
        <div class="sf-summary-card"><div class="sf-summary-label">총 랜딩 수</div><div class="sf-summary-value"><?php echo number_format((int)$summary_total['cnt']); ?></div></div>
        <div class="sf-summary-card"><div class="sf-summary-label">공개 랜딩 수</div><div class="sf-summary-value"><?php echo number_format((int)$summary_public['cnt']); ?></div></div>
        <div class="sf-summary-card"><div class="sf-summary-label">비공개 랜딩 수</div><div class="sf-summary-value"><?php echo number_format((int)$summary_private['cnt']); ?></div></div>
        <div class="sf-summary-card"><div class="sf-summary-label">총 문의 수</div><div class="sf-summary-value"><?php echo number_format((int)$summary_inquiry['cnt']); ?></div></div>
    </div>
    <div class="sf-admin-top">
        <form method="get" class="sf-top-form">
            <input type="text" name="stx" value="<?php echo get_text($stx); ?>" class="frm_input" placeholder="회사명, 업종, 지역명 검색">
            <button type="submit" class="sf-btn sf-btn-primary">검색</button>
        </form>
        <a href="<?php echo SF_MANAGER_URL; ?>/landing/landing_form.php" class="sf-btn sf-btn-primary">등록</a>
    </div>
    <div class="sf-table-wrap">
        <table class="sf-data-table">
            <thead>
                <tr class="sf-row-main">
                    <th>ID</th><th>템플릿</th><th>업종</th><th>회사명</th><th>지역</th><th>문의수</th><th>상태</th><th>관리</th>
                </tr>
            </thead>
            <tbody>
            <?php $count = 0; while ($row = sql_fetch_array($result)) { $count++; ?>
                <tr>
                    <td class="td_num"><?php echo (int)$row['id']; ?></td>
                    <td><?php echo get_text($row['template_type']); ?></td>
                    <td><?php echo get_text($row['industry']); ?></td>
                    <td><a href="/page/landing.php?id=<?php echo (int)$row['id']; ?>" target="_blank"><?php echo get_text($row['company_name']); ?></a></td>
                    <td><?php echo get_text($row['area_name']); ?></td>
                    <td class="td_num"><?php echo number_format((int)$row['inq_cnt']); ?></td>
                    <td><?php echo $row['is_active'] === 'Y' ? '<span class="sf-badge sf-badge-green">공개</span>' : '<span class="sf-badge sf-badge-gray">비공개</span>'; ?></td>
                    <td><div class="sf-table-actions"><a class="sf-btn sf-btn-light" href="<?php echo SF_MANAGER_URL; ?>/landing/landing_form.php?id=<?php echo (int)$row['id']; ?>">수정</a><a class="sf-btn sf-btn-dark" href="./marketing.php?id=<?php echo (int)$row['id']; ?>">마케팅</a><a class="sf-btn sf-btn-danger" href="./landing_delete.php?id=<?php echo (int)$row['id']; ?>" onclick="return confirm('삭제하시겠습니까?');">삭제</a></div></td>
                </tr>
            <?php }
            if ($count === 0) echo '<tr><td colspan="8" class="sf-empty">등록된 데이터가 없습니다.</td></tr>'; ?>
            </tbody>
        </table>
    </div>
    <?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?stx='.urlencode($stx).'&page='); ?>
</div>
<?php include_once(__DIR__ . '/../layout/footer.php'); ?>
