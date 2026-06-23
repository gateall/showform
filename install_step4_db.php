<?php
include_once('./common.php');

$table_name = $g5['table_prefix'] . 'landing_page';

$sql = " CREATE TABLE IF NOT EXISTS `{$table_name}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `company` varchar(100) NOT NULL DEFAULT '',
  `tel` varchar(50) NOT NULL DEFAULT '',
  `kakao` varchar(100) NOT NULL DEFAULT '',
  `email` varchar(100) NOT NULL DEFAULT '',
  `address` varchar(255) NOT NULL DEFAULT '',
  `category` varchar(50) NOT NULL DEFAULT '',
  `area` varchar(100) NOT NULL DEFAULT '',
  `hero_text` text NOT NULL,
  `intro_text` text NOT NULL,
  `adv1` varchar(255) NOT NULL DEFAULT '',
  `adv2` varchar(255) NOT NULL DEFAULT '',
  `adv3` varchar(255) NOT NULL DEFAULT '',
  `youtube_url` varchar(255) NOT NULL DEFAULT '',
  `map_url` varchar(255) NOT NULL DEFAULT '',
  `hero_image` varchar(255) NOT NULL DEFAULT '',
  `gallery1` varchar(255) NOT NULL DEFAULT '',
  `gallery2` varchar(255) NOT NULL DEFAULT '',
  `gallery3` varchar(255) NOT NULL DEFAULT '',
  `gallery4` varchar(255) NOT NULL DEFAULT '',
  `gallery5` varchar(255) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `updated_at` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8; ";

sql_query($sql, true);

// Check if id=1 exists
$row = sql_fetch(" select count(*) as cnt from `{$table_name}` where id = 1 ");
if ($row['cnt'] == 0) {
    // Insert sample data
    $insert_sql = " INSERT INTO `{$table_name}` SET
        `id` = 1,
        `subject` = 'AI 랜딩페이지 자동화 시스템',
        `company` = 'ShowForm',
        `tel` = '010-0000-0000',
        `category` = '랜딩페이지 자동화',
        `area` = '전국',
        `hero_text` = '사진과 업체 정보만 입력하면 랜딩페이지가 자동 생성됩니다.',
        `intro_text` = 'ShowForm은 소상공인과 지역업체를 위한 AI 기반 랜딩페이지 자동화 시스템입니다.',
        `adv1` = '빠르고 간편한 제작',
        `adv2` = 'AI 기반 맞춤형 카피라이팅',
        `adv3` = '고객 유입 증가 효과',
        `created_at` = '".G5_TIME_YMDHIS."',
        `updated_at` = '".G5_TIME_YMDHIS."'
    ";
    sql_query($insert_sql, true);
    echo "<h2>샘플 데이터(id=1) 생성이 완료되었습니다.</h2>";
} else {
    echo "<h2>테이블과 샘플 데이터(id=1)가 이미 존재합니다.</h2>";
}

echo "<p><a href='".G5_URL."/page/landing.php?id=1'>랜딩페이지 확인하기</a></p>";
?>
