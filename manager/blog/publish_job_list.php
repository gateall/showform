<?php
$sub_menu = '360700';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '예약 발행 관리';

$tbl_jobs = bp_table('publish_jobs');
$tbl_targets = bp_table('post_targets');
$tbl_posts = bp_table('posts');
$tbl_sites = bp_table('sites');
$tbl_projects = bp_table('content_projects');

$sql_search = " where 1=1 ";
$qstr = '';

$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
if ($stx) {
    $stx_safe = sql_real_escape_string($stx);
    $sql_search .= " and p.title like '%{$stx_safe}%' ";
    $qstr .= '&stx='.urlencode($stx);
}

$sfl_status = isset($_GET['sfl_status']) ? trim($_GET['sfl_status']) : '';
if ($sfl_status) {
    $sfl_status_safe = sql_real_escape_string($sfl_status);
    $sql_search .= " and j.status = '{$sfl_status_safe}' ";
    $qstr .= '&sfl_status='.urlencode($sfl_status);
}

$sfl_project = isset($_GET['sfl_project']) ? (int)$_GET['sfl_project'] : 0;
if ($sfl_project > 0) {
    $sql_search .= " and p.project_id = '{$sfl_project}' ";
    $qstr .= '&sfl_project='.$sfl_project;
}

$sfl_site = isset($_GET['sfl_site']) ? (int)$_GET['sfl_site'] : 0;
if ($sfl_site > 0) {
    $sql_search .= " and t.site_id = '{$sfl_site}' ";
    $qstr .= '&sfl_site='.$sfl_site;
}

$sdate = isset($_GET['sdate']) ? trim($_GET['sdate']) : '';
$edate = isset($_GET['edate']) ? trim($_GET['edate']) : '';
if ($sdate && $edate) {
    $sdate_safe = sql_real_escape_string($sdate);
    $edate_safe = sql_real_escape_string($edate);
    $sql_search .= " and j.scheduled_at between '{$sdate_safe} 00:00:00' and '{$edate_safe} 23:59:59' ";
    $qstr .= '&sdate='.urlencode($sdate).'&edate='.urlencode($edate);
}

$sql_common = " from {$tbl_jobs} j 
                join {$tbl_targets} t on j.post_target_id = t.id 
                join {$tbl_posts} p on t.post_id = p.id 
                join {$tbl_sites} s on t.site_id = s.id 
                join {$tbl_projects} prj on p.project_id = prj.id 
                {$sql_search} ";

$row = sql_fetch(" select count(*) as cnt " . $sql_common);
$total_count = $row['cnt'];

$rows = isset($config['cf_page_rows']) ? $config['cf_page_rows'] : 20;
$total_page  = ceil($total_count / $rows);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select j.*, t.site_id, p.title as post_title, p.project_id, s.name as site_name, prj.topic as project_topic 
         {$sql_common} 
         order by j.id desc 
         limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$projects = sql_query(" select id, topic from {$tbl_projects} order by id desc ");
$sites = sql_query(" select id, name from {$tbl_sites} order by id desc ");

include_once(__DIR__ . '/../layout/header.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/admin_extend_sf_blog.css">', 0);
?>
<div class="local_desc01 local_desc">
    <p>포스트의 예약 발행 일정을 관리합니다. 생성된 발행 작업은 설정된 시각에 크론 데몬 또는 관리자 수동 조작을 통해 외부 사이트로 전송됩니다.</p>
</div>

<details class="bp-filter-wrap" <?php echo ($sfl_status || $sfl_project || $sfl_site || $sdate || $edate || $stx) ? 'open' : ''; ?>>
    <summary class="bp-filter-summary">검색·필터</summary>
    <form name="fsearch" id="fsearch" class="bp-filter-form" method="get">
        <div class="bp-filter-row">
            <label>상태
                <select name="sfl_status" id="sfl_status">
                    <option value="">전체 상태</option>
                    <option value="scheduled" <?php echo $sfl_status=='scheduled'?'selected':'';?>>예약됨</option>
                    <option value="pending" <?php echo $sfl_status=='pending'?'selected':'';?>>대기중</option>
                    <option value="processing" <?php echo $sfl_status=='processing'?'selected':'';?>>처리중</option>
                    <option value="succeeded" <?php echo $sfl_status=='succeeded'?'selected':'';?>>성공</option>
                    <option value="failed" <?php echo $sfl_status=='failed'?'selected':'';?>>실패</option>
                    <option value="cancelled" <?php echo $sfl_status=='cancelled'?'selected':'';?>>취소</option>
                </select>
            </label>
            <label>프로젝트
                <select name="sfl_project" id="sfl_project">
                    <option value="">전체 프로젝트</option>
                    <?php while($prow = sql_fetch_array($projects)){ ?>
                    <option value="<?php echo $prow['id'];?>" <?php echo $sfl_project==$prow['id']?'selected':'';?>><?php echo get_text(cut_str($prow['topic'], 20));?></option>
                    <?php } ?>
                </select>
            </label>
            <label>사이트
                <select name="sfl_site" id="sfl_site">
                    <option value="">전체 사이트</option>
                    <?php while($srow = sql_fetch_array($sites)){ ?>
                    <option value="<?php echo $srow['id'];?>" <?php echo $sfl_site==$srow['id']?'selected':'';?>><?php echo get_text($srow['name']);?></option>
                    <?php } ?>
                </select>
            </label>
        </div>
        <div class="bp-filter-row">
            <label>예약일 시작<input type="text" name="sdate" value="<?php echo get_text($sdate); ?>" id="sdate" class="frm_input" size="10" maxlength="10" placeholder="YYYY-MM-DD"></label>
            <label>예약일 종료<input type="text" name="edate" value="<?php echo get_text($edate); ?>" id="edate" class="frm_input" size="10" maxlength="10" placeholder="YYYY-MM-DD"></label>
            <label>검색어<input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input" size="15" placeholder="포스트 제목 검색"></label>
        </div>
        <div class="bp-filter-actions">
            <button type="submit" class="btn btn_submit btn">검색</button>
            <a href="./publish_job_list.php" class="btn btn_02">초기화</a>
        </div>
    </form>
</details>

<div class="btn_fixed_top">
    <a href="./publish_job_form.php" class="btn_01 btn">예약 등록</a>
</div>

<?php
$list = array();
if ($total_count > 0) {
    while ($row = sql_fetch_array($result)) {
        $status_color = '#666';
        if ($row['status'] == 'succeeded') $status_color = '#008000';
        if ($row['status'] == 'failed') $status_color = '#ff0000';
        if ($row['status'] == 'processing') $status_color = '#ff9900';
        if ($row['status'] == 'pending' || $row['status'] == 'scheduled') $status_color = '#0066cc';
        
        $can_edit = in_array($row['status'], array('scheduled', 'pending'));
        $can_cancel = $can_edit;
        $can_retry = ($row['status'] == 'failed');

        $row['status_color'] = $status_color;
        $row['can_edit'] = $can_edit;
        $row['can_cancel'] = $can_cancel;
        $row['can_retry'] = $can_retry;
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
                <span class="bp-status-badge" style="background-color:<?php echo $row['status_color']; ?>"><?php echo strtoupper($row['status']); ?></span>
                <span class="bp-project-title"><?php echo get_text(cut_str($row['post_title'], 40)); ?></span>
            </div>
            <div class="bp-project-meta">
                <span>작업ID: <?php echo $row['id']; ?></span>
                <span>사이트: <?php echo get_text($row['site_name']); ?></span>
                <span>일시: <?php echo $row['scheduled_at'] ? substr($row['scheduled_at'], 0, 16) : '-'; ?></span>
                <span>방식: <?php echo $row['schedule_type']; ?></span>
                <span>재시도: <?php echo $row['attempt_count']; ?> / <?php echo $row['max_retries']; ?></span>
                <?php if($row['status'] == 'failed' && $row['last_error']) { ?>
                    <span style="color:red; word-break:break-all;">오류: <?php echo get_text(cut_str($row['last_error'], 100)); ?></span>
                <?php } ?>
            </div>
            <div class="bp-project-actions">
                <a href=G5_ADMIN_URL . "/blog/publish_job_view.php?id=<?php echo $row['id']; ?>" class="btn_02 btn">상세</a>
                <?php if ($row['can_edit']) { ?>
                    <a href="./publish_job_form.php?id=<?php echo $row['id']; ?>&w=u" class="btn_03 btn">수정</a>
                    <button type="button" class="btn_02 btn btn-cancel" data-id="<?php echo $row['id']; ?>">취소</button>
                <?php } ?>
                <?php if ($row['can_retry']) { ?>
                    <button type="button" class="btn_03 btn btn-retry" data-id="<?php echo $row['id']; ?>">재시도</button>
                <?php } ?>
            </div>
        </div>
        <?php } ?>
    <?php } else { ?>
        <div class="bp-empty">자료가 없습니다.</div>
    <?php } ?>
</div>

<!-- PC: 테이블 뷰 -->
<div class="tbl_head01 tbl_wrap" id="pc_table_view" style="margin-top:15px;">
    <table>
        <thead>
            <tr>
                <th scope="col" style="width:60px;">작업ID</th>
                <th scope="col">포스트 제목</th>
                <th scope="col">프로젝트</th>
                <th scope="col" style="width:120px;">대상 사이트</th>
                <th scope="col" style="width:100px;">예약 방식</th>
                <th scope="col" style="width:140px;">예약 일시</th>
                <th scope="col" style="width:100px;">상태</th>
                <th scope="col" style="width:80px;">재시도</th>
                <th scope="col" style="width:150px;">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($list) > 0) { ?>
                <?php foreach ($list as $row) { ?>
                <tr>
                    <td style="text-align:center;"><?php echo $row['id']; ?></td>
                    <td><?php echo get_text(cut_str($row['post_title'], 40)); ?></td>
                    <td><?php echo get_text(cut_str($row['project_topic'], 20)); ?></td>
                    <td style="text-align:center;"><?php echo get_text($row['site_name']); ?></td>
                    <td style="text-align:center;"><?php echo $row['schedule_type']; ?></td>
                    <td style="text-align:center;"><?php echo $row['scheduled_at'] ? substr($row['scheduled_at'], 0, 16) : '-'; ?></td>
                    <td style="text-align:center;"><span style="color:<?php echo $row['status_color']; ?>; font-weight:bold;"><?php echo strtoupper($row['status']); ?></span></td>
                    <td style="text-align:center;"><?php echo $row['attempt_count']; ?>/<?php echo $row['max_retries']; ?></td>
                    <td style="text-align:center;">
                        <a href=G5_ADMIN_URL . "/blog/publish_job_view.php?id=<?php echo $row['id']; ?>" class="btn_02 btn" style="padding:2px 5px;">상세</a>
                        <?php if ($row['can_edit']) { ?>
                            <a href="./publish_job_form.php?id=<?php echo $row['id']; ?>&w=u" class="btn_03 btn" style="padding:2px 5px;">수정</a>
                            <button type="button" class="btn_02 btn btn-cancel" data-id="<?php echo $row['id']; ?>" style="padding:2px 5px;">취소</button>
                        <?php } ?>
                        <?php if ($row['can_retry']) { ?>
                            <button type="button" class="btn_03 btn btn-retry" data-id="<?php echo $row['id']; ?>" style="padding:2px 5px;">재시도</button>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="9" class="empty_table">자료가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

<script>
$(function() {
    $('.btn-cancel').click(function() {
        if (!confirm('이 예약 작업을 취소하시겠습니까?')) return false;
        var id = $(this).data('id');
        post_action('cancel', id);
    });
    
    $('.btn-retry').click(function() {
        if (!confirm('이 실패 작업을 재시도 상태로 변경하시겠습니까?')) return false;
        var id = $(this).data('id');
        post_action('retry', id);
    });
    
    function post_action(action, id) {
        var $form = $('<form></form>');
        $form.attr('action', SF_MANAGER_URL . '/blog/publish_job_update.php');
        $form.attr('method', 'post');
        $form.append('<input type="hidden" name="w" value="'+action+'">');
        $form.append('<input type="hidden" name="id" value="'+id+'">');
        $form.append('<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">');
        $form.appendTo('body').submit();
    }
});
</script>

<?php include_once(__DIR__ . '/../layout/footer.php'); ?>
