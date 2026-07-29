<?php
include_once('./_common.php');

$sub_menu = '361000';
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '보고서 대시보드';

$today = bp_report_period_range('today');
$week = bp_report_period_range('this_week');
$month = bp_report_period_range('this_month');

$today_jobs = bp_report_job_counts($today['from'], $today['to']);
$today_attempts = bp_report_attempt_counts($today['from'], $today['to']);
$week_jobs = bp_report_job_counts($week['from'], $week['to']);
$month_jobs = bp_report_job_counts($month['from'], $month['to']);
$month_attempts = bp_report_attempt_counts($month['from'], $month['to']);

$adv_table = bp_table('advertisers');
$sites_table = bp_table('sites');
$active_advertisers = sql_fetch(" select count(*) as cnt from {$adv_table} where status = 'Y' ");
$active_sites = sql_fetch(" select count(*) as cnt from {$sites_table} where status = 'Y' ");

// 최근 문제 — 최근 실패, 장기 대기, 재시도 초과, 외부 URL 누락 성공.
$tbl_jobs = bp_table('publish_jobs');
$tbl_targets = bp_table('post_targets');
$tbl_posts = bp_table('posts');
$tbl_projects = bp_table('content_projects');

$recent_failures = sql_query(" select j.id, j.completed_at, j.last_error, po.title, prj.topic
                                from {$tbl_jobs} j
                                inner join {$tbl_targets} t on t.id = j.post_target_id
                                inner join {$tbl_posts} po on po.id = t.post_id
                                inner join {$tbl_projects} prj on prj.id = po.project_id
                                where j.status = 'failed'
                                order by j.completed_at desc limit 10 ");

$stale_pending = sql_query(" select j.id, j.status, j.created_at, j.scheduled_at, po.title
                              from {$tbl_jobs} j
                              inner join {$tbl_targets} t on t.id = j.post_target_id
                              inner join {$tbl_posts} po on po.id = t.post_id
                              where j.status in ('pending','claimed','processing')
                                and j.created_at < date_sub(now(), interval 24 hour)
                              order by j.created_at asc limit 10 ");

$maxed_out = sql_query(" select j.id, j.attempt_count, j.max_retries, po.title
                          from {$tbl_jobs} j
                          inner join {$tbl_targets} t on t.id = j.post_target_id
                          inner join {$tbl_posts} po on po.id = t.post_id
                          where j.status = 'failed' and j.attempt_count >= j.max_retries
                          order by j.completed_at desc limit 10 ");

$missing_url = sql_query(" select j.id, j.completed_at, po.title
                            from {$tbl_jobs} j
                            inner join {$tbl_targets} t on t.id = j.post_target_id
                            inner join {$tbl_posts} po on po.id = t.post_id
                            where j.status = 'published' and (t.published_url is null or t.published_url = '')
                            order by j.completed_at desc limit 10 ");

bp_log_activity(0, 'report_viewed', bp_current_admin_id(), 'dashboard');

include_once(G5_ADMIN_PATH . '/admin.head.php');
add_stylesheet('<link rel="stylesheet" href="' . G5_ADMIN_URL . '/css/admin_extend_sf_blog.css">', 0);
?>
<div class="local_desc01 local_desc">
    <p>발행 작업·시도 데이터를 기준으로 집계한 요약입니다. 차트 없이 표로 확인할 수 있습니다.
       PDF·엑셀 내보내기, 주간·월간·광고주별 보고서, 콘텐츠 성과 기록은 이번 단계에서 아직 구현되지 않았습니다.</p>
</div>

<div class="bp-report-cards">
    <div class="bp-report-card"><span class="bp-report-card-label">오늘 발행 시도</span><span class="bp-report-card-value"><?php echo (int) $today_attempts['total']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">오늘 성공</span><span class="bp-report-card-value bp-num-good"><?php echo (int) $today_attempts['success']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">오늘 실패</span><span class="bp-report-card-value bp-num-bad"><?php echo (int) $today_attempts['failed']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">이번 주 발행(예약+대기+처리중)</span><span class="bp-report-card-value"><?php echo (int) ($week_jobs['scheduled'] + $week_jobs['pending'] + $week_jobs['processing']); ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">이번 달 발행 시도</span><span class="bp-report-card-value"><?php echo (int) $month_attempts['total']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">전체 성공률(이번 달)</span><span class="bp-report-card-value"><?php echo bp_report_success_rate($month_attempts['success'], $month_attempts['total']); ?>%</span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">운영 사이트</span><span class="bp-report-card-value"><?php echo (int) $active_sites['cnt']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">활성 광고주</span><span class="bp-report-card-value"><?php echo (int) $active_advertisers['cnt']; ?></span></div>
</div>

<div class="bp-report-nav">
    <a href="./report_daily.php" class="btn btn_02">일간 발행 보고서</a>
    <a href="./report_site.php" class="btn btn_02">사이트별 발행 통계</a>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>최근 실패한 발행 작업(최대 10건)</caption>
        <thead><tr><th scope="col">작업 ID</th><th scope="col">완료 시각</th><th scope="col">프로젝트</th><th scope="col">포스트 제목</th><th scope="col">오류</th></tr></thead>
        <tbody>
            <?php if ($recent_failures && sql_num_rows($recent_failures) > 0) { ?>
                <?php while ($r = sql_fetch_array($recent_failures)) { ?>
                <tr>
                    <td><?php echo (int) $r['id']; ?></td>
                    <td><?php echo $r['completed_at'] ? get_text($r['completed_at']) : '-'; ?></td>
                    <td><?php echo get_text($r['topic']); ?></td>
                    <td style="text-align:left;"><?php echo get_text($r['title']); ?></td>
                    <td style="text-align:left;color:#c00;"><?php echo get_text(mb_substr((string) $r['last_error'], 0, 60)); ?></td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="5" class="empty_table">최근 실패한 작업이 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>24시간 이상 대기·처리중인 작업(최대 10건)</caption>
        <thead><tr><th scope="col">작업 ID</th><th scope="col">상태</th><th scope="col">생성일</th><th scope="col">포스트 제목</th></tr></thead>
        <tbody>
            <?php if ($stale_pending && sql_num_rows($stale_pending) > 0) { ?>
                <?php while ($r = sql_fetch_array($stale_pending)) { ?>
                <tr>
                    <td><?php echo (int) $r['id']; ?></td>
                    <td><?php echo get_text($r['status']); ?></td>
                    <td><?php echo get_text($r['created_at']); ?></td>
                    <td style="text-align:left;"><?php echo get_text($r['title']); ?></td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="4" class="empty_table">장기 대기 작업이 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>최대 재시도 도달 후 실패(최대 10건)</caption>
        <thead><tr><th scope="col">작업 ID</th><th scope="col">시도/최대</th><th scope="col">포스트 제목</th></tr></thead>
        <tbody>
            <?php if ($maxed_out && sql_num_rows($maxed_out) > 0) { ?>
                <?php while ($r = sql_fetch_array($maxed_out)) { ?>
                <tr>
                    <td><?php echo (int) $r['id']; ?></td>
                    <td><?php echo (int) $r['attempt_count']; ?> / <?php echo (int) $r['max_retries']; ?></td>
                    <td style="text-align:left;"><?php echo get_text($r['title']); ?></td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="3" class="empty_table">해당 작업이 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>발행 성공했지만 외부 URL이 누락된 작업(최대 10건)</caption>
        <thead><tr><th scope="col">작업 ID</th><th scope="col">완료 시각</th><th scope="col">포스트 제목</th></tr></thead>
        <tbody>
            <?php if ($missing_url && sql_num_rows($missing_url) > 0) { ?>
                <?php while ($r = sql_fetch_array($missing_url)) { ?>
                <tr>
                    <td><?php echo (int) $r['id']; ?></td>
                    <td><?php echo get_text($r['completed_at']); ?></td>
                    <td style="text-align:left;"><?php echo get_text($r['title']); ?></td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="3" class="empty_table">해당 작업이 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
