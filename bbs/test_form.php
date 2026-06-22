<?php
ini_set('display_errors', 1); error_reporting(E_ALL);
include_once('./_common.php');
try {
    include_once(G5_LIB_PATH . '/naver_syndi.lib.php');
    echo "1. naver_syndi loaded<br>";
    include_once(G5_CAPTCHA_PATH . '/captcha.lib.php');
    echo "2. captcha loaded<br>";
    $bo_table = 'inquiry';
    $write_table = $g5['write_prefix'] . $bo_table;
    echo "3. table: $write_table<br>";
    $wr_num = get_next_num($write_table);
    echo "4. wr_num: $wr_num<br>";
    
    include_once(G5_LIB_PATH . '/mailer.lib.php');
    echo "5. mailer.lib loaded<br>";
    
    ob_start();
    include_once('./form_mail.php');
    $content = ob_get_contents();
    ob_end_clean();
    echo "6. form_mail loaded<br>";
    
    echo "OK";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
} catch (Error $e) {
    echo "Fatal Error: " . $e->getMessage();
}
?>
