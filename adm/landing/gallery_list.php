<?php
include_once('./_common.php');

auth_check_menu($auth, '900400', 'r');

$g5['title'] = '갤러리관리';
$gallery_table = G5_TABLE_PREFIX . 'landing_gallery';
$page_table = G5_TABLE_PREFIX . 'landing_pages';

$landing_id = isset($_GET['landing_id']) ? (int)$_GET['landing_id'] : 0;
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rows = 20;
$offset = ($page - 1) * $rows;

$sql_search = ' where 1=1 ';
if ($landing_id > 0) {
    $sql_search .= " and a.landing_id = '" . (int)$landing_id . "' ";
}
if ($stx !== '') {
    $safe = sql_real_escape_string($stx);
    $sql_search .= " and (p.company_name like '%{$safe}%' or a.title like '%{$safe}%') ";
}

$row_count = sql_fetch(" select count(*) as cnt from {$gallery_table} a left join {$page_table} p on p.id = a.landing_id {$sql_search} ");
$total_count = isset($row_count['cnt']) ? (int)$row_count['cnt'] : 0;
$total_page = $rows > 0 ? ceil($total_count / $rows) : 1;

$sql = " select a.*, p.company_name from {$gallery_table} a left join {$page_table} p on p.id = a.landing_id {$sql_search} order by a.sort_order asc, a.id desc limit {$offset}, {$rows} ";
$result = sql_query($sql, false);
$landing_list = sql_query(" select id, company_name from {$page_table} order by id desc ");

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<style>
.sf-top { display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap:wrap; margin-bottom:16px; }
.sf-search { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.sf-search select, .sf-search input { padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; }
.sf-thumb { width:84px; height:56px; border-radius:10px; object-fit:cover; background:#f1f5f9; }
.sf-status { display:inline-flex; padding:6px 10px; border-radius:999px; font-size:12px; font-weight:700; }
.sf-on { background:#dcfce7; color:#166534; }
.sf-off { background:#fee2e2; color:#991b1b; }
.sf-actions a { margin-right:8px; text-decoration:none; font-weight:700; }
</style>

<div class="sf-top">
    <form method="get" class="sf-search">
        <select name="landing_id">
            <option value="0">전체 랜딩</option>
            <?php while ($p = sql_fetch_array($landing_list)) { ?>
            <option value="<?php echo (int)$p['id']; ?>" <?php echo get_selected($landing_id, $p['id']); ?>><?php echo get_text($p['company_name']); ?></option>
            <?php } ?>
        </select>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" placeholder="회사명, 제목 검색">
        <button type="submit" class="btn_submit btn">검색</button>
    </form>
    <div><span class="btn_ov01"><span class="ov_txt">총 갤러리</span><span class="ov_num"> <?php echo number_format($total_count); ?>건</span></span></div>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">랜딩회사명</th>
                <th scope="col">썸네일</th>
                <th scope="col">제목</th>
                <th scope="col">정렬</th>
                <th scope="col">노출</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $count = 0;
        while ($row = sql_fetch_array($result)) {
            $count++;
            $thumb = !empty($row['image_path']) ? $row['image_path'] : '';
        ?>
            <tr>
                <td class="td_num"><?php echo (int)$row['id']; ?></td>
                <td><a href="/page/landing.php?id=<?php echo (int)$row['landing_id']; ?>" target="_blank"><?php echo get_text($row['company_name'] ? $row['company_name'] : '랜딩 #' . (int)$row['landing_id']); ?></a></td>
                <td><?php if ($thumb) { ?><img class="sf-thumb" src="<?php echo get_text($thumb); ?>" alt="<?php echo get_text($row['title']); ?>"><?php } else { ?>-<?php } ?></td>
                <td><?php echo get_text($row['title']); ?></td>
                <td class="td_num"><?php echo (int)$row['sort_order']; ?></td>
                <td><span class="sf-status <?php echo $row['is_active'] === 'Y' ? 'sf-on' : 'sf-off'; ?>"><?php echo $row['is_active'] === 'Y' ? '노출' : '숨김'; ?></span></td>
                <td class="sf-actions">
                    <a href="./gallery_form.php?id=<?php echo (int)$row['id']; ?>">수정</a>
                    <a href="./gallery_delete.php?id=<?php echo (int)$row['id']; ?>" onclick="return confirm('삭제하시겠습니까?');" style="color:#dc2626;">삭제</a>
                </td>
            </tr>
        <?php }
        if ($count === 0) {
            echo '<tr><td colspan="7" class="empty_table">등록된 갤러리가 없습니다.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?landing_id='.urlencode((string)$landing_id).'&stx='.urlencode($stx).'&page='); ?>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>