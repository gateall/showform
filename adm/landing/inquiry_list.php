<?php
include_once('./_common.php');

auth_check_menu($auth, '900200', 'r');

$g5['title'] = '문의관리';
$inq_table = G5_TABLE_PREFIX . 'landing_inquiries';
$page_table = G5_TABLE_PREFIX . 'landing_pages';

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
$sfl = isset($_GET['sfl']) ? trim($_GET['sfl']) : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rows = 20;
$offset = ($page - 1) * $rows;

$sql_search = ' where 1=1 ';
if (in_array($status, array('new', 'contacted', 'completed'), true)) {
    $sql_search .= " and a.status = '" . sql_real_escape_string($status) . "' ";
}
if ($stx !== '') {
    $safe = sql_real_escape_string($stx);
    if ($sfl === 'company_name') {
        $sql_search .= " and p.company_name like '%{$safe}%' ";
    } elseif ($sfl === 'phone') {
        $sql_search .= " and a.phone like '%{$safe}%' ";
    } else {
        $sql_search .= " and (p.company_name like '%{$safe}%' or a.phone like '%{$safe}%' or a.name like '%{$safe}%') ";
    }
}

$row_count = sql_fetch(" select count(*) as cnt from {$inq_table} a left join {$page_table} p on p.id = a.landing_id {$sql_search} ");
$total_count = isset($row_count['cnt']) ? (int)$row_count['cnt'] : 0;
$total_page = $rows > 0 ? ceil($total_count / $rows) : 1;

$sql = " select a.*, p.company_name, p.area_name from {$inq_table} a left join {$page_table} p on p.id = a.landing_id {$sql_search} order by a.id desc limit {$offset}, {$rows} ";
$result = sql_query($sql, false);

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<style>
.sf-top { display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
.sf-search { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.sf-search select, .sf-search input { padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; }
.sf-table a { color:#0f766e; font-weight:700; text-decoration:none; }
.sf-badge { display:inline-flex; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700; }
.status-new { background:#fef3c7; color:#92400e; }
.status-contacted { background:#dbeafe; color:#1d4ed8; }
.status-completed { background:#dcfce7; color:#166534; }
</style>

<div class="sf-top">
    <form method="get" class="sf-search">
        <select name="status">
            <option value="">전체 상태</option>
            <option value="new" <?php echo get_selected($status, 'new'); ?>>new</option>
            <option value="contacted" <?php echo get_selected($status, 'contacted'); ?>>contacted</option>
            <option value="completed" <?php echo get_selected($status, 'completed'); ?>>completed</option>
        </select>
        <select name="sfl">
            <option value="all" <?php echo get_selected($sfl, 'all'); ?>>통합 검색</option>
            <option value="company_name" <?php echo get_selected($sfl, 'company_name'); ?>>회사명</option>
            <option value="phone" <?php echo get_selected($sfl, 'phone'); ?>>연락처</option>
        </select>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" placeholder="검색어">
        <button type="submit" class="btn_submit btn">검색</button>
    </form>
    <div>
        <span class="btn_ov01"><span class="ov_txt">총 문의</span><span class="ov_num"> <?php echo number_format($total_count); ?>건</span></span>
    </div>
</div>

<div class="tbl_head01 tbl_wrap sf-table">
    <table>
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">랜딩회사명</th>
                <th scope="col">이름</th>
                <th scope="col">연락처</th>
                <th scope="col">문의내용</th>
                <th scope="col">상태</th>
                <th scope="col">등록일</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $count = 0;
        while ($row = sql_fetch_array($result)) {
            $count++;
            $status_class = 'status-new';
            if ($row['status'] === 'contacted') {
                $status_class = 'status-contacted';
            } elseif ($row['status'] === 'completed') {
                $status_class = 'status-completed';
            }
        ?>
            <tr>
                <td class="td_num"><?php echo (int)$row['id']; ?></td>
                <td>
                    <a href="/page/landing.php?id=<?php echo (int)$row['landing_id']; ?>" target="_blank">
                        <?php echo get_text($row['company_name'] ? $row['company_name'] : '랜딩 #' . (int)$row['landing_id']); ?>
                    </a>
                </td>
                <td><?php echo get_text($row['name']); ?></td>
                <td><?php echo get_text($row['phone']); ?></td>
                <td><?php echo nl2br(get_text(cut_str($row['message'], 80, '...'))); ?></td>
                <td>
                    <select class="sf-status-select" data-id="<?php echo (int)$row['id']; ?>">
                        <option value="new" <?php echo get_selected($row['status'], 'new'); ?>>new</option>
                        <option value="contacted" <?php echo get_selected($row['status'], 'contacted'); ?>>contacted</option>
                        <option value="completed" <?php echo get_selected($row['status'], 'completed'); ?>>completed</option>
                    </select>
                </td>
                <td><?php echo get_text($row['created_at']); ?></td>
                <td>
                    <a href="./inquiry_delete.php?id=<?php echo (int)$row['id']; ?>" onclick="return confirm('삭제하시겠습니까?');" style="color:#dc2626;">삭제</a>
                </td>
            </tr>
        <?php }
        if ($count === 0) {
            echo '<tr><td colspan="8" class="empty_table">등록된 문의가 없습니다.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?status='.urlencode($status).'&sfl='.urlencode($sfl).'&stx='.urlencode($stx).'&page='); ?>

<script>
$(function() {
    $('.sf-status-select').on('change', function() {
        var id = $(this).data('id');
        var status = $(this).val();
        $.post('./inquiry_status_update.php', { id: id, status: status }, function(res) {
            if (res && res.success) {
                return;
            }
            alert((res && res.error) ? res.error : '상태 변경 실패');
        }, 'json').fail(function() {
            alert('서버 통신 오류');
        });
    });
});
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>