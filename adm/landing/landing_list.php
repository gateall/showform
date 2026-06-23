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

$row_count = sql_fetch(" select count(*) as cnt from {$table} {$sql_search} ");
$total_count = isset($row_count['cnt']) ? (int)$row_count['cnt'] : 0;
$total_page = $rows > 0 ? ceil($total_count / $rows) : 1;

$sql = " select a.*, (select count(*) from {$inq_table} where landing_id = a.id) as inq_cnt from {$table} a {$sql_search} order by a.id desc limit {$offset}, {$rows} ";
$result = sql_query($sql, false);

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<style>
.sf-top { display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
.sf-search { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.sf-btn { display:inline-block; padding:8px 14px; border-radius:8px; text-decoration:none; font-weight:700; }
.sf-btn-primary { background:#0f766e; color:#fff; }
.sf-btn-muted { background:#e2e8f0; color:#0f172a; }
.sf-table a { color:#0f766e; font-weight:700; text-decoration:none; }
.sf-table a:hover { text-decoration:underline; }
</style>

<div class="sf-top">
    <form method="get" class="sf-search">
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" class="frm_input" placeholder="회사명, 업종, 지역명 검색">
        <button type="submit" class="btn_submit btn">검색</button>
    </form>
    <div>
        <a href="./landing_form.php" class="sf-btn sf-btn-primary">+ 랜딩 생성</a>
    </div>
</div>

<div class="tbl_head01 tbl_wrap sf-table">
    <table>
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">템플릿</th>
                <th scope="col">업종</th>
                <th scope="col">회사명</th>
                <th scope="col">지역</th>
                <th scope="col">문의</th>
                <th scope="col">상태</th>
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
                <td><?php echo get_text($row['template_type']); ?></td>
                <td><?php echo get_text($row['industry']); ?></td>
                <td><a href="/page/landing.php?id=<?php echo (int)$row['id']; ?>" target="_blank"><?php echo get_text($row['company_name']); ?></a></td>
                <td><?php echo get_text($row['area_name']); ?></td>
                <td class="td_num"><?php echo number_format((int)$row['inq_cnt']); ?></td>
                <td><?php echo $row['is_active'] === 'Y' ? '공개' : '비공개'; ?></td>
                <td>
                    <a href="./landing_form.php?id=<?php echo (int)$row['id']; ?>">수정</a>
                    <a href="./landing_delete.php?id=<?php echo (int)$row['id']; ?>" onclick="return confirm('삭제하시겠습니까?');" style="color:#dc2626;">삭제</a>
                </td>
            </tr>
        <?php }
        if ($count === 0) {
            echo '<tr><td colspan="8" class="empty_table">등록된 랜딩페이지가 없습니다.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?stx='.urlencode($stx).'&page='); ?>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>