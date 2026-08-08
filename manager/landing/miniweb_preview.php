<?php
// 빌더 중앙의 iframe 안에 들어가는 미리보기.
//
// iframe으로 띄우는 이유: div 폭만 줄이면 미디어쿼리는 여전히 바깥 창 폭(예: 1440)을
// 보기 때문에, 390px 상자 안에 PC 레이아웃이 그려진다. iframe은 자체 뷰포트를 가지므로
// 폭을 390으로 주면 그 안의 CSS가 진짜 390 기준으로 평가된다.
//
// 렌더링은 adm/landing/lib/miniweb_render.lib.php 하나만 쓴다. 실제 발행도 같은 함수를
// 쓰게 해서 "미리보기와 결과가 다르다"가 생길 여지를 없앤다.
$sub_menu = '900100';
include_once('./_common.php');

auth_check_menu($auth, $sub_menu, 'r');

require_once G5_ADMIN_PATH . '/landing/lib/miniweb_render.lib.php';

$pid = isset($_GET['pid']) ? (int) $_GET['pid'] : 0;

$parts = array('html' => '', 'css' => '', 'js' => '');
if ($pid > 0) {
    $parts = mw_render_project($pid);
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>미니웹 미리보기</title>
<style>
*, *::before, *::after { box-sizing: border-box; }
html, body { margin:0; padding:0; }
body { font-family: -apple-system, BlinkMacSystemFont, "Malgun Gothic", sans-serif;
       color:#0f172a; background:#fff; -webkit-text-size-adjust:100%; }
img { max-width:100%; height:auto; }

/* 블록 공통 CSS */
<?php echo mw_render_base_css(); ?>

/* 선택된 블록들이 가져온 CSS */
<?php echo $parts['css']; ?>

/* 아직 아무 것도 고르지 않았을 때 */
.mw-empty { padding:64px 24px; text-align:center; color:#94a3b8; line-height:1.8; }
</style>
</head>
<body>
<?php
if (trim($parts['html']) === '') {
    echo '<div class="mw-empty">아직 선택한 디자인이 없습니다.<br>오른쪽에서 Hero 디자인을 골라 보세요.</div>';
} else {
    echo $parts['html'];
}
?>
<?php if (trim($parts['js']) !== '') { ?>
<?php // 블록의 js_code 에 스크립트 종료 태그가 들어 있으면 HTML 파서가 여기서 script 를
      // 닫아버려, 나머지 코드가 화면에 글자로 쏟아진다. 종료로 읽히지 않게 끊어 준다
      // (자바스크립트 문자열 안에서는 <\/ 가 / 와 같으므로 동작은 그대로다). ?>
<script><?php echo str_replace('</script', '<\/script', $parts['js']); ?></script>
<?php } ?>
</body>
</html>
