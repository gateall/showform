<?php
include_once('./_common.php');

$type = isset($_GET['type']) ? $_GET['type'] : '';
if (!$type) {
    alert('내보내기 유형이 지정되지 않았습니다.');
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
        echo "<td>{$r['completed_at']}</td>";
        echo "<td>{$r['site_name']}</td>";
        echo "<td>{$r['platform']}</td>";
        echo "<td>{$r['post_title']}</td>";
        echo "<td>{$r['published_url']}</td>";
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
        echo "<td>{$r['post_title']}</td>";
        echo "<td>{$r['advertiser_name']}</td>";
        echo "<td>{$r['site_name']}</td>";
        echo "<td>{$r['published_url']}</td>";
        echo "<td class='num'>".(int)$r['view_count']."</td>";
        echo "<td class='num'>".(int)$r['like_count']."</td>";
        echo "<td class='num'>".(int)$r['comment_count']."</td>";
        echo "<td class='num'>".(int)$r['share_count']."</td>";
        echo "<td>{$r['last_synced_at']}</td>";
        echo "</tr>";
    }
    echo "</tbody>";
    
} else {
    echo "<tr><td>지원하지 않는 내보내기 유형입니다.</td></tr>";
}

echo "</table>";
exit;
