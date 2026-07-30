<?php
// 처리 전용 파일 — 화면은 install_form.php가 담당한다. GET으로 직접 접근하면
// 설치 화면으로 안내만 하고 아무 것도 실행하지 않는다.
$sub_menu = '360900';
include_once('./_common.php');
require_once __DIR__ . '/lib/blog_install.lib.php';

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$admin_form_url = G5_ADMIN_URL . '/blog/install_form.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alert('설치 화면에서 진행해 주세요.', $admin_form_url);
}

check_admin_token();

$table_prefix = G5_TABLE_PREFIX;
$versions = bp_install_get_versions($table_prefix);

$mode = isset($_POST['mode']) ? $_POST['mode'] : '';
$version = isset($_POST['version']) ? (int) $_POST['version'] : 0;

if ($mode !== 'run_all' && $mode !== 'run_version') {
    alert('잘못된 요청입니다.', $admin_form_url);
}
if ($mode === 'run_version' && !isset($versions[$version])) {
    alert('잘못된 버전 요청입니다.', $admin_form_url);
}

$created = array();
$install_error = '';

try {
    foreach ($versions as $v => $info) {
        if (!is_file($info['file'])) {
            throw new RuntimeException('SQL 파일을 찾을 수 없습니다: ' . $info['file']);
        }
        $should_run = ($mode === 'run_all') ? true : ($v === $version);
        if ($should_run) {
            $created = array_merge($created, bp_install_run_sql_file($info['file'], $table_prefix));
        }
    }

    $blog_img_dir = G5_DATA_PATH . '/blog_images';
    if (!is_dir($blog_img_dir)) {
        @mkdir($blog_img_dir, G5_DIR_PERMISSION);
        @chmod($blog_img_dir, G5_DIR_PERMISSION);
    }
} catch (Throwable $e) {
    $install_error = $e->getMessage();
}

set_session('bp_install_result', array('created' => $created, 'error' => $install_error));
goto_url($admin_form_url);
