<?php
include_once('./_common.php');

// Admin auth check
if ($is_admin !== 'super') {
    die(json_encode(array('success' => false, 'message' => '권한이 없습니다.')));
}

$industry = isset($_POST['industry']) ? trim($_POST['industry']) : '';
if ($industry === '') {
    die(json_encode(array('success' => false, 'message' => '업종이 입력되지 않았습니다.')));
}

$preset_file = G5_PATH . '/config/showform_preset.php';
if (!is_file($preset_file)) {
    die(json_encode(array('success' => false, 'message' => '프리셋 파일을 찾을 수 없습니다.')));
}

include_once($preset_file);

if (isset($sf_presets[$industry])) {
    echo json_encode(array(
        'success' => true,
        'data' => $sf_presets[$industry]
    ), JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(array(
        'success' => false,
        'message' => '해당 업종의 프리셋이 없습니다.'
    ), JSON_UNESCAPED_UNICODE);
}
