<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

include_once('./_common.php');

include_once(G5_LIB_PATH . '/naver_syndi.lib.php');

include_once(G5_CAPTCHA_PATH . '/captcha.lib.php');



function clean_post_value($key) {
    if (!isset($_POST[$key])) return '';
    if (is_array($_POST[$key])) {
        return implode(', ', array_map('trim', $_POST[$key]));
    }
    return trim((string)$_POST[$key]);
}

$mode = clean_post_value('mode');

$bo_table = clean_post_value('bo_table');
if (!$bo_table) $bo_table = 'speed';

$wr_name = clean_post_value('wr_name');

$wr_1 = clean_post_value('wr_1');

$wr_content_post = clean_post_value('wr_content');

$wr_email = clean_post_value('wr_email');

$wr_link1 = clean_post_value('wr_link1');

$wr_link2 = clean_post_value('wr_link2');

$wr_homepage = clean_post_value('wr_homepage');

$html = isset($_POST['html']) ? $_POST['html'] : '';

$mail = isset($_POST['mail']) ? $_POST['mail'] : '';



$wr_subject = $wr_name . ' wrote ' . $mode . '.';

$wr_subject = get_text(stripslashes($wr_subject));



if (!preg_match("/^[0-9]/smi", $wr_1)) {

    alert('정확한 연락처를 입력해주세요.');

}



if ($wr_content_post && preg_match("/^[a-z]/smi", $wr_content_post)) {

    alert('견적 내용을 올바르게 입력해주세요.');

}



$write_table = $g5['write_prefix'] . $bo_table;

$wr_num = get_next_num($write_table);

$wr_password = get_encrypt_string('a1111');

$wr_8 = (isset($_POST['wr_8']) && trim((string) $_POST['wr_8']) !== '') ? trim((string) $_POST['wr_8']) : '';

if ($bo_table == 'inquiry' && $wr_8 === '') {

    $wr_8 = '견적접수';

}



$wr_content = '';

if ($bo_table == 'inquiry') {

    $wr_content .= 'Inquiry type: ' . (isset($_POST['wr_9']) ? $_POST['wr_9'] : '') . ' / ' . (isset($_POST['wr_10']) ? $_POST['wr_10'] : '') . "\n";

}



$wr_content .= 'Name: ' . $wr_name . "\n";

$wr_content .= 'Phone: ' . $wr_1;

if (isset($_POST['qc_type']) && $_POST['qc_type']) {

    $wr_content .= ' (contact type: ' . $_POST['qc_type'] . ')';

}



if ($bo_table == 'inquiry') {

    $wr_content .= "\n" . 'Move-out date: ' . (isset($_POST['wr_2']) ? $_POST['wr_2'] : '') . "\n";

    $wr_content .= 'Move-out address: ' . (isset($_POST['wr_3']) ? $_POST['wr_3'] : '') . ' / ' . (isset($_POST['wr_3_2']) ? $_POST['wr_3_2'] : '') . ' / ' . (isset($_POST['wr_3_3']) ? $_POST['wr_3_3'] : '') . "\n";

    $wr_content .= 'Move-in address: ' . (isset($_POST['wr_4']) ? $_POST['wr_4'] : '') . ' / ' . (isset($_POST['wr_4_2']) ? $_POST['wr_4_2'] : '') . ' / ' . (isset($_POST['wr_4_3']) ? $_POST['wr_4_3'] : '') . "\n";

    $wr_5 = isset($_POST['wr_5']) && is_array($_POST['wr_5']) ? implode(', ', $_POST['wr_5']) : '';

    $wr_content .= 'Items: ' . $wr_5 . "\n";

}

$wr_content .= "\n" . $wr_content_post;



if (!$wr_email) {

    $wr_email = 'webmaster@xn--2e0bl1svji8lhr7f.kr';

}

$wr_1_val = $wr_1;
$wr_2_val = isset($_POST['wr_2']) ? $_POST['wr_2'] : '';
$wr_3_val = isset($_POST['wr_3']) ? $_POST['wr_3'] : '';
$wr_4_val = isset($_POST['wr_4']) ? $_POST['wr_4'] : '';
$wr_5_val = isset($wr_5) ? $wr_5 : '';
$wr_6_val = isset($_POST['wr_6']) ? $_POST['wr_6'] : '';
$wr_7_val = isset($_POST['wr_7']) ? $_POST['wr_7'] : '';
$wr_8_val = $wr_8;
$wr_9_val = isset($_POST['wr_9']) ? $_POST['wr_9'] : '';
$wr_10_val = isset($_POST['wr_10']) ? $_POST['wr_10'] : '';

if ($bo_table == 'inquiry') {
    if ($mode == '상담신청') {
        // 모바일 하단 상담신청인 경우
        $wr_name = clean_post_value('wr_name');
        $wr_1_val = $wr_name; // wr_1에 이름 저장
        $wr_2_val = clean_post_value('wr_1'); // wr_2에 연락처 저장
        $wr_3_val = '상담신청'; // 이사종류
        $wr_4_val = ''; // 출발지
        $wr_5_val = ''; // 도착지
        $wr_6_val = ''; // 이사일
        $wr_7_val = ''; // 선택품목
        $wr_8_val = '견적접수'; // 견적상태
        
        // 문의내용을 포맷에 맞추어 구성
        $wr_content_post = "상담신청 내용\n\n";
        $wr_content_post .= "성명: " . $wr_name . "\n";
        $wr_content_post .= "연락처: " . $wr_2_val . "\n";
        $wr_content_post .= "이메일: " . $wr_email . "\n";
        $wr_content_post .= "문의내용: " . clean_post_value('wr_content') . "\n";
        $wr_content_post .= "접수경로: 모바일 하단 상담창";
        
        $wr_9_val = clean_post_value('wr_content'); // 기타메모 컬럼(wr_9)에 문의내용 저장
        $wr_10_val = '모바일 하단 상담창'; // 주거형태 컬럼에 접수경로 저장
        
        $wr_subject = "[인터넷2424] 모바일 하단 상담신청 접수";
        
        // 이메일 본문 주입용 변수
        $wr_content = $wr_content_post;
    } else {
        $wr_name = isset($_POST['wr_name']) ? trim($_POST['wr_name']) : '';
        $wr_1_val = $wr_name; // wr_1에 고객명 저장
        
        // 연락처
        $wr_2_val = isset($_POST['wr_1']) ? trim($_POST['wr_1']) : '';
        
        // 이사종류
        $wr_3_val = isset($_POST['wr_9']) ? trim($_POST['wr_9']) : '';
        
        // 출발지 정보 결합
        $addr_out = isset($_POST['wr_3']) ? trim($_POST['wr_3']) : '';
        $pyung_out = isset($_POST['wr_3_2']) ? trim($_POST['wr_3_2']) : '';
        $floor_out = isset($_POST['wr_3_3']) ? trim($_POST['wr_3_3']) : '';
        $elevator_out = isset($_POST['wr_3_elevator']) ? trim($_POST['wr_3_elevator']) : '';
        $wr_4_val = $addr_out;
        if ($pyung_out !== '') $wr_4_val .= ' / ' . $pyung_out . '평';
        if ($floor_out !== '') $wr_4_val .= ' / ' . $floor_out . '층';
        if ($elevator_out !== '') $wr_4_val .= ' / 엘리베이터 ' . $elevator_out;
        
        // 도착지 정보 결합
        $addr_in = isset($_POST['wr_4']) ? trim($_POST['wr_4']) : '';
        $pyung_in = isset($_POST['wr_4_2']) ? trim($_POST['wr_4_2']) : '';
        $floor_in = isset($_POST['wr_4_3']) ? trim($_POST['wr_4_3']) : '';
        $elevator_in = isset($_POST['wr_4_elevator']) ? trim($_POST['wr_4_elevator']) : '';
        $wr_5_val = $addr_in;
        if ($pyung_in !== '') $wr_5_val .= ' / ' . $pyung_in . '평';
        if ($floor_in !== '') $wr_5_val .= ' / ' . $floor_in . '층';
        if ($elevator_in !== '') $wr_5_val .= ' / 엘리베이터 ' . $elevator_in;
        
        // 이사일
        $wr_6_val = isset($_POST['wr_2']) ? trim($_POST['wr_2']) : '';
        
        // 선택품목
        $furniture_arr = isset($_POST['wr_5']) ? $_POST['wr_5'] : array();
        if (is_array($furniture_arr)) {
            $wr_7_val = implode(',', $furniture_arr);
        } else {
            $wr_7_val = trim($furniture_arr);
        }
        
        // 견적상태
        $wr_8_val = '견적접수';
        
        // 기타메모
        $wr_9_val = isset($_POST['wr_content']) ? trim($_POST['wr_content']) : '';
        
        // 주거형태
        $wr_10_val = isset($_POST['wr_10']) ? trim($_POST['wr_10']) : '';
        
        // 그누보드 표준 필드 대응
        $wr_subject = $wr_name . " 이사견적문의";
        $wr_content_post = $wr_9_val;
    }
}

$sql = " insert into $write_table set

            wr_num = '$wr_num',

            wr_reply = '',

            wr_comment = 0,

            wr_option = '$html,secret,$mail',

            wr_subject = '$wr_subject',

            wr_content = '" . sql_real_escape_string($wr_content_post) . "',

            wr_link1 = '$wr_link1',

            wr_link2 = '$wr_link2',

            wr_link1_hit = 0,

            wr_link2_hit = 0,

            wr_hit = 0,

            wr_good = 0,

            wr_nogood = 0,

            mb_id = '',

            wr_password = '$wr_password',

            wr_name = '$wr_name',

            ca_name = '" . $bo_table . "',

            wr_email = '$wr_email',

            wr_homepage = '$wr_homepage',

            wr_datetime = '" . G5_TIME_YMDHIS . "',

            wr_last = '" . G5_TIME_YMDHIS . "',

            wr_ip = '" . $_SERVER['REMOTE_ADDR'] . "',

            wr_1 = '$wr_1_val',

            wr_2 = '" . sql_real_escape_string($wr_2_val) . "',

            wr_3 = '" . sql_real_escape_string($wr_3_val) . "',

            wr_4 = '" . sql_real_escape_string($wr_4_val) . "',

            wr_5 = '" . sql_real_escape_string($wr_5_val) . "',

            wr_6 = '" . sql_real_escape_string($wr_6_val) . "',

            wr_7 = '" . sql_real_escape_string($wr_7_val) . "',

            wr_8 = '" . sql_real_escape_string($wr_8_val) . "',

            wr_9 = '" . sql_real_escape_string($wr_9_val) . "',

            wr_10 = '" . sql_real_escape_string($wr_10_val) . "' ";



sql_query($sql);

$wr_id = sql_insert_id();



sql_query(" update $write_table set wr_parent = '$wr_id' where wr_id = '$wr_id' ");

sql_query(" insert into {$g5['board_new_table']} ( bo_table, wr_id, wr_parent, bn_datetime, mb_id ) values ( '{$bo_table}', '{$wr_id}', '{$wr_id}', '" . G5_TIME_YMDHIS . "', '{$member['mb_id']}' ) ");

sql_query(" update {$g5['board_table']} set bo_count_write = bo_count_write + 1 where bo_table = '{$bo_table}' ");



$tmp_html = 0;

if (strstr($html, 'html1')) {

    $tmp_html = 1;

} else if (strstr($html, 'html2')) {

    $tmp_html = 2;

}



$wr_content_mail = conv_content(conv_unescape_nl(stripslashes($wr_content)), $tmp_html);

$subject = '[' . (!empty($config['cf_1']) ? $config['cf_1'] : 'FriendLogis') . '/' . $mode . '] ' . $wr_subject;



include_once(G5_LIB_PATH . '/mailer.lib.php');



ob_start();

$form_mail_file = G5_BBS_PATH . '/form_mail.php';
if (file_exists($form_mail_file)) {
    include_once($form_mail_file);
}

$content = ob_get_contents();

ob_end_clean();



function MobileCheck()
{

    $mAgent = array('iPhone', 'iPod', 'Android', 'Blackberry', 'Opera Mini', 'Windows ce', 'Nokia', 'sony');

    for ($i = 0; $i < sizeof($mAgent); $i++) {

        if (isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], $mAgent[$i]) !== false) {

            return 'Mobile';

        }

    }

    return 'PC';

}



function friendlogis_order_mail_debug($recipient, $from_email, $reply_to, $subject, $sent, $extra_message = '')
{

    $log_file = G5_DATA_PATH . '/order_mail_debug.txt';

    $log_dir = dirname($log_file);

    if (!is_dir($log_dir)) {

        @mkdir($log_dir, 0755, true);

    }



    $php_error = 'none';

    if (!$sent && function_exists('error_get_last')) {

        $last_error = error_get_last();

        if (!empty($last_error['message'])) {

            $php_error = $last_error['message'];

        }

    }



    $lines = array(

        'time: ' . date('Y-m-d H:i:s'),

        'page: ' . (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : 'unknown'),

        'post: ' . (!empty($_POST) ? 'yes' : 'no'),

        'to: ' . $recipient,

        'from: ' . $from_email,

        'reply_to: ' . ($reply_to ? $reply_to : 'none'),

        'subject: ' . $subject,

        'mail() called: yes',

        'result: ' . ($sent ? 'SUCCESS' : 'FAILED'),

        'php_error: ' . $php_error

    );



    if ($extra_message !== '') {

        $lines[] = $extra_message;

    }



    @file_put_contents($log_file, implode("\n", $lines) . "\n---\n", FILE_APPEND | LOCK_EX);

}



$recipient_list = array();

if (!empty($config['cf_admin_email']) && filter_var($config['cf_admin_email'], FILTER_VALIDATE_EMAIL)) {

    $recipient_list[] = $config['cf_admin_email'];

}

if (!empty($config['cf_11']) && filter_var($config['cf_11'], FILTER_VALIDATE_EMAIL)) {

    $recipient_list[] = $config['cf_11'];

}

if (!empty($config['cf_18']) && filter_var($config['cf_18'], FILTER_VALIDATE_EMAIL)) {

    $recipient_list[] = $config['cf_18'];

}



$recipient_list = array_values(array_unique(array_filter(array_map('trim', $recipient_list))));



if (!$wr_email || !filter_var($wr_email, FILTER_VALIDATE_EMAIL)) {

    $wr_email = 'webmaster@xn--2e0bl1svji8lhr7f.kr';

}



$file_name = array();

if (!empty($_FILES['bf_file']['tmp_name'])) {

    $file_name[] = attach_file($_FILES['bf_file']['name'], $_FILES['bf_file']['tmp_name']);

}



$sender_name = !empty($config['cf_admin_email_name']) ? $config['cf_admin_email_name'] : (!empty($config['cf_1']) ? $config['cf_1'] : 'FriendLogis');

$sender_email = mailer_default_from();

$reply_to = $wr_email;

$device = MobileCheck();

if ($mode == '상담신청') {
    $mail_subject = '[인터넷2424] 모바일 하단 상담신청 접수';
} else {
    $mail_subject = $subject . ' [' . $device . ']';
}



for ($i = 0; $i < count($recipient_list); $i++) {

    error_log('[FORM MAIL] start - To: ' . $recipient_list[$i] . ' | Subject: ' . $mail_subject);

    $sent = mailer($sender_name, $sender_email, $recipient_list[$i], $mail_subject, $content, 1, $file_name, '', '', $reply_to);

    error_log('[FORM MAIL] end - To: ' . $recipient_list[$i] . ' | Result: ' . ($sent ? 'SUCCESS' : 'FAILED'));

    friendlogis_order_mail_debug(

        $recipient_list[$i],

        $sender_email,

        $reply_to,

        $mail_subject,

        $sent,

        'device: ' . $device . ' | name: ' . $wr_name . ' | tel: ' . $wr_1

    );



    if (!$sent) {

        error_log('[form_insert] Mail send failed - To: ' . $recipient_list[$i] . ' | Subject: ' . $mail_subject . ' | IP: ' . (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown'));

    }

}



$_SESSION['http_log'] = 'ok';



if ($mode == '상담신청') {
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : G5_URL;
    exit("<script>alert('상담 신청이 접수되었습니다.'); location.href='" . $redirect_url . "';</script>");
}

if ($bo_table == 'inquiry') {
    exit("<script>alert('견적접수가 정상적으로 완료되었습니다.'); location.href='" . G5_URL . "/estimate01.php';</script>");
}

if (isset($_POST['ret']) && $_POST['ret'] == 'end') {

    error_log('[FORM] finishing via end redirect');

    exit("<script>alert('견적접수가 정상적으로 완료되었습니다.'); location.href='" . G5_URL . "/estimate01.php';</script>");

} else if (isset($_POST['ret']) && $_POST['ret'] == 'reset') {

    $target_form = isset($fname) ? $fname : 'fwrite';

    error_log('[FORM] finishing via reset redirect');

    exit("<script>alert('견적접수가 정상적으로 완료되었습니다.'); if (parent && parent.document && parent.document." . $target_form . ") { parent.document." . $target_form . ".reset(); } location.href='" . G5_URL . "/estimate01.php';</script>");

}



alert('견적접수가 정상적으로 완료되었습니다.');

exit;



