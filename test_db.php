<?php
$conn = @mysqli_connect('localhost', 'logis79', 'ever7001!', 'logis79');
if ($conn) {
    $res = mysqli_query($conn, "select bo_table, bo_skin, bo_mobile_skin from g5_board where bo_table = 'notice'");
    print_r(mysqli_fetch_assoc($res));
} else {
    echo "DB Connection FAILED";
}
