<?php
include_once('./_common.php');

$sub_menu = '990050';
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '업종 관리';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$display = isset($_GET['display']) ? trim($_GET['display']) : '';
$rows = isset($_GET['rows']) ? (int) $_GET['rows'] : 20;
if (!in_array($rows, array(20, 50, 100), true)) {
    $rows = 20;
}
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;

$table = G5_TABLE_PREFIX . 'landing_category';
$landing_table = G5_TABLE_PREFIX . 'landing_page';

$where = array('1=1');
if ($search !== '') {
    $safe_search = sql_real_escape_string($search);
    $where[] = "(category_name like '%{$safe_search}%' or category_code like '%{$safe_search}%')";
}
if ($display === 'Y' || $display === 'N') {
    $where[] = "is_display = '" . sql_real_escape_string($display) . "'";
}
$where_sql = ' where ' . implode(' and ', $where);

$total_row = sql_fetch(" select count(*) as cnt from {$table} {$where_sql} ");
$total_count = isset($total_row['cnt']) ? (int)$total_row['cnt'] : 0;
$total_page = $rows > 0 ? ceil($total_count / $rows) : 1;
if ($total_page < 1) $total_page = 1;
if ($page > $total_page) $page = $total_page;
$from_record = ($page - 1) * $rows;

$sql = " select c.*, (select count(*) from {$landing_table} p where p.category_id = c.id) as landing_count from {$table} c {$where_sql} order by c.sort_order asc, c.id desc limit {$from_record}, {$rows} ";
$result = sql_query($sql);

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>

<div class="local_desc01 local_desc">
    <p>랜딩페이지 업종을 관리합니다. 카테고리는 등록 후 랜딩 등록 화면에서 선택 항목으로 재사용됩니다.</p>
</div>

<form id="fsearch" method="get" class="local_sch03 local_sch">
    <input type="text" name="search" value="<?php echo get_text($search); ?>" class="frm_input" placeholder="카테고리명 또는 코드">
    <select name="display">
        <option value="">노출 전체</option>
        <option value="Y" <?php echo $display === 'Y' ? 'selected' : ''; ?>>노출</option>
        <option value="N" <?php echo $display === 'N' ? 'selected' : ''; ?>>숨김</option>
    </select>
    <select name="rows">
        <option value="20" <?php echo $rows === 20 ? 'selected' : ''; ?>>20개씩</option>
        <option value="50" <?php echo $rows === 50 ? 'selected' : ''; ?>>50개씩</option>
        <option value="100" <?php echo $rows === 100 ? 'selected' : ''; ?>>100개씩</option>
    </select>
    <button type="submit" class="btn_submit btn">검색</button>
    <a href="./category_list.php" class="btn btn_02">초기화</a>
    <a href="./category_form.php" class="btn btn_01">카테고리 등록</a>
</form>

<form id="fcategorylist" method="post" action="./category_update.php">
    <input type="hidden" name="mode" value="sort_save">
    <input type="hidden" name="search" value="<?php echo get_text($search); ?>">
    <input type="hidden" name="display" value="<?php echo get_text($display); ?>">
    <input type="hidden" name="rows" value="<?php echo (int)$rows; ?>">
    <input type="hidden" name="page" value="<?php echo (int)$page; ?>">

    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption>랜딩 카테고리 목록</caption>
            <thead>
                <tr>
                    <th scope="col"><input type="checkbox" id="chkall" onclick="if (this.checked) all_checked(true); else all_checked(false);"></th>
                    <th scope="col">번호</th>
                    <th scope="col">카테고리 코드</th>
                    <th scope="col">카테고리명</th>
                    <th scope="col">연결 랜딩</th>
                    <th scope="col">정렬 순서</th>
                    <th scope="col">노출</th>
                    <th scope="col">등록일</th>
                    <th scope="col">수정일</th>
                    <th scope="col">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($total_count > 0) { ?>
                    <?php for ($i = 0; $row = sql_fetch_array($result); $i++) { ?>
                        <tr>
                            <td class="td_chk"><input type="checkbox" name="chk[]" value="<?php echo (int)$row['id']; ?>"></td>
                            <td><?php echo $total_count - (($page - 1) * $rows) - $i; ?></td>
                            <td><?php echo get_text($row['category_code']); ?></td>
                            <td style="text-align:left;">
                                <a href="./category_form.php?id=<?php echo (int)$row['id']; ?>"><strong><?php echo get_text($row['category_name']); ?></strong></a>
                                <?php if ($row['category_desc']) { ?><div style="color:#666;font-size:12px;margin-top:4px;"><?php echo nl2br(get_text($row['category_desc'])); ?></div><?php } ?>
                            </td>
                            <td><?php echo number_format((int)$row['landing_count']); ?></td>
                            <td><input type="text" name="sort_order[<?php echo (int)$row['id']; ?>]" value="<?php echo (int)$row['sort_order']; ?>" class="frm_input" style="width:70px;text-align:center;"></td>
                            <td>
                                <a href="./category_toggle.php?id=<?php echo (int)$row['id']; ?>&amp;token=<?php echo get_admin_token(); ?>" class="btn btn_03"><?php echo $row['is_display'] === 'Y' ? '노출' : '숨김'; ?></a>
                            </td>
                            <td><?php echo get_text($row['created_at']); ?></td>
                            <td><?php echo get_text($row['updated_at']); ?></td>
                            <td>
                                <a href="./category_form.php?id=<?php echo (int)$row['id']; ?>" class="btn btn_02">수정</a>
                                <a href="./category_delete.php?id=<?php echo (int)$row['id']; ?>&amp;token=<?php echo get_admin_token(); ?>" class="btn btn_01" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="10" class="empty_table">등록된 카테고리가 없습니다.</td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="btn_list03 btn_list" style="margin-top:15px;">
        <button type="submit" class="btn_submit btn">순서 저장</button>
        <button type="submit" formaction="./category_delete.php" formmethod="post" onclick="return confirm('선택한 카테고리를 삭제하시겠습니까?');" class="btn btn_01">선택 삭제</button>
    </div>
</form>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, './category_list.php?' . http_build_query(array('search' => $search, 'display' => $display, 'rows' => $rows))); ?>

<script>
function all_checked(sw) {
    var f = document.getElementById('fcategorylist');
    if (!f) return;
    for (var i = 0; i < f.length; i++) {
        if (f.elements[i].name === 'chk[]') f.elements[i].checked = sw;
    }
}
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
