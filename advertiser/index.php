<?php
// /advertiser/index.php
include_once('./_common.php');
adv_check_login();

$g5['title'] = '광고주 대시보드';
adv_head($g5['title']);

$adv_id = (int)$_SESSION['ss_adv_advertiser_id'];
$tbl_adv = bp_table('advertisers');
$tbl_sites = bp_table('sites');
$tbl_jobs = bp_table('publish_jobs');
$tbl_targets = bp_table('post_targets');
$tbl_posts = bp_table('posts');
$tbl_projects = bp_table('content_projects');

// 광고주 계약 정보
$adv = sql_fetch(" select * from {$tbl_adv} where id = '{$adv_id}' ");
$start_dt = $adv['contract_start_date'];
$end_dt = $adv['contract_end_date'];
$status = $adv['contract_status'];
$quota = (int)$adv['monthly_post_quota'];

$remain_days = 0;
$today = date('Y-m-d');
if ($end_dt && $end_dt !== '0000-00-00') {
    $remain_days = (strtotime($end_dt) - strtotime($today)) / 86400;
}

// 연결된 사이트 수
$site_row = sql_fetch(" select count(*) as cnt from {$tbl_sites} where advertiser_id = '{$adv_id}' and status = 'Y' ");
$site_count = $site_row['cnt'];

// 이번 달 통계
$month_start = date('Y-m-01 00:00:00');
$month_end = date('Y-m-t 23:59:59');

$sql_stats = " select 
    sum(case when j.status = 'published' then 1 else 0 end) as pub_cnt,
    sum(case when j.status = 'pending' then 1 else 0 end) as pend_cnt,
    sum(case when j.status = 'failed' then 1 else 0 end) as fail_cnt
    from {$tbl_jobs} j
    inner join {$tbl_targets} t on t.id = j.post_target_id
    inner join {$tbl_posts} po on po.id = t.post_id
    inner join {$tbl_projects} prj on prj.id = po.project_id
    where prj.advertiser_id = '{$adv_id}'
    and j.scheduled_at between '{$month_start}' and '{$month_end}' ";
$st = sql_fetch($sql_stats);

$pub_cnt = (int)$st['pub_cnt'];
$pend_cnt = (int)$st['pend_cnt'];
$fail_cnt = (int)$st['fail_cnt'];

$achieve_rate = 0;
if ($quota > 0) {
    $achieve_rate = round(($pub_cnt / $quota) * 100);
}
?>

<div class="adv_section">
    <h3>계약 정보</h3>
    <div class="adv_contract_info">
        <p><strong>상태:</strong> <span class="badge badge_<?php echo $status === '운영 중' ? 'good' : 'warn'; ?>"><?php echo $status; ?></span></p>
        <p><strong>계약 기간:</strong> <?php echo $start_dt && $start_dt !== '0000-00-00' ? $start_dt : '미지정'; ?> ~ <?php echo $end_dt && $end_dt !== '0000-00-00' ? $end_dt : '미지정'; ?></p>
        <?php if ($remain_days > 0 && $remain_days <= 30) { ?>
        <p class="adv_warn_text">⚠️ 계약 종료까지 <b><?php echo $remain_days; ?>일</b> 남았습니다. 갱신이 필요합니다.</p>
        <?php } ?>
    </div>
</div>

<div class="adv_section">
    <h3>이번 달 발행 현황 (<?php echo date('Y년 m월'); ?>)</h3>
    <div class="adv_cards">
        <div class="adv_card">
            <div class="adv_card_title">운영 사이트</div>
            <div class="adv_card_value"><?php echo number_format($site_count); ?><span>개</span></div>
        </div>
        <div class="adv_card">
            <div class="adv_card_title">월간 목표</div>
            <div class="adv_card_value"><?php echo number_format($quota); ?><span>건</span></div>
        </div>
        <div class="adv_card">
            <div class="adv_card_title">발행 완료</div>
            <div class="adv_card_value num_good"><?php echo number_format($pub_cnt); ?><span>건</span></div>
        </div>
        <div class="adv_card">
            <div class="adv_card_title">발행 예정</div>
            <div class="adv_card_value"><?php echo number_format($pend_cnt); ?><span>건</span></div>
        </div>
        <div class="adv_card">
            <div class="adv_card_title">실패</div>
            <div class="adv_card_value num_bad"><?php echo number_format($fail_cnt); ?><span>건</span></div>
        </div>
        <div class="adv_card">
            <div class="adv_card_title">달성률</div>
            <div class="adv_card_value num_good"><?php echo $achieve_rate; ?><span>%</span></div>
        </div>
    </div>
</div>

<div class="adv_section">
    <h3>최근 발행 완료 글</h3>
    <div class="adv_list">
        <?php
        $sql = " select j.completed_at, po.title, s.name as site_name, t.published_url
                 from {$tbl_jobs} j
                 inner join {$tbl_targets} t on t.id = j.post_target_id
                 inner join {$tbl_posts} po on po.id = t.post_id
                 inner join {$tbl_projects} prj on prj.id = po.project_id
                 left join {$tbl_sites} s on s.id = t.site_id
                 where prj.advertiser_id = '{$adv_id}' and j.status = 'published'
                 order by j.completed_at desc limit 5 ";
        $res = sql_query($sql);
        $has_list = false;
        while ($r = sql_fetch_array($res)) {
            $has_list = true;
            echo '<div class="adv_list_item">';
            echo '  <div class="adv_list_meta"><span>'.$r['completed_at'].'</span> | <span>'.get_text($r['site_name']).'</span></div>';
            echo '  <div class="adv_list_title">'.get_text($r['title']).'</div>';
            if ($r['published_url']) {
                echo '  <div class="adv_list_link"><a href="'.$r['published_url'].'" target="_blank">게시물 보기 &rarr;</a></div>';
            }
            echo '</div>';
        }
        if (!$has_list) echo '<div class="adv_empty">최근 발행된 글이 없습니다.</div>';
        ?>
    </div>
    <a href="./posts_published.php" class="btn_more">더보기</a>
</div>

<?php adv_tail(); ?>
