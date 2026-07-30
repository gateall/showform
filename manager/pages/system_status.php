<?php
include_once(__DIR__ . '/../_common.php');
auth_check_menu($auth, '370100', 'r');
require_once __DIR__ . '/../lib/dashboard.lib.php';
require_once __DIR__ . '/../components/status_badge.php';
require_once __DIR__ . '/../components/data_table.php';

$page_title = '운영 상태';
include __DIR__ . '/../layout/header.php';

$tables_to_check = array(
    '쇼폼(제작 사례)' => G5_TABLE_PREFIX . 'sf_portfolio',
    '랜딩페이지' => G5_TABLE_PREFIX . 'landing_pages',
    '랜딩 문의' => G5_TABLE_PREFIX . 'landing_inquiries',
    '블로그 콘텐츠 프로젝트' => G5_TABLE_PREFIX . 'blog_content_projects',
    '블로그 발행 대상' => G5_TABLE_PREFIX . 'blog_post_targets',
    '블로그 발행 작업' => G5_TABLE_PREFIX . 'blog_publish_jobs',
);

$rows = array();
foreach ($tables_to_check as $label => $table) {
    $installed = mgr_table_exists($table);
    $rows[] = array(
        'label' => htmlspecialchars($label),
        'table' => '<code>' . htmlspecialchars($table) . '</code>',
        'status' => $installed ? mgr_status_badge('설치됨', 'success') : mgr_status_badge('미설치', 'muted'),
    );
}
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

<?php
echo mgr_data_table(
    array(
        array('key' => 'label', 'label' => '기능'),
        array('key' => 'table', 'label' => '테이블'),
        array('key' => 'status', 'label' => '설치 상태'),
    ),
    $rows,
    array('empty_title' => '확인할 테이블이 없습니다')
);
?>

<?php include __DIR__ . '/../layout/footer.php'; ?>
