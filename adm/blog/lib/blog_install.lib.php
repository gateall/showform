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

function bp_install_run_sql_file(string $path, string $prefix): array
{
    $sql_content = file_get_contents($path);
    $sql_content = str_replace('{prefix}', $prefix, $sql_content);

    $lines = explode("\n", $sql_content);
    $lines = array_filter($lines, function ($line) {
        return strpos(trim($line), '--') !== 0;
    });
    $clean_sql = implode("\n", $lines);
    $statements = array_filter(array_map('trim', explode(';', $clean_sql)));

    $created = array();
    foreach ($statements as $stmt) {
        if ($stmt === '') {
            continue;
        }
        sql_query($stmt, false);
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

// 버전 번호 => array('label'=>표시명, 'desc'=>설명, 'file'=>SQL 파일 절대경로, 'installed'=>bool)
function bp_install_get_versions(string $table_prefix): array
{
    $sql_dir = G5_ADMIN_PATH . '/blog/sql';

    return array(
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
            'installed' => bp_install_table_exists($table_prefix, 'image_presets'),
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
    );
}
