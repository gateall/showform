<?php
if (!defined('_GNUBOARD_')) exit; // 媛쒕퀎 ?섏씠吏 ?묎렐 遺덇?

if ($bo_table == 'inquiry') {
    $wr_name = isset($_POST['wr_name']) ? trim($_POST['wr_name']) : '';
    $wr_1 = $wr_name; // wr_name 諛?wr_1??怨좉컼紐??숈떆 ???
    
    // ?곕씫泥??대???
    $wr_2 = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    // ?댁궗醫낅쪟
    $wr_3 = isset($_POST['move_type']) ? trim($_POST['move_type']) : '';
    
    // 異쒕컻吏 ?뺣낫 寃고빀 (二쇱냼 / ?됱닔 / 痢듭닔 / ?몄닔 / ?섎━踰좎씠???щ?)
    $pyung_out = isset($_POST['pyung_out']) ? trim($_POST['pyung_out']) : '';
    $floor_out = isset($_POST['floor_out']) ? trim($_POST['floor_out']) : '';
    $ho_out = isset($_POST['ho_out']) ? trim($_POST['ho_out']) : '';
    $elevator_out = isset($_POST['elevator_out']) ? trim($_POST['elevator_out']) : '';
    $wr_4 = trim($_POST['addr_out']);
    if ($pyung_out !== '') $wr_4 .= ' / ' . $pyung_out . '??;
    if ($floor_out !== '') $wr_4 .= ' / ' . $floor_out . '痢?;
    if ($ho_out !== '') $wr_4 .= ' / ' . $ho_out . '??;
    if ($elevator_out !== '') $wr_4 .= ' / ?섎━踰좎씠??' . $elevator_out;

    // ?꾩갑吏 ?뺣낫 寃고빀 (二쇱냼 / ?됱닔 / 痢듭닔 / ?몄닔 / ?섎━踰좎씠???щ?)
    $pyung_in = isset($_POST['pyung_in']) ? trim($_POST['pyung_in']) : '';
    $floor_in = isset($_POST['floor_in']) ? trim($_POST['floor_in']) : '';
    $ho_in = isset($_POST['ho_in']) ? trim($_POST['ho_in']) : '';
    $elevator_in = isset($_POST['elevator_in']) ? trim($_POST['elevator_in']) : '';
    $wr_5 = trim($_POST['addr_in']);
    if ($pyung_in !== '') $wr_5 .= ' / ' . $pyung_in . '??;
    if ($floor_in !== '') $wr_5 .= ' / ' . $floor_in . '痢?;
    if ($ho_in !== '') $wr_5 .= ' / ' . $ho_in . '??;
    if ($elevator_in !== '') $wr_5 .= ' / ?섎━踰좎씠??' . $elevator_in;

    // ?댁궗??
    $wr_6 = isset($_POST['move_date']) ? trim($_POST['move_date']) : '';

    // ?ы븿媛援?寃고빀 (肄ㅻ쭏 援щ텇)
    $furniture_arr = isset($_POST['furniture']) ? $_POST['furniture'] : array();
    $wr_7 = implode(',', $furniture_arr);

    // 寃ъ쟻?곹깭 ?ㅼ젙
    if ($w == 'u') {
        if ($is_admin && isset($_POST['status_val'])) {
            $wr_8 = trim($_POST['status_val']);
        } else {
            $wr_8 = isset($write['wr_8']) ? trim($write['wr_8']) : '寃ъ쟻?묒닔';
        }
    } else {
        $wr_8 = '寃ъ쟻?묒닔';
    }

    // 湲고?硫붾え (異붽??ы빆)
    $wr_9 = isset($_POST['memo']) ? trim($_POST['memo']) : '';
    
    // 二쇨굅?뺥깭
    $wr_10 = isset($_POST['housing_type']) ? trim($_POST['housing_type']) : '';

    // 洹몃늻蹂대뱶 ?쒖? ?꾨뱶 ???
    $wr_subject = $wr_name . " ?댁궗寃ъ쟻臾몄쓽";
    $wr_content = $wr_9;

    // DB 荑쇰━瑜??듯빐 ?뺤떎?섍쾶 蹂댁젙
    sql_query(" update {$write_table} set
        wr_subject = '".sql_real_escape_string($wr_subject)."',
        wr_content = '".sql_real_escape_string($wr_content)."',
        wr_name = '".sql_real_escape_string($wr_name)."',
        wr_1 = '".sql_real_escape_string($wr_1)."',
        wr_2 = '".sql_real_escape_string($wr_2)."',
        wr_3 = '".sql_real_escape_string($wr_3)."',
        wr_4 = '".sql_real_escape_string($wr_4)."',
        wr_5 = '".sql_real_escape_string($wr_5)."',
        wr_6 = '".sql_real_escape_string($wr_6)."',
        wr_7 = '".sql_real_escape_string($wr_7)."',
        wr_8 = '".sql_real_escape_string($wr_8)."',
        wr_9 = '".sql_real_escape_string($wr_9)."',
        wr_10 = '".sql_real_escape_string($wr_10)."'
        where wr_id = '{$wr_id}'
    ");

    goto_url(G5_URL.'/estimate01.php');
    exit;
}
