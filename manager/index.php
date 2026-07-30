<?php
// _common.php(admin.lib.php)가 이미 로그인 여부와 관리자 권한 보유 여부를 검사해
// 통과하지 못하면 여기까지 도달하지 않는다. auth_check_menu()가 이 메뉴 코드에 대한
// 개별 read 권한을 검사하는 실질적인 게이트다 - super가 아니어도 au_menu/au_auth에
// 'r'만 있으면 통과해야 하므로 $is_admin의 진위 여부로 별도 차단하지 않는다.
include_once(__DIR__ . '/_common.php');
auth_check_menu($auth, '370000', 'r');

require_once __DIR__ . '/lib/dashboard.lib.php';
require_once __DIR__ . '/components/stat_card.php';
require_once __DIR__ . '/components/status_badge.php';
require_once __DIR__ . '/components/data_table.php';
require_once __DIR__ . '/components/empty_state.php';

$stats = mgr_dashboard_stats();
$recent_inquiries = mgr_recent_inquiries(5);
$recent_posts = mgr_recent_posts(5);
$recent_failures = mgr_recent_publish_failures(5);

$page_title = '대시보드';
include __DIR__ . '/layout/header.php';
?>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <?php
    echo mgr_stat_card('등록된 쇼폼', number_format($stats['showform_total']), array('icon' => 'showform', 'tone' => 'primary'));
    echo mgr_stat_card('공개 중인 쇼폼', number_format($stats['showform_public']), array('icon' => 'showform', 'tone' => 'success'));
    echo mgr_stat_card('등록된 랜딩페이지', number_format($stats['landing_total']), array('icon' => 'landing', 'tone' => 'primary'));
    echo mgr_stat_card('접수된 문의', number_format($stats['inquiry_total']), array('icon' => 'landing', 'tone' => 'warning'));
    echo mgr_stat_card('블로그 프로젝트', number_format($stats['blog_project_total']), array('icon' => 'blog', 'tone' => 'ai'));
    echo mgr_stat_card('발행 대기 글', number_format($stats['publish_pending']), array('icon' => 'blog', 'tone' => 'warning'));
    echo mgr_stat_card('발행 완료 글', number_format($stats['publish_done']), array('icon' => 'blog', 'tone' => 'success'));
    echo mgr_stat_card('최근 실패 작업', number_format($stats['publish_failed']), array('icon' => 'blog', 'tone' => 'danger'));
    ?>
</div>

<div class="mgr-card" style="padding:1.25rem;margin-bottom:1.5rem;">
    <h2 style="margin:0 0 .75rem;font-size:1rem;">빠른 작업</h2>
    <div style="display:flex;flex-wrap:wrap;gap:.5rem;">
        <a class="mgr-btn mgr-btn-primary" href="<?php echo SF_MANAGER_URL ?>/showform/form.php">쇼폼 등록</a>
        <a class="mgr-btn" href="<?php echo G5_ADMIN_URL ?>/landing/landing_form.php">랜딩페이지 등록</a>
        <a class="mgr-btn" href="<?php echo G5_ADMIN_URL ?>/blog/post_builder.php">포스팅 제작</a>
        <a class="mgr-btn" href="<?php echo G5_ADMIN_URL ?>/blog/advertiser_form.php">광고주 등록</a>
        <a class="mgr-btn" href="<?php echo G5_ADMIN_URL ?>/blog/ai_provider_list.php">AI API 설정</a>
        <a class="mgr-btn" href="<?php echo G5_ADMIN_URL ?>/landing/inquiry_list.php">문의 확인</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr;gap:1.5rem;">

    <section>
        <h2 style="font-size:1rem;margin:0 0 .75rem;">최근 접수된 상담</h2>
        <?php
        if (empty($recent_inquiries)) {
            echo '<div class="mgr-card">' . mgr_empty_state('접수된 문의가 없습니다') . '</div>';
        } else {
            $rows = array();
            foreach ($recent_inquiries as $row) {
                $status_tone = $row['status'] === 'completed' ? 'success' : ($row['status'] === 'contacted' ? 'primary' : 'warning');
                $rows[] = array(
                    'company' => htmlspecialchars($row['company_name'] ? $row['company_name'] : ('랜딩 #' . (int) $row['landing_id'])),
                    'name' => htmlspecialchars($row['name']),
                    'phone' => htmlspecialchars($row['phone']),
                    'created_at' => htmlspecialchars($row['created_at']),
                    'status' => mgr_status_badge($row['status'], $status_tone),
                    'action' => '<a href="' . G5_ADMIN_URL . '/landing/inquiry_list.php" class="mgr-btn">상세보기</a>',
                );
            }
            echo mgr_data_table(
                array(
                    array('key' => 'company', 'label' => '업체명'),
                    array('key' => 'name', 'label' => '이름'),
                    array('key' => 'phone', 'label' => '연락처'),
                    array('key' => 'created_at', 'label' => '접수일'),
                    array('key' => 'status', 'label' => '상태'),
                    array('key' => 'action', 'label' => ''),
                ),
                $rows
            );
        }
        ?>
    </section>

    <section>
        <h2 style="font-size:1rem;margin:0 0 .75rem;">최근 포스팅 작업</h2>
        <?php
        if (empty($recent_posts)) {
            echo '<div class="mgr-card">' . mgr_empty_state('진행 중인 포스팅 작업이 없습니다') . '</div>';
        } else {
            $status_labels = array('draft' => '초안', 'publish_pending' => '발행대기', 'publishing' => '발행중', 'published' => '발행완료', 'failed' => '실패');
            $rows = array();
            foreach ($recent_posts as $row) {
                $status = $row['publish_status'];
                $tone = $status === 'published' ? 'success' : ($status === 'failed' ? 'danger' : 'warning');
                $rows[] = array(
                    'title' => htmlspecialchars($row['title'] ? $row['title'] : $row['post_title']),
                    'advertiser' => htmlspecialchars($row['advertiser_name'] ? $row['advertiser_name'] : '-'),
                    'status' => mgr_status_badge(isset($status_labels[$status]) ? $status_labels[$status] : $status, $tone),
                    'scheduled_at' => htmlspecialchars($row['scheduled_at'] ? $row['scheduled_at'] : '-'),
                    'updated_at' => htmlspecialchars($row['updated_at']),
                    'action' => '<a href="' . G5_ADMIN_URL . '/blog/project_view.php?id=' . (int) $row['project_id'] . '" class="mgr-btn">상세보기</a>',
                );
            }
            echo mgr_data_table(
                array(
                    array('key' => 'title', 'label' => '제목'),
                    array('key' => 'advertiser', 'label' => '광고주'),
                    array('key' => 'status', 'label' => '상태'),
                    array('key' => 'scheduled_at', 'label' => '예약일'),
                    array('key' => 'updated_at', 'label' => '수정일'),
                    array('key' => 'action', 'label' => ''),
                ),
                $rows
            );
        }
        ?>
    </section>

    <section>
        <h2 style="font-size:1rem;margin:0 0 .75rem;">최근 발행 실패</h2>
        <?php
        if (empty($recent_failures)) {
            echo '<div class="mgr-card">' . mgr_empty_state('발행 실패 이력이 없습니다') . '</div>';
        } else {
            $rows = array();
            foreach ($recent_failures as $row) {
                $error_summary = $row['last_error'] ? mb_substr($row['last_error'], 0, 80) : '오류 메시지 없음';
                $rows[] = array(
                    'title' => htmlspecialchars($row['title'] ? $row['title'] : $row['post_title']),
                    'site' => htmlspecialchars($row['site_name'] ? $row['site_name'] : '-'),
                    'updated_at' => htmlspecialchars($row['updated_at']),
                    'error' => htmlspecialchars($error_summary),
                    'action' => '<a href="' . G5_ADMIN_URL . '/blog/project_view.php?id=' . (int) $row['project_id'] . '" class="mgr-btn">상세보기</a>',
                );
            }
            echo mgr_data_table(
                array(
                    array('key' => 'title', 'label' => '콘텐츠'),
                    array('key' => 'site', 'label' => '채널'),
                    array('key' => 'updated_at', 'label' => '실패 시각'),
                    array('key' => 'error', 'label' => '실패 원인'),
                    array('key' => 'action', 'label' => ''),
                ),
                $rows
            );
        }
        ?>
    </section>

</div>

<?php include __DIR__ . '/layout/footer.php'; ?>
