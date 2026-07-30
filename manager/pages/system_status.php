<?php
include_once(__DIR__ . '/../_common.php');
auth_check_menu($auth, '370100', 'r');
require_once __DIR__ . '/../lib/dashboard.lib.php';
require_once __DIR__ . '/../components/status_badge.php';
require_once __DIR__ . '/../components/data_table.php';
require_once __DIR__ . '/../components/modal.php';

// 서비스 단위로 묶는다(테이블 여러 개가 한 서비스에 속할 수 있음) - 화면에는 서비스명만
// 1차로 보여주고, 실제 테이블명은 "시스템 상세 보기" 모달에서만 노출한다.
$services = array(
    array('label' => '쇼폼 관리', 'tables' => array(G5_TABLE_PREFIX . 'sf_portfolio'), 'install_url' => G5_ADMIN_URL . '/showform/install_form.php'),
    array('label' => '랜딩페이지', 'tables' => array(G5_TABLE_PREFIX . 'landing_pages'), 'install_url' => null),
    array('label' => '랜딩 문의', 'tables' => array(G5_TABLE_PREFIX . 'landing_inquiries'), 'install_url' => null),
    array('label' => '블로그 자동화', 'tables' => array(G5_TABLE_PREFIX . 'blog_content_projects'), 'install_url' => G5_ADMIN_URL . '/blog/install_form.php'),
    array('label' => '발행 시스템', 'tables' => array(G5_TABLE_PREFIX . 'blog_post_targets', G5_TABLE_PREFIX . 'blog_publish_jobs'), 'install_url' => G5_ADMIN_URL . '/blog/install_form.php'),
);

$service_rows = array();
$detail_rows = array();
foreach ($services as $svc) {
    $all_installed = true;
    foreach ($svc['tables'] as $t) {
        if (!mgr_table_exists($t)) {
            $all_installed = false;
        }
        $detail_rows[] = array(
            'label' => htmlspecialchars($svc['label']),
            'table' => '<code>' . htmlspecialchars($t) . '</code>',
            'status' => mgr_table_exists($t) ? mgr_status_badge('설치됨', 'success') : mgr_status_badge('미설치', 'muted'),
        );
    }
    $action = '';
    if (!$all_installed && $svc['install_url']) {
        $action = '<a href="' . $svc['install_url'] . '" class="mgr-btn">설치 확인</a>';
    }
    $service_rows[] = array(
        'service' => htmlspecialchars($svc['label']),
        'status' => $all_installed ? mgr_status_badge('정상', 'success') : mgr_status_badge('설정 필요', 'warning'),
        'action' => $action,
    );
}

$page_title = '운영 상태';
include __DIR__ . '/../layout/header.php';
?>

<div class="mgr-card" style="padding:1.25rem;margin-bottom:1rem;">
    <h2 style="margin:0 0 .75rem;font-size:1rem;">서버 환경</h2>
    <table class="mgr-table" style="width:100%;">
        <tbody>
            <tr><td style="width:10rem;color:var(--mgr-text-muted);">PHP 버전</td><td><?php echo htmlspecialchars(PHP_VERSION) ?></td></tr>
            <tr><td style="color:var(--mgr-text-muted);">DB 연결</td><td><?php echo mgr_table_exists(G5_TABLE_PREFIX . 'config') ? mgr_status_badge('정상', 'success') : mgr_status_badge('확인 필요', 'danger'); ?></td></tr>
        </tbody>
    </table>
</div>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.75rem;">
    <h2 style="margin:0;font-size:1rem;">서비스 상태</h2>
    <button type="button" class="mgr-btn" data-mgr-modal-open="sysDetailModal">시스템 상세 보기</button>
</div>

<?php
echo mgr_data_table(
    array(
        array('key' => 'service', 'label' => '서비스'),
        array('key' => 'status', 'label' => '상태'),
        array('key' => 'action', 'label' => '조치'),
    ),
    $service_rows,
    array('empty_title' => '확인할 서비스가 없습니다')
);

echo mgr_modal(
    'sysDetailModal',
    '시스템 상세 보기',
    mgr_data_table(
        array(
            array('key' => 'label', 'label' => '서비스'),
            array('key' => 'table', 'label' => '테이블'),
            array('key' => 'status', 'label' => '설치 상태'),
        ),
        $detail_rows
    )
);

include __DIR__ . '/../layout/footer.php';
