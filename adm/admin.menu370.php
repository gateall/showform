<?php
// 쇼폼 통합관리(/manager/) 권한 코드 등록 전용 파일.
// /manager/ 화면은 그누보드 admin.lib.php의 auth_check_menu()를 그대로 재사용하기
// 위해 이 파일로 코드만 등록한다 - 실제 화면은 /adm/이 아니라 /manager/에 있으므로
// href는 참고용이며, 네이티브 <nav id="gnb">에는 노출되지 않는다
// (adm/inc/admin_area_map.php에서 menu370을 'content'로 분류하고 수직메뉴 매핑에서는
// 제외했기 때문 - 기존 .sf-vswitch 상단 전환 메뉴에도 나타나지 않는다).
$menu['menu370'] = array(
    array('370000', '쇼폼 통합관리', G5_URL . '/manager/', 'manager_dashboard'),
    array('370100', '쇼폼 통합관리 - 운영 상태', G5_URL . '/manager/pages/system_status.php', 'manager_system_status'),
);
