<?php
include_once('./common.php');
$row = sql_fetch(" select bo_skin, bo_mobile_skin from {$g5['board_table']} where bo_table = 'notice' ");
echo "PC SKIN: " . $row['bo_skin'] . "<br>";
echo "MOBILE SKIN: " . $row['bo_mobile_skin'] . "<br>";
?>
