<?php
include_once('./_common.php');
$sub_menu = '360300';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$site_id = isset($_POST['site_id']) ? (int) $_POST['site_id'] : 0;
if ($site_id < 1) {
    alert('잘못된 접근입니다.');
}

$cat_table = bp_table('category_mappings');

$map_ids = isset($_POST['map_id']) ? $_POST['map_id'] : array();
$internal_cats = isset($_POST['internal_category']) ? $_POST['internal_category'] : array();
$remote_ids = isset($_POST['remote_id']) ? $_POST['remote_id'] : array();
$remote_names = isset($_POST['remote_name']) ? $_POST['remote_name'] : array();
$del_maps = isset($_POST['del_map']) ? $_POST['del_map'] : array();

for ($i = 0; $i < count($map_ids); $i++) {
    $m_id = (int) $map_ids[$i];
    $int_cat = trim($internal_cats[$i]);
    $rem_id = trim($remote_ids[$i]);
    $rem_name = trim($remote_names[$i]);

    if ($m_id > 0 && in_array($m_id, $del_maps)) {
        sql_query(" delete from {$cat_table} where id = '{$m_id}' and site_id = '{$site_id}' ");
        continue;
    }

    if ($int_cat === '' || $rem_id === '') {
        continue;
    }

    if ($m_id > 0) {
        sql_query(" update {$cat_table} 
                       set internal_category_name = '" . sql_real_escape_string($int_cat) . "',
                           remote_category_id = '" . sql_real_escape_string($rem_id) . "',
                           remote_category_name = '" . sql_real_escape_string($rem_name) . "',
                           updated_at = '" . G5_TIME_YMDHIS . "'
                     where id = '{$m_id}' and site_id = '{$site_id}' ");
    } else {
        sql_query(" insert into {$cat_table} 
                       set site_id = '{$site_id}',
                           internal_category_name = '" . sql_real_escape_string($int_cat) . "',
                           remote_category_id = '" . sql_real_escape_string($rem_id) . "',
                           remote_category_name = '" . sql_real_escape_string($rem_name) . "',
                           created_at = '" . G5_TIME_YMDHIS . "' 
                        ON DUPLICATE KEY UPDATE 
                           remote_category_id = '" . sql_real_escape_string($rem_id) . "',
                           remote_category_name = '" . sql_real_escape_string($rem_name) . "',
                           updated_at = '" . G5_TIME_YMDHIS . "' ");
    }
}

alert('카테고리 매핑 정보가 저장되었습니다.', "./site_category_mapping.php?site_id={$site_id}");
