<?php
include_once('./_common.php');

$sub_menu = '900100';
auth_check_menu($auth, $sub_menu, 'r');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    header('Location: ./landing_form.php?id=' . $id);
    exit;
}

header('Location: ./landing_form.php');
exit;