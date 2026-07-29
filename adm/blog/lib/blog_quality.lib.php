<?php
if (!defined('_GNUBOARD_')) exit;

// 콘텐츠 품질 검사 (BLOG_AUTOMATION_QA.md 자동검사 항목 중 외부 서비스 없이 결정적으로
// 판정 가능한 부분만 구현한다 — 사실관계 검증·타사이트 유사도 검사는 별도 서비스가 필요해
// 이번 MVP 범위 밖이다). 각 검사는 pass/warn/fail 중 하나를 반환하며, fail이 하나라도 있으면
// project_action.php의 mode=approve가 승인을 차단한다(작업지시서: "미통과 시 승인 차단").
//
// 임계값은 실제 운영 콘텐츠로 보정된 값이 아니라 MVP 기본값이다 — 운영 데이터가 쌓이면
// 조정이 필요할 수 있다.

const BP_QUALITY_TITLE_MIN = 10;
const BP_QUALITY_TITLE_MAX = 60;
const BP_QUALITY_BODY_FAIL_MIN = 100;
const BP_QUALITY_BODY_WARN_MIN = 400;
const BP_QUALITY_KEYWORD_WARN_DENSITY = 0.03;
const BP_QUALITY_KEYWORD_FAIL_DENSITY = 0.05;

function bp_quality_check_title_length(string $title): array
{
    $len = mb_strlen(trim($title));
    if ($len === 0) {
        return array('status' => 'fail', 'detail' => '제목이 비어 있습니다.');
    }
    if ($len < BP_QUALITY_TITLE_MIN) {
        return array('status' => 'warn', 'detail' => "제목이 짧습니다({$len}자).");
    }
    if ($len > BP_QUALITY_TITLE_MAX) {
        return array('status' => 'fail', 'detail' => "제목이 {$len}자로 60자를 초과합니다.");
    }
    return array('status' => 'pass', 'detail' => "{$len}자");
}

function bp_quality_check_body_length(string $body): array
{
    $len = mb_strlen(trim($body));
    if ($len < BP_QUALITY_BODY_FAIL_MIN) {
        return array('status' => 'fail', 'detail' => "본문이 {$len}자로 너무 짧습니다(최소 " . BP_QUALITY_BODY_FAIL_MIN . "자).");
    }
    if ($len < BP_QUALITY_BODY_WARN_MIN) {
        return array('status' => 'warn', 'detail' => "본문이 {$len}자로 다소 짧습니다.");
    }
    return array('status' => 'pass', 'detail' => "{$len}자");
}

function bp_quality_check_forbidden_words(string $body, string $forbiddenWordsRaw): array
{
    $words = array_filter(array_map('trim', explode("\n", $forbiddenWordsRaw)));
    if (empty($words)) {
        return array('status' => 'pass', 'detail' => '등록된 금지어 없음');
    }
    $found = array();
    foreach ($words as $w) {
        if ($w !== '' && mb_stripos($body, $w) !== false) {
            $found[] = $w;
        }
    }
    if (!empty($found)) {
        return array('status' => 'fail', 'detail' => '금지어 발견: ' . implode(', ', $found));
    }
    return array('status' => 'pass', 'detail' => '금지어 없음');
}

function bp_quality_check_contact_missing(string $body, string $phone, string $consultUrl): array
{
    $missing = array();
    if ($phone !== '' && mb_strpos($body, $phone) === false) {
        $missing[] = '전화번호';
    }
    if ($consultUrl !== '' && mb_strpos($body, $consultUrl) === false) {
        $missing[] = '상담 URL';
    }
    if (in_array('전화번호', $missing, true)) {
        return array('status' => 'fail', 'detail' => '본문에 연락처가 누락됨: ' . implode(', ', $missing));
    }
    if (!empty($missing)) {
        return array('status' => 'warn', 'detail' => '본문에 일부 연락 정보가 누락됨: ' . implode(', ', $missing));
    }
    return array('status' => 'pass', 'detail' => '연락처 정상 포함');
}

// 다른 광고주의 전화번호가 이 본문에 섞여 들어갔는지 확인한다(사업체 정보 혼입).
function bp_quality_check_business_mismatch(string $body, int $currentAdvertiserId): array
{
    $table = bp_table('advertisers');
    $result = sql_query(" select id, name, phone from {$table} where id != '" . (int) $currentAdvertiserId . "' and phone != '' ");
    $mismatches = array();
    while ($row = sql_fetch_array($result)) {
        if (mb_strpos($body, $row['phone']) !== false) {
            $mismatches[] = $row['name'];
        }
    }
    if (!empty($mismatches)) {
        return array('status' => 'fail', 'detail' => '다른 업체 정보 혼입 의심: ' . implode(', ', $mismatches));
    }
    return array('status' => 'pass', 'detail' => '업체 정보 혼입 없음');
}

function bp_quality_check_duplicate_sentences(string $body): array
{
    $sentences = preg_split('/[\.\!\?\n]+/u', $body);
    $sentences = array_filter(array_map('trim', $sentences), function ($s) {
        return mb_strlen($s) > 5;
    });
    $counts = array_count_values($sentences);
    $dupes = array_filter($counts, function ($c) {
        return $c >= 2;
    });
    if (empty($dupes)) {
        return array('status' => 'pass', 'detail' => '중복 문장 없음');
    }
    $max_repeat = max($dupes);
    $status = $max_repeat >= 3 ? 'fail' : 'warn';
    return array('status' => $status, 'detail' => '반복 문장 ' . count($dupes) . '건(최대 ' . $max_repeat . '회 반복)');
}

function bp_quality_check_keyword_stuffing(string $body, string $keyword): array
{
    $keyword = trim($keyword);
    if ($keyword === '') {
        return array('status' => 'pass', 'detail' => '대표 키워드 미설정 — 검사 생략');
    }
    $word_count = max(1, count(preg_split('/\s+/u', trim($body))));
    $keyword_count = substr_count($body, $keyword);
    $density = $keyword_count / $word_count;

    if ($density >= BP_QUALITY_KEYWORD_FAIL_DENSITY) {
        return array('status' => 'fail', 'detail' => "키워드 '{$keyword}' 과다 반복({$keyword_count}회, 밀도 " . round($density * 100, 1) . '%)');
    }
    if ($density >= BP_QUALITY_KEYWORD_WARN_DENSITY) {
        return array('status' => 'warn', 'detail' => "키워드 '{$keyword}' 반복 다소 많음({$keyword_count}회)");
    }
    return array('status' => 'pass', 'detail' => "키워드 '{$keyword}' {$keyword_count}회");
}

// 전체 검사를 실행하고 결과 배열을 반환한다. 저장은 이 함수 밖(bp_run_and_save_quality_check)에서 한다.
function bp_quality_check(array $post, array $advertiser, array $project): array
{
    $body = isset($post['body']) ? (string) $post['body'] : '';
    $title = isset($post['title']) ? (string) $post['title'] : '';

    return array(
        'title_length' => bp_quality_check_title_length($title),
        'body_length' => bp_quality_check_body_length($body),
        'forbidden_words' => bp_quality_check_forbidden_words($body, isset($advertiser['forbidden_words']) ? (string) $advertiser['forbidden_words'] : ''),
        'contact_missing' => bp_quality_check_contact_missing($body, isset($advertiser['phone']) ? (string) $advertiser['phone'] : '', isset($advertiser['consult_url']) ? (string) $advertiser['consult_url'] : ''),
        'business_mismatch' => bp_quality_check_business_mismatch($body, (int) $advertiser['id']),
        'duplicate_sentences' => bp_quality_check_duplicate_sentences($body),
        'keyword_stuffing' => bp_quality_check_keyword_stuffing($body, isset($project['primary_keyword']) ? (string) $project['primary_keyword'] : ''),
    );
}

// 검사를 실행하고 content_quality_checks에 저장, content_projects.quality_score를 갱신한다.
// 반환값은 bp_quality_check()와 동일한 구조 — 호출부가 즉시 화면에 쓸 수 있다.
function bp_run_and_save_quality_check(int $projectId, int $postId, array $post, array $advertiser, array $project): array
{
    $checks = bp_quality_check($post, $advertiser, $project);
    $table = bp_table('content_quality_checks');

    $pass_count = 0;
    foreach ($checks as $key => $result) {
        if ($result['status'] === 'pass') {
            $pass_count++;
        }
        sql_query(" insert into {$table}
                        set project_id = '{$projectId}',
                            post_id = '{$postId}',
                            check_key = '" . sql_real_escape_string($key) . "',
                            status = '" . sql_real_escape_string($result['status']) . "',
                            detail = '" . sql_real_escape_string(mb_substr($result['detail'], 0, 500)) . "',
                            created_at = '" . G5_TIME_YMDHIS . "' ");
    }

    $score = (int) round(($pass_count / max(1, count($checks))) * 100);
    $projects_table = bp_table('content_projects');
    sql_query(" update {$projects_table} set quality_score = '{$score}' where id = '{$projectId}' ");

    return $checks;
}

// 현재 프로젝트/포스트에 대한 가장 최근 검사 실행분만 조회한다(승인 게이트·화면 표시용).
function bp_get_latest_quality_checks(int $projectId, int $postId): array
{
    $table = bp_table('content_quality_checks');
    $result = sql_query(" select * from {$table}
                            where project_id = '{$projectId}' and post_id = '{$postId}'
                            order by id desc limit 20 ");
    $latest = array();
    while ($row = sql_fetch_array($result)) {
        // 같은 check_key가 여러 번 저장돼 있어도(재생성 이력) id desc라 가장 최근 것만 채택
        if (!isset($latest[$row['check_key']])) {
            $latest[$row['check_key']] = $row;
        }
    }
    return $latest;
}

// 최근 검사 결과 중 하나라도 'fail'이 있으면 승인을 막는다.
function bp_quality_check_has_blocking_failure(array $latestChecks): bool
{
    foreach ($latestChecks as $row) {
        if ($row['status'] === 'fail') {
            return true;
        }
    }
    return false;
}
