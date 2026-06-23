<?php
include_once('./_common.php');
include_once(G5_PATH . '/lib/showform_ai.lib.php');

header('Content-Type: application/json; charset=UTF-8');

auth_check_menu($auth, '900300', 'w');

$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$industry = isset($_POST['industry']) ? trim($_POST['industry']) : '';
$company_name = isset($_POST['company_name']) ? trim($_POST['company_name']) : '';
$area_name = isset($_POST['area_name']) ? trim($_POST['area_name']) : '';
$intro_text = isset($_POST['intro_text']) ? trim($_POST['intro_text']) : '';

if ($action === '') {
    echo json_encode(array('success' => false, 'error' => 'action 값이 필요합니다.'));
    exit;
}
if ($industry === '') {
    echo json_encode(array('success' => false, 'error' => 'industry 값이 필요합니다.'));
    exit;
}
if ($company_name === '') {
    echo json_encode(array('success' => false, 'error' => 'company_name 값이 필요합니다.'));
    exit;
}

$data = array(
    'main_copy' => generate_main_copy($industry, $company_name, $area_name, $intro_text),
    'sub_copy' => generate_sub_copy($industry, $company_name, $area_name, $intro_text),
    'problem_text' => generate_problem_text($industry, $company_name, $area_name, $intro_text),
    'strength_text' => generate_strength_text($industry, $company_name, $area_name, $intro_text),
    'faq_text' => generate_faq_text($industry, $company_name, $area_name, $intro_text),
    'cta_text' => generate_cta_text($industry, $company_name, $area_name, $intro_text),
);

$map = array(
    'main' => array('main_copy'),
    'problem' => array('problem_text'),
    'strength' => array('strength_text'),
    'faq' => array('faq_text'),
    'cta' => array('cta_text'),
    'all' => array('main_copy', 'problem_text', 'strength_text', 'faq_text', 'cta_text', 'sub_copy'),
);

if (!isset($map[$action])) {
    echo json_encode(array('success' => false, 'error' => '허용되지 않은 action입니다.'));
    exit;
}

$result = array();
foreach ($map[$action] as $key) {
    $result[$key] = $data[$key];
}

echo json_encode(array('success' => true, 'data' => $result));