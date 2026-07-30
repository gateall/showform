<?php
include_once('./_common.php');

$sub_menu = '360600';
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '키워드 관리';

$projects_table = bp_table('content_projects');
$keywords_table = bp_table('content_keywords');
$adv_table = bp_table('advertisers');

// 필터 및 검색 파라미터 처리
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$rows = 20;

$where = array("k.keyword_group = 'primary'"); // 메인 키워드만 표시
if ($project_id > 0) {
    $where[] = "k.project_id = '{$project_id}'";
}
if ($status === 'Y' || $status === 'N') {
    $where[] = "k.status = '{$status}'";
}
if ($stx !== '') {
    $where[] = "k.keyword like '%" . sql_real_escape_string($stx) . "%'";
}
$where_sql = ' where ' . implode(' and ', $where);

// 전체 레코드 수
$sql_count = " select count(*) as cnt 
               from {$keywords_table} k 
               {$where_sql} ";
$row_count = sql_fetch($sql_count);
$total_count = (int)$row_count['cnt'];
$total_page  = ceil($total_count / $rows);
$offset = ($page - 1) * $rows;

// 목록 조회
$sql = " select k.*, p.topic as project_topic, a.name as advertiser_name 
         from {$keywords_table} k
         left join {$projects_table} p on p.id = k.project_id
         left join {$adv_table} a on a.id = p.advertiser_id
         {$where_sql}
         order by k.id desc
         limit {$offset}, {$rows} ";
$result = sql_query($sql);

// 프로젝트 목록 (필터용)
$sql_projects = " select id, topic from {$projects_table} order by id desc ";
$res_projects = sql_query($sql_projects);

// GET 파라미터 유지를 위한 쿼리스트링 생성
$qstr = '';
if ($project_id > 0) $qstr .= '&amp;project_id=' . $project_id;
if ($status !== '') $qstr .= '&amp;status=' . urlencode($status);
if ($stx !== '') $qstr .= '&amp;stx=' . urlencode($stx);
$qstr_page = $qstr . '&amp;page=';

include_once(__DIR__ . '/../layout/header.php');
?>
<style>
/* 폼 요소 반응형 유지 */
@media (max-width: 768px) {
    .local_sch select, .local_sch input[type="text"], .local_sch .btn { min-height: 48px; font-size: 16px; margin-bottom: 5px; box-sizing: border-box; width: 100%; display: block; }
}
.local_sch { display: flex; flex-wrap: wrap; gap: 5px; align-items: center; }
</style>

<div class="local_desc01 local_desc">
    <p>콘텐츠 프로젝트의 메인 키워드를 전역적으로 관리합니다. 사용 상태에 따라 포스팅 생성 대상 여부가 결정됩니다.</p>
</div>

<details class="bp-filter-wrap" <?php echo ($project_id || $status !== '' || $stx !== '') ? 'open' : ''; ?>>
    <summary class="bp-filter-summary">검색·필터</summary>
    <form method="get" class="bp-filter-form">
        <div class="bp-filter-row">
            <label>
                <span>프로젝트</span>
                <select name="project_id" onchange="this.form.submit();">
                    <option value="0">전체 프로젝트</option>
                    <?php while ($prow = sql_fetch_array($res_projects)) { ?>
                        <option value="<?php echo (int)$prow['id']; ?>" <?php echo $project_id === (int)$prow['id'] ? 'selected' : ''; ?>><?php echo get_text($prow['topic']); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>
                <span>상태</span>
                <select name="status" onchange="this.form.submit();">
                    <option value="">전체 상태</option>
                    <option value="Y" <?php echo $status === 'Y' ? 'selected' : ''; ?>>사용(활성)</option>
                    <option value="N" <?php echo $status === 'N' ? 'selected' : ''; ?>>미사용(비활성)</option>
                </select>
            </label>
            <label>
                <span>키워드</span>
                <input type="text" name="stx" value="<?php echo get_text($stx); ?>" placeholder="메인 키워드 검색" class="frm_input">
            </label>
            <div class="bp-filter-actions">
                <button type="submit" class="btn_submit btn">검색</button>
                <a href="./keyword_list.php" class="btn btn_02">초기화</a>
            </div>
        </div>
    </form>
</details>

<div class="btn_fixed_top">
    <a href=G5_ADMIN_URL . "/blog/keyword_form.php" class="btn btn_01">키워드 신규 등록</a>
</div>

<!-- PC 테이블 (769px 이상) -->
<div class="tbl_head01 tbl_wrap" id="pc_table_view" style="margin-top:15px;">
    <table>
        <caption>키워드 관리 목록</caption>
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">프로젝트 (주제)</th>
                <th scope="col">광고주</th>
                <th scope="col">메인 키워드</th>
                <th scope="col">상태</th>
                <th scope="col">등록일</th>
                <th scope="col">수정일</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($total_count > 0) {
                while ($row = sql_fetch_array($result)) {
            ?>
                <tr>
                    <td><?php echo (int)$row['id']; ?></td>
                    <td style="text-align:left;"><?php echo get_text($row['project_topic']); ?></td>
                    <td><?php echo get_text($row['advertiser_name']); ?></td>
                    <td style="text-align:left;"><strong><?php echo get_text($row['keyword']); ?></strong></td>
                    <td><?php echo $row['status'] === 'Y' ? '활성' : '<span style="color:#c00;">비활성</span>'; ?></td>
                    <td><?php echo get_text($row['created_at']); ?></td>
                    <td><?php echo get_text($row['updated_at']); ?></td>
                    <td>
                        <a href=G5_ADMIN_URL . "/blog/keyword_form.php?w=u&amp;id=<?php echo (int)$row['id']; ?><?php echo $qstr; ?>" class="btn btn_02">수정</a>
                        <a href=G5_ADMIN_URL . "/blog/keyword_update.php?mode=delete&amp;id=<?php echo (int)$row['id']; ?>&amp;token=<?php echo get_admin_token(); ?><?php echo $qstr; ?>" class="btn btn_01" onclick="return confirm('이 키워드를 삭제하시겠습니까?');">삭제</a>
                    </td>
                </tr>
            <?php 
                }
            } else { ?>
                <tr><td colspan="8" class="empty_table">등록된 키워드가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<!-- 모바일 카드 목록 (768px 이하) -->
<div class="bp-project-cards" id="mobile_card_view" style="margin-top:15px;">
    <?php if ($total_count > 0) { 
        // 결과셋 포인터 리셋
        sql_data_seek($result, 0);
        while ($row = sql_fetch_array($result)) {
    ?>
    <div class="bp-project-card">
        <div class="bp-project-card-head">
            <span class="bp-project-title"><strong><?php echo get_text($row['keyword']); ?></strong></span>
            <span class="bp-status-badge"><?php echo $row['status'] === 'Y' ? '활성' : '<span style="color:#c00;">비활성</span>'; ?></span>
        </div>
        <div class="bp-project-meta">
            <span>ID: <?php echo (int)$row['id']; ?></span>
            <span>프로젝트: <?php echo get_text($row['project_topic']); ?></span>
            <span>광고주: <?php echo get_text($row['advertiser_name']); ?></span>
            <span>등록일: <?php echo get_text($row['created_at']); ?></span>
        </div>
        <div class="bp-project-actions">
            <a href=G5_ADMIN_URL . "/blog/keyword_form.php?w=u&amp;id=<?php echo (int)$row['id']; ?><?php echo $qstr; ?>" class="btn btn_02">수정</a>
            <a href=G5_ADMIN_URL . "/blog/keyword_update.php?mode=delete&amp;id=<?php echo (int)$row['id']; ?>&amp;token=<?php echo get_admin_token(); ?><?php echo $qstr; ?>" class="btn btn_01" onclick="return confirm('이 키워드를 삭제하시겠습니까?');">삭제</a>
        </div>
    </div>
    <?php } } else { ?>
    <div class="mobile-card" style="text-align:center; padding: 30px 15px;">등록된 키워드가 없습니다.</div>
    <?php } ?>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['SCRIPT_NAME'].'?'.$qstr_page); ?>

<?php
include_once(__DIR__ . '/../layout/footer.php');
