<?php
include_once('./_common.php');

if ($is_admin != 'super') {
    alert('권한이 없습니다.');
}

$chk = isset($_POST['chk']) ? $_POST['chk'] : array();

if (count($chk) > 0) {
    $table = G5_TABLE_PREFIX . 'landing_ai_log';
    $ids = array();
    foreach($chk as $id) {
        $ids[] = (int)$id;
    }
    $in_str = implode(',', $ids);
    
    sql_query(" delete from {$table} where id in ({$in_str}) ");
}

alert('선택한 로그가 삭제되었습니다.', './ai_log.php?'.$qstr);
?>
