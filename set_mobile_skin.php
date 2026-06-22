<?php
include "./common.php";
$sql = " update {$g5['board_table']} set bo_mobile_skin = 'inquiry' where bo_table = 'inquiry' ";
sql_query($sql);
echo "Updated bo_mobile_skin to inquiry.";
?>
