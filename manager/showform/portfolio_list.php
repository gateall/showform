<?php
$sub_menu = '700100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '제작 사례 목록';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;
$rows = 20;

$table = G5_TABLE_PREFIX . 'sf_portfolio';

$where = array('deleted_at is null');
if ($search !== '') {
    $safe_search = sql_real_escape_string($search);
    $where[] = "(title like '%{$safe_search}%' or industry like '%{$safe_search}%')";
}
$where_sql = ' where ' . implode(' and ', $where);

$total_row = sql_fetch(" select count(*) as cnt from {$table} {$where_sql} ");
$total_count = isset($total_row['cnt']) ? (int) $total_row['cnt'] : 0;
$total_page = $rows > 0 ? (int) ceil($total_count / $rows) : 1;
if ($total_page < 1) $total_page = 1;
if ($page > $total_page) $page = $total_page;
$offset = ($page - 1) * $rows;

$result = sql_query(" select * from {$table} {$where_sql} order by sort_order asc, id desc limit {$offset}, {$rows} ");

include_once(__DIR__ . '/../layout/header.php');
?>
<div class="local_desc01 local_desc">
    <p>완성된 홈페이지·랜딩페이지 제작 사례를 카드로 등록·관리합니다. 방문자에게는 "자세히 보기"(설명 페이지)와 "사이트 보기"(실제 사이트, 새 창)로 구분되어 노출됩니다.</p>
</div>

<form method="get" action="./portfolio_list.php" class="local_search01 local_search">
    <input type="text" name="search" value="<?php echo get_text($search); ?>" placeholder="프로젝트명·업종 검색" class="frm_input">
    <input type="submit" value="검색" class="btn_submit btn">
</form>

<a href="./portfolio_form.php" class="btn btn_01">제작 사례 등록</a>

<div class="tbl_head01 tbl_wrap" style="margin-top:10px;">
    <table>
        <caption>제작 사례 목록</caption>
        <thead>
            <tr>
                <th scope="col">번호</th>
                <th scope="col">대표 이미지</th>
                <th scope="col">프로젝트명</th>
                <th scope="col">업종</th>
                <th scope="col">공개</th>
                <th scope="col">추천</th>
                <th scope="col">순서</th>
                <th scope="col">수정일</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && sql_num_rows($result) > 0) { ?>
                <?php while ($row = sql_fetch_array($result)) { ?>
                    <tr>
                        <td><?php echo (int) $row['id']; ?></td>
                        <td>
                            <?php if ($row['thumbnail']) { ?>
                                <img src="<?php echo get_text($row['thumbnail']); ?>" alt="" style="width:60px;height:40px;object-fit:cover;">
                            <?php } else { ?>
                                <span style="color:#999;">없음</span>
                            <?php } ?>
                        </td>
                        <td style="text-align:left;"><a href="./portfolio_form.php?id=<?php echo (int) $row['id']; ?>"><strong><?php echo get_text($row['title']); ?></strong></a></td>
                        <td><?php echo get_text($row['industry']); ?></td>
                        <td><?php echo $row['is_display'] === 'Y' ? '공개' : '<span style="color:#c00;">비공개</span>'; ?></td>
                        <td><?php echo $row['is_featured'] === 'Y' ? '★' : ''; ?></td>
                        <td><?php echo (int) $row['sort_order']; ?></td>
                        <td><?php echo $row['updated_at'] ? get_text($row['updated_at']) : get_text($row['created_at']); ?></td>
                        <td>
                            <a href="./portfolio_form.php?id=<?php echo (int) $row['id']; ?>" class="btn btn_02">수정</a>
                            <a href="./portfolio_delete.php?id=<?php echo (int) $row['id']; ?>&amp;token=<?php echo get_admin_token(); ?>" class="btn btn_03" onclick="return confirm('삭제하시겠습니까?');">삭제</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="9" class="empty_table">등록된 제작 사례가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php
include_once(__DIR__ . '/../layout/footer.php');
