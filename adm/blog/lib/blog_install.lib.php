<?php
if (!defined('_GNUBOARD_')) exit;

// install_form.php(화면 표시, GET)와 install.php(설치 처리, POST 전용)가 같은 버전
// 정의/판정 로직을 공유하기 위한 라이브러리. 두 파일이 각자 이 판정 로직을 따로
// 들고 있으면 한쪽만 고쳐질 때 화면 표시와 실제 처리가 어긋날 수 있어 여기로 모았다.

function bp_install_check_extensions(): array
{
    $required = array('mysqli', 'openssl', 'json', 'mbstring', 'curl');
    $status = array();
    foreach ($required as $ext) {
        $status[$ext] = extension_loaded($ext);
    }
    return $status;
}

function bp_install_table_exists(string $prefix, string $table): bool
{
    $like = sql_real_escape_string($prefix . 'blog_' . $table);
    $result = sql_query(" show tables like '{$like}' ", false);
    return $result && sql_num_rows($result) > 0;
}

function bp_install_column_exists(string $prefix, string $table, string $column): bool
{
    $table_name = sql_real_escape_string($prefix . 'blog_' . $table);
    $col_name = sql_real_escape_string($column);
    $result = sql_query(" show columns from `{$table_name}` like '{$col_name}' ", false);
    return $result && sql_num_rows($result) > 0;
}

function bp_install_column_is_nullable(string $prefix, string $table, string $column): bool
{
    $table_name = sql_real_escape_string($prefix . 'blog_' . $table);
    $col_name = sql_real_escape_string($column);
    $result = sql_query(" show columns from `{$table_name}` like '{$col_name}' ", false);
    if (!$result || sql_num_rows($result) === 0) {
        return false;
    }
    $row = sql_fetch_array($result);
    return isset($row['Null']) && strtoupper($row['Null']) === 'YES';
}

// 컬럼의 선언 길이가 최소 $min 이상인지 확인한다(예: varchar(191) >= 191).
// v31처럼 "컬럼을 더 넓게 바꾸는" 마이그레이션의 적용 여부를 판정하는 데 쓴다.
function bp_install_column_length_at_least(string $prefix, string $table, string $column, int $min): bool
{
    $table_name = sql_real_escape_string($prefix . 'blog_' . $table);
    $col_name = sql_real_escape_string($column);
    $result = sql_query(" show columns from `{$table_name}` like '{$col_name}' ", false);
    if (!$result || sql_num_rows($result) === 0) {
        return false;
    }
    $row = sql_fetch_array($result);
    if (empty($row['Type']) || !preg_match('/\((\d+)\)/', $row['Type'], $m)) {
        return false;
    }
    return (int)$m[1] >= $min;
}

function bp_install_index_exists(string $prefix, string $table, string $index): bool
{
    $table_name = sql_real_escape_string($prefix . 'blog_' . $table);
    $index_name = sql_real_escape_string($index);
    $result = sql_query(" SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table_name}' AND INDEX_NAME = '{$index_name}' LIMIT 1 ", false);
    return $result && sql_num_rows($result) > 0;
}

function bp_install_summarize_sql(string $sql): string
{
    $sql = preg_replace('/\s+/', ' ', trim($sql));
    return mb_substr($sql, 0, 300);
}

function bp_install_run_sql_file(string $path, string $prefix): array
{
    global $g5;
    $sql_content = file_get_contents($path);
    $sql_content = str_replace('{prefix}', $prefix, $sql_content);

    $lines = explode("\n", $sql_content);
    $lines = array_filter($lines, function ($line) {
        return strpos(trim($line), '--') !== 0;
    });
    $clean_sql = implode("\n", $lines);
    $statements = array_filter(array_map('trim', explode(';', $clean_sql)));

    $created = array();
    $statement_no = 0;
    
    foreach ($statements as $stmt) {
        $statement_no++;
        if ($stmt === '') {
            continue;
        }
        
        $result = sql_query($stmt, false);
        if ($result === false) {
            $link = isset($g5['connect_db']) ? $g5['connect_db'] : null;
            $error_code = $link ? mysqli_errno($link) : 0;
            
            // 1050: Table already exists
            // 1060: Duplicate column name
            // 1061: Duplicate key name
            // 재설치(업데이트) 시 이미 있는 테이블/컬럼/인덱스 생성 시도는 안전하게 무시 (멱등성 보장)
            if (in_array($error_code, array(1050, 1060, 1061))) {
                continue;
            }
            
            $error_msg = $link ? mysqli_error($link) : 'Unknown error';
            $sqlstate = $link ? mysqli_sqlstate($link) : '';
            
            $error_data = array(
                'statement_no' => $statement_no,
                'error_code' => $error_code,
                'sqlstate' => $sqlstate,
                'message' => $error_msg,
                'sql_summary' => bp_install_summarize_sql($stmt)
            );
            
            throw new RuntimeException(json_encode($error_data));
        }
        
        if (preg_match('/CREATE TABLE IF NOT EXISTS `([a-zA-Z0-9_]+)`/i', $stmt, $m)) {
            $created[] = $m[1];
        }
    }
    return $created;
}

function bp_install_diagnostics(): array
{
    global $g5;
    $link = isset($g5['connect_db']) ? $g5['connect_db'] : null;

    $db_version = '';
    $db_charset = '';
    $db_collation = '';
    if ($link) {
        $row = sql_fetch(" select VERSION() as v, @@character_set_database as cs, @@collation_database as co ");
        if ($row) {
            $db_version = $row['v'];
            $db_charset = $row['cs'];
            $db_collation = $row['co'];
        }
    }

    return array(
        'php_version' => PHP_VERSION,
        'db_version' => $db_version,
        'db_charset_default' => $db_charset,
        'db_collation_default' => $db_collation,
        'connection_charset' => $link ? mysqli_character_set_name($link) : '',
        'mysqli_client_version' => function_exists('mysqli_get_client_info') ? mysqli_get_client_info() : '',
        'extensions' => bp_install_check_extensions(),
    );
}

// SQL 파일 첫머리의 설명 주석에서 한 줄 요약을 뽑아낸다.
// (예: "-- BLOG AUTOMATION: V31 active_lock_key 컬럼 폭 정정" → "active_lock_key 컬럼 폭 정정")
// 자동 발견된 버전의 설명으로 쓰며, 못 읽으면 빈 문자열을 반환한다.
function bp_install_read_sql_title(string $path): string
{
    $fp = @fopen($path, 'r');
    if (!$fp) {
        return '';
    }
    $title = '';
    $line_no = 0;
    while (($line = fgets($fp)) !== false && $line_no < 10) {
        $line_no++;
        $line = trim($line);
        if ($line === '' || strpos($line, '--') !== 0) {
            continue;
        }
        $line = trim(ltrim($line, '-'));
        if ($line === '') {
            continue;
        }
        // "BLOG AUTOMATION: V31 ..." 형태의 접두사를 떼어낸다.
        $line = preg_replace('/^BLOG\s+AUTOMATION\s*:\s*V\d+\s*/i', '', $line);
        $title = $line;
        break;
    }
    fclose($fp);
    return $title;
}

// 버전 번호 => array('label'=>표시명, 'desc'=>설명, 'file'=>SQL 파일 절대경로, 'installed'=>bool|null)
// installed 가 null 이면 "적용 여부를 코드로 판정하지 않는(등록되지 않은) 버전"을 뜻한다.
//
// 아래 목록에 없는 blog_automation_v*.sql 파일이 새로 추가되면 자동으로 목록에 나타난다.
// (예전에는 이 배열에 직접 추가하지 않으면 설치 화면에 아예 표시되지 않아서, 새 마이그레이션이
//  있는지조차 알 수 없었다.) 다만 "설치됨/미설치" 판정은 버전마다 확인 대상이 달라 자동 유추가
// 불가능하므로, 자동 발견된 항목은 installed=null(확인 불가)로 두고 실행만 가능하게 한다.
// 정확한 판정이 필요하면 아래 배열에 해당 버전을 명시적으로 추가하면 된다.
function bp_install_get_versions(string $table_prefix): array
{
    $sql_dir = G5_ADMIN_PATH . '/blog/sql';

    $versions = array(
        1 => array(
            'label' => 'Phase 1', 'desc' => '광고주/사이트 기본 테이블',
            'file' => $sql_dir . '/blog_automation_v1.sql',
            'installed' => bp_install_table_exists($table_prefix, 'advertisers'),
        ),
        2 => array(
            'label' => 'Phase 2', 'desc' => 'AI 생성/품질검사 테이블',
            'file' => $sql_dir . '/blog_automation_v2.sql',
            'installed' => bp_install_table_exists($table_prefix, 'content_title_candidates')
                && bp_install_table_exists($table_prefix, 'content_quality_checks'),
        ),
        3 => array(
            'label' => '콘텐츠 파이프라인', 'desc' => '해시태그 컬럼 + 생성 로그 테이블',
            'file' => $sql_dir . '/blog_automation_v3.sql',
            'installed' => bp_install_table_exists($table_prefix, 'content_generation_logs')
                && bp_install_column_exists($table_prefix, 'posts', 'hashtags'),
        ),
        4 => array(
            'label' => '키워드', 'desc' => 'content_keywords 상태 컬럼',
            'file' => $sql_dir . '/blog_automation_v4.sql',
            'installed' => bp_install_column_exists($table_prefix, 'content_keywords', 'status'),
        ),
        5 => array(
            'label' => '포스트', 'desc' => '포스트 소프트 삭제 컬럼',
            'file' => $sql_dir . '/blog_automation_v5.sql',
            'installed' => bp_install_column_exists($table_prefix, 'content_projects', 'deleted_at'),
        ),
        6 => array(
            'label' => '예약 발행', 'desc' => '예약 발행 관련 컬럼',
            'file' => $sql_dir . '/blog_automation_v6.sql',
            'installed' => bp_install_column_exists($table_prefix, 'publish_jobs', 'schedule_type'),
        ),
        7 => array(
            'label' => '이미지', 'desc' => 'images 테이블',
            'file' => $sql_dir . '/blog_automation_v7.sql',
            'installed' => bp_install_table_exists($table_prefix, 'images'),
        ),
        8 => array(
            'label' => 'Category Mappings', 'desc' => 'category_mappings 테이블',
            'file' => $sql_dir . '/blog_automation_v8.sql',
            'installed' => bp_install_table_exists($table_prefix, 'category_mappings'),
        ),
        9 => array(
            'label' => 'Naver Packages', 'desc' => 'naver_packages 테이블',
            'file' => $sql_dir . '/blog_automation_v9.sql',
            'installed' => bp_install_table_exists($table_prefix, 'naver_packages'),
        ),
        10 => array(
            'label' => 'Image Pipeline', 'desc' => 'image_presets 테이블',
            'file' => $sql_dir . '/blog_automation_v10.sql',
            'installed' => bp_install_table_exists($table_prefix, 'image_presets')
                && bp_install_column_exists($table_prefix, 'images', 'file_hash')
                && bp_install_index_exists($table_prefix, 'images', 'idx_file_hash')
                // v10.sql은 supports_image를 ai_providers에 추가한다(content_projects 아님) -
                // 잘못된 테이블을 확인하고 있어서 V10을 완벽히 설치해도 항상 미설치로 표시됐다.
                && bp_install_column_exists($table_prefix, 'ai_providers', 'supports_image'),
        ),
        11 => array(
            'label' => 'Scheduler/Retry', 'desc' => 'publish_jobs 확장(락, 에러코드)',
            'file' => $sql_dir . '/blog_automation_v11.sql',
            'installed' => bp_install_column_exists($table_prefix, 'publish_jobs', 'locked_at'),
        ),
        12 => array(
            'label' => 'Reports/Performance', 'desc' => '콘텐츠 성과(post_performance) 및 스냅샷',
            'file' => $sql_dir . '/blog_automation_v12.sql',
            'installed' => bp_install_table_exists($table_prefix, 'post_performance')
                && bp_install_table_exists($table_prefix, 'report_snapshots'),
        ),
        13 => array(
            'label' => 'Advertiser Dashboard', 'desc' => '광고주 계정 및 계약 정보 컬럼',
            'file' => $sql_dir . '/blog_automation_v13.sql',
            'installed' => bp_install_table_exists($table_prefix, 'advertiser_accounts')
                && bp_install_column_exists($table_prefix, 'advertisers', 'contract_start_date'),
        ),
        14 => array(
            'label' => 'SNS/채널 연동', 'desc' => 'channel_apps 테이블(채널별 OAuth 앱 설정)',
            'file' => $sql_dir . '/blog_automation_v14.sql',
            'installed' => bp_install_table_exists($table_prefix, 'channel_apps'),
        ),
        15 => array(
            'label' => '통합 제작 폼', 'desc' => 'posts 테이블 builder_state 추가',
            'file' => $sql_dir . '/blog_automation_v15.sql',
            'installed' => bp_install_column_exists($table_prefix, 'posts', 'builder_state'),
        ),
        16 => array(
            'label' => '프론트 스튜디오 (Phase 1)', 'desc' => '단락/프롬프트 이력/콘텐츠 태그 테이블',
            'file' => $sql_dir . '/blog_automation_v16.sql',
            // blog_automation_v16.sql이 만드는 세 테이블이 모두 있어야 적용된 것으로 본다.
            // 판정이 없어 "확인 불가"로만 표시되던 유일한 버전이었다(1~15, 17~31은 등록돼 있음).
            'installed' => bp_install_table_exists($table_prefix, 'post_sections')
                && bp_install_table_exists($table_prefix, 'ai_prompts')
                && bp_install_table_exists($table_prefix, 'content_tags'),
        ),
        17 => array(
            'label' => '새 프로젝트 등록 화면 개편', 'desc' => 'advertisers 대표자/업종, content_projects 목적/타깃/유형 다중선택 컬럼',
            'file' => $sql_dir . '/blog_automation_v17.sql',
            'installed' => bp_install_column_exists($table_prefix, 'advertisers', 'industry')
                && bp_install_column_exists($table_prefix, 'content_projects', 'purpose_tags'),
        ),
        18 => array(
            'label' => '광고주 이메일', 'desc' => 'advertisers 이메일 컬럼 추가',
            'file' => $sql_dir . '/blog_automation_v18.sql',
            'installed' => bp_install_column_exists($table_prefix, 'advertisers', 'email'),
        ),
        19 => array(
            'label' => '프로젝트별 AI 공급자 선택', 'desc' => 'content_projects ai_provider_id/ai_disabled 컬럼 추가',
            'file' => $sql_dir . '/blog_automation_v19.sql',
            'installed' => bp_install_column_exists($table_prefix, 'content_projects', 'ai_provider_id'),
        ),
        20 => array(
            'label' => 'AI 공급자 API 주소 오버라이드', 'desc' => 'ai_providers api_endpoint 컬럼 추가',
            'file' => $sql_dir . '/blog_automation_v20.sql',
            'installed' => bp_install_column_exists($table_prefix, 'ai_providers', 'api_endpoint'),
        ),
        21 => array(
            'label' => 'AI 전체 사용 여부', 'desc' => 'blog_ai_global_settings 테이블 추가(전역 AI 켜기/끄기)',
            'file' => $sql_dir . '/blog_automation_v21.sql',
            'installed' => bp_install_table_exists($table_prefix, 'ai_global_settings'),
        ),
        22 => array(
            'label' => '암호화 키 버전 관리', 'desc' => 'ai_providers encryption_key_version 컬럼 추가(마스터 키 회전 대비)',
            'file' => $sql_dir . '/blog_automation_v22.sql',
            'installed' => bp_install_column_exists($table_prefix, 'ai_providers', 'encryption_key_version'),
        ),
        23 => array(
            'label' => '감사로그 project_id NULL 허용', 'desc' => '프로젝트 무관 로그(키 열람 등)가 외래키 위반으로 조용히 유실되던 문제 수정',
            'file' => $sql_dir . '/blog_automation_v23.sql',
            'installed' => bp_install_column_is_nullable($table_prefix, 'content_activity_logs', 'project_id'),
        ),
        24 => array(
            'label' => '연결 테스트 결과 저장', 'desc' => 'ai_providers last_test_status/last_test_message/last_test_at 컬럼 추가',
            'file' => $sql_dir . '/blog_automation_v24.sql',
            'installed' => bp_install_column_exists($table_prefix, 'ai_providers', 'last_test_status'),
        ),
        25 => array(
            'label' => 'AI 글 생성 조건 프리셋', 'desc' => 'blog_generation_rules 테이블 추가(작성/기술/SEO/금지 조건 체크리스트)',
            'file' => $sql_dir . '/blog_automation_v25.sql',
            'installed' => bp_install_table_exists($table_prefix, 'generation_rules'),
        ),
        26 => array(
            'label' => '발행 작업 확장 및 스냅샷', 'desc' => 'publish_jobs에 스냅샷 및 스케줄러 상태 컬럼 추가',
            'file' => $sql_dir . '/blog_automation_v26.sql',
            'installed' => bp_install_column_exists($table_prefix, 'publish_jobs', 'snapshot_title'),
        ),
        27 => array(
            'label' => '포스트 버전 관리 추가', 'desc' => 'post_versions 테이블 및 타겟 버전 ID 추가',
            'file' => $sql_dir . '/blog_automation_v27.sql',
            'installed' => bp_install_table_exists($table_prefix, 'post_versions'),
        ),
        28 => array(
            'label' => '중복 방지 락 재설계', 'desc' => 'active_lock_key 기반 Idempotency 발행 방어 및 상태 정리',
            'file' => $sql_dir . '/blog_automation_v28.sql',
            'installed' => bp_install_column_exists($table_prefix, 'publish_jobs', 'active_lock_key')
                && bp_install_index_exists($table_prefix, 'publish_jobs', 'uq_active_lock_key'),
        ),
        29 => array(
            'label' => '발행 락 통합 완성', 'desc' => 'lock_hash 컬럼 제거 및 active_lock_key 단일화, 중복 방어 완성',
            'file' => $sql_dir . '/blog_automation_v29.sql',
            'installed' => !bp_install_column_exists($table_prefix, 'publish_jobs', 'lock_hash')
                && bp_install_index_exists($table_prefix, 'publish_jobs', 'uq_publish_jobs_active_lock_key'),
        ),
        30 => array(
            'label' => '단계별 누적 프롬프트 저장', 'desc' => 'blog_post_prompts 테이블 추가(PromptManager 저장/불러오기/작업지시문 생성용)',
            'file' => $sql_dir . '/blog_automation_v30.sql',
            'installed' => bp_install_table_exists($table_prefix, 'post_prompts'),
        ),
        31 => array(
            'label' => '발행 락 키 폭 정정', 'desc' => 'publish_jobs.active_lock_key 를 varchar(191)로 확장 + 종료 상태의 잔여 락 해제(선택 적용 - 코드 수정만으로도 동작함)',
            'file' => $sql_dir . '/blog_automation_v31.sql',
            'installed' => bp_install_column_length_at_least($table_prefix, 'publish_jobs', 'active_lock_key', 191),
        ),
    );

    // 위 목록에 등록되지 않은 SQL 파일을 자동으로 찾아 덧붙인다.
    $files = glob($sql_dir . '/blog_automation_v*.sql');
    if (is_array($files)) {
        foreach ($files as $path) {
            if (!preg_match('/blog_automation_v(\d+)\.sql$/i', basename($path), $m)) {
                continue;
            }
            $v = (int)$m[1];
            if (isset($versions[$v])) {
                continue; // 이미 명시적으로 등록된 버전 - 판정 로직을 그대로 쓴다
            }
            $desc = bp_install_read_sql_title($path);
            $versions[$v] = array(
                'label'     => '자동 감지',
                'desc'      => $desc !== '' ? $desc : basename($path),
                'file'      => $path,
                'installed' => null, // 판정 로직이 등록되지 않음 → 화면에 "확인 불가"로 표시
            );
        }
    }

    ksort($versions);
    return $versions;
}
