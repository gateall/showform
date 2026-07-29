<?php
if (!defined('_GNUBOARD_')) exit;

// 블로그 자동화 보고서 집계 라이브러리 (Stage 8).
// 이번 단계에서 실제 구현하는 보고서(대시보드/일간/사이트별)만 지원한다 — 주간·월간·
// 콘텐츠 성과·PDF·Excel·자동 생성 일정은 이 라이브러리 범위 밖(별도 단계로 남김).
//
// 상태값은 실제 코드에 존재하는 값만 사용한다(작업지시서 예시의 'blocked'는 이 코드베이스의
// publish_jobs 상태머신에 존재하지 않아 사용하지 않음 — 아래 bp_report_job_status_map() 참조).

date_default_timezone_set('Asia/Seoul');

// 원본 상태값 -> 집계용 구분의 명시적 대응표(§4.3).
function bp_report_job_status_map(): array
{
    return array(
        'scheduled'  => array('label' => '예약',    'bucket' => 'scheduled'),
        'pending'    => array('label' => '대기',    'bucket' => 'pending'),
        'claimed'    => array('label' => '처리중',  'bucket' => 'processing'), // 워커가 선점한 상태 — '처리중' 버킷으로 합산
        'processing' => array('label' => '처리중',  'bucket' => 'processing'),
        'published'  => array('label' => '성공',    'bucket' => 'succeeded'),
        'failed'     => array('label' => '실패',    'bucket' => 'failed'),
        'cancelled'  => array('label' => '취소',    'bucket' => 'cancelled'),
    );
}

function bp_report_attempt_status_map(): array
{
    return array(
        'success' => '성공',
        'failed'  => '실패',
    );
}

// 기간 프리셋 -> [from, to] (Asia/Seoul, 'Y-m-d H:i:s'). 잘못된 프리셋은 '오늘'로 대체한다.
function bp_report_period_range(string $preset, string $custom_from = '', string $custom_to = ''): array
{
    $now = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    $today = $now->format('Y-m-d');

    switch ($preset) {
        case 'yesterday':
            $d = (new DateTime('yesterday', new DateTimeZone('Asia/Seoul')))->format('Y-m-d');
            return array('from' => "{$d} 00:00:00", 'to' => "{$d} 23:59:59", 'label' => '어제');
        case 'last7':
            $from = (clone $now)->modify('-6 days')->format('Y-m-d');
            return array('from' => "{$from} 00:00:00", 'to' => "{$today} 23:59:59", 'label' => '최근 7일');
        case 'this_week': {
            $monday = (clone $now)->modify('monday this week')->format('Y-m-d');
            $sunday = (clone $now)->modify('sunday this week')->format('Y-m-d');
            return array('from' => "{$monday} 00:00:00", 'to' => "{$sunday} 23:59:59", 'label' => '이번 주');
        }
        case 'last_week': {
            $monday = (clone $now)->modify('monday this week')->modify('-7 days')->format('Y-m-d');
            $sunday = (clone $now)->modify('sunday this week')->modify('-7 days')->format('Y-m-d');
            return array('from' => "{$monday} 00:00:00", 'to' => "{$sunday} 23:59:59", 'label' => '지난주');
        }
        case 'this_month': {
            $from = $now->format('Y-m-01');
            $to = $now->format('Y-m-t');
            return array('from' => "{$from} 00:00:00", 'to' => "{$to} 23:59:59", 'label' => '이번 달');
        }
        case 'last_month': {
            $lastMonth = (clone $now)->modify('first day of last month');
            $from = $lastMonth->format('Y-m-01');
            $to = $lastMonth->format('Y-m-t');
            return array('from' => "{$from} 00:00:00", 'to' => "{$to} 23:59:59", 'label' => '지난달');
        }
        case 'custom':
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $custom_from) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $custom_to) && $custom_from <= $custom_to) {
                return array('from' => "{$custom_from} 00:00:00", 'to' => "{$custom_to} 23:59:59", 'label' => "{$custom_from} ~ {$custom_to}");
            }
            // 잘못된 사용자 지정 기간은 오늘로 안전하게 대체
            return array('from' => "{$today} 00:00:00", 'to' => "{$today} 23:59:59", 'label' => '오늘(잘못된 기간 지정 → 대체)');
        case 'today':
        default:
            return array('from' => "{$today} 00:00:00", 'to' => "{$today} 23:59:59", 'label' => '오늘');
    }
}

function bp_report_success_rate(int $success, int $totalAttempts): float
{
    if ($totalAttempts <= 0) {
        return 0.0;
    }
    return round(($success / $totalAttempts) * 100, 1);
}

// 오류 코드/메시지로부터 실패 원인을 분류한다 — last_error_code가 있으면 그것을 우선 사용하고
// (§8.2 "기존 구조에 오류 코드가 있으면 오류 코드를 우선 사용한다"), 없을 때만 메시지 패턴을 본다.
function bp_report_classify_error(?string $errorCode, ?string $errorMessage): string
{
    $code = trim((string) $errorCode);
    if ($code !== '') {
        if (in_array($code, array('401', '403'), true)) return '인증 실패';
        if ($code === '404') return '대상 사이트 오류';
        if ($code === '408' || $code === '0') return '요청 시간 초과';
        if ($code === '429') return '요청 한도 초과';
        if (preg_match('/^5\d\d$/', $code)) return '대상 사이트 오류';
        return '기타(코드: ' . $code . ')';
    }

    $msg = mb_strtolower(trim((string) $errorMessage));
    if ($msg === '') return '미분류';
    if (strpos($msg, '인증') !== false || strpos($msg, 'auth') !== false) return '인증 실패';
    if (strpos($msg, 'timeout') !== false || strpos($msg, '시간 초과') !== false) return '요청 시간 초과';
    if (strpos($msg, '연결') !== false || strpos($msg, 'connect') !== false || strpos($msg, 'curl') !== false) return 'API 연결 실패';
    if (strpos($msg, '중복') !== false) return '중복 발행';
    if (strpos($msg, '이미지') !== false) return '이미지 오류';
    if (strpos($msg, '권한') !== false) return '권한 부족';
    if (strpos($msg, '설정') !== false) return '잘못된 설정';
    if (strpos($msg, '검증') !== false || strpos($msg, '검사') !== false) return '콘텐츠 검증 실패';
    return '기타';
}

// 공통 필터(§4.2)를 SQL where 절 배열로 변환한다. $alias는 post_targets 별칭.
function bp_report_build_filters(array $filters, string $projectAlias = 'p', string $targetAlias = 't'): array
{
    $where = array();
    if (!empty($filters['advertiser_id'])) {
        $where[] = "{$projectAlias}.advertiser_id = '" . (int) $filters['advertiser_id'] . "'";
    }
    if (!empty($filters['site_id'])) {
        $where[] = "{$targetAlias}.site_id = '" . (int) $filters['site_id'] . "'";
    }
    if (!empty($filters['project_id'])) {
        $where[] = "{$projectAlias}.id = '" . (int) $filters['project_id'] . "'";
    }
    if (!empty($filters['stx'])) {
        $stx = sql_real_escape_string(trim((string) $filters['stx']));
        $where[] = "(po.title like '%{$stx}%' or {$projectAlias}.primary_keyword like '%{$stx}%')";
    }
    return $where;
}

// 작업(job) 단위 카운트 — §17.1 "포스트 수/발행 작업 수/발행 시도 수"를 서로 다른 지표로 유지.
// 예약·대기·처리중은 현재 그 상태인 작업을 대상, 성공·실패·취소는 completed_at/cancelled_at이
// 기간에 포함되는 작업을 대상으로 한다(§5.3 "예약·대기·취소 건수는 실제 발행 시도 건수에서 제외").
function bp_report_job_counts(string $from, string $to, array $filters = array()): array
{
    $tbl_jobs = bp_table('publish_jobs');
    $tbl_targets = bp_table('post_targets');
    $tbl_posts = bp_table('posts');
    $tbl_projects = bp_table('content_projects');

    $where = bp_report_build_filters($filters, 'prj', 't');
    $where_sql = $where ? ' and ' . implode(' and ', $where) : '';

    $base_join = " from {$tbl_jobs} j
                   inner join {$tbl_targets} t on t.id = j.post_target_id
                   inner join {$tbl_posts} po on po.id = t.post_id
                   inner join {$tbl_projects} prj on prj.id = po.project_id
                   where 1=1 {$where_sql} ";

    $counts = array('scheduled' => 0, 'pending' => 0, 'processing' => 0, 'succeeded' => 0, 'failed' => 0, 'cancelled' => 0);

    // 예약·대기·처리중: 현재 그 상태인 작업 중, scheduled_at 또는 생성일이 기간에 걸리는 것.
    $res = sql_query(" select j.status, count(*) as cnt {$base_join}
                        and j.status in ('scheduled','pending','claimed','processing')
                        and (
                            (j.scheduled_at is not null and j.scheduled_at between '{$from}' and '{$to}')
                            or (j.scheduled_at is null and j.created_at between '{$from}' and '{$to}')
                        )
                        group by j.status ");
    $map = bp_report_job_status_map();
    while ($r = sql_fetch_array($res)) {
        $bucket = isset($map[$r['status']]) ? $map[$r['status']]['bucket'] : null;
        if ($bucket === 'scheduled') $counts['scheduled'] += (int) $r['cnt'];
        elseif ($bucket === 'pending') $counts['pending'] += (int) $r['cnt'];
        elseif ($bucket === 'processing') $counts['processing'] += (int) $r['cnt'];
    }

    // 성공·실패·취소: 완료(completed_at) 또는 취소(cancelled_at)가 기간에 포함되는 작업.
    $res2 = sql_query(" select j.status, count(*) as cnt {$base_join}
                         and (
                             (j.status = 'cancelled' and j.cancelled_at between '{$from}' and '{$to}')
                             or (j.status in ('published','failed') and j.completed_at between '{$from}' and '{$to}')
                         )
                         group by j.status ");
    while ($r = sql_fetch_array($res2)) {
        $bucket = isset($map[$r['status']]) ? $map[$r['status']]['bucket'] : null;
        if ($bucket === 'succeeded') $counts['succeeded'] += (int) $r['cnt'];
        elseif ($bucket === 'failed') $counts['failed'] += (int) $r['cnt'];
        elseif ($bucket === 'cancelled') $counts['cancelled'] += (int) $r['cnt'];
    }

    return $counts;
}

// 시도(attempt) 단위 카운트 — §17.2 재시도 구분 예시를 그대로 반영.
function bp_report_attempt_counts(string $from, string $to, array $filters = array()): array
{
    $tbl_attempts = bp_table('publish_attempts');
    $tbl_jobs = bp_table('publish_jobs');
    $tbl_targets = bp_table('post_targets');
    $tbl_posts = bp_table('posts');
    $tbl_projects = bp_table('content_projects');

    $where = bp_report_build_filters($filters, 'prj', 't');
    $where_sql = $where ? ' and ' . implode(' and ', $where) : '';

    $sql = " select a.status, a.attempt_no, count(*) as cnt
             from {$tbl_attempts} a
             inner join {$tbl_jobs} j on j.id = a.publish_job_id
             inner join {$tbl_targets} t on t.id = j.post_target_id
             inner join {$tbl_posts} po on po.id = t.post_id
             inner join {$tbl_projects} prj on prj.id = po.project_id
             where a.created_at between '{$from}' and '{$to}' {$where_sql}
             group by a.status, (a.attempt_no > 1) ";

    $out = array(
        'total' => 0, 'success' => 0, 'failed' => 0,
        'first_try_success' => 0, 'retry_success' => 0, 'retry_attempts' => 0,
    );
    $res = sql_query($sql);
    while ($r = sql_fetch_array($res)) {
        $cnt = (int) $r['cnt'];
        $out['total'] += $cnt;
        if ($r['status'] === 'success') $out['success'] += $cnt;
        if ($r['status'] === 'failed') $out['failed'] += $cnt;
    }

    // attempt_no>1 그룹만 다시 분리 집계(위 group by는 status만 기준이라 재시도 여부는 별도 조회로 정확히 구한다)
    $res2 = sql_query(" select a.status, count(*) as cnt
                         from {$tbl_attempts} a
                         inner join {$tbl_jobs} j on j.id = a.publish_job_id
                         inner join {$tbl_targets} t on t.id = j.post_target_id
                         inner join {$tbl_posts} po on po.id = t.post_id
                         inner join {$tbl_projects} prj on prj.id = po.project_id
                         where a.created_at between '{$from}' and '{$to}' and a.attempt_no > 1 {$where_sql}
                         group by a.status ");
    while ($r = sql_fetch_array($res2)) {
        $cnt = (int) $r['cnt'];
        $out['retry_attempts'] += $cnt;
        if ($r['status'] === 'success') $out['retry_success'] += $cnt;
    }
    $out['first_try_success'] = $out['success'] - $out['retry_success'];

    return $out;
}

function bp_report_max_retry_failed_count(string $from, string $to, array $filters = array()): int
{
    $tbl_jobs = bp_table('publish_jobs');
    $tbl_targets = bp_table('post_targets');
    $tbl_posts = bp_table('posts');
    $tbl_projects = bp_table('content_projects');

    $where = bp_report_build_filters($filters, 'prj', 't');
    $where_sql = $where ? ' and ' . implode(' and ', $where) : '';

    $row = sql_fetch(" select count(*) as cnt
                        from {$tbl_jobs} j
                        inner join {$tbl_targets} t on t.id = j.post_target_id
                        inner join {$tbl_posts} po on po.id = t.post_id
                        inner join {$tbl_projects} prj on prj.id = po.project_id
                        where j.status = 'failed' and j.attempt_count >= j.max_retries
                          and j.completed_at between '{$from}' and '{$to}' {$where_sql} ");
    return $row ? (int) $row['cnt'] : 0;
}

// 일간 보고서 상세 목록(§5.4).
function bp_report_daily_detail(string $from, string $to, array $filters = array(), int $limit = 200): array
{
    $tbl_jobs = bp_table('publish_jobs');
    $tbl_targets = bp_table('post_targets');
    $tbl_posts = bp_table('posts');
    $tbl_projects = bp_table('content_projects');
    $tbl_adv = bp_table('advertisers');
    $tbl_sites = bp_table('sites');

    $where = bp_report_build_filters($filters, 'prj', 't');
    if (!empty($filters['status']) && isset(bp_report_job_status_map()[$filters['status']])) {
        $where[] = "j.status = '" . sql_real_escape_string($filters['status']) . "'";
    }
    $where_sql = $where ? ' and ' . implode(' and ', $where) : '';

    $sql = " select j.id as job_id, j.scheduled_at, j.completed_at, j.status, j.attempt_count,
                    po.title as post_title, prj.topic, a.name as advertiser_name, s.name as site_name,
                    s.platform, t.published_url, j.last_error, j.last_error_code
             from {$tbl_jobs} j
             inner join {$tbl_targets} t on t.id = j.post_target_id
             inner join {$tbl_posts} po on po.id = t.post_id
             inner join {$tbl_projects} prj on prj.id = po.project_id
             left join {$tbl_adv} a on a.id = prj.advertiser_id
             left join {$tbl_sites} s on s.id = t.site_id
             where (
                 (j.scheduled_at between '{$from}' and '{$to}')
                 or (j.completed_at between '{$from}' and '{$to}')
                 or (j.scheduled_at is null and j.completed_at is null and j.created_at between '{$from}' and '{$to}')
             ) {$where_sql}
             order by coalesce(j.completed_at, j.scheduled_at, j.created_at) desc
             limit {$limit} ";
    $res = sql_query($sql);
    $rows = array();
    while ($r = sql_fetch_array($res)) {
        $rows[] = $r;
    }
    return $rows;
}

// 사이트별 통계(§9).
function bp_report_site_stats(string $from, string $to, array $filters = array()): array
{
    $tbl_sites = bp_table('sites');
    $tbl_adv = bp_table('advertisers');
    $tbl_targets = bp_table('post_targets');
    $tbl_jobs = bp_table('publish_jobs');
    $tbl_posts = bp_table('posts');
    $tbl_projects = bp_table('content_projects');

    $adv_where = '';
    if (!empty($filters['advertiser_id'])) {
        $adv_where = " and s.advertiser_id = '" . (int) $filters['advertiser_id'] . "' ";
    }

    $sql = " select s.id as site_id, s.name as site_name, s.platform, s.status as site_status, a.name as advertiser_name,
                    count(j.id) as total_attempts_jobs,
                    sum(case when j.status = 'published' then 1 else 0 end) as succeeded,
                    sum(case when j.status = 'failed' then 1 else 0 end) as failed,
                    sum(case when j.status in ('pending','claimed','processing') then 1 else 0 end) as pending,
                    sum(case when j.status = 'cancelled' then 1 else 0 end) as cancelled,
                    sum(j.attempt_count) as total_retries,
                    max(case when j.status = 'published' then j.completed_at end) as last_success_at,
                    max(case when j.status = 'failed' then j.completed_at end) as last_failure_at
             from {$tbl_sites} s
             left join {$tbl_adv} a on a.id = s.advertiser_id
             left join {$tbl_targets} t on t.site_id = s.id
             left join {$tbl_jobs} j on j.post_target_id = t.id
                    and (j.scheduled_at between '{$from}' and '{$to}' or j.completed_at between '{$from}' and '{$to}')
             left join {$tbl_posts} po on po.id = t.post_id
             left join {$tbl_projects} prj on prj.id = po.project_id
             where 1=1 {$adv_where}
             group by s.id, s.name, s.platform, s.status, a.name
             order by total_attempts_jobs desc ";
    $res = sql_query($sql);
    $rows = array();
    while ($r = sql_fetch_array($res)) {
        $total = (int) $r['total_attempts_jobs'];
        $succeeded = (int) $r['succeeded'];
        $r['success_rate'] = $total > 0 ? round(($succeeded / $total) * 100, 1) : 0.0;
        $rows[] = $r;
    }
    return $rows;
}
