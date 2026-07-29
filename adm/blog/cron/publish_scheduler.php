<?php
/**
 * 블로그 자동화 스케줄러 (Stage 7)
 * CLI 환경 또는 특정 Secret Key를 통해 예약된 작업(publish_jobs)을 폴링하고 실행한다.
 */
$is_cli = (php_sapi_name() === 'cli');

// 웹 브라우저에서 접근 시 차단 방어 (CLI 전용 권장)
// 필요한 경우 cron_web_secret 같은 설정으로 통과시킬 수 있음
if (!$is_cli) {
    if (!isset($_GET['secret']) || $_GET['secret'] !== 'SECRET_DEV_KEY_REPLACE_ME') {
        header('HTTP/1.0 403 Forbidden');
        die('CLI execution only or invalid secret.');
    }
} else {
    // CLI 환경에서 common.php 오류 방지를 위한 임시 변수 할당
    $_SERVER['SERVER_NAME'] = 'showform.kr';
    $_SERVER['SERVER_PORT'] = '80';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_HOST'] = 'showform.kr';
}

// 그누보드 루트 상대경로 (상황에 맞게 수정)
$g5_path = dirname(dirname(dirname(__DIR__)));
include_once($g5_path . '/common.php');
include_once(G5_ADMIN_PATH . '/blog/lib/blog_scheduler.lib.php');

$limit = 20;
// 인자로 --limit=X 가 들어올 경우 처리
if ($is_cli) {
    global $argv;
    foreach ((array)$argv as $arg) {
        if (strpos($arg, '--limit=') === 0) {
            $val = (int) substr($arg, 8);
            if ($val > 0) $limit = $val;
        }
    }
}

echo "[Scheduler] Started at " . G5_TIME_YMDHIS . "\n";

// 1. Orphan Lock Recovery (만료된 processing 복구)
$recovered = bp_scheduler_recover_locks();
if ($recovered > 0) {
    echo "[Scheduler] Recovered {$recovered} orphaned locks.\n";
}

// 2. 예약 작업 및 재시도 대기 작업 폴링 및 잠금 (Lock)
$locked_jobs = bp_scheduler_poll_and_lock_jobs($limit);
$locked_count = count($locked_jobs);
echo "[Scheduler] Polled and locked {$locked_count} jobs (Limit: {$limit}).\n";

// 3. 작업 실행
$success_count = 0;
$fail_count = 0;

foreach ($locked_jobs as $job) {
    echo "  -> Processing Job ID {$job['id']} (Target: {$job['post_target_id']})\n";
    $res = bp_scheduler_process_job($job);
    if ($res['ok']) {
        $success_count++;
        echo "     [SUCCESS] " . (isset($res['published_url']) ? $res['published_url'] : '') . "\n";
    } else {
        $fail_count++;
        echo "     [FAILED] " . $res['error'] . "\n";
    }
}

echo "[Scheduler] Completed. (Success: {$success_count}, Fail: {$fail_count})\n";
exit(0);
