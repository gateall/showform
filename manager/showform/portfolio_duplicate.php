<?php
// 기획서 3.2 "복제하여 새 쇼폼 만들기". portfolio_update.php/delete.php와 같은 위치에
// 두는 기존 관례를 따른다 - 목록/폼 화면(adm 또는 manager)이 바뀌어도 처리 로직은
// 한 곳에서 재사용한다.
$sub_menu = '700100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$table = G5_TABLE_PREFIX . 'sf_portfolio';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$return = isset($_GET['return']) ? trim($_GET['return']) : '';

if ($id <= 0) {
    alert('잘못된 접근입니다.');
}

$source = sql_fetch(" select * from {$table} where id = '{$id}' and deleted_at is null ");
if (!$source) {
    alert('제작 사례를 찾을 수 없습니다.');
}

$new_title = $source['title'] . ' (복사본)';
$new_slug = ($source['slug'] !== '' ? $source['slug'] : 'sf') . '-copy-' . time();

sql_query(" insert into {$table}
                set title = '" . sql_real_escape_string($new_title) . "',
                    slug = '" . sql_real_escape_string($new_slug) . "',
                    industry = '" . sql_real_escape_string($source['industry']) . "',
                    summary = '" . sql_real_escape_string($source['summary']) . "',
                    thumbnail = '" . sql_real_escape_string($source['thumbnail']) . "',
                    body = '" . sql_real_escape_string($source['body']) . "',
                    site_url = '" . sql_real_escape_string($source['site_url']) . "',
                    build_type = '" . sql_real_escape_string($source['build_type']) . "',
                    price_note = '" . sql_real_escape_string($source['price_note']) . "',
                    is_featured = 'N',
                    is_display = 'N',
                    sort_order = '" . (int) $source['sort_order'] . "',
                    updated_by = '" . sql_real_escape_string(isset($member['mb_id']) ? $member['mb_id'] : '') . "',
                    created_at = '" . G5_TIME_YMDHIS . "',
                    updated_at = '" . G5_TIME_YMDHIS . "' ");
$new_id = (int) sql_insert_id();

$back_url = $return === 'manager'
    ? SF_MANAGER_URL . '/showform/form.php?id=' . $new_id
    : SF_MANAGER_URL . '/showform/portfolio_form.php?id=' . $new_id;
alert('복사본이 생성되었습니다. 공개 여부를 확인해 주세요.', $back_url);
