<?php
include_once('./_common.php');

$sub_menu = '360100';
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '광고주 관리';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$rows = isset($_GET['rows']) ? (int) $_GET['rows'] : 20;
if (!in_array($rows, array(20, 50, 100), true)) {
    $rows = 20;
}
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) $page = 1;

$table = bp_table('advertisers');

$where = array('1=1');
if ($search !== '') {
    $safe_search = sql_real_escape_string($search);
    $where[] = "(name like '%{$safe_search}%' or domain like '%{$safe_search}%')";
}
$where_sql = ' where ' . implode(' and ', $where);

$total_row = sql_fetch(" select count(*) as cnt from {$table} {$where_sql} ");
$total_count = isset($total_row['cnt']) ? (int) $total_row['cnt'] : 0;
$total_page = $rows > 0 ? ceil($total_count / $rows) : 1;
if ($total_page < 1) $total_page = 1;
if ($page > $total_page) $page = $total_page;
$from_record = ($page - 1) * $rows;

$result = sql_query(" select * from {$table} {$where_sql} order by id desc limit {$from_record}, {$rows} ");

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>블로그 자동화에서 사용할 광고주(업체) 정보를 관리합니다. 상호·전화·주소·상담URL은 콘텐츠 생성 시 자동 삽입됩니다.</p>
</div>

<details class="bp-filter-wrap" <?php echo $search ? 'open' : ''; ?>>
    <summary class="bp-filter-summary">검색·필터</summary>
    <form id="fsearch" method="get" class="bp-filter-form">
        <div class="bp-filter-row">
            <label>
                <span>검색어</span>
                <input type="text" name="search" value="<?php echo get_text($search); ?>" class="frm_input" placeholder="상호 또는 도메인">
            </label>
            <div class="bp-filter-actions">
                <button type="submit" class="btn btn_submit btn">검색</button>
                <a href="./advertiser_list.php" class="btn btn_02">초기화</a>
            </div>
        </div>
    </form>
</details>

<div class="btn_fixed_top">
    <a href="./advertiser_form.php" class="btn_01 btn">광고주 등록</a>
</div>

<?php
$list = array();
if ($total_count > 0) {
    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        $row['disp_num'] = $total_count - (($page - 1) * $rows) - $i;
        $list[] = $row;
    }
}
?>

<!-- 모바일: 카드 뷰 -->
<div class="bp-project-cards" id="mobile_card_view" style="margin-top:15px;">
    <?php if (count($list) > 0) { ?>
        <?php foreach ($list as $row) { ?>
        <div class="bp-project-card">
            <div class="bp-project-card-head">
                <a href="./advertiser_form.php?id=<?php echo (int) $row['id']; ?>" class="bp-project-title">
                    <?php echo get_text($row['name']); ?>
                </a>
                <span class="bp-status-badge bp-status-badge-<?php echo $row['status'] === 'Y' ? 'published' : 'failed'; ?>">
                    <?php echo $row['status'] === 'Y' ? '사용' : '중지'; ?>
                </span>
            </div>
            <div class="bp-project-meta">
                <span>번호: <?php echo $row['disp_num']; ?></span>
                <span>전화: <?php echo get_text($row['phone']); ?></span>
                <span>지역: <?php echo get_text($row['service_region']); ?></span>
                <span>등록일: <?php echo get_text($row['created_at']); ?></span>
            </div>
            <div class="bp-project-actions">
                <a href="./advertiser_form.php?id=<?php echo (int) $row['id']; ?>" class="btn btn_02">수정</a>
                <a href="./advertiser_delete.php?id=<?php echo (int) $row['id']; ?>&amp;token=<?php echo get_admin_token(); ?>" class="btn btn_01" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
            </div>
        </div>
        <?php } ?>
    <?php } else { ?>
        <div class="bp-empty">등록된 광고주가 없습니다.</div>
    <?php } ?>
</div>

<!-- PC: 테이블 뷰 -->
<div class="tbl_head01 tbl_wrap" id="pc_table_view">
    <table>
        <caption>광고주 목록</caption>
        <thead>
            <tr>
                <th scope="col">번호</th>
                <th scope="col">상호</th>
                <th scope="col">전화</th>
                <th scope="col">서비스 지역</th>
                <th scope="col">상태</th>
                <th scope="col">등록일</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($list) > 0) { ?>
                <?php foreach ($list as $row) { ?>
                    <tr>
                        <td><?php echo $row['disp_num']; ?></td>
                        <td style="text-align:left;"><a href="./advertiser_form.php?id=<?php echo (int)$row['id']; ?>"><strong><?php echo get_text($row['name']); ?></strong></a></td>
                        <td><?php echo get_text($row['phone']); ?></td>
                        <td><?php echo get_text($row['service_region']); ?></td>
                        <td><?php echo $row['status'] === 'Y' ? '사용' : '중지'; ?></td>
                        <td><?php echo get_text($row['created_at']); ?></td>
                        <td>
                            <a href="./advertiser_form.php?id=<?php echo (int)$row['id']; ?>" class="btn btn_02">수정</a>
                            <a href="./advertiser_delete.php?id=<?php echo (int)$row['id']; ?>&amp;token=<?php echo get_admin_token(); ?>" class="btn btn_01" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="7" class="empty_table">등록된 광고주가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, './advertiser_list.php?' . http_build_query(array('search' => $search, 'rows' => $rows))); ?>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
