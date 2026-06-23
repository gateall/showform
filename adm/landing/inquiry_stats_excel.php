<?php
include_once('./_common.php');

if ($is_admin != 'super') {
    alert('권한이 없습니다.');
}

$fr_date = isset($_GET['fr_date']) ? $_GET['fr_date'] : date('Y-m-d', strtotime('-6 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

$stat_table = G5_TABLE_PREFIX . 'landing_page_stats';
$inq_table = G5_TABLE_PREFIX . 'landing_inquiry';

$daily_data = array();
$current = strtotime($fr_date);
$last = strtotime($to_date);
while($current <= $last) {
    $d = date('Y-m-d', $current);
    $daily_data[$d] = array('pv'=>0, 'inq'=>0, 'wait'=>0, 'done'=>0, 'cancel'=>0);
    $current = strtotime('+1 day', $current);
}

// PV 가져오기
$res_pv = sql_query(" select stat_date, sum(pv_count) as cnt from {$stat_table} where stat_date between '{$fr_date}' and '{$to_date}' group by stat_date ");
while($r = sql_fetch_array($res_pv)) {
    if(isset($daily_data[$r['stat_date']])) $daily_data[$r['stat_date']]['pv'] = (int)$r['cnt'];
}

// 문의 가져오기
$res_inq = sql_query(" select DATE(created_at) as dt, status, count(*) as cnt from {$inq_table} where DATE(created_at) between '{$fr_date}' and '{$to_date}' group by DATE(created_at), status ");
while($r = sql_fetch_array($res_inq)) {
    $dt = $r['dt'];
    if(isset($daily_data[$dt])) {
        $cnt = (int)$r['cnt'];
        $daily_data[$dt]['inq'] += $cnt;
        if($r['status'] == '접수대기') $daily_data[$dt]['wait'] += $cnt;
        else if($r['status'] == '취소') $daily_data[$dt]['cancel'] += $cnt;
        else $daily_data[$dt]['done'] += $cnt;
    }
}

$filename = '상담통계내역_'.$fr_date.'_to_'.$to_date.'.csv';

header("Content-Type: text/csv; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
echo "\xEF\xBB\xBF"; // UTF-8 BOM

$fp = fopen('php://output', 'w');
fputcsv($fp, array('날짜', '방문자수(PV)', '문의건수', '전환율(%)', '접수대기', '상담/완료', '취소'));

$rev_daily = array_reverse($daily_data, true);
foreach ($rev_daily as $dt => $d) {
    $cr = ($d['pv'] > 0) ? round(($d['inq'] / $d['pv']) * 100, 2) : 0;
    fputcsv($fp, array(
        $dt,
        $d['pv'],
        $d['inq'],
        $cr,
        $d['wait'],
        $d['done'],
        $d['cancel']
    ));
}
fclose($fp);
exit;
?>
