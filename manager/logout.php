<?php
// 그누보드 표준 로그아웃 처리를 그대로 사용한다(별도 세션 파기 로직을 만들지 않는다).
$g5_path = '..';
include_once(__DIR__ . '/../common.php');

goto_url(G5_BBS_URL . '/logout.php');
