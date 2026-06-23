<?php
if (!defined('G5_PATH')) {
    return;
}

function sf_ai_normalize_industry($industry)
{
    $industry = trim((string)$industry);
    return $industry === '' ? '서비스' : $industry;
}

function sf_ai_is_hospital_industry($industry)
{
    $list = array('병원', '치과', '한의원', '요양병원', '건강검진');
    foreach ($list as $item) {
        if (mb_strpos($industry, $item) !== false) return true;
    }
    return false;
}

function sf_ai_is_local_industry($industry)
{
    $list = array('식당', '카페', '미용실', '펜션', '카센터');
    foreach ($list as $item) {
        if (mb_strpos($industry, $item) !== false) return true;
    }
    return false;
}

function sf_ai_is_service_industry($industry)
{
    return !sf_ai_is_hospital_industry($industry) && !sf_ai_is_local_industry($industry);
}

function sf_ai_area_prefix($area_name)
{
    $area_name = trim((string)$area_name);
    return $area_name !== '' ? $area_name . ' ' : '';
}

function generate_main_copy($industry, $company_name, $area_name, $intro_text)
{
    $industry = sf_ai_normalize_industry($industry);
    $prefix = sf_ai_area_prefix($area_name);

    if (sf_ai_is_hospital_industry($industry)) {
        return $prefix . $industry . ' 전문, 환자 중심의 정직한 진료';
    }
    if (sf_ai_is_local_industry($industry)) {
        return $prefix . $industry . ' 추천, 믿고 찾는 지역상권 파트너';
    }

    return $prefix . $industry . ' 전문, 빠르고 정확한 해결';
}

function generate_sub_copy($industry, $company_name, $area_name, $intro_text)
{
    $industry = sf_ai_normalize_industry($industry);
    if (sf_ai_is_hospital_industry($industry)) {
        return '안심할 수 있는 상담부터 예약, 진료 안내까지 한 번에 안내합니다.';
    }
    if (sf_ai_is_local_industry($industry)) {
        return '지역 고객이 바로 문의할 수 있도록 방문, 예약, 상담 동선을 단순화합니다.';
    }

    return '정확한 진단과 빠른 대응으로 문의 전환을 높이는 랜딩 구조를 제안합니다.';
}

function generate_problem_text($industry, $company_name, $area_name, $intro_text)
{
    $industry = sf_ai_normalize_industry($industry);
    $area = $area_name ? $area_name . '에서 ' : '';

    if (sf_ai_is_hospital_industry($industry)) {
        return $area . '믿고 진료받을 병원을 찾고 계신가요?\n예약이 복잡해 답답하셨나요?\n진료 안내가 한눈에 보이지 않아 불편하셨나요?';
    }
    if (sf_ai_is_local_industry($industry)) {
        return $area . '가까운 곳에서 바로 방문할 수 있는 업체를 찾으시나요?\n메뉴와 서비스가 잘 정리된 곳이 필요하신가요?\n전화 문의가 쉽게 연결되길 원하시나요?';
    }

    return $area . $industry . ' 문제를 빠르게 해결해야 하나요?\n원인을 찾기 어려워 계속 불안하셨나요?\n상담부터 작업까지 한 번에 처리되길 원하시나요?';
}

function generate_strength_text($industry, $company_name, $area_name, $intro_text)
{
    $industry = sf_ai_normalize_industry($industry);
    if (sf_ai_is_hospital_industry($industry)) {
        return '1. 환자 중심 상담 체계\n2. 진료과목별 안내 구조\n3. 예약 전환을 높이는 CTA\n4. 신뢰를 주는 후기와 정보 정리';
    }
    if (sf_ai_is_local_industry($industry)) {
        return '1. 지역 방문에 최적화된 정보 구성\n2. 메뉴/서비스를 한눈에 안내\n3. 전화와 카카오 문의 동선 강화\n4. 모바일 중심 전환 설계';
    }

    return '1. 빠른 상담 응대\n2. 현장 대응 중심 안내\n3. 문의를 높이는 핵심 강점 정리\n4. 모바일 최적화 전환 구조';
}

function generate_faq_text($industry, $company_name, $area_name, $intro_text)
{
    $industry = sf_ai_normalize_industry($industry);
    if (sf_ai_is_hospital_industry($industry)) {
        return 'Q. 예약은 어떻게 하나요?\nA. 전화 또는 카카오 상담으로 빠르게 예약 가능합니다.\n\nQ. 진료 시간은 어떻게 확인하나요?\nA. 문의 시 상세 안내드립니다.\n\nQ. 초진 상담도 가능한가요?\nA. 네, 상황에 맞는 예약 안내를 드립니다.';
    }
    if (sf_ai_is_local_industry($industry)) {
        return 'Q. 방문 예약이 가능한가요?\nA. 네, 시간에 맞춰 방문 예약을 도와드립니다.\n\nQ. 주차는 가능한가요?\nA. 업체별로 상이하니 문의 시 안내드립니다.\n\nQ. 단체/가족 방문도 가능한가요?\nA. 상황에 맞게 안내드립니다.';
    }

    return 'Q. 상담은 무료인가요?\nA. 네, 기본 상담은 무료로 안내드립니다.\n\nQ. 긴급 요청도 가능한가요?\nA. 가능 여부를 빠르게 확인해드립니다.\n\nQ. 작업 시간은 얼마나 걸리나요?\nA. 현장 상황에 따라 안내드립니다.';
}

function generate_cta_text($industry, $company_name, $area_name, $intro_text)
{
    $industry = sf_ai_normalize_industry($industry);
    if (sf_ai_is_hospital_industry($industry)) {
        return '빠른 예약 상담하기';
    }
    if (sf_ai_is_local_industry($industry)) {
        return '무료 상담받기';
    }

    return '지금 무료 상담받기';
}