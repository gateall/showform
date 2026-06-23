<?php
include_once('./_common.php');

// DB 테이블 자동 생성 (존재하지 않을 경우)
$table = G5_TABLE_PREFIX . 'landing_inquiry';
$sql_create = "
CREATE TABLE IF NOT EXISTS `{$table}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `landing_id` int(11) NOT NULL DEFAULT '0',
  `name` varchar(100) NOT NULL DEFAULT '',
  `tel` varchar(100) NOT NULL DEFAULT '',
  `schedule` varchar(100) NOT NULL DEFAULT '',
  `people` varchar(50) NOT NULL DEFAULT '',
  `content` text,
  `status` varchar(20) NOT NULL DEFAULT '접수대기',
  `remote_ip` varchar(50) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `landing_id` (`landing_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
";
@sql_query($sql_create, false);

$landing_id = isset($_POST['landing_id']) ? (int)$_POST['landing_id'] : 0;
$name       = isset($_POST['name']) ? sql_real_escape_string(trim($_POST['name'])) : '';
$tel        = isset($_POST['tel']) ? sql_real_escape_string(trim($_POST['tel'])) : '';
$schedule   = isset($_POST['schedule']) ? sql_real_escape_string(trim($_POST['schedule'])) : '';
$people     = isset($_POST['people']) ? sql_real_escape_string(trim($_POST['people'])) : '';
$content    = isset($_POST['content']) ? sql_real_escape_string(trim($_POST['content'])) : '';
$agree      = isset($_POST['agree']) ? (int)$_POST['agree'] : 0;

if (!$name || !$tel || !$agree) {
    alert('이름과 연락처, 개인정보 동의는 필수입니다.');
}

$sql = "
    INSERT INTO {$table}
    SET landing_id = '{$landing_id}',
        name = '{$name}',
        tel = '{$tel}',
        schedule = '{$schedule}',
        people = '{$people}',
        content = '{$content}',
        status = '접수대기',
        remote_ip = '{$_SERVER['REMOTE_ADDR']}',
        created_at = '".G5_TIME_YMDHIS."',
        updated_at = '".G5_TIME_YMDHIS."'
";
sql_query($sql);

alert('상담 예약 신청이 완료되었습니다.\\n담당자가 확인 후 빠르게 연락드리겠습니다.', '/page/landing.php?id='.$landing_id);
