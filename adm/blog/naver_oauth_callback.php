<?php
// 네이버 OAuth 인가 코드 콜백 — 채널 앱의 redirect_uri에 이 파일의 절대 URL을 등록해야 한다
// (예: https://showform.kr/adm/blog/naver_oauth_callback.php). GET으로 code/state를 받는다.
include_once('./_common.php');

// 사이트 연결의 하위 액션이라 발행사이트관리(site_list.php)와 같은 코드를 쓴다.
$sub_menu = '360300';
auth_check_menu($auth, $sub_menu, 'w');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

if (isset($_GET['error'])) {
    $desc = isset($_GET['error_description']) ? $_GET['error_description'] : $_GET['error'];
    alert('네이버 로그인이 취소되었거나 거부되었습니다: ' . $desc, G5_ADMIN_URL . '/blog/site_list.php');
}

$code = isset($_GET['code']) ? $_GET['code'] : '';
$state = isset($_GET['state']) ? $_GET['state'] : '';

$expected_state = get_session('bp_naver_oauth_state');
set_session('bp_naver_oauth_state', '');

if ($code === '' || $state === '' || !$expected_state || !hash_equals($expected_state, $state)) {
    alert('연동 요청이 유효하지 않습니다(상태값 불일치). 사이트 화면에서 다시 시도해 주세요.', G5_ADMIN_URL . '/blog/site_list.php');
}

$parts = explode(':', $state);
$site_id = isset($parts[1]) ? (int) $parts[1] : 0;

$site_table = bp_table('sites');
$site = $site_id > 0 ? sql_fetch(" select id from {$site_table} where id = '{$site_id}' ") : null;
if (!$site) {
    alert('사이트 정보를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/site_list.php');
}

$result = bp_naver_exchange_code($code, $state);
if (!$result['ok']) {
    alert('네이버 토큰 발급 실패: ' . $result['error'], G5_ADMIN_URL . '/blog/site_form.php?id=' . $site_id);
}

bp_naver_store_tokens($site_id, $result);

alert('네이버 블로그 연동이 완료되었습니다.', G5_ADMIN_URL . '/blog/site_form.php?id=' . $site_id);
