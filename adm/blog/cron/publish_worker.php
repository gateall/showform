<?php
// adm/blog/cron/publish_worker.php
// CLI 실행 전용 또는 특정 키 인증 기반 실행
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    if (empty($_GET['token']) || $_GET['token'] !== 'MY_SECRET_TOKEN') { // 실 환경에서는 token 값을 외부 설정에서 불러오도록 변경 필요
        die("Unauthorized");
    }
}

$path = dirname(__FILE__);
while (true) {
    if (file_exists($path . '/common.php')) {
        include_once($path . '/common.php');
        break;
    }
    $path = dirname($path);
    if ($path === '/' || preg_match('/^[a-z]:\\\\$/i', $path)) {
        die('common.php not found.');
    }
}

include_once(G5_ADMIN_PATH . '/blog/lib/blog_publisher.lib.php');

global $g5;
$jobs_table = bp_table('publish_jobs');

// 1. 상태가 pending 이거나 scheduled 이면서 시간이 지난 작업 조회
// Stage 4에서는 scheduled_at 처리 로직도 포함 (status가 pending이거나, scheduled 상태이면서 scheduled_at이 과거인 경우)
// 현재 시스템의 job_dispatch는 status = 'pending' 일 때만 동작하지만, 
// V6에서 scheduled_at 컬럼을 추가했으므로 이를 쿼리에 반영하여 status를 pending으로 바꾼 뒤 진행하는 형태가 필요할 수 있음.
// MVP에서는 pending 인 것들만 가져와 처리. (예약 발행은 별도의 스케줄러가 pending으로 상태를 전이시킨다고 가정)
$sql = " SELECT * FROM {$jobs_table} 
          WHERE status = 'pending'
            AND next_retry_at IS NULL 
            AND (scheduled_at IS NULL OR scheduled_at <= NOW())
          ORDER BY priority DESC, id ASC 
          LIMIT 10 ";

$result = sql_query($sql);
$actor = 'cron';
$count = 0;

while ($row = sql_fetch_array($result)) {
    $res = bp_dispatch_publish_job($row['post_target_id'], $actor);
    
    if ($is_cli) {
        echo "Job ID {$row['id']}: " . ($res['ok'] ? "SUCCESS" : "FAILED ({$res['error']})") . "\n";
    }
    $count++;
}

// 2. 백오프 대기 시간이 지나서 다시 재시도해야 하는 failed 작업 조회
$sql_retry = " SELECT * FROM {$jobs_table} 
                WHERE status = 'failed' 
                  AND next_retry_at <= NOW()
                  AND attempt_count < " . BP_MAX_PUBLISH_ATTEMPTS . "
                ORDER BY priority DESC, id ASC 
                LIMIT 5 ";

$result_retry = sql_query($sql_retry);
while ($row = sql_fetch_array($result_retry)) {
    // bp_retry_publish_job 은 내부적으로 dispatch를 호출함
    $res = bp_retry_publish_job($row['post_target_id'], $actor);
    
    if ($is_cli) {
        echo "Retry Job ID {$row['id']}: " . ($res['ok'] ? "SUCCESS" : "FAILED ({$res['error']})") . "\n";
    }
    $count++;
}

if ($is_cli) {
    echo "Worker finished. Total processed: {$count}\n";
} else {
    echo "OK: {$count}";
}
