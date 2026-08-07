<?php
// 포스팅 제작 화면은 /manager/blog/post_builder.php 한 곳으로 통합됐다.
//
// 여기 있던 구버전은 builder_managers.js를 로드하지 않는다. 즉 글전개 구조 관리와
// AI 생성조건 관리가 같은 모달·같은 렌더러를 쓰던 시절의 화면이라, 이쪽으로 들어오면
// 분리 작업과 무관하게 예전 증상이 그대로 재현된다. 화면을 두 벌 유지하는 한 계속
// 재발하므로 진입점만 남기고 새 주소로 넘긴다. 원본은 git 이력(86a68c0)에 있다.
//
// 같은 폴더의 post_builder_ajax.php는 건드리지 않는다 — 통합된 manager 화면이 빌더
// 본체 액션(단계 이동, 카드 저장, 발행)을 여전히 그 파일로 호출한다.
//
// 라우팅은 로그인 여부와 무관하므로 common.php를 부르기 전에 처리한다.

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper($_SERVER['REQUEST_METHOD']) : 'GET';
$query = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
    ? '?' . $_SERVER['QUERY_STRING']
    : '';
$target = '/manager/blog/post_builder.php' . $query;

if ($method === 'GET' || $method === 'HEAD') {
    header('Location: ' . $target, true, 301);
    exit;
}

// 301은 브라우저가 GET으로 바꿔 재요청하면서 본문을 버린다. 변경 요청을 조용히
// 흘려보내지 않도록 405로 끊는다.
header('Allow: GET, HEAD');
header('Content-Location: ' . $target);
http_response_code(405);
exit('Method Not Allowed');
