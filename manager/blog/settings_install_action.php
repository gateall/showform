<?php
// 처리 전용 파일 — 화면은 settings.php?tab=install이 담당한다.
// adm/blog/install.php와 동일한 bp_install_* 라이브러리를 그대로 재사용해서,
// manager/에서도 adm으로 건너가지 않고 같은 로직으로 설치를 실행할 수 있게 한다.
include_once('./_common.php');
require_once G5_ADMIN_PATH . '/blog/lib/blog_install.lib.php';

if ($is_admin !== 'super') {
    alert('설치 관리는 최고관리자만 접근할 수 있습니다.');
}

$settings_url = './settings.php?tab=install';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    goto_url($settings_url);
}

check_admin_token();

$table_prefix = G5_TABLE_PREFIX;
$versions = bp_install_get_versions($table_prefix);

$mode = isset($_POST['mode']) ? $_POST['mode'] : '';
$version = isset($_POST['version']) ? (int) $_POST['version'] : 0;

if ($mode !== 'run_all' && $mode !== 'run_version') {
    alert('잘못된 요청입니다.', $settings_url);
}
if ($mode === 'run_version' && !isset($versions[$version])) {
    alert('잘못된 버전 요청입니다.', $settings_url);
}

$created = array();
$install_error = '';
$install_error_data = null;

try {
    foreach ($versions as $v => $info) {
        if (!is_file($info['file'])) {
            throw new RuntimeException('SQL 파일을 찾을 수 없습니다: ' . $info['file']);
        }
        $should_run = ($mode === 'run_all') ? true : ($v === $version);
        if ($should_run) {
            try {
                $created = array_merge($created, bp_install_run_sql_file($info['file'], $table_prefix));
            } catch (RuntimeException $e) {
                $err = json_decode($e->getMessage(), true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($err)) {
                    $err['version'] = 'V' . $v;
                    $err['file'] = basename($info['file']);
                    $install_error_data = $err;
                } else {
                    $install_error = $e->getMessage();
                }
                break; // 실패한 버전에서 멈춘다 - 이후 버전은 실행하지 않는다.
            }
        }
    }

    if (!$install_error && !$install_error_data) {
        $blog_img_dir = G5_DATA_PATH . '/blog_images';
        if (!is_dir($blog_img_dir)) {
            @mkdir($blog_img_dir, G5_DIR_PERMISSION);
            @chmod($blog_img_dir, G5_DIR_PERMISSION);
        }
    }
} catch (Throwable $e) {
    $install_error = $e->getMessage();
}

set_session('bp_install_result', array('created' => $created, 'error' => $install_error, 'error_data' => $install_error_data));
goto_url($settings_url);
