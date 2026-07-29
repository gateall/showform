<?php
include_once('./_common.php');

if (!defined('IS_ADVERTISER_PORTAL')) {
    $sub_menu = '361000';
    auth_check_menu($auth, $sub_menu, 'r');
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
if (!$type) {
    alert('내보내기 유형이 지정되지 않았습니다.');
}

// 엑셀 수식 주입 방지 — HTML 표 기반 .xls라도 값이 =,+,-,@로 시작하면 안전 문자를 붙여 텍스트로만 해석되게 한다.
function bp_export_safe(string $v): string
{
    $v = (string) $v;
    if ($v !== '' && strpos('=+-@', $v[0]) !== false) {
        $v = "'" . $v;
    }
    return get_text($v);
}

// 엑셀 다운로드용 헤더 설정
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=blog_report_{$type}_" . date('Ymd_His') . ".xls");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Pragma: public");

// BOM 추가 (엑셀에서 한글 깨짐 방지)
echo "\xEF\xBB\xBF";

// 기본 CSS (엑셀에서 테이블 테두리용)
echo "<style>
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #cccccc; padding: 5px; font-size: 12px; }
    th { background-color: #eeeeee; text-align: center; font-weight: bold; }
    .num { text-align: right; }
</style>";

$adv_table = bp_table('advertisers');
$sites_table = bp_table('sites');

// 필터 파라미터 공통
$advertiser_id = isset($_GET['advertiser_id']) ? (int)$_GET['advertiser_id'] : 0;
$site_id = isset($_GET['site_id']) ? (int)$_GET['site_id'] : 0;
$filters = array('advertiser_id' => $advertiser_id, 'site_id' => $site_id);

echo "<table>";

if ($type === 'weekly') {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d');
    
    echo "<caption>주간 운영 보고서 ({$start_date} 주간)</caption>";
    echo "<thead><tr><th>날짜</th><th>발행 예정</th><th>발행 시도</th><th>성공</th><th>실패</th><th>성공률</th></tr></thead><tbody>";
    
    for ($i = 0; $i <= 6; $i++) {
        $d = date('Y-m-d', strtotime($start_date . " +{$i} days"));
        $p = bp_report_period_range('custom', $d, $d);
        $j = bp_report_job_counts($p['from'], $p['to'], $filters);
        $a = bp_report_attempt_counts($p['from'], $p['to'], $filters);
        $rate = bp_report_success_rate($a['success'], $a['total']);
        
        echo "<tr>";
        echo "<td>{$d}</td>";
        echo "<td class='num'>{$j['scheduled']}</td>";
        echo "<td class='num'>{$a['total']}</td>";
        echo "<td class='num'>{$a['success']}</td>";
        echo "<td class='num'>{$a['failed']}</td>";
        echo "<td class='num'>{$rate}%</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    
} elseif ($type === 'monthly_adv') {
    $month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
    $start_date = $month . '-01';
    $end_date = date('Y-m-t', strtotime($start_date));
    $period_month = bp_report_period_range('custom', $start_date, $end_date);
    
    $tbl_jobs = bp_table('publish_jobs');
    $tbl_targets = bp_table('post_targets');
    $tbl_posts = bp_table('posts');
    $tbl_projects = bp_table('content_projects');
    $tbl_adv = bp_table('advertisers');
    $tbl_sites = bp_table('sites');

    $sql = " select j.completed_at, po.title as post_title, a.name as advertiser_name, s.name as site_name, s.platform, t.published_url
             from {$tbl_jobs} j
             inner join {$tbl_targets} t on t.id = j.post_target_id
             inner join {$tbl_posts} po on po.id = t.post_id
             inner join {$tbl_projects} prj on prj.id = po.project_id
             left join {$tbl_adv} a on a.id = prj.advertiser_id
             left join {$tbl_sites} s on s.id = t.site_id
             where j.status = 'published'
               and prj.advertiser_id = '{$advertiser_id}'
               and j.completed_at between '{$period_month['from']}' and '{$period_month['to']}'
             order by j.completed_at desc ";
    $res = sql_query($sql);
    
    echo "<caption>월간 광고주 발행 완료 목록 ({$month})</caption>";
    echo "<thead><tr><th>발행 일시</th><th>사이트명</th><th>플랫폼</th><th>포스트 제목</th><th>발행된 외부 URL</th></tr></thead><tbody>";
    while ($r = sql_fetch_array($res)) {
        echo "<tr>";
        echo "<td>" . bp_export_safe($r['completed_at']) . "</td>";
        echo "<td>" . bp_export_safe($r['site_name']) . "</td>";
        echo "<td>" . bp_export_safe($r['platform']) . "</td>";
        echo "<td>" . bp_export_safe($r['post_title']) . "</td>";
        echo "<td>" . bp_export_safe($r['published_url']) . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";

} elseif ($type === 'performance') {
    $stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
    $tbl_perf = bp_table('post_performance');
    $tbl_targets = bp_table('post_targets');
    $tbl_posts = bp_table('posts');
    $tbl_projects = bp_table('content_projects');
    $tbl_adv = bp_table('advertisers');
    $tbl_sites = bp_table('sites');

    $where = array();
    $where[] = " t.published_url != '' ";
    if ($advertiser_id) $where[] = " prj.advertiser_id = '{$advertiser_id}' ";
    if ($site_id) $where[] = " t.site_id = '{$site_id}' ";
    if ($stx) $where[] = " po.title like '%" . sql_real_escape_string($stx) . "%' ";
    $where_sql = $where ? ' where ' . implode(' and ', $where) : '';

    $sql = " select t.published_url, po.title as post_title, a.name as advertiser_name, s.name as site_name, s.platform,
                    perf.view_count, perf.like_count, perf.comment_count, perf.share_count, perf.last_synced_at
             from {$tbl_targets} t
             inner join {$tbl_posts} po on po.id = t.post_id
             inner join {$tbl_projects} prj on prj.id = po.project_id
             left join {$tbl_adv} a on a.id = prj.advertiser_id
             left join {$tbl_sites} s on s.id = t.site_id
             left join {$tbl_perf} perf on perf.post_target_id = t.id
             {$where_sql} order by t.id desc limit 1000 ";
    $res = sql_query($sql);

    echo "<caption>콘텐츠 성과 기록</caption>";
    echo "<thead><tr><th>포스트 제목</th><th>광고주</th><th>사이트</th><th>발행 외부 URL</th><th>조회수</th><th>공감</th><th>댓글</th><th>공유</th><th>동기화 일시</th></tr></thead><tbody>";
    while ($r = sql_fetch_array($res)) {
        echo "<tr>";
        echo "<td>" . bp_export_safe($r['post_title']) . "</td>";
        echo "<td>" . bp_export_safe($r['advertiser_name']) . "</td>";
        echo "<td>" . bp_export_safe($r['site_name']) . "</td>";
        echo "<td>" . bp_export_safe($r['published_url']) . "</td>";
        echo "<td class='num'>".(int)$r['view_count']."</td>";
        echo "<td class='num'>".(int)$r['like_count']."</td>";
        echo "<td class='num'>".(int)$r['comment_count']."</td>";
        echo "<td class='num'>".(int)$r['share_count']."</td>";
        echo "<td>" . bp_export_safe($r['last_synced_at']) . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";

} elseif ($type === 'daily') {
    $date = isset($_GET['date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date']) ? $_GET['date'] : date('Y-m-d');
    $period = bp_report_period_range('custom', $date, $date);
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    if ($status !== '') $filters['status'] = $status;
    $rows = bp_report_daily_detail($period['from'], $period['to'], $filters, 5000);
    $status_map = bp_report_job_status_map();

    echo "<caption>일간 발행 보고서 ({$date})</caption>";
    echo "<thead><tr><th>작업ID</th><th>예약시각</th><th>완료시각</th><th>광고주</th><th>사이트</th><th>포스트 제목</th><th>상태</th><th>재시도</th><th>외부URL</th><th>오류</th></tr></thead><tbody>";
    foreach ($rows as $r) {
        $lbl = isset($status_map[$r['status']]) ? $status_map[$r['status']]['label'] : $r['status'];
        echo "<tr>";
        echo "<td>" . bp_export_safe($r['job_id']) . "</td>";
        echo "<td>" . bp_export_safe($r['scheduled_at']) . "</td>";
        echo "<td>" . bp_export_safe($r['completed_at']) . "</td>";
        echo "<td>" . bp_export_safe($r['advertiser_name']) . "</td>";
        echo "<td>" . bp_export_safe($r['site_name']) . "</td>";
        echo "<td>" . bp_export_safe($r['post_title']) . "</td>";
        echo "<td>" . bp_export_safe($lbl) . "</td>";
        echo "<td class='num'>" . (int) $r['attempt_count'] . "</td>";
        echo "<td>" . bp_export_safe($r['published_url']) . "</td>";
        echo "<td>" . bp_export_safe(mb_substr((string) $r['last_error'], 0, 200)) . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";

} elseif ($type === 'site') {
    $preset = isset($_GET['period']) ? trim($_GET['period']) : 'this_month';
    $period = bp_report_period_range($preset);
    $site_rows = bp_report_site_stats($period['from'], $period['to'], $filters);

    echo "<caption>사이트별 발행 통계 ({$period['label']})</caption>";
    echo "<thead><tr><th>사이트</th><th>광고주</th><th>채널</th><th>전체시도</th><th>성공</th><th>실패</th><th>성공률</th><th>대기</th><th>취소</th><th>재시도합계</th></tr></thead><tbody>";
    foreach ($site_rows as $s) {
        echo "<tr>";
        echo "<td>" . bp_export_safe($s['site_name']) . "</td>";
        echo "<td>" . bp_export_safe($s['advertiser_name']) . "</td>";
        echo "<td>" . bp_export_safe($s['platform']) . "</td>";
        echo "<td class='num'>" . (int) $s['total_attempts_jobs'] . "</td>";
        echo "<td class='num'>" . (int) $s['succeeded'] . "</td>";
        echo "<td class='num'>" . (int) $s['failed'] . "</td>";
        echo "<td class='num'>" . $s['success_rate'] . "%</td>";
        echo "<td class='num'>" . (int) $s['pending'] . "</td>";
        echo "<td class='num'>" . (int) $s['cancelled'] . "</td>";
        echo "<td class='num'>" . (int) $s['total_retries'] . "</td>";
        echo "</tr>";
    }
    echo "</tbody>";

} elseif ($type === 'stats') {
    $preset = isset($_GET['period']) ? trim($_GET['period']) : 'this_month';
    $period = bp_report_period_range($preset);
    $job_counts = bp_report_job_counts($period['from'], $period['to'], $filters);
    $job_ext = bp_report_job_extended_stats($period['from'], $period['to'], $filters);
    $attempt_counts = bp_report_attempt_counts($period['from'], $period['to'], $filters);
    $success_rate = bp_report_success_rate($attempt_counts['success'], $attempt_counts['total']);
    $error_breakdown = bp_report_error_breakdown($period['from'], $period['to'], $filters);

    echo "<caption>성공·실패 통계 ({$period['label']})</caption>";
    echo "<thead><tr><th>구분</th><th>지표</th><th>값</th></tr></thead><tbody>";
    $metrics = array(
        array('Job', '전체 Job', $job_ext['total_jobs']),
        array('Job', '최종 성공', $job_counts['succeeded']),
        array('Job', '최종 실패', $job_counts['failed']),
        array('Job', '재시도 중', $job_ext['retrying']),
        array('Job', '평균 시도 횟수', $job_ext['avg_attempts']),
        array('Attempt', '전체 시도', $attempt_counts['total']),
        array('Attempt', '성공 시도', $attempt_counts['success']),
        array('Attempt', '실패 시도', $attempt_counts['failed']),
        array('Attempt', '최초 시도 성공', $attempt_counts['first_try_success']),
        array('Attempt', '재시도 후 성공', $attempt_counts['retry_success']),
        array('Attempt', '성공률(%)', $attempt_counts['total'] > 0 ? $success_rate : 0),
    );
    foreach ($metrics as $m) {
        echo "<tr><td>" . bp_export_safe($m[0]) . "</td><td>" . bp_export_safe($m[1]) . "</td><td class='num'>" . bp_export_safe((string) $m[2]) . "</td></tr>";
    }
    echo "</tbody></table><table style='margin-top:10px;'>";
    echo "<caption>실패 원인 분류</caption>";
    echo "<thead><tr><th>원인</th><th>건수</th></tr></thead><tbody>";
    foreach ($error_breakdown as $cat => $cnt) {
        echo "<tr><td>" . bp_export_safe($cat) . "</td><td class='num'>" . (int) $cnt . "</td></tr>";
    }
    echo "</tbody>";

} else {
    echo "<tr><td>지원하지 않는 내보내기 유형입니다.</td></tr>";
}

echo "</table>";
exit;
