<?php
include_once('./_common.php');

$inquiry_id = isset($_POST['inquiry_id']) ? (int)$_POST['inquiry_id'] : 0;
$memo = isset($_POST['memo']) ? trim($_POST['memo']) : '';

if (!$inquiry_id || !$memo) {
    alert('잘못된 요청입니다.');
}

$mb_id = $member['mb_id'] ? $member['mb_id'] : 'admin';

$table_memo = G5_TABLE_PREFIX . 'landing_inquiry_memo';
$sql = "
    insert into {$table_memo}
       set inquiry_id = '{$inquiry_id}',
           mb_id = '{$mb_id}',
           memo = '".sql_real_escape_string($memo)."',
           created_at = '".G5_TIME_YMDHIS."'
";
sql_query($sql);

goto_url('./inquiry_form.php?id='.$inquiry_id);
