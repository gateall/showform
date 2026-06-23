<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가
?>
<!doctype html>
<html lang="ko">
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

<title><?php echo isset($g5['title']) ? get_text($g5['title']) : $config['cf_title']; ?></title>

<!-- ✅ 기본 SEO/메타(그누보드 설정 메타 포함) -->
<?php
// 그누보드 관리자에서 추가메타 넣은 경우 출력
if (isset($config['cf_add_meta'])) echo $config['cf_add_meta'];
?>

<!-- favicon -->
<link rel="shortcut icon" href="/assets/images/favicon.ico" />

<!-- inject css start -->
<link href="/assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
<link rel="stylesheet" href="/assets/css/pbminfotech-base-icons.css">
<link href="/assets/css/themify-icons.css" rel="stylesheet" type="text/css" />
<link href="/assets/css/fontawesome-all.css" rel="stylesheet" type="text/css" />
<link href="/assets/css/style.css" rel="stylesheet" type="text/css" />
<!-- inject css end -->

<?php
// 그누보드/테마에서 add_stylesheet 로 들어오는 것들 출력
if (function_exists('print_stylesheet')) print_stylesheet();
?>
</head>
<body>