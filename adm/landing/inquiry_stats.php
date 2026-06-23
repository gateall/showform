<?php
$sub_menu = "900250"; // 문의 통계 서브메뉴 추가 권장 (admin.menu900.php에 900250 없으면 임의 매핑)
include_once('./_common.php');

$g5['title'] = '랜딩페이지 문의 통계 대시보드';

// 기본 날짜 설정 (최근 7일)
$fr_date = isset($_GET['fr_date']) ? $_GET['fr_date'] : date('Y-m-d', strtotime('-6 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');

// 테이블 명
$stat_table = G5_TABLE_PREFIX . 'landing_page_stats';
$inq_table = G5_TABLE_PREFIX . 'landing_inquiry';
$page_table = G5_TABLE_PREFIX . 'landing_page';

// 1. 요약 카드 데이터 집계
// 총 PV
$sql_pv = " select sum(pv_count) as total_pv from {$stat_table} where stat_date between '{$fr_date}' and '{$to_date}' ";
$row_pv = sql_fetch($sql_pv);
$total_pv = (int)$row_pv['total_pv'];

// 총 문의
$sql_inq = " select count(*) as total_inq from {$inq_table} where DATE(created_at) between '{$fr_date}' and '{$to_date}' ";
$row_inq = sql_fetch($sql_inq);
$total_inq = (int)$row_inq['total_inq'];

// 상담 확정/완료
$sql_done = " select count(*) as done_cnt from {$inq_table} where DATE(created_at) between '{$fr_date}' and '{$to_date}' and status in ('상담중', '예약확정') ";
$row_done = sql_fetch($sql_done);
$done_cnt = (int)$row_done['done_cnt'];

// 전환율 계산
$avg_cr = ($total_pv > 0) ? round(($total_inq / $total_pv) * 100, 2) : 0;
$done_ratio = ($total_inq > 0) ? round(($done_cnt / $total_inq) * 100, 1) : 0;

// 2. 일별 데이터 (라인 차트 및 테이블용)
$daily_data = array();
// 날짜 배열 생성 (빈 날짜 채우기용)
$current = strtotime($fr_date);
$last = strtotime($to_date);
while($current <= $last) {
    $d = date('Y-m-d', $current);
    $daily_data[$d] = array('pv'=>0, 'inq'=>0, 'wait'=>0, 'done'=>0, 'cancel'=>0);
    $current = strtotime('+1 day', $current);
}

// 일별 PV 가져오기
$res_pv = sql_query(" select stat_date, sum(pv_count) as cnt from {$stat_table} where stat_date between '{$fr_date}' and '{$to_date}' group by stat_date ");
while($r = sql_fetch_array($res_pv)) {
    if(isset($daily_data[$r['stat_date']])) $daily_data[$r['stat_date']]['pv'] = (int)$r['cnt'];
}

// 일별 문의 가져오기
$res_inq = sql_query(" select DATE(created_at) as dt, status, count(*) as cnt from {$inq_table} where DATE(created_at) between '{$fr_date}' and '{$to_date}' group by DATE(created_at), status ");
while($r = sql_fetch_array($res_inq)) {
    $dt = $r['dt'];
    if(isset($daily_data[$dt])) {
        $cnt = (int)$r['cnt'];
        $daily_data[$dt]['inq'] += $cnt;
        if($r['status'] == '접수대기') $daily_data[$dt]['wait'] += $cnt;
        else if($r['status'] == '취소') $daily_data[$dt]['cancel'] += $cnt;
        else $daily_data[$dt]['done'] += $cnt; // 상담중, 예약확정
    }
}

// 3. 랜딩페이지별 비중 (도넛 차트용)
$page_data = array();
$sql_page = " select a.landing_id, b.subject, count(*) as cnt 
              from {$inq_table} a left join {$page_table} b on a.landing_id = b.id
              where DATE(a.created_at) between '{$fr_date}' and '{$to_date}'
              group by a.landing_id 
              order by cnt desc limit 5 ";
$res_page = sql_query($sql_page);
while($r = sql_fetch_array($res_page)) {
    $title = $r['subject'] ? get_text(cut_str($r['subject'], 20)) : '랜딩 ID: '.$r['landing_id'];
    $page_data[] = array('label' => $title, 'data' => (int)$r['cnt']);
}

include_once(G5_ADMIN_PATH.'/admin.head.php');

// Chart.js 로드
add_javascript('<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>', 0);
?>

<style>
.stats_wrap { font-family: 'Malgun Gothic', sans-serif; }
.search_box { background:#f8fafc; padding:15px; border-radius:8px; margin-bottom:20px; display:flex; gap:10px; align-items:center; border:1px solid #e2e8f0; }
.search_box input[type="date"] { padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px; }
.btn-quick { padding:6px 12px; background:#e2e8f0; color:#475569; border:none; border-radius:4px; cursor:pointer; font-size:13px; }
.btn-quick:hover { background:#cbd5e1; }
.btn-submit { padding:6px 20px; background:#3b82f6; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold; }

.summary_grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:20px; margin-bottom:30px; }
.summary_card { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:20px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
.summary_card .title { font-size:14px; color:#64748b; margin-bottom:10px; font-weight:bold; }
.summary_card .value { font-size:28px; color:#1e293b; font-weight:900; }
.summary_card .sub { font-size:13px; color:#10b981; margin-top:5px; }

.charts_grid { display:grid; grid-template-columns:2fr 1fr; gap:20px; margin-bottom:30px; }
.chart_box { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:20px; box-shadow:0 1px 3px rgba(0,0,0,0.05); }
.chart_box h3 { margin:0 0 15px 0; font-size:16px; color:#334155; }

.tbl_head01 th, .tbl_head01 td { text-align:center !important; }
</style>

<div class="stats_wrap">

    <form name="fsearch" method="get" class="search_box">
        <strong style="margin-right:10px;">조회기간</strong>
        <button type="button" class="btn-quick" onclick="setDate('<?php echo date('Y-m-d'); ?>','<?php echo date('Y-m-d'); ?>')">오늘</button>
        <button type="button" class="btn-quick" onclick="setDate('<?php echo date('Y-m-d', strtotime('-1 days')); ?>','<?php echo date('Y-m-d', strtotime('-1 days')); ?>')">어제</button>
        <button type="button" class="btn-quick" onclick="setDate('<?php echo date('Y-m-d', strtotime('-6 days')); ?>','<?php echo date('Y-m-d'); ?>')">7일</button>
        <button type="button" class="btn-quick" onclick="setDate('<?php echo date('Y-m-01'); ?>','<?php echo date('Y-m-t'); ?>')">이번달</button>
        <button type="button" class="btn-quick" onclick="setDate('<?php echo date('Y-m-d', strtotime('-30 days')); ?>','<?php echo date('Y-m-d'); ?>')">30일</button>
        
        <input type="date" name="fr_date" id="fr_date" value="<?php echo $fr_date; ?>"> ~ 
        <input type="date" name="to_date" id="to_date" value="<?php echo $to_date; ?>">
        
        <button type="submit" class="btn-submit">통계 조회</button>
    </form>

    <div class="summary_grid">
        <div class="summary_card">
            <div class="title">총 방문자 수 (PV)</div>
            <div class="value"><?php echo number_format($total_pv); ?> <span style="font-size:16px; font-weight:normal;">명</span></div>
        </div>
        <div class="summary_card">
            <div class="title">총 문의 건수</div>
            <div class="value"><?php echo number_format($total_inq); ?> <span style="font-size:16px; font-weight:normal;">건</span></div>
        </div>
        <div class="summary_card">
            <div class="title">평균 전환율 (CR)</div>
            <div class="value"><?php echo number_format($avg_cr, 2); ?> <span style="font-size:16px; font-weight:normal;">%</span></div>
        </div>
        <div class="summary_card">
            <div class="title">상담 완료/확정 건수</div>
            <div class="value"><?php echo number_format($done_cnt); ?> <span style="font-size:16px; font-weight:normal;">건</span></div>
            <div class="sub">(전체 대비 <?php echo $done_ratio; ?>%)</div>
        </div>
    </div>

    <div class="charts_grid">
        <div class="chart_box">
            <h3>일별 문의 인입 추이</h3>
            <canvas id="lineChart" height="100"></canvas>
        </div>
        <div class="chart_box">
            <h3>랜딩페이지별 인입 비중</h3>
            <canvas id="donutChart" height="200"></canvas>
        </div>
    </div>

    <div class="tbl_head01 tbl_wrap">
        <h3>상세 데이터 테이블</h3>
        <table>
            <thead>
                <tr>
                    <th>날짜</th>
                    <th>방문자 수(PV)</th>
                    <th>문의 신청 건수</th>
                    <th>전환율(CR)</th>
                    <th>접수대기</th>
                    <th>상담/완료</th>
                    <th>취소(허위)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $sum_wait = $sum_done = $sum_cancel = 0;
                // 역순으로 출력 (최신 날짜가 위로)
                $rev_daily = array_reverse($daily_data, true);
                foreach ($rev_daily as $dt => $d) {
                    $cr = ($d['pv'] > 0) ? round(($d['inq'] / $d['pv']) * 100, 2) : 0;
                    $sum_wait += $d['wait'];
                    $sum_done += $d['done'];
                    $sum_cancel += $d['cancel'];
                ?>
                <tr>
                    <td><strong><?php echo $dt; ?></strong></td>
                    <td><?php echo number_format($d['pv']); ?> 명</td>
                    <td><strong style="color:#3b82f6;"><?php echo number_format($d['inq']); ?> 건</strong></td>
                    <td><?php echo number_format($cr, 2); ?>%</td>
                    <td><?php echo number_format($d['wait']); ?> 건</td>
                    <td style="color:#10b981;"><?php echo number_format($d['done']); ?> 건</td>
                    <td style="color:#ef4444;"><?php echo number_format($d['cancel']); ?> 건</td>
                </tr>
                <?php } ?>
            </tbody>
            <tfoot>
                <tr style="background:#f8fafc; font-weight:bold;">
                    <td>합계 / 평균</td>
                    <td><?php echo number_format($total_pv); ?> 명</td>
                    <td style="color:#3b82f6;"><?php echo number_format($total_inq); ?> 건</td>
                    <td><?php echo number_format($avg_cr, 2); ?>%</td>
                    <td><?php echo number_format($sum_wait); ?> 건</td>
                    <td style="color:#10b981;"><?php echo number_format($sum_done); ?> 건</td>
                    <td style="color:#ef4444;"><?php echo number_format($sum_cancel); ?> 건</td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div style="margin-top:20px; text-align:right;">
        <a href="./inquiry_stats_excel.php?fr_date=<?php echo $fr_date; ?>&to_date=<?php echo $to_date; ?>" class="btn_submit btn" style="background:#10b981;">통계 엑셀 다운로드</a>
    </div>

</div>

<?php
// 차트용 JSON 데이터 생성
$lbl_dates = array_keys($daily_data);
$data_inq = array_column($daily_data, 'inq');
$data_pv = array_column($daily_data, 'pv');

$lbl_pages = array_column($page_data, 'label');
$data_pages = array_column($page_data, 'data');
?>

<script>
function setDate(fr, to) {
    document.getElementById('fr_date').value = fr;
    document.getElementById('to_date').value = to;
    document.forms['fsearch'].submit();
}

// Line Chart (일별 추이)
const ctxLine = document.getElementById('lineChart').getContext('2d');
new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($lbl_dates); ?>,
        datasets: [
            {
                label: '문의 신청 건수',
                data: <?php echo json_encode($data_inq); ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3,
                yAxisID: 'y'
            },
            {
                label: '방문자 수(PV)',
                data: <?php echo json_encode($data_pv); ?>,
                borderColor: '#cbd5e1',
                backgroundColor: 'transparent',
                borderWidth: 2,
                borderDash: [5, 5],
                tension: 0.3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        interaction: { mode: 'index', intersect: false },
        scales: {
            y: { type: 'linear', display: true, position: 'left', beginAtZero: true },
            y1: { type: 'linear', display: true, position: 'right', beginAtZero: true, grid: { drawOnChartArea: false } }
        }
    }
});

// Donut Chart (랜딩페이지별 비중)
const ctxDonut = document.getElementById('donutChart').getContext('2d');
new Chart(ctxDonut, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(count($lbl_pages) ? $lbl_pages : array('데이터 없음')); ?>,
        datasets: [{
            data: <?php echo json_encode(count($data_pages) ? $data_pages : array(1)); ?>,
            backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' }
        }
    }
});
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
