<?php
include_once('./_common.php');
header('Content-Type: application/json; charset=utf-8');

if ($is_admin != 'super') {
    echo json_encode(array('success' => false, 'error' => '관리자 권한이 없습니다.'));
    exit;
}

$action = isset($_POST['action']) ? $_POST['action'] : '';
$adv_table = bp_table('advertisers');
$projects_table = bp_table('content_projects');
$posts_table = bp_table('posts');
$logs_table = bp_table('content_activity_logs');

if ($action === 'update_status') {
    $project_id = (int)$_POST['project_id'];
    $status = trim($_POST['status']);
    
    sql_query(" update {$projects_table} set status = '" . sql_real_escape_string($status) . "', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$project_id}' ");
    sql_query(" insert into {$logs_table} set project_id = '{$project_id}', action = 'status_change', actor = '" . sql_real_escape_string($member['mb_id']) . "', detail = '상태 변경: {$status}', created_at = '" . G5_TIME_YMDHIS . "' ");
    
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
    $advertiser = sql_fetch(" select id, name from {$adv_table} where id = '{$project['advertiser_id']}' ");
    
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
                 content_length = '" . sql_real_escape_string($content_length) . "' ";

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
    $field_default = function ($key) use ($existing) {
        return isset($existing[$key]) ? $existing[$key] : '';
    };

    $name = isset($_POST['name']) ? trim($_POST['name']) : $field_default('name');
    $ceo_name = isset($_POST['ceo_name']) ? trim($_POST['ceo_name']) : $field_default('ceo_name');
    $industry = isset($_POST['industry']) ? trim($_POST['industry']) : $field_default('industry');
    $industry_detail = isset($_POST['industry_detail']) ? trim($_POST['industry_detail']) : $field_default('industry_detail');
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : $field_default('phone');
    $sub_phone = isset($_POST['sub_phone']) ? trim($_POST['sub_phone']) : $field_default('sub_phone');
    $email = isset($_POST['email']) ? trim($_POST['email']) : $field_default('email');
    $domain = isset($_POST['domain']) ? trim($_POST['domain']) : $field_default('domain');
    $address = isset($_POST['address']) ? trim($_POST['address']) : $field_default('address');
    $service_region = isset($_POST['service_region']) ? trim($_POST['service_region']) : $field_default('service_region');
    $core_service = isset($_POST['core_service']) ? trim($_POST['core_service']) : $field_default('core_service');
    $intro_text = isset($_POST['intro_text']) ? trim($_POST['intro_text']) : $field_default('intro_text');
    $memo = isset($_POST['memo']) ? trim($_POST['memo']) : $field_default('memo');

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

        // 프로젝트 상태 갱신 (초안 -> 생성됨)
        sql_query(" update {$projects_table} set status = 'generated', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$project_id}' ");

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

echo json_encode(array('success' => false, 'error' => '알 수 없는 요청입니다.'));
exit;
