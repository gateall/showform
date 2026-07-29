<?php
include_once('./_common.php');

$sub_menu = '361000';
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '성공·실패 통계';

$adv_table = bp_table('advertisers');

$preset = isset($_GET['period']) ? trim($_GET['period']) : 'this_month';
$advertiser_id = isset($_GET['advertiser_id']) ? (int) $_GET['advertiser_id'] : 0;

$period = bp_report_period_range($preset);
$filters = array('advertiser_id' => $advertiser_id);

$job_counts = bp_report_job_counts($period['from'], $period['to'], $filters);
$job_ext = bp_report_job_extended_stats($period['from'], $period['to'], $filters);
$max_retry_failed = bp_report_max_retry_failed_count($period['from'], $period['to'], $filters);

$attempt_counts = bp_report_attempt_counts($period['from'], $period['to'], $filters);
$success_rate = bp_report_success_rate($attempt_counts['success'], $attempt_counts['total']);
$fail_rate = $attempt_counts['total'] > 0 ? round(100 - $success_rate, 1) : 0.0;

$error_breakdown = bp_report_error_breakdown($period['from'], $period['to'], $filters);
$site_stats = bp_report_site_stats($period['from'], $period['to'], $filters);
$platform_stats = bp_report_platform_stats($period['from'], $period['to'], $filters);

$advertisers = sql_query(" select id, name from {$adv_table} order by name asc ");

bp_log_activity(0, 'report_viewed', bp_current_admin_id(), "stats:{$preset}");

include_once(G5_ADMIN_PATH . '/admin.head.php');
add_stylesheet('<link rel="stylesheet" href="' . G5_ADMIN_URL . '/css/admin_extend_sf_blog.css">', 0);
?>
<div class="local_desc01 local_desc">
    <p>발행 작업(Job)과 발행 시도(Attempt)를 별도 구역으로 나눠 표시합니다. 재시도 1건이 3번 시도되면
       Job은 1건, Attempt는 최대 3건으로 각각 집계되므로 두 수치를 섞어 비교하지 마세요.</p>
</div>

<details class="bp-filter-wrap" open>
    <summary class="bp-filter-summary">조회 조건</summary>
    <form method="get" class="bp-filter-form">
        <div class="bp-filter-row">
            <label>기간
                <select name="period">
                    <?php $periods = array('today' => '오늘', 'yesterday' => '어제', 'last7' => '최근 7일', 'this_week' => '이번 주', 'last_week' => '지난주', 'this_month' => '이번 달', 'last_month' => '지난달'); ?>
                    <?php foreach ($periods as $code => $label) { ?>
                        <option value="<?php echo $code; ?>" <?php echo $preset === $code ? 'selected' : ''; ?>><?php echo $label; ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>광고주
                <select name="advertiser_id">
                    <option value="0">전체</option>
                    <?php while ($a = sql_fetch_array($advertisers)) { ?>
                        <option value="<?php echo (int) $a['id']; ?>" <?php echo $advertiser_id === (int) $a['id'] ? 'selected' : ''; ?>><?php echo get_text($a['name']); ?></option>
                    <?php } ?>
                </select>
            </label>
        </div>
        <div class="bp-filter-actions">
            <button type="submit" class="btn btn_submit btn">조회</button>
            <a href="./report_stats.php" class="btn btn_02">초기화</a>
        </div>
    </form>
</details>

<div class="bp-report-nav">
    <a href="./export_excel.php?type=stats&period=<?php echo get_text($preset); ?>&advertiser_id=<?php echo $advertiser_id; ?>" class="btn btn_02">엑셀 다운로드</a>
</div>

<h3 style="margin-top:10px;">Job(작업) 단위 통계</h3>
<div class="bp-report-cards">
    <div class="bp-report-card"><span class="bp-report-card-label">전체 Job</span><span class="bp-report-card-value"><?php echo $job_ext['total_jobs']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">최종 성공</span><span class="bp-report-card-value bp-num-good"><?php echo $job_counts['succeeded']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">최종 실패</span><span class="bp-report-card-value bp-num-bad"><?php echo $job_counts['failed']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">재시도 중</span><span class="bp-report-card-value"><?php echo $job_ext['retrying']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">재시도 소진 후 실패</span><span class="bp-report-card-value bp-num-bad"><?php echo $max_retry_failed; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">취소</span><span class="bp-report-card-value"><?php echo $job_counts['cancelled']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">평균 시도 횟수</span><span class="bp-report-card-value"><?php echo $job_ext['avg_attempts']; ?></span></div>
</div>

<h3 style="margin-top:25px;">Attempt(시도) 단위 통계</h3>
<div class="bp-report-cards">
    <div class="bp-report-card"><span class="bp-report-card-label">전체 시도</span><span class="bp-report-card-value"><?php echo $attempt_counts['total']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">성공 시도</span><span class="bp-report-card-value bp-num-good"><?php echo $attempt_counts['success']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">실패 시도</span><span class="bp-report-card-value bp-num-bad"><?php echo $attempt_counts['failed']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">최초 시도 성공</span><span class="bp-report-card-value"><?php echo $attempt_counts['first_try_success']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">재시도 후 성공</span><span class="bp-report-card-value"><?php echo $attempt_counts['retry_success']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">성공률</span><span class="bp-report-card-value"><?php echo $attempt_counts['total'] > 0 ? $success_rate . '%' : '집계 대상 없음'; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">실패율</span><span class="bp-report-card-value"><?php echo $attempt_counts['total'] > 0 ? $fail_rate . '%' : '집계 대상 없음'; ?></span></div>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>실패 원인 분류(실패 시도 기준)</caption>
        <thead><tr><th scope="col">원인</th><th scope="col">건수</th><th scope="col">비율</th></tr></thead>
        <tbody>
            <?php if (!empty($error_breakdown)) { ?>
                <?php foreach ($error_breakdown as $cat => $cnt) { ?>
                <tr>
                    <td style="text-align:left;"><?php echo get_text($cat); ?></td>
                    <td><?php echo (int) $cnt; ?></td>
                    <td><?php echo $attempt_counts['failed'] > 0 ? round($cnt / $attempt_counts['failed'] * 100, 1) . '%' : '-'; ?></td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="3" class="empty_table">기간 내 실패한 시도가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>사이트별 실패율</caption>
        <thead><tr><th scope="col">사이트</th><th scope="col">전체</th><th scope="col">성공</th><th scope="col">실패</th><th scope="col">실패율</th></tr></thead>
        <tbody>
            <?php if (!empty($site_stats)) { ?>
                <?php foreach ($site_stats as $s) {
                    $t = (int) $s['total_attempts_jobs'];
                    $fr = $t > 0 ? round((100 - (float) $s['success_rate']), 1) : 0.0;
                ?>
                <tr>
                    <td style="text-align:left;"><?php echo get_text($s['site_name']); ?></td>
                    <td><?php echo $t; ?></td>
                    <td><?php echo (int) $s['succeeded']; ?></td>
                    <td><?php echo (int) $s['failed']; ?></td>
                    <td><?php echo $t > 0 ? $fr . '%' : '-'; ?></td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="5" class="empty_table">데이터가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>플랫폼별 실패율</caption>
        <thead><tr><th scope="col">플랫폼</th><th scope="col">전체</th><th scope="col">성공</th><th scope="col">실패</th><th scope="col">실패율</th></tr></thead>
        <tbody>
            <?php if (!empty($platform_stats)) { ?>
                <?php foreach ($platform_stats as $p) { ?>
                <tr>
                    <td style="text-align:left;"><?php echo get_text($p['platform']); ?></td>
                    <td><?php echo (int) $p['total_jobs']; ?></td>
                    <td><?php echo (int) $p['succeeded']; ?></td>
                    <td><?php echo (int) $p['failed']; ?></td>
                    <td><?php echo $p['failure_rate']; ?>%</td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="5" class="empty_table">데이터가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
