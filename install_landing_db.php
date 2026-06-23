<?php
include_once('./common.php');

// 랜딩페이지 정보 저장 테이블
$sql1 = " CREATE TABLE IF NOT EXISTS `{$g5['table_prefix']}landing_page` (
  `ld_id` int(11) NOT NULL AUTO_INCREMENT,
  `mb_id` varchar(20) NOT NULL DEFAULT '',
  `ld_domain` varchar(255) NOT NULL DEFAULT '',
  `ld_title` varchar(255) NOT NULL DEFAULT '',
  `ld_category` varchar(50) NOT NULL DEFAULT '',
  `ld_company` varchar(100) NOT NULL DEFAULT '',
  `ld_summary` text NOT NULL,
  `ld_usp` text NOT NULL,
  `ld_cta` varchar(50) NOT NULL DEFAULT '',
  `ld_content` longtext NOT NULL,
  `ld_template` varchar(50) NOT NULL DEFAULT 'basic',
  `ld_status` tinyint(4) NOT NULL DEFAULT 0,
  `ld_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ld_id`),
  KEY `mb_id` (`mb_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8; ";

sql_query($sql1, true);

// 랜딩페이지 문의 내역 저장 테이블
$sql2 = " CREATE TABLE IF NOT EXISTS `{$g5['table_prefix']}landing_inquiry` (
  `inq_id` int(11) NOT NULL AUTO_INCREMENT,
  `ld_id` int(11) NOT NULL DEFAULT 0,
  `mb_id` varchar(20) NOT NULL DEFAULT '',
  `inq_name` varchar(50) NOT NULL DEFAULT '',
  `inq_tel` varchar(50) NOT NULL DEFAULT '',
  `inq_content` text NOT NULL,
  `inq_status` tinyint(4) NOT NULL DEFAULT 0,
  `inq_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`inq_id`),
  KEY `ld_id` (`ld_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8; ";

sql_query($sql2, true);

echo "<h2>랜딩페이지 자동화 DB 테이블(landing_page, landing_inquiry) 생성이 완료되었습니다.</h2>";
echo "<p>보안을 위해 본 파일(install_landing_db.php)을 삭제해 주시기 바랍니다.</p>";
?>
