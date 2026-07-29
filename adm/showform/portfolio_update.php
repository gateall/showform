<?php
$sub_menu = '700100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$table = G5_TABLE_PREFIX . 'sf_portfolio';
$actor = isset($member['mb_id']) ? $member['mb_id'] : '';

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
$industry = isset($_POST['industry']) ? trim($_POST['industry']) : '';
$summary = isset($_POST['summary']) ? trim($_POST['summary']) : '';
$thumbnail = isset($_POST['thumbnail']) ? trim($_POST['thumbnail']) : '';
$body = isset($_POST['body']) ? trim($_POST['body']) : '';
$site_url = isset($_POST['site_url']) ? trim($_POST['site_url']) : '';
$build_type = isset($_POST['build_type']) ? trim($_POST['build_type']) : '';
$price_note = isset($_POST['price_note']) ? trim($_POST['price_note']) : '';
$is_featured = isset($_POST['is_featured']) && $_POST['is_featured'] === 'Y' ? 'Y' : 'N';
$is_display = isset($_POST['is_display']) && $_POST['is_display'] === 'N' ? 'N' : 'Y';
$sort_order = isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0;

if ($title === '') {
    alert('프로젝트명을 입력해 주세요.');
}
if (!sf_is_safe_external_url($site_url)) {
    alert('사이트 URL은 http:// 또는 https:// 로 시작해야 합니다.');
}

if ($slug === '') {
    $auto_slug = preg_replace('/[^a-z0-9\-]/', '', strtolower(str_replace(' ', '-', $title)));
    $auto_slug = trim(preg_replace('/-+/', '-', (string) $auto_slug), '-');
    // 한글 등 영문/숫자가 아닌 제목은 걸러내면 빈 문자열(또는 하이픈만) 남는다 —
    // 그 경우 프로젝트명을 URL로 못 쓰므로 고유한 대체 슬러그를 만든다.
    $slug = $auto_slug !== '' ? $auto_slug : ('sf-' . time());
}
if (!preg_match('/^[a-z0-9\-]{1,200}$/', $slug)) {
    alert('슬러그는 영문 소문자·숫자·하이픈만 사용할 수 있습니다.');
}

// ---- 수정 ----
if ($id > 0) {
    $existing = sql_fetch(" select id from {$table} where id = '{$id}' and deleted_at is null ");
    if (!$existing) {
        alert('제작 사례를 찾을 수 없습니다.', G5_ADMIN_URL . '/showform/portfolio_list.php');
    }
    $dup = sql_fetch(" select id from {$table} where slug = '" . sql_real_escape_string($slug) . "' and id != '{$id}' and deleted_at is null ");
    if ($dup) {
        alert('이미 사용 중인 슬러그입니다.');
    }

    sql_query(" update {$table}
                    set title = '" . sql_real_escape_string($title) . "',
                        slug = '" . sql_real_escape_string($slug) . "',
                        industry = '" . sql_real_escape_string($industry) . "',
                        summary = '" . sql_real_escape_string($summary) . "',
                        thumbnail = '" . sql_real_escape_string($thumbnail) . "',
                        body = '" . sql_real_escape_string($body) . "',
                        site_url = '" . sql_real_escape_string($site_url) . "',
                        build_type = '" . sql_real_escape_string($build_type) . "',
                        price_note = '" . sql_real_escape_string($price_note) . "',
                        is_featured = '{$is_featured}',
                        is_display = '{$is_display}',
                        sort_order = '{$sort_order}',
                        updated_by = '" . sql_real_escape_string($actor) . "',
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = '{$id}' ");

    alert('제작 사례가 저장되었습니다.', G5_ADMIN_URL . '/showform/portfolio_form.php?id=' . $id);
}

// ---- 신규 등록 ----
$dup = sql_fetch(" select id from {$table} where slug = '" . sql_real_escape_string($slug) . "' and deleted_at is null ");
if ($dup) {
    alert('이미 사용 중인 슬러그입니다.');
}

sql_query(" insert into {$table}
                set title = '" . sql_real_escape_string($title) . "',
                    slug = '" . sql_real_escape_string($slug) . "',
                    industry = '" . sql_real_escape_string($industry) . "',
                    summary = '" . sql_real_escape_string($summary) . "',
                    thumbnail = '" . sql_real_escape_string($thumbnail) . "',
                    body = '" . sql_real_escape_string($body) . "',
                    site_url = '" . sql_real_escape_string($site_url) . "',
                    build_type = '" . sql_real_escape_string($build_type) . "',
                    price_note = '" . sql_real_escape_string($price_note) . "',
                    is_featured = '{$is_featured}',
                    is_display = '{$is_display}',
                    sort_order = '{$sort_order}',
                    updated_by = '" . sql_real_escape_string($actor) . "',
                    created_at = '" . G5_TIME_YMDHIS . "',
                    updated_at = '" . G5_TIME_YMDHIS . "' ");
$new_id = (int) sql_insert_id();

alert('제작 사례가 등록되었습니다.', G5_ADMIN_URL . '/showform/portfolio_form.php?id=' . $new_id);
