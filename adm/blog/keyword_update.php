<?php
$sub_menu = '360600';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$w = isset($_POST['w']) ? trim($_POST['w']) : (isset($_GET['mode']) && $_GET['mode'] === 'delete' ? 'd' : '');
$id = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;
$qstr = isset($_POST['qstr']) ? $_POST['qstr'] : (isset($_GET['qstr']) ? $_GET['qstr'] : '');

$keywords_table = bp_table('content_keywords');
$projects_table = bp_table('content_projects');
$logs_table = bp_table('content_activity_logs');

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$keyword = isset($_POST['keyword']) ? trim(strip_tags($_POST['keyword'])) : '';
$status = isset($_POST['status']) && $_POST['status'] === 'N' ? 'N' : 'Y';

function write_activity_log($project_id, $action, $detail) {
    global $logs_table, $member;
    $actor = isset($member['mb_id']) ? $member['mb_id'] : 'admin';
    $project_id = (int)$project_id;
    $action = sql_real_escape_string($action);
    $actor = sql_real_escape_string($actor);
    $detail = sql_real_escape_string($detail);
    sql_query(" insert into {$logs_table} set project_id = '{$project_id}', action = '{$action}', actor = '{$actor}', detail = '{$detail}', created_at = NOW() ");
}

if ($w === '' || $w === 'u') {
    // 필수 검증
    if ($project_id <= 0) alert('프로젝트를 선택해 주세요.');
    if ($keyword === '') alert('키워드를 입력해 주세요.');
    
    // 프로젝트 존재 검증
    $proj = sql_fetch(" select id from {$projects_table} where id = '{$project_id}' ");
    if (!$proj) alert('존재하지 않는 프로젝트입니다.');

    // 중복 검증 (동일 프로젝트 내 중복 메인 키워드)
    $sql_dup = " select id from {$keywords_table} where project_id = '{$project_id}' and keyword_group = 'primary' and keyword = '" . sql_real_escape_string($keyword) . "' ";
    if ($w === 'u') {
        $sql_dup .= " and id <> '{$id}' ";
    }
    $dup = sql_fetch($sql_dup);
    if ($dup) alert('해당 프로젝트에 이미 동일한 메인 키워드가 존재합니다.');

    if ($w === '') {
        // 등록
        $sql = " insert into {$keywords_table}
                 set project_id = '{$project_id}',
                     keyword_group = 'primary',
                     keyword = '" . sql_real_escape_string($keyword) . "',
                     status = '{$status}',
                     created_at = NOW(),
                     updated_at = NOW() ";
        sql_query($sql);
        $new_id = sql_insert_id();
        
        write_activity_log($project_id, 'keyword_add', "키워드 등록 (ID: {$new_id}, {$keyword}, 상태: {$status})");
        
        goto_url('./keyword_list.php?' . ltrim($qstr, '&amp;'));
    } else if ($w === 'u') {
        // 수정
        if ($id <= 0) alert('잘못된 ID입니다.');
        $old = sql_fetch(" select * from {$keywords_table} where id = '{$id}' ");
        if (!$old) alert('존재하지 않는 키워드입니다.');
        
        $sql = " update {$keywords_table}
                 set project_id = '{$project_id}',
                     keyword = '" . sql_real_escape_string($keyword) . "',
                     status = '{$status}',
                     updated_at = NOW()
                 where id = '{$id}' ";
        sql_query($sql);
        
        $changes = array();
        if ((int)$old['project_id'] !== $project_id) $changes[] = "프로젝트 변경({$old['project_id']} -> {$project_id})";
        if ($old['keyword'] !== $keyword) $changes[] = "키워드 변경({$old['keyword']} -> {$keyword})";
        if ($old['status'] !== $status) {
            $changes[] = "상태 변경({$old['status']} -> {$status})";
            $action = $status === 'Y' ? 'keyword_activate' : 'keyword_deactivate';
            write_activity_log($project_id, $action, "키워드 상태 변경 (ID: {$id}, {$old['status']} -> {$status})");
        }
        
        if (!empty($changes)) {
            write_activity_log($project_id, 'keyword_update', "키워드 수정 (ID: {$id}, " . implode(", ", $changes) . ")");
        }
        
        goto_url('./keyword_list.php?' . ltrim($qstr, '&amp;'));
    }
} else if ($w === 'd') {
    // 삭제
    if ($id <= 0) alert('잘못된 ID입니다.');
    $old = sql_fetch(" select * from {$keywords_table} where id = '{$id}' ");
    if (!$old) alert('존재하지 않는 키워드입니다.');
    
    // 참조 무결성 검사 (활동 로그에 해당 ID가 있는지, 또는 포스트가 이미 생성되었는지 간접 확인)
    $posts_table = bp_table('posts');
    // 실제 DB엔 keyword_id 참조가 없으므로 프로젝트에 글이 있으면 위험하다고 판단
    $post_exists = sql_fetch(" select id from {$posts_table} where project_id = '{$old['project_id']}' limit 1 ");
    
    // 로그에 명시적으로 남아있는지
    $log_exists = sql_fetch(" select id from {$logs_table} where project_id = '{$old['project_id']}' and detail like '%(ID: {$id}%' limit 1 ");
    
    if ($post_exists || $log_exists) {
        // 참조가 있으므로 삭제 차단하고 비활성화 유도
        write_activity_log($old['project_id'], 'keyword_delete_blocked', "키워드 삭제 차단 (ID: {$id}, 참조 데이터 존재)");
        alert('이 키워드와 관련된 활동 이력이나 생성된 포스트가 존재하여 삭제할 수 없습니다. 대신 [수정]에서 사용 상태를 비활성(N)으로 변경해 주세요.', SF_MANAGER_URL . '/blog/keyword_list.php?' . ltrim($qstr, '&amp;'));
    } else {
        // 실제 삭제
        sql_query(" delete from {$keywords_table} where id = '{$id}' ");
        write_activity_log($old['project_id'], 'keyword_delete', "키워드 삭제 (ID: {$id}, {$old['keyword']})");
        
        goto_url('./keyword_list.php?' . ltrim($qstr, '&amp;'));
    }
} else {
    alert('잘못된 접근입니다.');
}
