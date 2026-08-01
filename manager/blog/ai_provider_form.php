<?php
include_once('./_common.php');

$sub_menu = '360400';
auth_check_menu($auth, $sub_menu, 'w');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$table = bp_table('ai_providers');

if ($id > 0) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' ");
    if (!$row) {
        alert('AI 공급자를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/ai_provider_list.php');
    }
} else {
    $row = array(
        'provider_code' => '', 'display_name' => '', 'is_active' => 'N',
        'masked_hint' => '', 'default_model' => '', 'api_endpoint' => '', 'max_tokens' => 2000, 'temperature' => 0.70,
    );
}

// get_admin_token()은 호출할 때마다 세션 값을 새로 덮어쓴다 - 한 페이지 안에서 두 번
// 부르면 먼저 화면에 찍힌 값(폼)은 세션과 어긋난 채로 남아 저장 시 "올바른 방법으로
// 이용해 주십시오" 오류가 난다. 반드시 한 번만 호출해서 재사용해야 한다.
$admin_token = get_admin_token();

// settings.php?tab=ai가 인라인 컨테이너에 fetch로 끼워 넣기 위한 조각 모드 - 레이아웃
// 없이 partials/ai_provider_form_fields.php 하나만 그대로 출력한다(iframe 금지 지침).
$fragment = isset($_GET['fragment']) && $_GET['fragment'] === '1';
$is_inline = $fragment;

if ($fragment) {
    header('Content-Type: text/html; charset=utf-8');
    include __DIR__ . '/partials/ai_provider_form_fields.php';
    exit;
}

$g5['title'] = $id > 0 ? 'AI 공급자 수정' : 'AI 공급자 등록';

include_once(__DIR__ . '/../layout/header.php');
include __DIR__ . '/partials/ai_provider_form_fields.php';
include_once(__DIR__ . '/../layout/footer.php');
