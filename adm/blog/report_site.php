<?php
include_once('./_common.php');

$sub_menu = '361000';
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '사이트별 발행 통계';

// 경고 판정 기준값 — 하드코딩 대신 상수로 관리(§9.3).
const BP_REPORT_WARN_MIN_SUCCESS_RATE = 70.0; // 성공률이 이 값 미만이면 경고
const BP_REPORT_WARN_STALE_HOURS = 72;         // 마지막 성공이 이 시간(시) 이전이면 "장기 미발행" 경고
const BP_REPORT_WARN_MIN_ATTEMPTS_FOR_RATE = 3; // 시도 건수가 이보다 적으면 성공률 경고를 보류(왜곡 방지)

$adv_table = bp_table('advertisers');

$preset = isset($_GET['period']) ? trim($_GET['period']) : 'this_month';
$advertiser_id = isset($_GET['advertiser_id']) ? (int) $_GET['advertiser_id'] : 0;
$sort = isset($_GET['sort']) ? trim($_GET['sort']) : 'total';

$period = bp_report_period_range($preset);
$filters = array('advertiser_id' => $advertiser_id);
$rows = bp_report_site_stats($period['from'], $period['to'], $filters);

$sort_map = array(
    'total' => 'total_attempts_jobs', 'succeeded' => 'succeeded', 'failed' => 'failed', 'rate' => 'success_rate',
);
$sort_key = isset($sort_map[$sort]) ? $sort_map[$sort] : 'total_attempts_jobs';
usort($rows, function ($a, $b) use ($sort_key) {
    return $b[$sort_key] <=> $a[$sort_key];
});

$advertisers = sql_query(" select id, name from {$adv_table} order by name asc ");

bp_log_activity(0, 'report_viewed', bp_current_admin_id(), "site:{$preset}");

include_once(G5_ADMIN_PATH . '/admin.head.php');
add_stylesheet('<link rel="stylesheet" href="' . G5_ADMIN_URL . '/css/admin_extend_sf_blog.css">', 0);
?>
<div class="local_desc01 local_desc">
    <p>선택 기간(<?php echo get_text($period['label']); ?>) 동안 사이트별 발행 성과를 비교합니다.
       성공률만으로 비교하면 시도 건수가 적은 사이트가 왜곡되어 보일 수 있어 전체 시도 건수를 함께 표시합니다.</p>
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
            <label>정렬
                <select name="sort">
                    <option value="total" <?php echo $sort === 'total' ? 'selected' : ''; ?>>발행량</option>
                    <option value="succeeded" <?php echo $sort === 'succeeded' ? 'selected' : ''; ?>>성공 건수</option>
                    <option value="failed" <?php echo $sort === 'failed' ? 'selected' : ''; ?>>실패 건수</option>
                    <option value="rate" <?php echo $sort === 'rate' ? 'selected' : ''; ?>>성공률</option>
                </select>
            </label>
        </div>
        <div class="bp-filter-actions">
            <button type="submit" class="btn btn_submit btn">조회</button>
            <a href="./report_site.php" class="btn btn_02">초기화</a>
        </div>
    </form>
</details>

<div class="tbl_head01 tbl_wrap" style="margin-top:15px;">
    <table>
        <caption>사이트별 발행 통계</caption>
        <thead>
            <tr>
                <th scope="col">사이트</th><th scope="col">광고주</th><th scope="col">채널</th>
                <th scope="col">전체 시도</th><th scope="col">성공</th><th scope="col">실패</th><th scope="col">성공률</th>
                <th scope="col">대기</th><th scope="col">취소</th><th scope="col">재시도 합계</th>
                <th scope="col">마지막 성공</th><th scope="col">마지막 실패</th><th scope="col">경고</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($rows)) { ?>
                <?php foreach ($rows as $r) {
                    $total = (int) $r['total_attempts_jobs'];
                    $rate = (float) $r['success_rate'];
                    $warnings = array();

                    if ($total >= BP_REPORT_WARN_MIN_ATTEMPTS_FOR_RATE && $rate < BP_REPORT_WARN_MIN_SUCCESS_RATE) {
                        $warnings[] = '성공률 저조';
                    }
                    if ($r['last_success_at']) {
                        $hours_since = (time() - strtotime($r['last_success_at'])) / 3600;
                        if ($hours_since >= BP_REPORT_WARN_STALE_HOURS) {
                            $warnings[] = '장기 미발행';
                        }
                    } elseif ($total === 0) {
                        $warnings[] = '기간 내 발행 이력 없음';
                    }
                    if ((int) $r['pending'] >= 5) {
                        $warnings[] = '예약 작업 누적';
                    }
                    if ($r['site_status'] !== 'Y' && (int) $r['pending'] > 0) {
                        $warnings[] = '비활성 사이트에 예약 존재';
                    }
                ?>
                <tr>
                    <td style="text-align:left;"><?php echo get_text($r['site_name']); ?></td>
                    <td><?php echo get_text($r['advertiser_name']); ?></td>
                    <td><?php echo get_text($r['platform']); ?></td>
                    <td><?php echo $total; ?></td>
                    <td><?php echo (int) $r['succeeded']; ?></td>
                    <td><?php echo (int) $r['failed']; ?></td>
                    <td><?php echo $total > 0 ? $rate . '%' : '-'; ?></td>
                    <td><?php echo (int) $r['pending']; ?></td>
                    <td><?php echo (int) $r['cancelled']; ?></td>
                    <td><?php echo (int) $r['total_retries']; ?></td>
                    <td><?php echo $r['last_success_at'] ? get_text($r['last_success_at']) : '-'; ?></td>
                    <td><?php echo $r['last_failure_at'] ? get_text($r['last_failure_at']) : '-'; ?></td>
                    <td style="color:#c00;text-align:left;"><?php echo $warnings ? get_text(implode(', ', $warnings)) : ''; ?></td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="13" class="empty_table">등록된 사이트가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
