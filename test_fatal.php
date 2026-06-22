<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
define('_GNUBOARD_', True);
$bo_table = 'inquiry';
$is_admin = true;
$view = array();
$view['content'] = 'Test content';
$view['wr_8'] = 'Test wr_8';
$view['wr_9'] = 'Test wr_9';
$board = array();
$member = array();

include "theme/knusu/skin/board/inquiry/view.skin.php";
echo "\nDONE\n";
?>
