<?php
include_once('./_common.php');

auth_check_menu($auth, '900500', 'w');

function sf_extract_youtube_id($url)
{
    $url = trim((string)$url);
    if ($url === '') {
        return '';
    }

    $patterns = array(
        '~(?:https?://)?(?:www\.)?youtu\.be/([A-Za-z0-9_-]{11})~i',
        '~(?:https?://)?(?:www\.)?youtube\.com/watch\?v=([A-Za-z0-9_-]{11})~i',
        '~(?:https?://)?(?:www\.)?youtube\.com/embed/([A-Za-z0-9_-]{11})~i',
        '~(?:https?://)?(?:www\.)?youtube\.com/shorts/([A-Za-z0-9_-]{11})~i',
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $url, $m)) {
            return $m[1];
        }
    }

    if (preg_match('~[?&]v=([A-Za-z0-9_-]{11})~i', $url, $m)) {
        return $m[1];
    }

    return '';
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$landing_id = isset($_POST['landing_id']) ? (int)$_POST['landing_id'] : 0;
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$youtube_url = isset($_POST['youtube_url']) ? trim($_POST['youtube_url']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
$is_active = isset($_POST['is_active']) ? trim($_POST['is_active']) : 'Y';

if ($landing_id < 1) {
    alert('랜딩을 선택하세요.', './youtube_form.php' . ($id ? '?id=' . $id : ''));
}
if ($title === '') {
    alert('제목을 입력하세요.', './youtube_form.php' . ($id ? '?id=' . $id : ''));
}
if ($youtube_url === '') {
    alert('YouTube URL을 입력하세요.', './youtube_form.php' . ($id ? '?id=' . $id : ''));
}

$video_id = sf_extract_youtube_id($youtube_url);
if ($video_id === '') {
    alert('유효한 YouTube URL이 아닙니다.', './youtube_form.php' . ($id ? '?id=' . $id : ''));
}

if ($is_active !== 'N') {
    $is_active = 'Y';
}

$table = G5_TABLE_PREFIX . 'landing_youtube';
$normalized_url = 'https://www.youtube.com/watch?v=' . $video_id;
$set_sql = " landing_id = '" . (int)$landing_id . "', title = '" . sql_real_escape_string($title) . "', youtube_url = '" . sql_real_escape_string($normalized_url) . "', description = '" . sql_real_escape_string($description) . "', sort_order = '" . (int)$sort_order . "', is_active = '" . sql_real_escape_string($is_active) . "' ";

if ($id) {
    sql_query(" update {$table} set {$set_sql} where id = '{$id}' ");
    alert('유튜브 항목을 수정했습니다.', './youtube_form.php?id=' . $id);
}

sql_query(" insert into {$table} set {$set_sql}, created_at = '" . G5_TIME_YMDHIS . "' ");
$new_id = sql_insert_id();
alert('유튜브 항목을 등록했습니다.', './youtube_form.php?id=' . $new_id);