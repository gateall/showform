<?php
include "./common.php";
$sql = "select bo_skin, bo_mobile_skin from {$g5['board_table']} where bo_table = 'inquiry'";
$row = sql_fetch($sql);
echo json_encode($row);
?>
