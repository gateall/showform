<?php
include_once('./_common.php');

$sub_menu = '361000';
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '일간 발행 보고서';

$adv_table = bp_table('advertisers');
$sites_table = bp_table('sites');

$date = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : date('Y-m-d');
$advertiser_id = isset($_GET['advertiser_id']) ? (int) $_GET['advertiser_id'] : 0;
$site_id = isset($_GET['site_id']) ? (int) $_GET['site_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

$period = bp_report_period_range('custom', $date, $date);
$filters = array('advertiser_id' => $advertiser_id, 'site_id' => $site_id, 'status' => $status);

$job_counts = bp_report_job_counts($period['from'], $period['to'], $filters);
$attempt_counts = bp_report_attempt_counts($period['from'], $period['to'], $filters);
$success_rate = bp_report_success_rate($attempt_counts['success'], $attempt_counts['total']);
$fail_rate = $attempt_counts['total'] > 0 ? round(100 - $success_rate, 1) : 0.0;
$detail_rows = bp_report_daily_detail($period['from'], $period['to'], $filters);

$advertisers = sql_query(" select id, name from {$adv_table} order by name asc ");
$sites = sql_query(" select id, name from {$sites_table} order by name asc ");

$status_map = bp_report_job_status_map();

bp_log_activity(0, 'report_viewed', bp_current_admin_id(), "daily:{$date}");

include_once(G5_ADMIN_PATH . '/admin.head.php');
add_stylesheet('<link rel="stylesheet" href="' . G5_ADMIN_URL . '/css/admin_extend_sf_blog.css">', 0);
?>
<div class="local_desc01 local_desc">
    <p>선택한 날짜의 발행 예정·시도·결과를 요약합니다. 성공률은 예약·대기·취소를 제외한 실제 발행 시도 건수를 기준으로 계산합니다.</p>
</div>

<details class="bp-filter-wrap" open>
    <summary class="bp-filter-summary">조회 조건</summary>
    <form method="get" class="bp-filter-form">
        <div class="bp-filter-row">
            <label>날짜<input type="date" name="date" value="<?php echo get_text($date); ?>" class="frm_input"></label>
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
            <label>상태
                <select name="status">
                    <option value="">전체</option>
                    <?php foreach ($status_map as $code => $info) { ?>
                        <option value="<?php echo $code; ?>" <?php echo $status === $code ? 'selected' : ''; ?>><?php echo $info['label']; ?>(<?php echo $code; ?>)</option>
                    <?php } ?>
                </select>
            </label>
        </div>
        <div class="bp-filter-actions">
            <button type="submit" class="btn btn_submit btn">조회</button>
            <a href="./report_daily.php" class="btn btn_02">초기화</a>
        </div>
    </form>
</details>

<div class="bp-report-nav">
    <a href="./export_excel.php?type=daily&date=<?php echo get_text($date); ?>&advertiser_id=<?php echo $advertiser_id; ?>&site_id=<?php echo $site_id; ?>&status=<?php echo get_text($status); ?>" class="btn btn_02">엑셀 다운로드</a>
</div>

<div class="bp-report-cards" style="margin-top:15px;">
    <div class="bp-report-card"><span class="bp-report-card-label">발행 예정</span><span class="bp-report-card-value"><?php echo (int) $job_counts['scheduled']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">발행 시도</span><span class="bp-report-card-value"><?php echo (int) $attempt_counts['total']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">성공</span><span class="bp-report-card-value bp-num-good"><?php echo (int) $attempt_counts['success']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">실패</span><span class="bp-report-card-value bp-num-bad"><?php echo (int) $attempt_counts['failed']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">대기</span><span class="bp-report-card-value"><?php echo (int) $job_counts['pending']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">처리 중</span><span class="bp-report-card-value"><?php echo (int) $job_counts['processing']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">취소</span><span class="bp-report-card-value"><?php echo (int) $job_counts['cancelled']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">성공률</span><span class="bp-report-card-value"><?php echo $attempt_counts['total'] > 0 ? $success_rate . '%' : '집계 대상 없음'; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">실패율</span><span class="bp-report-card-value"><?php echo $attempt_counts['total'] > 0 ? $fail_rate . '%' : '집계 대상 없음'; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">재시도 건수</span><span class="bp-report-card-value"><?php echo (int) $attempt_counts['retry_attempts']; ?></span></div>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption><?php echo get_text($date); ?> 발행 작업 상세(최대 200건)</caption>
        <thead>
            <tr>
                <th scope="col">작업 ID</th><th scope="col">예약 시각</th><th scope="col">완료 시각</th>
                <th scope="col">광고주</th><th scope="col">사이트</th><th scope="col">포스트 제목</th>
                <th scope="col">상태</th><th scope="col">재시도</th><th scope="col">외부 URL</th><th scope="col">오류</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($detail_rows)) { ?>
                <?php foreach ($detail_rows as $r) {
                    $lbl = isset($status_map[$r['status']]) ? $status_map[$r['status']]['label'] : $r['status'];
                ?>
                <tr>
                    <td><?php echo (int) $r['job_id']; ?></td>
                    <td><?php echo $r['scheduled_at'] ? get_text($r['scheduled_at']) : '-'; ?></td>
                    <td><?php echo $r['completed_at'] ? get_text($r['completed_at']) : '-'; ?></td>
                    <td><?php echo get_text($r['advertiser_name']); ?></td>
                    <td><?php echo get_text($r['site_name']); ?></td>
                    <td style="text-align:left;"><?php echo get_text($r['post_title']); ?></td>
                    <td><?php echo get_text($lbl); ?></td>
                    <td><?php echo (int) $r['attempt_count']; ?></td>
                    <td><?php echo $r['published_url'] ? '<a href="' . get_text($r['published_url']) . '" target="_blank">' . get_text($r['published_url']) . '</a>' : ''; ?></td>
                    <td style="text-align:left;color:#c00;"><?php echo get_text(mb_substr((string) $r['last_error'], 0, 60)); ?></td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="10" class="empty_table">조건에 맞는 발행 작업이 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
