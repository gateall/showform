<?php
$sub_menu = '360700';
include_once('./_common.php');

$w = isset($_REQUEST['w']) ? $_REQUEST['w'] : '';

// AJAX Targets request
if ($w == 'ajax_targets') {
    header('Content-Type: application/json; charset=utf-8');
    $post_id = (int)$_POST['post_id'];
    
    $tbl_jobs = bp_table('publish_jobs');
    $tbl_targets = bp_table('post_targets');
    $tbl_sites = bp_table('sites');
    
    $sql = " select t.id, s.name as site_name, 
             (select count(*) from {$tbl_jobs} j where j.post_target_id = t.id and j.status in ('scheduled', 'pending', 'processing')) as active_cnt 
             from {$tbl_targets} t 
             join {$tbl_sites} s on t.site_id = s.id 
             where t.post_id = '{$post_id}' order by t.id asc ";
    $res = sql_query($sql);
    $targets = array();
    while($row = sql_fetch_array($res)){
        $targets[] = array(
            'id' => $row['id'],
            'site_name' => $row['site_name'],
            'has_job' => ($row['active_cnt'] > 0)
        );
    }
    echo json_encode(array('error' => '', 'targets' => $targets));
    exit;
}

auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$tbl_jobs = bp_table('publish_jobs');
$tbl_targets = bp_table('post_targets');

function log_publish_activity($job_id, $target_id, $action, $detail) {
    global $member;
    $tbl = bp_table('content_activity_logs');
    $actor = isset($member['mb_id']) ? $member['mb_id'] : 'admin';
    $action_safe = sql_real_escape_string($action);
    $actor_safe = sql_real_escape_string($actor);
    $detail_safe = sql_real_escape_string($detail);
    
    // 타겟을 통해 project_id 알아내기
    $proj_id = 0;
    if ($target_id) {
        $tbl_t = bp_table('post_targets');
        $tbl_p = bp_table('posts');
        $row = sql_fetch(" select p.project_id from {$tbl_t} t join {$tbl_p} p on t.post_id = p.id where t.id = '{$target_id}' ");
        if ($row) $proj_id = $row['project_id'];
    } else if ($job_id) {
        $tbl_j = bp_table('publish_jobs');
        $tbl_t = bp_table('post_targets');
        $tbl_p = bp_table('posts');
        $row = sql_fetch(" select p.project_id from {$tbl_j} j join {$tbl_t} t on j.post_target_id = t.id join {$tbl_p} p on t.post_id = p.id where j.id = '{$job_id}' ");
        if ($row) $proj_id = $row['project_id'];
    }
    
    sql_query(" insert into {$tbl} set project_id = '{$proj_id}', action = '{$action_safe}', actor = '{$actor_safe}', detail = '{$detail_safe}', created_at = NOW() ");
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($w == '') {
    // 다중 타겟 생성
    $post_target_ids = isset($_POST['post_target_ids']) ? $_POST['post_target_ids'] : array();
    if (empty($post_target_ids) || !is_array($post_target_ids)) {
        alert('발행 대상을 선택해주세요.');
    }
    
    $schedule_type = $_POST['schedule_type'];
    if (!in_array($schedule_type, array('fixed', 'random'))) alert('잘못된 예약 방식입니다.');
    
    $scheduled_at = trim($_POST['scheduled_at']);
    $start_at = trim($_POST['schedule_start_at']);
    $end_at = trim($_POST['schedule_end_at']);
    $priority = (int)$_POST['priority'];
    $memo = sql_real_escape_string(trim($_POST['internal_memo']));
    $creator = isset($member['mb_id']) ? sql_real_escape_string($member['mb_id']) : 'admin';
    
    $now = date('Y-m-d H:i:s');
    
    sql_query("START TRANSACTION");
    
    foreach ($post_target_ids as $tid) {
        $tid = (int)$tid;
        // 타겟 검증
        $trow = sql_fetch(" select * from {$tbl_targets} where id = '{$tid}' ");
        if (!$trow) {
            sql_query("ROLLBACK");
            alert('존재하지 않는 발행 대상이 포함되어 있습니다.');
        }
        
        // 중복 활성 작업 검사
        $active = sql_fetch(" select count(*) as cnt from {$tbl_jobs} where post_target_id = '{$tid}' and status in ('scheduled', 'pending', 'processing') ");
        if ($active['cnt'] > 0) {
            sql_query("ROLLBACK");
            alert("이미 활성 상태인 예약이 존재합니다. (Target ID: {$tid})");
        }
        
        $final_scheduled_at = 'NULL';
        $final_start = 'NULL';
        $final_end = 'NULL';
        
        if ($schedule_type == 'fixed') {
            if ($scheduled_at < $now) {
                sql_query("ROLLBACK");
                alert('과거 시각으로 예약할 수 없습니다.');
            }
            $final_scheduled_at = "'" . sql_real_escape_string($scheduled_at) . ":00'";
        } else {
            if ($start_at >= $end_at) {
                sql_query("ROLLBACK");
                alert('랜덤 종료 시각은 시작 시각보다 커야 합니다.');
            }
            if ($end_at < $now) {
                sql_query("ROLLBACK");
                alert('과거 시각으로 예약할 수 없습니다.');
            }
            
            $ts_start = strtotime($start_at);
            $ts_end = strtotime($end_at);
            $random_ts = mt_rand($ts_start, $ts_end);
            
            if ($random_ts < time()) {
                $random_ts = time() + 60; // 과거면 현재보다 조금 뒤로 보정
            }
            
            $final_start = "'" . sql_real_escape_string($start_at) . ":00'";
            $final_end = "'" . sql_real_escape_string($end_at) . ":00'";
            $final_scheduled_at = "'" . date('Y-m-d H:i:s', $random_ts) . "'";
        }
        
        $sql = " insert into {$tbl_jobs} 
                 set post_target_id = '{$tid}',
                     status = 'scheduled',
                     schedule_type = '{$schedule_type}',
                     scheduled_at = {$final_scheduled_at},
                     schedule_start_at = {$final_start},
                     schedule_end_at = {$final_end},
                     priority = '{$priority}',
                     internal_memo = '{$memo}',
                     created_by = '{$creator}',
                     created_at = NOW() ";
        if (!sql_query($sql, false)) {
            sql_query("ROLLBACK");
            alert("작업 생성 실패");
        }
        $new_job_id = sql_insert_id();
        
        log_publish_activity($new_job_id, $tid, 'job_created', "예약 발행 작업 생성 (ID: {$new_job_id}, 방식: {$schedule_type})");
    }
    
    sql_query("COMMIT");
    alert('정상적으로 예약되었습니다.', './publish_job_list.php');
} 
else if ($w == 'u') {
    // 수정
    $row = sql_fetch(" select * from {$tbl_jobs} where id = '{$id}' ");
    if (!$row) alert('작업이 존재하지 않습니다.');
    if (!in_array($row['status'], array('scheduled', 'pending'))) {
        alert('수정 가능한 상태(scheduled, pending)가 아닙니다.');
    }
    
    $schedule_type = $_POST['schedule_type'];
    if (!in_array($schedule_type, array('fixed', 'random'))) alert('잘못된 예약 방식입니다.');
    
    $scheduled_at = trim($_POST['scheduled_at']);
    $start_at = trim($_POST['schedule_start_at']);
    $end_at = trim($_POST['schedule_end_at']);
    $priority = (int)$_POST['priority'];
    $memo = sql_real_escape_string(trim($_POST['internal_memo']));
    $updater = isset($member['mb_id']) ? sql_real_escape_string($member['mb_id']) : 'admin';
    
    $now = date('Y-m-d H:i:s');
    
    $final_scheduled_at = 'NULL';
    $final_start = 'NULL';
    $final_end = 'NULL';
    
    if ($schedule_type == 'fixed') {
        if ($scheduled_at < $now) alert('과거 시각으로 수정할 수 없습니다.');
        $final_scheduled_at = "'" . sql_real_escape_string($scheduled_at) . ":00'";
    } else {
        if ($start_at >= $end_at) alert('랜덤 종료 시각은 시작 시각보다 커야 합니다.');
        if ($end_at < $now) alert('과거 시각으로 수정할 수 없습니다.');
        
        // 새로 랜덤 시간 생성
        $ts_start = strtotime($start_at);
        $ts_end = strtotime($end_at);
        $random_ts = mt_rand($ts_start, $ts_end);
        if ($random_ts < time()) $random_ts = time() + 60;
        
        $final_start = "'" . sql_real_escape_string($start_at) . ":00'";
        $final_end = "'" . sql_real_escape_string($end_at) . ":00'";
        $final_scheduled_at = "'" . date('Y-m-d H:i:s', $random_ts) . "'";
    }
    
    $sql = " update {$tbl_jobs} 
             set schedule_type = '{$schedule_type}',
                 scheduled_at = {$final_scheduled_at},
                 schedule_start_at = {$final_start},
                 schedule_end_at = {$final_end},
                 priority = '{$priority}',
                 internal_memo = '{$memo}',
                 updated_by = '{$updater}',
                 updated_at = NOW()
             where id = '{$id}' ";
             
    sql_query("START TRANSACTION");
    sql_query($sql);
    log_publish_activity($id, $row['post_target_id'], 'job_updated', "예약 발행 작업 수정 (ID: {$id})");
    sql_query("COMMIT");
    
    alert('수정되었습니다.', './publish_job_form.php?id='.$id.'&w=u');
}
else if ($w == 'cancel') {
    // 취소
    $row = sql_fetch(" select * from {$tbl_jobs} where id = '{$id}' ");
    if (!$row) alert('작업이 존재하지 않습니다.');
    if (!in_array($row['status'], array('scheduled', 'pending'))) {
        alert('취소 가능한 상태(scheduled, pending)가 아닙니다.');
    }
    
    $canceler = isset($member['mb_id']) ? sql_real_escape_string($member['mb_id']) : 'admin';
    
    sql_query("START TRANSACTION");
    $sql = " update {$tbl_jobs} 
             set status = 'cancelled', 
                 cancelled_by = '{$canceler}', 
                 cancelled_at = NOW(), 
                 updated_at = NOW() 
             where id = '{$id}' and status in ('scheduled', 'pending') ";
    sql_query($sql);
    if (sql_affected_rows() == 0) {
        sql_query("ROLLBACK");
        alert('상태 변경 실패. (동시 수정 발생)');
    }
    
    log_publish_activity($id, $row['post_target_id'], 'job_cancelled', "작업 취소됨 (ID: {$id})");
    sql_query("COMMIT");
    
    alert('작업이 취소되었습니다.', './publish_job_list.php');
}
else if ($w == 'retry') {
    // 재시도
    $row = sql_fetch(" select * from {$tbl_jobs} where id = '{$id}' ");
    if (!$row) alert('작업이 존재하지 않습니다.');
    if ($row['status'] != 'failed') {
        alert('실패한 작업만 재시도할 수 있습니다.');
    }
    if ($row['attempt_count'] >= $row['max_retries']) {
        alert('최대 재시도 횟수를 초과하여 재시도할 수 없습니다.');
    }
    
    $updater = isset($member['mb_id']) ? sql_real_escape_string($member['mb_id']) : 'admin';
    
    sql_query("START TRANSACTION");
    // pending으로 되돌리고 횟수 증가
    $sql = " update {$tbl_jobs} 
             set status = 'pending', 
                 attempt_count = attempt_count + 1,
                 updated_by = '{$updater}', 
                 updated_at = NOW() 
             where id = '{$id}' and status = 'failed' ";
    sql_query($sql);
    if (sql_affected_rows() == 0) {
        sql_query("ROLLBACK");
        alert('상태 변경 실패. (동시 수정 발생)');
    }
    
    log_publish_activity($id, $row['post_target_id'], 'job_retried', "작업 재시도 요청 (ID: {$id}, 횟수 증가됨)");
    sql_query("COMMIT");
    
    alert('작업 상태가 재시도(대기중)로 변경되었습니다.', './publish_job_list.php');
}
else {
    alert('잘못된 요청입니다.');
}
