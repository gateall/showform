<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

if ($bo_table == 'inquiry') {
    $wr_name = isset($_POST['wr_name']) ? trim($_POST['wr_name']) : '';
    $wr_1 = $wr_name; // wr_name 및 wr_1에 고객명 동시 저장
    
    // 연락처(휴대폰)
    $wr_2 = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    // 이사종류
    $wr_3 = isset($_POST['move_type']) ? trim($_POST['move_type']) : '';
    
    // 출발지 정보 결합 (주소 / 평수 / 층수 / 호수 / 엘리베이터 여부)
    $pyung_out = isset($_POST['pyung_out']) ? trim($_POST['pyung_out']) : '';
    $floor_out = isset($_POST['floor_out']) ? trim($_POST['floor_out']) : '';
    $ho_out = isset($_POST['ho_out']) ? trim($_POST['ho_out']) : '';
    $elevator_out = isset($_POST['elevator_out']) ? trim($_POST['elevator_out']) : '';
    $wr_4 = trim($_POST['addr_out']);
    if ($pyung_out !== '') $wr_4 .= ' / ' . $pyung_out . '평';
    if ($floor_out !== '') $wr_4 .= ' / ' . $floor_out . '층';
    if ($ho_out !== '') $wr_4 .= ' / ' . $ho_out . '호';
    if ($elevator_out !== '') $wr_4 .= ' / 엘리베이터 ' . $elevator_out;

    // 도착지 정보 결합 (주소 / 평수 / 층수 / 호수 / 엘리베이터 여부)
    $pyung_in = isset($_POST['pyung_in']) ? trim($_POST['pyung_in']) : '';
    $floor_in = isset($_POST['floor_in']) ? trim($_POST['floor_in']) : '';
    $ho_in = isset($_POST['ho_in']) ? trim($_POST['ho_in']) : '';
    $elevator_in = isset($_POST['elevator_in']) ? trim($_POST['elevator_in']) : '';
    $wr_5 = trim($_POST['addr_in']);
    if ($pyung_in !== '') $wr_5 .= ' / ' . $pyung_in . '평';
    if ($floor_in !== '') $wr_5 .= ' / ' . $floor_in . '층';
    if ($ho_in !== '') $wr_5 .= ' / ' . $ho_in . '호';
    if ($elevator_in !== '') $wr_5 .= ' / 엘리베이터 ' . $elevator_in;

    // 이사일
    $wr_6 = isset($_POST['move_date']) ? trim($_POST['move_date']) : '';

    // 포함가구 결합 (콤마 구분)
    $furniture_arr = isset($_POST['furniture']) ? $_POST['furniture'] : array();
    $wr_7 = implode(',', $furniture_arr);

    // 견적상태 설정
    if ($w == 'u') {
        if ($is_admin && isset($_POST['status_val'])) {
            $wr_8 = trim($_POST['status_val']);
        } else {
            $wr_8 = isset($write['wr_8']) ? trim($write['wr_8']) : '견적접수';
        }
    } else {
        $wr_8 = '견적접수';
    }

    // 기타메모 (추가사항)
    $wr_9 = isset($_POST['memo']) ? trim($_POST['memo']) : '';
    
    // 주거형태
    $wr_10 = isset($_POST['housing_type']) ? trim($_POST['housing_type']) : '';

    // 그누보드 표준 필드 대응
    $wr_subject = $wr_name . " 이사견적문의";
    $wr_content = $wr_9;

    // DB 쿼리를 통해 확실하게 보정
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
