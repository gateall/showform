<?php
// 처리 전용 파일 — 화면은 install_form.php가 담당한다(adm/blog/install.php와 동일 원칙:
// GET으로 직접 접근하면 설치 화면으로 안내만 하고 아무 것도 실행하지 않는다).
$sub_menu = '700900';
include_once('./_common.php');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$admin_form_url = SF_MANAGER_URL . '/showform/install_form.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    alert('설치 화면에서 진행해 주세요.', $admin_form_url);
}

check_admin_token();

$sql_file = __DIR__ . '/sql/showform_v1.sql';
$table_prefix = G5_TABLE_PREFIX;
$install_error = '';

try {
    if (!is_file($sql_file)) {
        throw new RuntimeException('SQL 파일을 찾을 수 없습니다: ' . $sql_file);
    }
    $sql_content = file_get_contents($sql_file);
    $sql_content = str_replace('{prefix}', $table_prefix, $sql_content);
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
    foreach ($statements as $stmt) {
        if ($stmt === '') {
            continue;
        }
        sql_query($stmt, false);
    }
} catch (Throwable $e) {
    $install_error = $e->getMessage();
}

set_session('sf_install_result', array('error' => $install_error));
goto_url($admin_form_url);
