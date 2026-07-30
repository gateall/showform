<?php
include_once('./_common.php');

$sub_menu = '361010';
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '주간 운영 보고서';

$adv_table = bp_table('advertisers');
$sites_table = bp_table('sites');

// 요일 헬퍼 — 아래 일자별 추이 표에서 사용되므로 사용 지점보다 먼저 정의해야 한다
// (조건부 정의라 PHP가 파일 앞으로 끌어올려 주지 않음 — 원래 위치였던 파일 하단에 두면
// "Call to undefined function" 치명적 오류가 난다).
if (!function_exists('bp_get_yoil')) {
    function bp_get_yoil($date) {
        $yoil = array('일','월','화','수','목','금','토');
        return $yoil[date('w', strtotime($date))];
    }
}

// 이번주의 월요일
$default_week_start = date('Y-m-d', strtotime('monday this week'));
$start_date = isset($_GET['start_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['start_date']) ? $_GET['start_date'] : $default_week_start;
$end_date = date('Y-m-d', strtotime($start_date . ' +6 days'));

$advertiser_id = isset($_GET['advertiser_id']) ? (int) $_GET['advertiser_id'] : 0;
$site_id = isset($_GET['site_id']) ? (int) $_GET['site_id'] : 0;

$filters = array('advertiser_id' => $advertiser_id, 'site_id' => $site_id);

$advertisers = sql_query(" select id, name from {$adv_table} order by name asc ");
$sites = sql_query(" select id, name from {$sites_table} order by name asc ");

// 7일간의 날짜별 통계 추출
$daily_stats = array();
for ($i = 0; $i <= 6; $i++) {
    $d = date('Y-m-d', strtotime($start_date . " +{$i} days"));
    $p = bp_report_period_range('custom', $d, $d);
    
    $j = bp_report_job_counts($p['from'], $p['to'], $filters);
    $a = bp_report_attempt_counts($p['from'], $p['to'], $filters);
    $rate = bp_report_success_rate($a['success'], $a['total']);
    
    $daily_stats[$d] = array(
        'scheduled' => $j['scheduled'],
        'attempts' => $a['total'],
        'success' => $a['success'],
        'failed' => $a['failed'],
        'success_rate' => $rate
    );
}

// 전체 주간 합계
$period_week = bp_report_period_range('custom', $start_date, $end_date);
$weekly_jobs = bp_report_job_counts($period_week['from'], $period_week['to'], $filters);
$weekly_attempts = bp_report_attempt_counts($period_week['from'], $period_week['to'], $filters);
$weekly_success_rate = bp_report_success_rate($weekly_attempts['success'], $weekly_attempts['total']);
$weekly_fail_rate = $weekly_attempts['total'] > 0 ? round(100 - $weekly_success_rate, 1) : 0.0;

// 문제 요약 (반복 실패 및 락/타임아웃 건수 추정용 상세)
$errors = bp_report_daily_detail($period_week['from'], $period_week['to'], array_merge($filters, array('status'=>'failed')), 50);

bp_log_activity(0, 'report_viewed', bp_current_admin_id(), "weekly:{$start_date}");

include_once(__DIR__ . '/../layout/header.php');
add_stylesheet('<link rel="stylesheet" href="' . G5_ADMIN_URL . '/css/admin_extend_sf_blog.css">', 0);
?>
<div class="local_desc01 local_desc">
    <p>선택한 월요일부터 일요일까지 7일간의 발행 추이 및 운영 문제 요약을 확인합니다.</p>
</div>

<details class="bp-filter-wrap" open>
    <summary class="bp-filter-summary">조회 조건</summary>
    <form method="get" class="bp-filter-form">
        <div class="bp-filter-row">
            <label>시작일(월요일 권장)
                <input type="date" name="start_date" value="<?php echo get_text($start_date); ?>" class="frm_input">
            </label>
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
        </div>
        <div class="bp-filter-actions">
            <button type="submit" class="btn btn_submit btn">조회</button>
            <button type="button" class="btn btn_01" onclick="window.location.href='./export_excel.php?type=weekly&start_date=<?php echo $start_date; ?>&advertiser_id=<?php echo $advertiser_id; ?>&site_id=<?php echo $site_id; ?>'">엑셀 다운로드</button>
            <button type="button" class="btn btn_01" onclick="window.print();">PDF 인쇄</button>
            <a href="./report_weekly.php" class="btn btn_02">초기화</a>
        </div>
    </form>
</details>

<div class="bp-report-cards" style="margin-top:15px;">
    <div class="bp-report-card"><span class="bp-report-card-label">주간 발행 예정</span><span class="bp-report-card-value"><?php echo (int) $weekly_jobs['scheduled']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">주간 발행 시도</span><span class="bp-report-card-value"><?php echo (int) $weekly_attempts['total']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">주간 성공</span><span class="bp-report-card-value bp-num-good"><?php echo (int) $weekly_attempts['success']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">주간 실패</span><span class="bp-report-card-value bp-num-bad"><?php echo (int) $weekly_attempts['failed']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">평균 성공률</span><span class="bp-report-card-value"><?php echo $weekly_attempts['total'] > 0 ? $weekly_success_rate . '%' : '-'; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">재시도 건수</span><span class="bp-report-card-value"><?php echo (int) $weekly_attempts['retry_attempts']; ?></span></div>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>일자별 발행 추이 (<?php echo $start_date; ?> ~ <?php echo $end_date; ?>)</caption>
        <thead>
            <tr>
                <th scope="col">날짜</th>
                <th scope="col">발행 예정</th>
                <th scope="col">발행 시도</th>
                <th scope="col">성공</th>
                <th scope="col">실패</th>
                <th scope="col">성공률</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($daily_stats as $d => $st) { ?>
            <tr>
                <td><?php echo $d; ?> (<?php echo bp_get_yoil($d); ?>)</td>
                <td><?php echo number_format($st['scheduled']); ?></td>
                <td><?php echo number_format($st['attempts']); ?></td>
                <td class="bp-num-good"><?php echo number_format($st['success']); ?></td>
                <td class="bp-num-bad"><?php echo number_format($st['failed']); ?></td>
                <td><?php echo $st['attempts'] > 0 ? $st['success_rate'].'%' : '-'; ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>운영 문제 요약 (최근 실패 사례 최대 50건)</caption>
        <thead>
            <tr>
                <th scope="col">발생 일시</th>
                <th scope="col">광고주</th>
                <th scope="col">사이트</th>
                <th scope="col">포스트 제목</th>
                <th scope="col">오류 메시지</th>
                <th scope="col">재시도</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($errors)) { ?>
                <?php foreach ($errors as $r) { ?>
                <tr>
                    <td><?php echo $r['completed_at'] ? get_text($r['completed_at']) : '-'; ?></td>
                    <td><?php echo get_text($r['advertiser_name']); ?></td>
                    <td><?php echo get_text($r['site_name']); ?></td>
                    <td style="text-align:left;"><?php echo get_text($r['post_title']); ?></td>
                    <td style="text-align:left;color:#c00;"><?php echo get_text(mb_substr((string) $r['last_error'], 0, 80)); ?></td>
                    <td><?php echo (int) $r['attempt_count']; ?></td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="6" class="empty_table">해당 주간에 발생한 오류 내역이 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php 
// 헬퍼 함수: 요일 구하기
if (!function_exists('bp_get_yoil')) {
    function bp_get_yoil($date) {
        $yoil = array('일','월','화','수','목','금','토');
        return $yoil[date('w', strtotime($date))];
    }
}
include_once(__DIR__ . '/../layout/footer.php');
