<?php
include_once('./_common.php');

$table = G5_TABLE_PREFIX . 'landing_page';
$sql = "
    INSERT INTO {$table} SET 
        id = 99,
        subject = 'Hun Golf 프리미엄 골프 투어',
        industry = '골프투어',
        use_yn = 'Y',
        hero_title = '단 하나의 완벽한 골프 휴양, Hun Golf 프리미엄 필리핀 투어',
        hero_text = '필리핀 풀빌라/골프 패키지 상품! 명문 골프장 티오프 타임 전격 확보 및 단독 풀빌라 전용 숙박을 제공합니다.',
        cta_text = '무료 상담 신청하기',
        cta_link = '#contact',
        problem_1 = '최고급 단독 풀빌라가 필요한가요?',
        problem_2 = '명문 골프장 티오프 타임 확보가 어려우신가요?',
        problem_3 = '단독 차량 및 전담 가이드 케어가 필요하신가요?',
        service_1 = '최고급 단독 풀빌라 전용 숙박',
        service_2 = '명문 골프장 티오프 타임 전격 확보',
        service_3 = '단독 차량 및 전담 가이드 케어',
        service_4 = 'VIP 맞춤형 밀착 서비스 제공',
        phone = '010-1234-5678',
        privacy_text = '개인정보 수집 및 이용에 동의합니다.',
        created_at = '".G5_TIME_YMDHIS."',
        updated_at = '".G5_TIME_YMDHIS."'
    ON DUPLICATE KEY UPDATE updated_at = '".G5_TIME_YMDHIS."'
";
@sql_query($sql);
echo "Seed Data Inserted";
?>
