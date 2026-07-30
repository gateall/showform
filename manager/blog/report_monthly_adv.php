<?php
include_once('./_common.php');

$sub_menu = '361020';
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '월간 광고주 보고서';

$adv_table = bp_table('advertisers');
$sites_table = bp_table('sites');

$default_month = date('Y-m');
$month = isset($_GET['month']) && preg_match('/^\d{4}-\d{2}$/', $_GET['month']) ? $_GET['month'] : $default_month;
$start_date = $month . '-01';
$end_date = date('Y-m-t', strtotime($start_date));

$advertiser_id = isset($_GET['advertiser_id']) ? (int) $_GET['advertiser_id'] : 0;
// 광고주가 선택되지 않았으면 목록에서 첫번째 껄로 강제
if (!$advertiser_id) {
    $first_adv = sql_fetch(" select id from {$adv_table} order by name asc limit 1 ");
    if ($first_adv) {
        $advertiser_id = (int)$first_adv['id'];
    }
}

$filters = array('advertiser_id' => $advertiser_id);

$advertisers = sql_query(" select id, name from {$adv_table} order by name asc ");

// 월간 합계
$period_month = bp_report_period_range('custom', $start_date, $end_date);
$monthly_jobs = bp_report_job_counts($period_month['from'], $period_month['to'], $filters);
$monthly_attempts = bp_report_attempt_counts($period_month['from'], $period_month['to'], $filters);
$monthly_success_rate = bp_report_success_rate($monthly_attempts['success'], $monthly_attempts['total']);

// 광고주에게 공유할 발행 완료(성공) 목록 위주로 추출 (내부 에러 제외)
// bp_report_daily_detail 대신 전용 쿼리로 published_url이 있는 성공건만 추출
$tbl_jobs = bp_table('publish_jobs');
$tbl_targets = bp_table('post_targets');
$tbl_posts = bp_table('posts');
$tbl_projects = bp_table('content_projects');
$tbl_adv = bp_table('advertisers');
$tbl_sites = bp_table('sites');

$sql = " select j.id as job_id, j.completed_at,
                po.title as post_title, prj.topic, a.name as advertiser_name, s.name as site_name,
                s.platform, t.published_url
         from {$tbl_jobs} j
         inner join {$tbl_targets} t on t.id = j.post_target_id
         inner join {$tbl_posts} po on po.id = t.post_id
         inner join {$tbl_projects} prj on prj.id = po.project_id
         left join {$tbl_adv} a on a.id = prj.advertiser_id
         left join {$tbl_sites} s on s.id = t.site_id
         where j.status = 'published'
           and prj.advertiser_id = '{$advertiser_id}'
           and j.completed_at between '{$period_month['from']}' and '{$period_month['to']}'
         order by j.completed_at desc
         limit 500 ";
$res = sql_query($sql);
$success_list = array();
while ($r = sql_fetch_array($res)) {
    $success_list[] = $r;
}

$adv_info = sql_fetch(" select name from {$adv_table} where id = '{$advertiser_id}' ");

bp_log_activity(0, 'report_viewed', bp_current_admin_id(), "monthly_adv:{$month}:{$advertiser_id}");

include_once(__DIR__ . '/../layout/header.php');
add_stylesheet('<link rel="stylesheet" href="' . G5_ADMIN_URL . '/css/admin_extend_sf_blog.css">', 0);
?>
<div class="local_desc01 local_desc">
    <p>특정 광고주의 월간 발행 성과(성공 내역 위주)를 요약하여 공유용으로 활용할 수 있습니다.</p>
</div>

<details class="bp-filter-wrap" open>
    <summary class="bp-filter-summary">조회 조건</summary>
    <form method="get" class="bp-filter-form">
        <div class="bp-filter-row">
            <label>대상 월
                <input type="month" name="month" value="<?php echo get_text($month); ?>" class="frm_input">
            </label>
            <label>광고주
                <select name="advertiser_id">
                    <?php while ($a = sql_fetch_array($advertisers)) { ?>
                        <option value="<?php echo (int) $a['id']; ?>" <?php echo $advertiser_id === (int) $a['id'] ? 'selected' : ''; ?>><?php echo get_text($a['name']); ?></option>
                    <?php } ?>
                </select>
            </label>
        </div>
        <div class="bp-filter-actions">
            <button type="submit" class="btn btn_submit btn">조회</button>
            <button type="button" class="btn btn_01" onclick="window.location.href='./export_excel.php?type=monthly_adv&month=<?php echo $month; ?>&advertiser_id=<?php echo $advertiser_id; ?>'">엑셀 다운로드</button>
            <button type="button" class="btn btn_01" onclick="window.print();">PDF 인쇄</button>
        </div>
    </form>
</details>

<?php if ($advertiser_id) { ?>
<div class="bp-report-cards" style="margin-top:15px;">
    <div class="bp-report-card"><span class="bp-report-card-label">광고주명</span><span class="bp-report-card-value"><?php echo get_text($adv_info['name']); ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">월간 발행 예정</span><span class="bp-report-card-value"><?php echo (int) $monthly_jobs['scheduled']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">월간 실제 발행 시도</span><span class="bp-report-card-value"><?php echo (int) $monthly_attempts['total']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">발행 성공 (완료)</span><span class="bp-report-card-value bp-num-good"><?php echo (int) $monthly_attempts['success']; ?></span></div>
    <div class="bp-report-card"><span class="bp-report-card-label">발행 성공률</span><span class="bp-report-card-value"><?php echo $monthly_attempts['total'] > 0 ? $monthly_success_rate . '%' : '-'; ?></span></div>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption><?php echo get_text($month); ?> 월간 발행 완료 목록 (총 <?php echo count($success_list); ?>건)</caption>
        <thead>
            <tr>
                <th scope="col">발행 일시</th>
                <th scope="col">사이트명</th>
                <th scope="col">플랫폼</th>
                <th scope="col">포스트 제목</th>
                <th scope="col">발행된 외부 URL</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($success_list)) { ?>
                <?php foreach ($success_list as $r) { ?>
                <tr>
                    <td><?php echo $r['completed_at'] ? get_text($r['completed_at']) : '-'; ?></td>
                    <td><?php echo get_text($r['site_name']); ?></td>
                    <td><?php echo get_text($r['platform']); ?></td>
                    <td style="text-align:left;"><?php echo get_text($r['post_title']); ?></td>
                    <td><?php echo $r['published_url'] ? '<a href="' . get_text($r['published_url']) . '" target="_blank">' . get_text($r['published_url']) . '</a>' : '-'; ?></td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="5" class="empty_table">해당 월에 발행 완료된 내역이 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php } else { ?>
<div class="empty_table" style="margin-top:20px;">광고주를 선택해주세요.</div>
<?php } ?>

<?php 
include_once(__DIR__ . '/../layout/footer.php');
