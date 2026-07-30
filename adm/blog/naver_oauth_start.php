<?php
// 네이버 로그인 연동 시작 — site_form.php의 버튼(POST)에서만 호출된다.
// state에 site_id를 실어 보내고, 세션에도 동일 state를 저장해 콜백에서 대조(CSRF 방지 + 위조 site_id 방지)한다.
include_once('./_common.php');

// 사이트 연결의 하위 액션이라 발행사이트관리(site_list.php)와 같은 코드를 쓴다.
$sub_menu = '360300';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$site_table = bp_table('sites');
$site_id = isset($_POST['site_id']) ? (int) $_POST['site_id'] : 0;

$site = $site_id > 0 ? sql_fetch(" select id from {$site_table} where id = '{$site_id}' ") : null;
if (!$site) {
    alert('사이트 정보를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/site_list.php');
}

$state = bin2hex(random_bytes(16)) . ':' . $site_id;
set_session('bp_naver_oauth_state', $state);

$result = bp_naver_build_authorize_url($state);
if (!$result['ok']) {
    alert($result['error'], G5_ADMIN_URL . '/blog/site_form.php?id=' . $site_id);
}

goto_url($result['url']);
