<?php
$sub_menu = '360700';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$tbl_jobs = bp_table('publish_jobs');
$tbl_targets = bp_table('post_targets');
$tbl_posts = bp_table('posts');
$tbl_sites = bp_table('sites');
$tbl_projects = bp_table('content_projects');
$tbl_logs = bp_table('content_activity_logs');

$sql = " select j.*, t.site_id, t.external_post_id, t.published_url, 
                p.title as post_title, p.project_id, 
                s.name as site_name, s.platform, 
                prj.topic as project_topic 
         from {$tbl_jobs} j 
         join {$tbl_targets} t on j.post_target_id = t.id 
         join {$tbl_posts} p on t.post_id = p.id 
         join {$tbl_sites} s on t.site_id = s.id 
         join {$tbl_projects} prj on p.project_id = prj.id 
         where j.id = '{$id}' ";
$job = sql_fetch($sql);

if (!$job) {
    alert('존재하지 않는 작업입니다.');
}

$g5['title'] = '예약 발행 상세';
include_once(G5_ADMIN_PATH . '/admin.head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/admin_extend_sf_blog.css">', 0);
?>
<div class="local_desc01 local_desc">
    <p>작업 ID: <strong><?php echo $job['id']; ?></strong> 의 상세 정보 및 이력입니다.</p>
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>기본 정보</caption>
        <tbody>
            <tr>
                <th scope="row">포스트 제목</th>
                <td colspan="3"><strong><?php echo get_text($job['post_title']); ?></strong></td>
            </tr>
            <tr>
                <th scope="row">콘텐츠 프로젝트</th>
                <td><?php echo get_text($job['project_topic']); ?></td>
                <th scope="row">발행 대상 (사이트)</th>
                <td><?php echo get_text($job['site_name']); ?> (<?php echo get_text($job['platform']); ?>)</td>
            </tr>
            <tr>
                <th scope="row">예약 방식</th>
                <td><?php echo $job['schedule_type']; ?></td>
                <th scope="row">예약 기간</th>
                <td>
                    <?php 
                    if ($job['schedule_type'] == 'random') {
                        echo substr($job['schedule_start_at'],0,16).' ~ '.substr($job['schedule_end_at'],0,16);
                    } else {
                        echo '-';
                    }
                    ?>
                </td>
            </tr>
            <tr>
                <th scope="row">확정 예약 일시</th>
                <td><strong><?php echo $job['scheduled_at'] ? substr($job['scheduled_at'],0,16) : '-'; ?></strong></td>
                <th scope="row">우선순위</th>
                <td><?php echo (int)$job['priority']; ?></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="tbl_frm01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>처리 정보</caption>
        <tbody>
            <tr>
                <th scope="row">현재 상태</th>
                <td colspan="3">
                    <span style="font-size:1.2em; font-weight:bold; color:<?php 
                        if($job['status']=='published') echo '#008000';
                        else if($job['status']=='failed') echo '#ff0000';
                        else if($job['status']=='processing') echo '#ff9900';
                        else echo '#0066cc';
                    ?>"><?php echo strtoupper($job['status']); ?></span>
                </td>
            </tr>
            <tr>
                <th scope="row">재시도 횟수</th>
                <td><?php echo $job['attempt_count']; ?> / <?php echo $job['max_retries']; ?></td>
                <th scope="row">처리 시작 시각</th>
                <td><?php echo $job['claimed_at'] ? $job['claimed_at'] : '-'; ?></td>
            </tr>
            <tr>
                <th scope="row">완료 시각</th>
                <td><?php echo $job['completed_at'] ? $job['completed_at'] : '-'; ?></td>
                <th scope="row">외부 게시물 URL</th>
                <td>
                    <?php if ($job['published_url']) { ?>
                        <?php if ($job['platform'] === 'naver') { ?>
                            <a href="<?php echo get_text($job['published_url']); ?>" class="btn btn_03">네이버 패키지 다운로드</a>
                        <?php } else { ?>
                            <a href="<?php echo get_text($job['published_url']); ?>" target="_blank"><?php echo get_text($job['published_url']); ?></a>
                        <?php } ?>
                        (ID: <?php echo get_text($job['external_post_id']); ?>)
                    <?php } else { ?>
                        -
                    <?php } ?>
                </td>
            </tr>
            <tr>
                <th scope="row">최근 오류</th>
                <td colspan="3" style="color:red; word-break:break-all;">
                    <?php echo nl2br(get_text($job['last_error'])); ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="tbl_frm01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>변경 기록</caption>
        <tbody>
            <tr>
                <th scope="row">등록자 / 등록일</th>
                <td><?php echo get_text($job['created_by']); ?> / <?php echo $job['created_at']; ?></td>
            </tr>
            <tr>
                <th scope="row">수정자 / 수정일</th>
                <td><?php echo get_text($job['updated_by']); ?> / <?php echo $job['updated_at'] ? $job['updated_at'] : '-'; ?></td>
            </tr>
            <tr>
                <th scope="row">취소자 / 취소일</th>
                <td><?php echo get_text($job['cancelled_by']); ?> / <?php echo $job['cancelled_at'] ? $job['cancelled_at'] : '-'; ?></td>
            </tr>
            <tr>
                <th scope="row">관리자 메모</th>
                <td><?php echo nl2br(get_text($job['internal_memo'])); ?></td>
            </tr>
        </tbody>
    </table>
</div>

<h3 style="margin-top:30px; margin-bottom:10px; font-size:1.1em; font-weight:bold;">관련 활동 로그 (최근 20건)</h3>
<div class="tbl_head01 tbl_wrap">
    <table>
        <thead>
            <tr>
                <th>로그 ID</th>
                <th>처리 시각</th>
                <th>작업 종류</th>
                <th>작업자</th>
                <th>상세 내용</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // job_id나 post_target_id와 관련된 로그를 추출. (로그의 detail에 해당 ID들이 기록되어 있을 경우를 위해 like 검색 활용)
            $safe_id = sql_real_escape_string($id);
            $safe_target = sql_real_escape_string($job['post_target_id']);
            $sql_log = " select * from {$tbl_logs} 
                         where detail like '%ID: {$safe_id}%' 
                            or detail like '%Target ID: {$safe_target}%'
                         order by id desc limit 20 ";
            $res_log = sql_query($sql_log);
            while($lrow = sql_fetch_array($res_log)) {
            ?>
            <tr>
                <td style="text-align:center;"><?php echo $lrow['id']; ?></td>
                <td style="text-align:center;"><?php echo $lrow['created_at']; ?></td>
                <td style="text-align:center;"><?php echo get_text($lrow['action']); ?></td>
                <td style="text-align:center;"><?php echo get_text($lrow['actor']); ?></td>
                <td><?php echo get_text($lrow['detail']); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./publish_job_list.php" class="btn_02 btn">목록</a>
    <?php if (in_array($job['status'], array('scheduled', 'pending'))) { ?>
        <a href="./publish_job_form.php?id=<?php echo $job['id']; ?>&w=u" class="btn_03 btn">수정</a>
    <?php } ?>
</div>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>
