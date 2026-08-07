<?php
$sub_menu = '700100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'd');
check_admin_token();

$table = G5_TABLE_PREFIX . 'sf_portfolio';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$return = isset($_GET['return']) ? trim($_GET['return']) : '';

if ($id > 0) {
    sql_query(" update {$table} set deleted_at = '" . G5_TIME_YMDHIS . "' where id = '{$id}' ");
}

goto_url($return === 'manager' ? SF_MANAGER_URL . '/showform/list.php' : SF_MANAGER_URL . '/showform/portfolio_list.php');
