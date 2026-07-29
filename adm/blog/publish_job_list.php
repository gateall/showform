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

include_once(G5_ADMIN_PATH . '/admin.head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/admin_extend_sf_blog.css">', 0);
?>
<div class="local_desc01 local_desc">
    <p>포스트의 예약 발행 일정을 관리합니다. 생성된 발행 작업은 설정된 시각에 크론 데몬 또는 관리자 수동 조작을 통해 외부 사이트로 전송됩니다.</p>
</div>

<div class="local_sch01 local_sch">
    <form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
    <label for="sfl_status" class="sound_only">상태</label>
    <select name="sfl_status" id="sfl_status">
        <option value="">전체 상태</option>
        <option value="scheduled" <?php echo $sfl_status=='scheduled'?'selected':'';?>>예약됨</option>
        <option value="pending" <?php echo $sfl_status=='pending'?'selected':'';?>>대기중</option>
        <option value="processing" <?php echo $sfl_status=='processing'?'selected':'';?>>처리중</option>
        <option value="succeeded" <?php echo $sfl_status=='succeeded'?'selected':'';?>>성공</option>
        <option value="failed" <?php echo $sfl_status=='failed'?'selected':'';?>>실패</option>
        <option value="cancelled" <?php echo $sfl_status=='cancelled'?'selected':'';?>>취소</option>
    </select>
    
    <label for="sfl_project" class="sound_only">프로젝트</label>
    <select name="sfl_project" id="sfl_project">
        <option value="">전체 프로젝트</option>
        <?php while($prow = sql_fetch_array($projects)){ ?>
        <option value="<?php echo $prow['id'];?>" <?php echo $sfl_project==$prow['id']?'selected':'';?>><?php echo get_text(cut_str($prow['topic'], 20));?></option>
        <?php } ?>
    </select>

    <label for="sfl_site" class="sound_only">사이트</label>
    <select name="sfl_site" id="sfl_site">
        <option value="">전체 사이트</option>
        <?php while($srow = sql_fetch_array($sites)){ ?>
        <option value="<?php echo $srow['id'];?>" <?php echo $sfl_site==$srow['id']?'selected':'';?>><?php echo get_text($srow['name']);?></option>
        <?php } ?>
    </select>

    <label for="sdate" class="sound_only">예약일 시작</label>
    <input type="text" name="sdate" value="<?php echo get_text($sdate); ?>" id="sdate" class="frm_input" size="10" maxlength="10" placeholder="YYYY-MM-DD"> ~
    <label for="edate" class="sound_only">예약일 종료</label>
    <input type="text" name="edate" value="<?php echo get_text($edate); ?>" id="edate" class="frm_input" size="10" maxlength="10" placeholder="YYYY-MM-DD">
    
    <label for="stx" class="sound_only">검색어</label>
    <input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input" size="15" placeholder="포스트 제목 검색">
    <input type="submit" class="btn_submit" value="검색">
    </form>
</div>

<div class="btn_fixed_top">
    <a href="./publish_job_form.php" class="btn_01 btn">예약 등록</a>
</div>

<div class="tbl_head01 tbl_wrap">
    <!-- 모바일 퍼스트 카드 컨테이너 -->
    <div class="mobile-card-list">
        <?php
        if ($total_count > 0) {
            $i = 0;
            while ($row = sql_fetch_array($result)) {
                $status_color = '#666';
                if ($row['status'] == 'succeeded') $status_color = '#008000';
                if ($row['status'] == 'failed') $status_color = '#ff0000';
                if ($row['status'] == 'processing') $status_color = '#ff9900';
                if ($row['status'] == 'pending' || $row['status'] == 'scheduled') $status_color = '#0066cc';
                
                $can_edit = in_array($row['status'], array('scheduled', 'pending'));
                $can_cancel = $can_edit;
                $can_retry = ($row['status'] == 'failed');
        ?>
        <!-- 모바일 카드 -->
        <div class="sf-card-item">
            <div class="sf-card-header">
                <span class="sf-badge" style="background-color:<?php echo $status_color; ?>"><?php echo strtoupper($row['status']); ?></span>
                <span class="sf-card-title"><?php echo get_text(cut_str($row['post_title'], 40)); ?></span>
            </div>
            <div class="sf-card-body">
                <p><strong>작업ID:</strong> <?php echo $row['id']; ?></p>
                <p><strong>사이트:</strong> <?php echo get_text($row['site_name']); ?></p>
                <p><strong>일시:</strong> <?php echo $row['scheduled_at'] ? substr($row['scheduled_at'], 0, 16) : '-'; ?></p>
                <p><strong>방식:</strong> <?php echo $row['schedule_type']; ?></p>
                <p><strong>재시도:</strong> <?php echo $row['attempt_count']; ?> / <?php echo $row['max_retries']; ?></p>
                <?php if($row['status'] == 'failed' && $row['last_error']) { ?>
                    <p style="color:red; word-break:break-all;"><strong>오류:</strong> <?php echo get_text(cut_str($row['last_error'], 100)); ?></p>
                <?php } ?>
            </div>
            <div class="sf-card-footer">
                <a href="./publish_job_view.php?id=<?php echo $row['id']; ?>" class="btn_02 btn">상세</a>
                <?php if ($can_edit) { ?>
                    <a href="./publish_job_form.php?id=<?php echo $row['id']; ?>&w=u" class="btn_03 btn">수정</a>
                    <button type="button" class="btn_02 btn btn-cancel" data-id="<?php echo $row['id']; ?>">취소</button>
                <?php } ?>
                <?php if ($can_retry) { ?>
                    <button type="button" class="btn_03 btn btn-retry" data-id="<?php echo $row['id']; ?>">재시도</button>
                <?php } ?>
            </div>
        </div>
        
        <!-- PC 테이블 Row (숨김 처리됨: CSS 미디어쿼리로 제어) -->
        <div class="sf-table-row">
            <table style="width:100%;">
            <?php if($i==0) { ?>
            <thead>
            <tr>
                <th>작업ID</th>
                <th>포스트 제목</th>
                <th>프로젝트</th>
                <th>대상 사이트</th>
                <th>예약 방식</th>
                <th>예약 일시</th>
                <th>상태</th>
                <th>재시도</th>
                <th>관리</th>
            </tr>
            </thead>
            <?php } ?>
            <tbody>
            <tr>
                <td style="width:60px; text-align:center;"><?php echo $row['id']; ?></td>
                <td><?php echo get_text(cut_str($row['post_title'], 40)); ?></td>
                <td><?php echo get_text(cut_str($row['project_topic'], 20)); ?></td>
                <td><?php echo get_text($row['site_name']); ?></td>
                <td style="text-align:center;"><?php echo $row['schedule_type']; ?></td>
                <td style="text-align:center;"><?php echo $row['scheduled_at'] ? substr($row['scheduled_at'], 0, 16) : '-'; ?></td>
                <td style="text-align:center;"><span style="color:<?php echo $status_color; ?>; font-weight:bold;"><?php echo strtoupper($row['status']); ?></span></td>
                <td style="text-align:center;"><?php echo $row['attempt_count']; ?>/<?php echo $row['max_retries']; ?></td>
                <td style="text-align:center;">
                    <a href="./publish_job_view.php?id=<?php echo $row['id']; ?>" class="btn_02 btn" style="padding:2px 5px;">상세</a>
                    <?php if ($can_edit) { ?>
                        <a href="./publish_job_form.php?id=<?php echo $row['id']; ?>&w=u" class="btn_03 btn" style="padding:2px 5px;">수정</a>
                        <button type="button" class="btn_02 btn btn-cancel" data-id="<?php echo $row['id']; ?>" style="padding:2px 5px;">취소</button>
                    <?php } ?>
                    <?php if ($can_retry) { ?>
                        <button type="button" class="btn_03 btn btn-retry" data-id="<?php echo $row['id']; ?>" style="padding:2px 5px;">재시도</button>
                    <?php } ?>
                </td>
            </tr>
            </tbody>
            </table>
        </div>
        <?php
                $i++;
            }
        } else {
            echo '<div class="empty_table" style="text-align:center; padding:50px;">자료가 없습니다.</div>';
        }
        ?>
    </div>
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
        $form.attr('action', './publish_job_update.php');
        $form.attr('method', 'post');
        $form.append('<input type="hidden" name="w" value="'+action+'">');
        $form.append('<input type="hidden" name="id" value="'+id+'">');
        $form.append('<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">');
        $form.appendTo('body').submit();
    }
});
</script>

<style>
/* CSS 분기 처리 (기본 PC 테이블 레이아웃을 모바일 카드와 분리) */
@media (max-width: 768px) {
    .sf-table-row { display: none; }
    .sf-card-item {
        border: 1px solid #ddd;
        border-radius: 8px;
        margin-bottom: 15px;
        padding: 15px;
        background: #fff;
    }
    .sf-card-header { margin-bottom: 10px; }
    .sf-badge {
        color: #fff;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 0.85em;
        margin-right: 8px;
        display: inline-block;
    }
    .sf-card-title { font-weight: bold; font-size: 1.1em; }
    .sf-card-body p { margin: 5px 0; color: #555; }
    .sf-card-footer { margin-top: 15px; text-align: right; }
    .sf-card-footer .btn { min-height: 48px; min-width: 60px; line-height: 48px; padding: 0 15px; }
}
@media (min-width: 769px) {
    .sf-card-item { display: none; }
    .sf-table-row table { border-collapse: collapse; width: 100%; }
    .sf-table-row th, .sf-table-row td {
        border: 1px solid #ddd;
        padding: 8px;
    }
    .sf-table-row th { background: #f5f5f5; text-align: center; font-weight: bold; }
}
</style>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>
