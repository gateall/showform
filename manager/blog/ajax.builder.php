<?php
include_once('./_common.php');
header('Content-Type: application/json; charset=utf-8');

// $g5['table_prefix']는 Gnuboard 코어 어디에도 정의돼 있지 않다(코어는 G5_TABLE_PREFIX
// 상수를 쓴다). 이 파일 여러 곳에서 $g5['table_prefix']를 그대로 참조하고 있었는데,
// PHP가 정의되지 않은 배열키 접근 경고를 화면에 출력하는 설정이면 그 경고 텍스트가
// JSON 응답 앞에 섞여 나가 fetch().json()이 깨지고 "통신 오류가 발생했습니다"로 보였다.
// 여기서 한 번 확정해두면 이 파일 안의 모든 $g5['table_prefix'] 참조가 정상적으로 풀린다.
if (!isset($g5['table_prefix']) || $g5['table_prefix'] === '') {
    $g5['table_prefix'] = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_';
}

if ($is_admin != 'super') {
    echo json_encode(array('success' => false, 'error' => '관리자 권한이 없습니다.'));
    exit;
}

// 테이블의 컬럼 목록을 한 번만 읽어 집합으로 돌려준다(테이블이 없으면 빈 배열).
// 컬럼마다 SHOW COLUMNS를 던지면 요청당 질의가 수십 건으로 늘어나므로 한 번만 읽는다.
function bp_table_columns($table) {
    $cols = array();
    $res = sql_query("SHOW COLUMNS FROM {$table}", false);
    if (!$res) return $cols;
    while ($row = sql_fetch_array($res)) {
        $cols[$row['Field']] = true;
    }
    return $cols;
}

// 컬럼을 하나씩 개별로 추가한다.
// 여러 컬럼을 한 ALTER 문에 묶으면 그중 하나만 이미 존재해도 MySQL이 문장 전체를
// 거부하고, sql_query(..., false)라 그 실패가 조용히 묻혀 나머지 컬럼이 통째로
// 누락된다. 센티넬 컬럼 하나로 나머지의 존재를 추정하던 방식도 같은 이유로 버렸다.
// AFTER 참조 컬럼이 아직 없으면 AFTER 절을 빼고 추가한다(컬럼 위치는 기능과 무관).
function bp_add_column($table, &$cols, $column, $definition, $after = '') {
    if (isset($cols[$column])) return true;

    $after_sql = '';
    if ($after !== '' && isset($cols[$after])) {
        $after_sql = " AFTER `{$after}`";
    }
    sql_query("ALTER TABLE {$table} ADD COLUMN `{$column}` {$definition}{$after_sql}", false);

    // 실패를 조용히 넘기지 않는다 — 실제 반영 여부를 확인하고 남긴다.
    if (!sql_fetch("SHOW COLUMNS FROM {$table} LIKE '{$column}'", false)) {
        error_log("[blog_library] {$table}.{$column} 컬럼 추가 실패");
        return false;
    }
    $cols[$column] = true;
    return true;
}

function check_and_update_library_schema($g5) {
    $items_table = $g5['table_prefix'] . 'blog_library_items';
    $cat_table = $g5['table_prefix'] . 'blog_library_categories';

    $item_cols = bp_table_columns($items_table);
    if (!$item_cols) return; // 테이블 자체가 없으면 설치 전 상태다.

    bp_add_column($items_table, $item_cols, 'description', 'text', 'instruction_content');
    bp_add_column($items_table, $item_cols, 'recommended_purpose', 'varchar(255) DEFAULT NULL', 'description');
    bp_add_column($items_table, $item_cols, 'recommended_industries', 'varchar(255) DEFAULT NULL', 'recommended_purpose');
    bp_add_column($items_table, $item_cols, 'tags', 'varchar(255) DEFAULT NULL', 'recommended_industries');
    bp_add_column($items_table, $item_cols, 'is_active', "tinyint(1) DEFAULT '1'", 'is_system');
    bp_add_column($items_table, $item_cols, 'deleted_at', 'datetime DEFAULT NULL', 'updated_at');
    bp_add_column($items_table, $item_cols, 'created_by', 'varchar(50) DEFAULT NULL', 'deleted_at');
    bp_add_column($items_table, $item_cols, 'updated_by', 'varchar(50) DEFAULT NULL', 'created_by');

    // V3: item_code, structure_steps, expected_length
    bp_add_column($items_table, $item_cols, 'item_code', 'varchar(100) DEFAULT NULL', 'item_name');
    bp_add_column($items_table, $item_cols, 'structure_steps', 'text', 'instruction_content');
    bp_add_column($items_table, $item_cols, 'expected_length', 'varchar(50) DEFAULT NULL', 'structure_steps');

    $cat_cols = bp_table_columns($cat_table);
    if ($cat_cols) {
        bp_add_column($cat_table, $cat_cols, 'category_code', 'varchar(100) DEFAULT NULL', 'category_name');
        bp_add_column($cat_table, $cat_cols, 'icon', 'varchar(50) DEFAULT NULL', 'description');
        bp_add_column($cat_table, $cat_cols, 'updated_at', 'datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'created_at');
        bp_add_column($cat_table, $cat_cols, 'deleted_at', 'datetime DEFAULT NULL', 'updated_at');

        // 유니크 인덱스는 대상 컬럼이 실제로 있을 때만 시도한다.
        if (isset($cat_cols['category_code'])) {
            $cat_idx = sql_fetch("SHOW INDEX FROM {$cat_table} WHERE Key_name = 'uq_cat_code'", false);
            if (!$cat_idx) {
                sql_query("ALTER TABLE {$cat_table} ADD UNIQUE KEY `uq_cat_code` (`category_code`)", false);
            }
        }
    }

    if (isset($item_cols['item_code'])) {
        $item_idx = sql_fetch("SHOW INDEX FROM {$items_table} WHERE Key_name = 'uq_item_code'", false);
        if (!$item_idx) {
            sql_query("ALTER TABLE {$items_table} ADD UNIQUE KEY `uq_item_code` (`item_code`)", false);
        }
    }
}
check_and_update_library_schema($g5);

$action = isset($_POST['action']) ? $_POST['action'] : '';

// ==========================================
// Compatibility Layer for New Managers
// ==========================================
$new_actions = array(
    'load_structures' => array('action' => 'load_library_items', 'type' => 'structure'),
    'load_structure_categories' => array('action' => 'load_library_categories', 'type' => 'structure'),
    'save_structure' => array('action' => 'save_library_item', 'type' => 'structure'),
    'delete_structure' => array('action' => 'delete_library_item', 'type' => 'structure'),
    'duplicate_structure' => array('action' => 'duplicate_library_item', 'type' => 'structure'),
    'toggle_favorite_structure' => array('action' => 'toggle_favorite_library_item', 'type' => 'structure'),
    
    'save_structure_category' => array('action' => 'save_library_category', 'type' => 'structure'),
    'delete_structure_category' => array('action' => 'delete_library_category', 'type' => 'structure'),
    
    'load_ai_conditions' => array('action' => 'load_library_items', 'type' => 'generation_condition'),
    'load_ai_condition_categories' => array('action' => 'load_library_categories', 'type' => 'generation_condition'),
    'save_ai_condition' => array('action' => 'save_library_item', 'type' => 'generation_condition'),
    'delete_ai_condition' => array('action' => 'delete_library_item', 'type' => 'generation_condition'),
    'duplicate_ai_condition' => array('action' => 'duplicate_library_item', 'type' => 'generation_condition'),
    'toggle_favorite_ai_condition' => array('action' => 'toggle_favorite_library_item', 'type' => 'generation_condition'),
    
    'save_ai_condition_category' => array('action' => 'save_library_category', 'type' => 'generation_condition'),
    'delete_ai_condition_category' => array('action' => 'delete_library_category', 'type' => 'generation_condition'),
);

$manager_filter_type = null;
if (isset($new_actions[$action])) {
    $manager_filter_type = $new_actions[$action]['type'];
    $_POST['instruction_type'] = $new_actions[$action]['type'];
    $instruction_type = $new_actions[$action]['type'];
    $action = $new_actions[$action]['action'];
}

$adv_table = bp_table('advertisers');
$projects_table = bp_table('content_projects');
$posts_table = bp_table('posts');
$logs_table = bp_table('content_activity_logs');

if ($action === 'migrate_v2_library') {
    if ($is_admin != 'super') {
        echo json_encode(array('success' => false, 'error' => '최고관리자만 실행할 수 있습니다.'));
        exit;
    }
    
    $sql_file = __DIR__ . '/migrate_library_v2.sql';
    if (!file_exists($sql_file)) {
        echo json_encode(array('success' => false, 'error' => 'SQL 파일을 찾을 수 없습니다. ('.$sql_file.')'));
        exit;
    }
    
    $items_table = $g5['table_prefix'] . 'blog_library_items';
    
    function get_library_counts($table) {
        $res = sql_query("SHOW TABLES LIKE '{$table}'", false);
        if ($res && sql_fetch_array($res)) {
            $row = sql_fetch("SELECT 
                SUM(CASE WHEN library_type='structure' THEN 1 ELSE 0 END) as structure_cnt,
                SUM(CASE WHEN library_type='generation_condition' THEN 1 ELSE 0 END) as condition_cnt,
                SUM(CASE WHEN library_type='additional_instruction' THEN 1 ELSE 0 END) as instruction_cnt
                FROM {$table}", false);
            if ($row) return $row;
        }
        return array('structure_cnt'=>0, 'condition_cnt'=>0, 'instruction_cnt'=>0);
    }
    
    $before_counts = get_library_counts($items_table);
    
    $sql_content = file_get_contents($sql_file);
    // Replace hardcoded prefix with current prefix
    $sql_content = str_replace('`g5_', '`' . $g5['table_prefix'], $sql_content);
    $queries = explode(';', $sql_content);
    $errors = array();

    $attempted = array('structure'=>0, 'generation_condition'=>0, 'additional_instruction'=>0);
    $failed = array('structure'=>0, 'generation_condition'=>0, 'additional_instruction'=>0);

    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;

        $lines = explode("\n", $query);
        $clean_query = "";
        foreach ($lines as $line) {
            if (strpos(trim($line), '--') !== 0) {
                $clean_query .= $line . "\n";
            }
        }
        
        $clean_query = trim($clean_query);
        if ($clean_query != "") {
            $is_item_insert = preg_match("/INSERT IGNORE INTO `{$g5['table_prefix']}blog_library_items`/i", $clean_query);
            $curr_attempt = array('structure'=>0, 'generation_condition'=>0, 'additional_instruction'=>0);
            
            if ($is_item_insert) {
                if (preg_match_all("/\(0,\s*'structure'/i", $clean_query, $matches)) {
                    $curr_attempt['structure'] = count($matches[0]);
                    $attempted['structure'] += $curr_attempt['structure'];
                }
                if (preg_match_all("/\(0,\s*'generation_condition'/i", $clean_query, $matches)) {
                    $curr_attempt['generation_condition'] = count($matches[0]);
                    $attempted['generation_condition'] += $curr_attempt['generation_condition'];
                }
                if (preg_match_all("/\(0,\s*'additional_instruction'/i", $clean_query, $matches)) {
                    $curr_attempt['additional_instruction'] = count($matches[0]);
                    $attempted['additional_instruction'] += $curr_attempt['additional_instruction'];
                }
            }

            $result = sql_query($clean_query, false);
            if (!$result) {
                $err_info = sql_error_info();
                $errors[] = $err_info;
                if ($is_item_insert) {
                    $failed['structure'] += $curr_attempt['structure'];
                    $failed['generation_condition'] += $curr_attempt['generation_condition'];
                    $failed['additional_instruction'] += $curr_attempt['additional_instruction'];
                }
            }
        }
    }

    $after_counts = get_library_counts($items_table);
    
    // V3 PHP Migration Script Execution
    $v3_script = __DIR__ . '/migrate_library_v3.php';
    if (file_exists($v3_script)) {
        include $v3_script;
    }
    
    $after_counts_v3 = get_library_counts($items_table);
    
    $results = array(
        'structure' => array('new' => 0, 'existing' => 0, 'failed' => 0),
        'condition' => array('new' => 0, 'existing' => 0, 'failed' => 0),
        'instruction' => array('new' => 0, 'existing' => 0, 'failed' => 0)
    );
    
    $results['structure']['new'] = max(0, (int)$after_counts['structure_cnt'] - (int)$before_counts['structure_cnt']);
    $results['structure']['failed'] = $failed['structure'];
    $results['structure']['existing'] = max(0, $attempted['structure'] - $results['structure']['new'] - $results['structure']['failed']);
    
    $results['condition']['new'] = max(0, (int)$after_counts['condition_cnt'] - (int)$before_counts['condition_cnt']);
    $results['condition']['failed'] = $failed['generation_condition'];
    $results['condition']['existing'] = max(0, $attempted['generation_condition'] - $results['condition']['new'] - $results['condition']['failed']);
    
    $results['instruction']['new'] = max(0, (int)$after_counts['instruction_cnt'] - (int)$before_counts['instruction_cnt']);
    $results['instruction']['failed'] = $failed['additional_instruction'];
    $results['instruction']['existing'] = max(0, $attempted['additional_instruction'] - $results['instruction']['new'] - $results['instruction']['failed']);
    
    // V3에서 추가된 구조체 카운트 재조정
    $results['structure']['new'] = max(0, (int)$after_counts_v3['structure_cnt'] - (int)$before_counts['structure_cnt']);
    
    $total_new = $results['structure']['new'] + $results['condition']['new'] + $results['instruction']['new'];
    
    if ($total_new == 0 && count($errors) > 0) {
        echo json_encode(array('success' => false, 'error' => "적용된 건수가 없으며 다음 오류가 발생했습니다:\n" . implode("\n", $errors)));
        exit;
    }
    
    $msg = "DB 반영 결과:\n";
    $msg .= "- 글 전개 구조: 신규 {$results['structure']['new']}건 / 기존 {$results['structure']['existing']}건 / 실패 {$results['structure']['failed']}건\n";
    $msg .= "- AI 생성 조건: 신규 {$results['condition']['new']}건 / 기존 {$results['condition']['existing']}건 / 실패 {$results['condition']['failed']}건\n";
    $msg .= "- 추가 지시 라이브러리: 신규 {$results['instruction']['new']}건 / 기존 {$results['instruction']['existing']}건 / 실패 {$results['instruction']['failed']}건\n";
    $msg .= "- 전체 DB 반영 건수: {$total_new}건";

    echo json_encode(array('success' => true, 'message' => $msg));
    exit;
}

if ($action === 'migrate_v28') {
    if ($is_admin != 'super') {
        echo json_encode(array('success' => false, 'error' => '최고관리자만 실행할 수 있습니다.'));
        exit;
    }
    
    $sql_file = __DIR__ . '/../../adm/blog/sql/blog_automation_v28.sql';
    if (!file_exists($sql_file)) {
        echo json_encode(array('success' => false, 'error' => 'SQL 파일을 찾을 수 없습니다. ('.$sql_file.')'));
        exit;
    }
    
    $sql_content = file_get_contents($sql_file);
    global $g5;
    $sql_content = str_replace('{prefix}', $g5['table_prefix'], $sql_content);

    // v28 Idempotency check for active_lock_key unique index
    $idx_check = sql_query("SHOW INDEX FROM {$g5['table_prefix']}blog_publish_jobs WHERE Key_name = 'uq_active_lock_key'", false);
    $has_index = ($idx_check && sql_fetch_array($idx_check));

    $queries = explode(';', $sql_content);
    $errors = array();
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;

        // migrate_v2_library에서 발견된 것과 동일한 버그를 여기서도 미리 제거한다 - 정제 전
        // 원본이 "--"로 시작하는지만 보고 통째로 skip하면, 앞에 주석이 붙은 실제 SQL 문이
        // 전부 유실된다. 반드시 줄 단위 주석 제거 결과로만 빈 값 여부를 판단한다.
        $lines = explode("\n", $query);
        $clean_query = "";
        foreach($lines as $line) {
            if(strpos(trim($line), '--') !== 0) {
                $clean_query .= $line . "\n";
            }
        }
        if(trim($clean_query) != "") {
            // If the index already exists, skip the ADD UNIQUE INDEX query
            if ($has_index && stripos($clean_query, 'ADD UNIQUE INDEX `uq_active_lock_key`') !== false) {
                continue;
            }
            
            $result = sql_query($clean_query, false);
            if (!$result) {
                $err = sql_error_info();
                // 1062 Duplicate entry or 1091 Can't drop (if dropping non-existent) can be ignored if we want,
                // but let's record them for visibility, or skip known safe errors.
                $errors[] = $err;
            }
        }
    }
    
    if (count($errors) > 0) {
        echo json_encode(array('success' => false, 'error' => '마이그레이션 중 일부 쿼리 실패', 'details' => $errors));
        exit;
    }
    
    echo json_encode(array('success' => true));
    exit;
}

if ($action === 'update_status') {
    $project_id = (int)$_POST['project_id'];
    $status = trim($_POST['status']);

    sql_query(" update {$projects_table} set status = '" . sql_real_escape_string($status) . "', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$project_id}' ");
    sql_query(" insert into {$logs_table} set project_id = '{$project_id}', action = 'status_change', actor = '" . sql_real_escape_string($member['mb_id']) . "', detail = '상태 변경: {$status}', created_at = '" . G5_TIME_YMDHIS . "' ");

    echo json_encode(array('success' => true));
    exit;
}

if ($action === 'update_ai_pref') {
    $project_id = (int) $_POST['project_id'];
    if ($project_id <= 0) {
        echo json_encode(array('success' => false, 'error' => '프로젝트가 지정되지 않았습니다.'));
        exit;
    }
    $ai_provider_id = isset($_POST['ai_provider_id']) ? (int) $_POST['ai_provider_id'] : 0;
    $ai_disabled = isset($_POST['ai_disabled']) && $_POST['ai_disabled'] === 'Y' ? 'Y' : 'N';

    // 선택한 공급자가 실재하는지 확인한다 - 존재하지 않는 id를 저장해두면
    // bp_ai_get_active_provider()가 조용히 전역 활성 공급자로 폴백해버려 원인 파악이 어려워진다.
    $provider_value = 'NULL';
    if ($ai_provider_id > 0) {
        $providers_table = bp_table('ai_providers');
        $found = sql_fetch(" select id from {$providers_table} where id = '{$ai_provider_id}' ");
        if (!$found) {
            echo json_encode(array('success' => false, 'error' => '선택한 AI 공급자를 찾을 수 없습니다.'));
            exit;
        }
        $provider_value = "'{$ai_provider_id}'";
    }

    sql_query(" update {$projects_table} set ai_provider_id = {$provider_value}, ai_disabled = '{$ai_disabled}', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$project_id}' ");
    echo json_encode(array('success' => true));
    exit;
}

if ($action === 'load_projects_by_adv') {
    $advertiser_id = (int)$_POST['advertiser_id'];
    $result = sql_query(" select id, topic, status from {$projects_table} where advertiser_id = '{$advertiser_id}' order by id desc ");
    $projects = array();
    while($row = sql_fetch_array($result)) {
        $projects[] = $row;
    }
    echo json_encode(array('success' => true, 'projects' => $projects));
    exit;
}

if ($action === 'load_project') {
    $project_id = (int)$_POST['project_id'];
    $project = sql_fetch(" select * from {$projects_table} where id = '{$project_id}' ");
    if (!$project) {
        echo json_encode(array('success' => false, 'error' => '프로젝트를 찾을 수 없습니다.'));
        exit;
    }
    // post_builder.php 1단계의 "업체 정보 삽입" 체크박스가 실제 값 유무를 판단하는 데
    // 필요해서 연락처 필드까지 함께 내려준다(이전엔 id, name만 있었음).
    $advertiser = sql_fetch(" select id, name, phone, email, address, domain, consult_url from {$adv_table} where id = '{$project['advertiser_id']}' ");
    
    // 가장 최근 임시저장 내용(draft)이나 작성된 내용 찾기
    $latest_post = sql_fetch(" select id, title, body, updated_at from {$posts_table} where project_id = '{$project_id}' order by id desc limit 1 ");
    
    echo json_encode(array(
        'success' => true,
        'project' => $project,
        'advertiser' => $advertiser,
        'latest_post' => $latest_post
    ));
    exit;
}

if ($action === 'save_project') {
    $id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $advertiser_id = (int)$_POST['advertiser_id'];
    $topic = trim($_POST['topic']);
    $primary_keyword = trim($_POST['primary_keyword']);
    $content_type = trim($_POST['content_type']);
    $content_length = trim($_POST['content_length']);

    // 선택 중심 UI 신규 필드 - 체크박스 값은 JS에서 콤마구분 문자열로 이미 합쳐서 전달한다.
    $project_name_input = trim(isset($_POST['project_name']) ? $_POST['project_name'] : '');
    $purpose_tags = trim(isset($_POST['purpose_tags']) ? $_POST['purpose_tags'] : '');
    $content_type_secondary = trim(isset($_POST['content_type_secondary']) ? $_POST['content_type_secondary'] : '');
    $target_reader_type = trim(isset($_POST['target_reader_type']) ? $_POST['target_reader_type'] : '');
    $target_age_group = trim(isset($_POST['target_age_group']) ? $_POST['target_age_group'] : '');
    $target_customer_stage = trim(isset($_POST['target_customer_stage']) ? $_POST['target_customer_stage'] : '');
    $target_region = trim(isset($_POST['target_region']) ? $_POST['target_region'] : '');
    $target_audience_detail = trim(isset($_POST['target_audience_detail']) ? $_POST['target_audience_detail'] : '');
    $project_notes = trim(isset($_POST['project_notes']) ? $_POST['project_notes'] : '');

    $cta_enabled = isset($_POST['cta_enabled']) ? (int)$_POST['cta_enabled'] : 1;
    $cta_content = trim(isset($_POST['cta_content']) ? $_POST['cta_content'] : '');
    $cta_company_fields = trim(isset($_POST['cta_company_fields']) ? $_POST['cta_company_fields'] : '');

    if (!$advertiser_id || !$topic) {
        echo json_encode(array('success' => false, 'error' => '필수 항목(광고주, 핵심 주제)이 누락되었습니다.'));
        exit;
    }

    // target_audience는 기존 코드(AI 프롬프트 조립 등)가 그대로 읽는 요약 컬럼이므로
    // 새로 나뉜 4개 체크 항목 + 상세설명을 사람이 읽을 수 있는 한 줄로 합쳐서 계속 채워준다.
    $target_summary_parts = array();
    if ($target_reader_type !== '') $target_summary_parts[] = $target_reader_type;
    if ($target_age_group !== '') $target_summary_parts[] = $target_age_group;
    if ($target_customer_stage !== '') $target_summary_parts[] = $target_customer_stage;
    if ($target_region !== '') $target_summary_parts[] = $target_region;
    $target_audience = implode(' / ', $target_summary_parts);
    if ($target_audience_detail !== '') {
        $target_audience = $target_audience !== '' ? $target_audience . ' - ' . $target_audience_detail : $target_audience_detail;
    }

    $set_sql = " advertiser_id = '{$advertiser_id}',
                 topic = '" . sql_real_escape_string($topic) . "',
                 primary_keyword = '" . sql_real_escape_string($primary_keyword) . "',
                 content_type = '" . sql_real_escape_string($content_type) . "',
                 content_type_secondary = '" . sql_real_escape_string($content_type_secondary) . "',
                 purpose_tags = '" . sql_real_escape_string($purpose_tags) . "',
                 target_audience = '" . sql_real_escape_string($target_audience) . "',
                 target_reader_type = '" . sql_real_escape_string($target_reader_type) . "',
                 target_age_group = '" . sql_real_escape_string($target_age_group) . "',
                 target_customer_stage = '" . sql_real_escape_string($target_customer_stage) . "',
                 target_region = '" . sql_real_escape_string($target_region) . "',
                 target_audience_detail = '" . sql_real_escape_string($target_audience_detail) . "',
                 project_notes = '" . sql_real_escape_string($project_notes) . "',
                 content_length = '" . sql_real_escape_string($content_length) . "',
                 cta_enabled = '{$cta_enabled}',
                 cta_content = '" . sql_real_escape_string($cta_content) . "',
                 cta_company_fields = '" . sql_real_escape_string($cta_company_fields) . "',
                 cta_updated_at = '" . G5_TIME_YMDHIS . "' ";

    if ($id > 0) {
        // 프로젝트명은 최초 등록 시에만 자동생성/중복검사를 하고, 수정 시엔 사용자가 입력한 값을 그대로 존중한다.
        if ($project_name_input !== '') {
            $set_sql .= ", project_name = '" . sql_real_escape_string($project_name_input) . "' ";
        }
        $set_sql .= ", updated_at = '" . G5_TIME_YMDHIS . "' ";
        sql_query(" update {$projects_table} set {$set_sql} where id = '{$id}' ");
        $project_id = $id;
    } else {
        // 프로젝트명 중복 시 -2, -3 ... 자동 넘버링
        $final_project_name = $project_name_input !== '' ? $project_name_input : $topic;
        $base_name = $final_project_name;
        $suffix = 2;
        while (true) {
            $dup = sql_fetch(" select id from {$projects_table} where project_name = '" . sql_real_escape_string($final_project_name) . "' limit 1 ");
            if (!$dup) break;
            $final_project_name = $base_name . '-' . $suffix;
            $suffix++;
        }

        // UUID 생성 (MySQL 8 이상 호환용, 간단히 고유 문자열)
        $uuid = uniqid('proj_') . '_' . time();
        $set_sql .= ", project_name = '" . sql_real_escape_string($final_project_name) . "',
                       project_uuid = '{$uuid}', status = 'draft', created_by = '" . sql_real_escape_string($member['mb_id']) . "', created_at = '" . G5_TIME_YMDHIS . "' ";
        sql_query(" insert into {$projects_table} set {$set_sql} ");
        $project_id = sql_insert_id();

        bp_log_activity($project_id, 'create', $member['mb_id'], '프로젝트 인라인 생성: ' . $final_project_name);
    }

    $project = sql_fetch(" select * from {$projects_table} where id = '{$project_id}' ");
    echo json_encode(array('success' => true, 'project_id' => $project_id, 'project' => $project));
    exit;
}

if ($action === 'save_advertiser') {
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

    // 업종만 저장하는 것처럼 일부 필드만 보내는 부분 업데이트 요청이 있을 수 있다.
    // 전송되지 않은 필드는 빈 문자열로 덮어쓰지 않고 기존 값을 그대로 유지한다.
    $existing = array();
    if ($id > 0) {
        $existing = sql_fetch(" select * from {$adv_table} where id = '{$id}' ");
        if (!$existing) {
            echo json_encode(array('success' => false, 'error' => '광고주를 찾을 수 없습니다.'));
            exit;
        }
    }
    $name = isset($_POST['name']) ? trim($_POST['name']) : (isset($existing['name']) ? $existing['name'] : '');
    $ceo_name = isset($_POST['ceo_name']) ? trim($_POST['ceo_name']) : (isset($existing['ceo_name']) ? $existing['ceo_name'] : '');
    $industry = isset($_POST['industry']) ? trim($_POST['industry']) : (isset($existing['industry']) ? $existing['industry'] : '');
    $industry_detail = isset($_POST['industry_detail']) ? trim($_POST['industry_detail']) : (isset($existing['industry_detail']) ? $existing['industry_detail'] : '');
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : (isset($existing['phone']) ? $existing['phone'] : '');
    $sub_phone = isset($_POST['sub_phone']) ? trim($_POST['sub_phone']) : (isset($existing['sub_phone']) ? $existing['sub_phone'] : '');
    $email = isset($_POST['email']) ? trim($_POST['email']) : (isset($existing['email']) ? $existing['email'] : '');
    $domain = isset($_POST['domain']) ? trim($_POST['domain']) : (isset($existing['domain']) ? $existing['domain'] : '');
    $address = isset($_POST['address']) ? trim($_POST['address']) : (isset($existing['address']) ? $existing['address'] : '');
    $service_region = isset($_POST['service_region']) ? trim($_POST['service_region']) : (isset($existing['service_region']) ? $existing['service_region'] : '');
    $core_service = isset($_POST['core_service']) ? trim($_POST['core_service']) : (isset($existing['core_service']) ? $existing['core_service'] : '');
    $intro_text = isset($_POST['intro_text']) ? trim($_POST['intro_text']) : (isset($existing['intro_text']) ? $existing['intro_text'] : '');
    $memo = isset($_POST['memo']) ? trim($_POST['memo']) : (isset($existing['memo']) ? $existing['memo'] : '');

    if ($name === '') {
        echo json_encode(array('success' => false, 'error' => '상호(업체명)를 입력해 주세요.'));
        exit;
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(array('success' => false, 'error' => '이메일 형식이 올바르지 않습니다.'));
        exit;
    }

    $set_sql = " name = '" . sql_real_escape_string($name) . "',
                 ceo_name = '" . sql_real_escape_string($ceo_name) . "',
                 industry = '" . sql_real_escape_string($industry) . "',
                 industry_detail = '" . sql_real_escape_string($industry_detail) . "',
                 phone = '" . sql_real_escape_string($phone) . "',
                 sub_phone = '" . sql_real_escape_string($sub_phone) . "',
                 email = '" . sql_real_escape_string($email) . "',
                 domain = '" . sql_real_escape_string($domain) . "',
                 address = '" . sql_real_escape_string($address) . "',
                 service_region = '" . sql_real_escape_string($service_region) . "',
                 core_service = '" . sql_real_escape_string($core_service) . "',
                 intro_text = '" . sql_real_escape_string($intro_text) . "',
                 memo = '" . sql_real_escape_string($memo) . "' ";

    if ($id > 0) {
        $set_sql .= ", updated_at = '" . G5_TIME_YMDHIS . "' ";
        sql_query(" update {$adv_table} set {$set_sql} where id = '{$id}' ");
        $advertiser_id = $id;
    } else {
        $set_sql .= ", status = 'Y', created_at = '" . G5_TIME_YMDHIS . "' ";
        sql_query(" insert into {$adv_table} set {$set_sql} ");
        $advertiser_id = sql_insert_id();
        bp_log_activity(0, 'advertiser_created', $member['mb_id'], '광고주 인라인 등록: ' . $name);
    }

    $advertiser = sql_fetch(" select * from {$adv_table} where id = '{$advertiser_id}' ");
    echo json_encode(array('success' => true, 'advertiser_id' => $advertiser_id, 'advertiser' => $advertiser));
    exit;
}

if ($action === 'load_advertiser_defaults') {
    $advertiser_id = (int)$_POST['advertiser_id'];
    $advertiser = sql_fetch(" select * from {$adv_table} where id = '{$advertiser_id}' ");
    if (!$advertiser) {
        echo json_encode(array('success' => false, 'error' => '광고주를 찾을 수 없습니다.'));
        exit;
    }

    // 이 광고주의 가장 최근 프로젝트에서 쓴 목적/타깃/유형 선택값을 다음 프로젝트 등록 시 미리 채워준다.
    $last_project = sql_fetch(" select content_type, content_type_secondary, purpose_tags,
                                        target_reader_type, target_age_group, target_customer_stage, target_region
                                 from {$projects_table}
                                 where advertiser_id = '{$advertiser_id}'
                                 order by id desc limit 1 ");

    echo json_encode(array('success' => true, 'advertiser' => $advertiser, 'last_project' => $last_project ? $last_project : null));
    exit;
}

if ($action === 'complete_post') {
    $project_id = (int)$_POST['project_id'];
    $title = trim($_POST['title']);
    $body = trim($_POST['body']);
    
    if (!$project_id || !$title || !$body) {
        echo json_encode(array('success' => false, 'error' => '필수 항목이 누락되었습니다.'));
        exit;
    }

    // CTA 조립
    $project = sql_fetch(" select advertiser_id, cta_enabled, cta_content, cta_company_fields from {$projects_table} where id = '{$project_id}' ");
    if ($project && $project['cta_enabled']) {
        $cta_html = '';
        if ($project['cta_content']) {
            $cta_html .= "<p>" . nl2br(htmlspecialchars($project['cta_content'])) . "</p>\n\n";
        }
        
        $fields = array_filter(explode(',', $project['cta_company_fields']));
        if (!empty($fields)) {
            $adv = sql_fetch(" select * from {$adv_table} where id = '{$project['advertiser_id']}' ");
            if ($adv) {
                $labels = array(
                    'adv_name' => '상호',
                    'phone' => '전화',
                    'sub_phone' => '보조전화',
                    'address' => '주소',
                    'domain' => '홈페이지',
                    'email' => '이메일',
                    'service_region' => '영업지역'
                );
                $cta_html .= "<ul style=\"list-style:none; padding:0;\">\n";
                foreach ($fields as $f) {
                    $val = ($f === 'adv_name') ? $adv['name'] : $adv[$f];
                    if ($val) {
                        $cta_html .= "<li><strong>{$labels[$f]}:</strong> " . htmlspecialchars($val) . "</li>\n";
                    }
                }
                $cta_html .= "</ul>\n";
            }
        }
        if ($cta_html !== '') {
            $body .= "\n\n" . $cta_html;
        }
    }

    // 트랜잭션 시작
    sql_query("START TRANSACTION");

    try {
        $post = sql_fetch(" select id, version from {$posts_table} where project_id = '{$project_id}' order by id desc limit 1 ");
        if ($post) {
            $new_version = $post['version'] + 1;
            sql_query(" update {$posts_table} set title = '" . sql_real_escape_string($title) . "', body = '" . sql_real_escape_string($body) . "', version = '{$new_version}', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$post['id']}' ");
            $post_id = $post['id'];
        } else {
            sql_query(" insert into {$posts_table} set project_id = '{$project_id}', title = '" . sql_real_escape_string($title) . "', body = '" . sql_real_escape_string($body) . "', version = 1, created_at = '" . G5_TIME_YMDHIS . "' ");
            $post_id = sql_insert_id();
        }

        // 프로젝트 상태 갱신 (초안 -> 승인됨)
        sql_query(" update {$projects_table} set status = 'approved', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$project_id}' ");

        // 로그 기록
        sql_query(" insert into {$logs_table} set project_id = '{$project_id}', action = 'post_complete', actor = '" . sql_real_escape_string($member['mb_id']) . "', detail = '포스팅 최종 완료(생성)', created_at = '" . G5_TIME_YMDHIS . "' ");

        sql_query("COMMIT");
        echo json_encode(array('success' => true, 'post_id' => $post_id));
    } catch (Exception $e) {
        sql_query("ROLLBACK");
        echo json_encode(array('success' => false, 'error' => '작업 저장 중 오류가 발생했습니다: ' . $e->getMessage()));
    }
    exit;
}

if ($action === 'load_library_items') {
    $items_table = $g5['table_prefix'] . 'blog_library_items';
    $cat_table = $g5['table_prefix'] . 'blog_library_categories';
    
    // sample_title/sample_content/industry_code는 실제 DB 컬럼값을 그대로 내려준다 - 값이 없는
    // 항목은 자연스럽게 NULL/빈 문자열로 오므로, 여기서 빈 문자열로 덮어써서 실제 데이터를
    // 숨기지 않는다("컬럼은 있는데 항상 빈 값만 내려온다"는 문제 재발 방지).
    $sql = "SELECT i.id, i.library_type as instruction_type, i.item_name as title, c.category_name as category, c.id as category_id, c.category_code, i.instruction_content as content, i.is_default as is_default_checked,
                   i.industry_code, i.sample_title, i.sample_content,
                   i.description, i.recommended_purpose, i.recommended_industries, i.tags,
                   i.is_favorite, i.usage_count, i.last_used_at, i.is_active,
                   i.item_code, i.structure_steps, i.expected_length
            FROM {$items_table} i
            LEFT JOIN {$cat_table} c ON i.category_id = c.id
            WHERE (c.is_active = 1 OR c.id IS NULL) AND (i.deleted_at IS NULL)
            ORDER BY i.library_type ASC, c.sort_order ASC, i.priority ASC, i.id ASC";

    $result = sql_query($sql, false);
    $items = array();

    // 만약 DB가 생성되기 전이라면 에러 없이 빈 배열 반환
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            // DB의 library_type 값은 'structure'인데, builder.js의 libraryItems 버킷은
            // 'structure_template' 키로 되어 있다(다른 두 타입은 이름이 그대로 일치해서
            // 문제가 없었음) - 이 값을 안 맞춰주면 프론트가 "if (this.libraryItems[item.
            // instruction_type])"에서 조용히 걸러버려서, 생성조건/추가지시는 정상 표시되는데
            // 전개구조만 항상 빈 목록으로 보였다.
            if ($row['instruction_type'] === 'structure') {
                $row['instruction_type'] = 'structure_template';
            }
            if ($row['instruction_type'] === 'structure_template') {
                $row['content_json'] = json_decode($row['content'], true);
            }
            $row['industry_code'] = $row['industry_code'] !== null ? $row['industry_code'] : '';
            $row['sample_title'] = $row['sample_title'] !== null ? $row['sample_title'] : '';
            $row['sample_content'] = $row['sample_content'] !== null ? $row['sample_content'] : '';

            if ($manager_filter_type) {
                $mapped_type = $manager_filter_type;
                if ($mapped_type === 'structure') $mapped_type = 'structure_template';
                
                if ($row['instruction_type'] === $mapped_type) {
                    $items[] = $row;
                }
            } else {
                $items[] = $row;
            }
        }
    }
    echo json_encode(array('success' => true, 'items' => $items));
    exit;
}

if ($action === 'save_library_item') {
    $items_table = $g5['table_prefix'] . 'blog_library_items';
    $cat_table = $g5['table_prefix'] . 'blog_library_categories';
    
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $type = trim($_POST['instruction_type']);
    // 프론트(builder.js)의 라이브러리 버킷 이름은 'structure_template'인데 DB의
    // library_type 값은 'structure'다 - load_library_items 조회 쪽에서 반대 방향으로
    // 변환해줬던 것과 짝을 맞춰, 저장할 때도 여기서 DB 값으로 되돌려야 한다. 안 그러면
    // 이 폼으로 저장한 구조 항목은 'structure_template'라는 값으로 저장되어 다른 모든
    // 구조 조회 쿼리(library_type = 'structure')에 걸리지 않아 보이지 않게 된다.
    if ($type === 'structure_template') {
        $type = 'structure';
    }
    $title = trim($_POST['title']);
    $category_name = trim(isset($_POST['category']) ? $_POST['category'] : '');
    $content = trim($_POST['content']);
    $description = trim(isset($_POST['description']) ? $_POST['description'] : '');
    $tags = trim(isset($_POST['tags']) ? $_POST['tags'] : '');
    $recommended_purpose = trim(isset($_POST['recommended_purpose']) ? $_POST['recommended_purpose'] : '');
    $recommended_industries = trim(isset($_POST['recommended_industries']) ? $_POST['recommended_industries'] : '');
    $is_default = isset($_POST['is_default_checked']) && $_POST['is_default_checked'] === 'Y' ? 1 : 0;
    $is_favorite = isset($_POST['is_favorite_checked']) && $_POST['is_favorite_checked'] === 'Y' ? 1 : 0;
    $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;
    
    if (!$type || !$title || !$content) {
        echo json_encode(array('success' => false, 'error' => '필수 항목이 누락되었습니다.'));
        exit;
    }
    
    $item_code = trim(isset($_POST['item_code']) ? $_POST['item_code'] : '');
    $structure_steps = trim(isset($_POST['structure_steps']) ? $_POST['structure_steps'] : '');
    $expected_length = trim(isset($_POST['expected_length']) ? $_POST['expected_length'] : '');
    
    // 카테고리 ID 확보
    $category_id = 0;
    if ($category_name !== '') {
        $cat_row = sql_fetch("SELECT id FROM {$cat_table} WHERE library_type='{$type}' AND category_name='".sql_real_escape_string($category_name)."'");
        if ($cat_row) {
            $category_id = $cat_row['id'];
        } else {
            sql_query("INSERT INTO {$cat_table} SET library_type='{$type}', category_name='".sql_real_escape_string($category_name)."', sort_order=0");
            $category_id = sql_insert_id();
        }
    }
    
    // item_code가 중복인지 체크 (본인 제외)
    if ($item_code !== '') {
        $dup = sql_fetch("SELECT id FROM {$items_table} WHERE item_code = '".sql_real_escape_string($item_code)."' AND id != '{$id}'");
        if ($dup) {
            echo json_encode(array('success' => false, 'error' => '이미 존재하는 구조 코드입니다.'));
            exit;
        }
    }
    
    $item_code_val = $item_code !== '' ? "'".sql_real_escape_string($item_code)."'" : "NULL";

    $set_sql = " library_type = '" . sql_real_escape_string($type) . "',
                 item_name = '" . sql_real_escape_string($title) . "',
                 item_code = {$item_code_val},
                 category_id = '{$category_id}',
                 instruction_content = '" . sql_real_escape_string($content) . "',
                 structure_steps = '" . sql_real_escape_string($structure_steps) . "',
                 expected_length = '" . sql_real_escape_string($expected_length) . "',
                 description = '" . sql_real_escape_string($description) . "',
                 tags = '" . sql_real_escape_string($tags) . "',
                 recommended_purpose = '" . sql_real_escape_string($recommended_purpose) . "',
                 recommended_industries = '" . sql_real_escape_string($recommended_industries) . "',
                 is_default = '{$is_default}',
                 is_favorite = '{$is_favorite}',
                 is_active = '{$is_active}' ";
                 
    if ($id > 0) {
        $set_sql .= ", updated_at = '" . G5_TIME_YMDHIS . "', updated_by = '" . sql_real_escape_string($member['mb_id']) . "' ";
        sql_query(" update {$items_table} set {$set_sql} where id = '{$id}' ");
    } else {
        $set_sql .= ", created_at = '" . G5_TIME_YMDHIS . "', updated_at = '" . G5_TIME_YMDHIS . "', created_by = '" . sql_real_escape_string($member['mb_id']) . "' ";
        sql_query(" insert into {$items_table} set {$set_sql} ");
        $id = sql_insert_id();
    }
    echo json_encode(array('success' => true, 'id' => $id));
    exit;
}

if ($action === 'delete_library_item') {
    $items_table = $g5['table_prefix'] . 'blog_library_items';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
        // Soft delete
        sql_query(" update {$items_table} set deleted_at = '" . G5_TIME_YMDHIS . "', is_active = 0, updated_by = '" . sql_real_escape_string($member['mb_id']) . "' where id = '{$id}' ");
        echo json_encode(array('success' => true));
    } else {
        echo json_encode(array('success' => false, 'error' => 'ID가 누락되었습니다.'));
    }
    exit;
}

if ($action === 'duplicate_library_item') {
    $items_table = $g5['table_prefix'] . 'blog_library_items';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
        $row = sql_fetch("SELECT * FROM {$items_table} WHERE id = '{$id}'");
        if ($row) {
            $new_title = $row['item_name'] . ' - 복사본';
            $set_sql = " library_type = '" . sql_real_escape_string($row['library_type']) . "',
                         category_id = '{$row['category_id']}',
                         item_name = '" . sql_real_escape_string($new_title) . "',
                         instruction_content = '" . sql_real_escape_string($row['instruction_content']) . "',
                         description = '" . sql_real_escape_string($row['description']) . "',
                         tags = '" . sql_real_escape_string($row['tags']) . "',
                         recommended_purpose = '" . sql_real_escape_string($row['recommended_purpose']) . "',
                         recommended_industries = '" . sql_real_escape_string($row['recommended_industries']) . "',
                         is_default = '{$row['is_default']}',
                         is_favorite = '{$row['is_favorite']}',
                         is_active = '{$row['is_active']}',
                         created_at = '" . G5_TIME_YMDHIS . "',
                         updated_at = '" . G5_TIME_YMDHIS . "',
                         created_by = '" . sql_real_escape_string($member['mb_id']) . "' ";
            sql_query(" insert into {$items_table} set {$set_sql} ");
            $new_id = sql_insert_id();
            echo json_encode(array('success' => true, 'id' => $new_id));
        } else {
            echo json_encode(array('success' => false, 'error' => '대상을 찾을 수 없습니다.'));
        }
    } else {
        echo json_encode(array('success' => false, 'error' => 'ID가 누락되었습니다.'));
    }
    exit;
}

if ($action === 'toggle_favorite_library_item') {
    $items_table = $g5['table_prefix'] . 'blog_library_items';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $is_favorite = isset($_POST['is_favorite']) ? (int)$_POST['is_favorite'] : 0;
    if ($id > 0) {
        sql_query(" update {$items_table} set is_favorite = '{$is_favorite}' where id = '{$id}' ");
        echo json_encode(array('success' => true));
    } else {
        echo json_encode(array('success' => false, 'error' => 'ID가 누락되었습니다.'));
    }
    exit;
}

if ($action === 'update_library_usage') {
    $items_table = $g5['table_prefix'] . 'blog_library_items';
    $ids = isset($_POST['ids']) ? $_POST['ids'] : array();
    if (!is_array($ids)) $ids = array($ids);
    
    if (count($ids) > 0) {
        $id_str = implode(',', array_map('intval', $ids));
        sql_query(" update {$items_table} set usage_count = usage_count + 1, last_used_at = '" . G5_TIME_YMDHIS . "' where id IN ({$id_str}) ");
        echo json_encode(array('success' => true));
    } else {
        echo json_encode(array('success' => false, 'error' => 'ID가 누락되었습니다.'));
    }
    exit;
}

if ($action === 'update_library_status') {
    $items_table = $g5['table_prefix'] . 'blog_library_items';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    if ($id > 0) {
        sql_query(" update {$items_table} set is_active = '{$is_active}', updated_by = '" . sql_real_escape_string($member['mb_id']) . "' where id = '{$id}' ");
        echo json_encode(array('success' => true));
    } else {
        echo json_encode(array('success' => false, 'error' => 'ID가 누락되었습니다.'));
    }
    exit;
}

if ($action === 'load_library_categories') {
    $cat_table = $g5['table_prefix'] . 'blog_library_categories';
    $sql = "SELECT * FROM {$cat_table} WHERE deleted_at IS NULL ORDER BY sort_order ASC, id ASC";
    $result = sql_query($sql, false);
    $categories = array();
    if ($result) {
        while ($row = sql_fetch_array($result)) {

            if ($manager_filter_type) {
                if ($row['library_type'] === $manager_filter_type) {
                    $categories[] = $row;
                }
            } else {
                $categories[] = $row;
            }
        }
    }
    echo json_encode(array('success' => true, 'categories' => $categories));
    exit;
}

if ($action === 'save_library_category') {
    $cat_table = $g5['table_prefix'] . 'blog_library_categories';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $library_type = trim($_POST['library_type']);
    if ($library_type === 'structure_template') {
        $library_type = 'structure';
    }
    $category_name = trim($_POST['category_name']);
    $description = trim(isset($_POST['description']) ? $_POST['description'] : '');
    $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
    $is_active = isset($_POST['is_active']) && $_POST['is_active'] === '0' ? 0 : 1;
    
    if (!$library_type || !$category_name) {
        echo json_encode(array('success' => false, 'error' => '필수 항목이 누락되었습니다.'));
        exit;
    }
    
    $set_sql = " library_type = '" . sql_real_escape_string($library_type) . "',
                 category_name = '" . sql_real_escape_string($category_name) . "',
                 description = '" . sql_real_escape_string($description) . "',
                 sort_order = '{$sort_order}',
                 is_active = '{$is_active}' ";
                 
    if ($id > 0) {
        sql_query(" update {$cat_table} set {$set_sql} where id = '{$id}' ");
    } else {
        sql_query(" insert into {$cat_table} set {$set_sql}, created_at = '" . G5_TIME_YMDHIS . "' ");
        $id = sql_insert_id();
    }
    echo json_encode(array('success' => true, 'id' => $id));
    exit;
}

if ($action === 'delete_library_category') {
    $items_table = $g5['table_prefix'] . 'blog_library_items';
    $cat_table = $g5['table_prefix'] . 'blog_library_categories';
    $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    if ($id > 0) {
        $row = sql_fetch("SELECT count(*) as cnt FROM {$items_table} WHERE category_id = '{$id}' AND deleted_at IS NULL");
        if ($row && $row['cnt'] > 0) {
            echo json_encode(array('success' => false, 'error' => '이 카테고리에 등록된 라이브러리 항목이 있습니다. 다른 카테고리로 이동하거나 사용 중지한 후 삭제해 주세요.'));
            exit;
        }
        sql_query(" update {$cat_table} set deleted_at = '" . G5_TIME_YMDHIS . "', is_active = 0 where id = '{$id}' ");
        echo json_encode(array('success' => true));
    } else {
        echo json_encode(array('success' => false, 'error' => 'ID가 누락되었습니다.'));
    }
    exit;
}

echo json_encode(array('success' => false, 'error' => '알 수 없는 요청입니다.'));
exit;
