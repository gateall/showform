<?php
$sub_menu = '360100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$w = isset($_POST['w']) ? trim($_POST['w']) : (isset($_GET['mode']) && $_GET['mode'] === 'delete' ? 'd' : '');
$id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;

$rules_table = bp_table('generation_rules');
$list_url = SF_MANAGER_URL . '/blog/content_management.php?tab=generation_rules';

$valid_types = array('writing_rule', 'technical_rule', 'seo_rule', 'prohibited_rule', 'quality_rule');

if ($w === '' || $w === 'u') {
    $rule_type = isset($_POST['rule_type']) ? trim($_POST['rule_type']) : '';
    $rule_name = isset($_POST['rule_name']) ? trim(strip_tags($_POST['rule_name'])) : '';
    $rule_instruction = isset($_POST['rule_instruction']) ? trim($_POST['rule_instruction']) : '';
    $is_default = isset($_POST['is_default']) && $_POST['is_default'] === 'Y' ? 'Y' : 'N';
    $is_active = isset($_POST['is_active']) && $_POST['is_active'] === 'N' ? 'N' : 'Y';
    $sort_order = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;

    if (!in_array($rule_type, $valid_types, true)) alert('조건 구분을 선택해 주세요.', $list_url);
    if ($rule_name === '') alert('조건 이름을 입력해 주세요.', $list_url);
    if ($rule_instruction === '') alert('AI 전달용 상세 지시문을 입력해 주세요.', $list_url);

    if ($w === '') {
        sql_query(" insert into {$rules_table}
                    set rule_type = '" . sql_real_escape_string($rule_type) . "',
                        rule_name = '" . sql_real_escape_string($rule_name) . "',
                        rule_instruction = '" . sql_real_escape_string($rule_instruction) . "',
                        is_default = '{$is_default}',
                        is_active = '{$is_active}',
                        sort_order = '{$sort_order}',
                        created_at = NOW() ");
        goto_url($list_url);
    } else {
        if ($id <= 0) alert('잘못된 ID입니다.', $list_url);
        $old = sql_fetch(" select id from {$rules_table} where id = '{$id}' ");
        if (!$old) alert('존재하지 않는 조건입니다.', $list_url);

        sql_query(" update {$rules_table}
                    set rule_type = '" . sql_real_escape_string($rule_type) . "',
                        rule_name = '" . sql_real_escape_string($rule_name) . "',
                        rule_instruction = '" . sql_real_escape_string($rule_instruction) . "',
                        is_default = '{$is_default}',
                        is_active = '{$is_active}',
                        sort_order = '{$sort_order}',
                        updated_at = NOW()
                    where id = '{$id}' ");
        goto_url($list_url);
    }
} else if ($w === 'd') {
    if ($id <= 0) alert('잘못된 ID입니다.', $list_url);
    $old = sql_fetch(" select id from {$rules_table} where id = '{$id}' ");
    if (!$old) alert('존재하지 않는 조건입니다.', $list_url);

    // 프로젝트별 선택 상태는 별도 테이블 없이 posts.builder_state JSON(selected_rule_ids)에만
    // 남아있으므로 참조 무결성 문제가 없다 - 삭제된 id는 프롬프트 조립 시 그냥 조용히
    // 건너뛰어진다(post_builder_ajax.php의 generate_all_cards에서 존재하는 것만 조회).
    sql_query(" delete from {$rules_table} where id = '{$id}' ");
    goto_url($list_url);
} else {
    alert('잘못된 접근입니다.', $list_url);
}
