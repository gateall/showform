<?php
include_once('./_common.php');

$sub_menu = '361030';
auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '콘텐츠 성과 기록';

$tbl_perf = bp_table('post_performance');
$tbl_targets = bp_table('post_targets');
$tbl_posts = bp_table('posts');
$tbl_projects = bp_table('content_projects');
$tbl_adv = bp_table('advertisers');
$tbl_sites = bp_table('sites');

// 폼 저장 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['act']) && $_POST['act'] === 'update_performance') {
    check_admin_token();
    
    $target_ids = $_POST['target_id'];
    $views = $_POST['view_count'];
    $likes = $_POST['like_count'];
    $comments = $_POST['comment_count'];
    $shares = $_POST['share_count'];
    
    $updated = 0;
    if (is_array($target_ids)) {
        foreach ($target_ids as $i => $t_id) {
            $t_id = (int)$t_id;
            $v = (int)$views[$i];
            $l = (int)$likes[$i];
            $c = (int)$comments[$i];
            $s = (int)$shares[$i];
            
            $sql = " INSERT INTO {$tbl_perf} (post_target_id, view_count, like_count, comment_count, share_count, last_synced_at, created_at, updated_at)
                     VALUES ('{$t_id}', '{$v}', '{$l}', '{$c}', '{$s}', NOW(), NOW(), NOW())
                     ON DUPLICATE KEY UPDATE 
                        view_count = '{$v}', like_count = '{$l}', comment_count = '{$c}', share_count = '{$s}', last_synced_at = NOW() ";
            sql_query($sql);
            $updated++;
        }
    }
    alert("총 {$updated}건의 성과 기록이 업데이트 되었습니다.", './report_performance.php?' . $_SERVER['QUERY_STRING']);
}

$advertiser_id = isset($_GET['advertiser_id']) ? (int) $_GET['advertiser_id'] : 0;
$site_id = isset($_GET['site_id']) ? (int) $_GET['site_id'] : 0;
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';

$where = array();
$where[] = " t.published_url != '' ";
if ($advertiser_id) $where[] = " prj.advertiser_id = '{$advertiser_id}' ";
if ($site_id) $where[] = " t.site_id = '{$site_id}' ";
if ($stx) $where[] = " po.title like '%" . sql_real_escape_string($stx) . "%' ";

$where_sql = $where ? ' where ' . implode(' and ', $where) : '';

$sql_common = " from {$tbl_targets} t
                inner join {$tbl_posts} po on po.id = t.post_id
                inner join {$tbl_projects} prj on prj.id = po.project_id
                left join {$tbl_adv} a on a.id = prj.advertiser_id
                left join {$tbl_sites} s on s.id = t.site_id
                left join {$tbl_perf} perf on perf.post_target_id = t.id
                {$where_sql} ";

$row = sql_fetch(" select count(*) as cnt {$sql_common} ");
$total_count = (int)$row['cnt'];

$rows = 30;
$total_page  = ceil($total_count / $rows);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) { $page = 1; }
$from_record = ($page - 1) * $rows;

$sql = " select t.id as target_id, t.published_url, po.title as post_title,
                a.name as advertiser_name, s.name as site_name, s.platform,
                perf.view_count, perf.like_count, perf.comment_count, perf.share_count, perf.last_synced_at
         {$sql_common}
         order by t.id desc
         limit {$from_record}, {$rows} ";
$res = sql_query($sql);
$list = array();
while ($r = sql_fetch_array($res)) {
    $list[] = $r;
}

$advertisers = sql_query(" select id, name from {$tbl_adv} order by name asc ");
$sites = sql_query(" select id, name from {$tbl_sites} order by name asc ");

include_once(__DIR__ . '/../layout/header.php');
add_stylesheet('<link rel="stylesheet" href="' . G5_ADMIN_URL . '/css/admin_extend_sf_blog.css">', 0);
?>
<div class="local_desc01 local_desc">
    <p>발행 완료된 콘텐츠의 실제 성과(조회수, 공감, 댓글 등)를 기록합니다. 현재는 수동 기입을 지원하며, 향후 외부 애널리틱스 연동 시 자동 동기화될 예정입니다.</p>
</div>

<details class="bp-filter-wrap" open>
    <summary class="bp-filter-summary">조회 조건</summary>
    <form method="get" class="bp-filter-form">
        <div class="bp-filter-row">
            <label>광고주
                <select name="advertiser_id">
                    <option value="0">전체</option>
                    <?php while ($a = sql_fetch_array($advertisers)) { ?>
                        <option value="<?php echo (int) $a['id']; ?>" <?php echo $advertiser_id === (int) $a['id'] ? 'selected' : ''; ?>><?php echo get_text($a['name']); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>사이트
                <select name="site_id">
                    <option value="0">전체</option>
                    <?php while ($s = sql_fetch_array($sites)) { ?>
                        <option value="<?php echo (int) $s['id']; ?>" <?php echo $site_id === (int) $s['id'] ? 'selected' : ''; ?>><?php echo get_text($s['name']); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>포스트 제목
                <input type="text" name="stx" value="<?php echo get_text($stx); ?>" class="frm_input">
            </label>
        </div>
        <div class="bp-filter-actions">
            <button type="submit" class="btn btn_submit btn">조회</button>
            <button type="button" class="btn btn_01" onclick="window.location.href='./export_excel.php?type=performance&advertiser_id=<?php echo $advertiser_id; ?>&site_id=<?php echo $site_id; ?>&stx=<?php echo urlencode($stx); ?>'">엑셀 다운로드</button>
            <a href="./report_performance.php" class="btn btn_02">초기화</a>
        </div>
    </form>
</details>

<form method="post" action="./report_performance.php?<?php echo $_SERVER['QUERY_STRING']; ?>">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <input type="hidden" name="act" value="update_performance">
    
    <div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
        <table>
            <caption>콘텐츠 성과 목록 (총 <?php echo number_format($total_count); ?>건)</caption>
            <thead>
                <tr>
                    <th scope="col">포스트 정보</th>
                    <th scope="col">광고주/사이트</th>
                    <th scope="col">발행 외부 URL</th>
                    <th scope="col">조회수</th>
                    <th scope="col">공감(좋아요)</th>
                    <th scope="col">댓글</th>
                    <th scope="col">공유</th>
                    <th scope="col">최근 동기화</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($list)) { ?>
                    <?php foreach ($list as $r) { ?>
                    <tr>
                        <td style="text-align:left;">
                            <input type="hidden" name="target_id[]" value="<?php echo (int)$r['target_id']; ?>">
                            <?php echo get_text($r['post_title']); ?>
                        </td>
                        <td><?php echo get_text($r['advertiser_name']); ?><br><span style="color:#888;font-size:11px;"><?php echo get_text($r['site_name']); ?></span></td>
                        <td><a href="<?php echo get_text($r['published_url']); ?>" target="_blank">링크 이동</a></td>
                        <td><input type="number" name="view_count[]" value="<?php echo (int)$r['view_count']; ?>" class="frm_input" size="5" style="width:70px;text-align:right;"></td>
                        <td><input type="number" name="like_count[]" value="<?php echo (int)$r['like_count']; ?>" class="frm_input" size="5" style="width:70px;text-align:right;"></td>
                        <td><input type="number" name="comment_count[]" value="<?php echo (int)$r['comment_count']; ?>" class="frm_input" size="5" style="width:70px;text-align:right;"></td>
                        <td><input type="number" name="share_count[]" value="<?php echo (int)$r['share_count']; ?>" class="frm_input" size="5" style="width:70px;text-align:right;"></td>
                        <td><?php echo $r['last_synced_at'] ? get_text($r['last_synced_at']) : '미기록'; ?></td>
                    </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr><td colspan="8" class="empty_table">발행 완료된 대상이 없습니다.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
    
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="현재 페이지 성과 저장" class="btn_submit btn">
    </div>
</form>

<?php
echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$_SERVER['QUERY_STRING'].'&amp;page=');
include_once(__DIR__ . '/../layout/footer.php');
