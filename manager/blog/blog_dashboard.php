<?php
$sub_menu = '360000';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

require_once __DIR__ . '/../lib/dashboard.lib.php';
require_once __DIR__ . '/../components/stat_card.php';
require_once __DIR__ . '/../components/status_badge.php';
require_once __DIR__ . '/../components/data_table.php';
require_once __DIR__ . '/../components/empty_state.php';

$adv_table = G5_TABLE_PREFIX . 'blog_advertisers';
$sites_table = G5_TABLE_PREFIX . 'blog_sites';
$projects_table = G5_TABLE_PREFIX . 'blog_content_projects';
$targets_table = G5_TABLE_PREFIX . 'blog_post_targets';
$jobs_table = G5_TABLE_PREFIX . 'blog_publish_jobs';

// ---- 요약 카드 4개 ----
$advertiser_count = mgr_count($adv_table, "status = 'Y'");
$site_count = mgr_count($sites_table, "status = 'Y'");
$publish_pending_count = mgr_count($jobs_table, "status = 'pending'");
$publish_failed_count = mgr_count($targets_table, "publish_status = 'failed'");

// ---- 오늘 할 일 ----
// 승인 대기: blog_content_projects.status='pending_approval' (project_list.php와 동일 라벨 체계)
$pending_approval_count = mgr_count($projects_table, "status = 'pending_approval'");
// 오늘 발행 예정: blog_publish_jobs 중 오늘 예정이고 아직 대기중인 건
$today_scheduled_count = mgr_count($jobs_table, "status = 'pending' AND DATE(scheduled_at) = CURDATE()");
// 재시도 필요: 일시 오류로 다음 재시도가 예약된 작업(영구실패는 next_retry_at이 NULL이라 제외됨)
$retry_needed_count = mgr_count($jobs_table, "status = 'failed' AND next_retry_at IS NOT NULL");
// "연결 오류"는 실제로 저장되는 컬럼이 없어(연결 테스트 결과 미영속화) 대신 최근 발행 실패가
// 발생한 사이트 수(서로 다른 site_id)로 대체한다 - 없는 지표를 그대로 만들지 않기 위함.
$failed_site_count = 0;
if (mgr_table_exists($targets_table)) {
    $row = sql_fetch("SELECT COUNT(DISTINCT site_id) AS cnt FROM {$targets_table} WHERE publish_status = 'failed'");
    $failed_site_count = $row ? (int) $row['cnt'] : 0;
}

$todo_items = array();
if ($today_scheduled_count > 0) {
    $todo_items[] = array('label' => '오늘 발행 예정', 'count' => $today_scheduled_count, 'tone' => 'primary', 'url' => SF_MANAGER_URL . '/blog/publish_job_list.php');
}
if ($pending_approval_count > 0) {
    $todo_items[] = array('label' => '승인 대기 콘텐츠', 'count' => $pending_approval_count, 'tone' => 'warning', 'url' => SF_MANAGER_URL . '/blog/project_list.php?status=pending_approval');
}
if ($failed_site_count > 0) {
    $todo_items[] = array('label' => '최근 발행 실패가 있는 사이트', 'count' => $failed_site_count, 'tone' => 'danger', 'url' => SF_MANAGER_URL . '/blog/site_list.php');
}
if ($retry_needed_count > 0) {
    $todo_items[] = array('label' => '재시도 대기 중인 작업', 'count' => $retry_needed_count, 'tone' => 'warning', 'url' => SF_MANAGER_URL . '/blog/publish_job_list.php');
}

// ---- 최근 포스팅 프로젝트 5건 ----
$status_label = array(
    'draft' => '초안', 'pending_approval' => '승인대기', 'approved' => '승인됨',
    'publish_pending' => '발행대기', 'publishing' => '발행중', 'published' => '발행완료', 'failed' => '발행실패',
);
$recent_projects = array();
if (mgr_table_exists($projects_table)) {
    $result = sql_query("SELECT p.id, p.topic, p.status, p.updated_at, a.name AS advertiser_name
        FROM {$projects_table} p
        LEFT JOIN {$adv_table} a ON a.id = p.advertiser_id
        ORDER BY p.updated_at DESC LIMIT 5");
    while ($row = sql_fetch_array($result)) {
        $recent_projects[] = $row;
    }
}

// ---- 발행 상태 요약 ----
$publish_done_count = mgr_count($targets_table, "publish_status = 'published'");

$page_title = '블로그 자동화';
include_once(__DIR__ . '/../layout/header.php');
?>

<div class="mgr-card" style="padding:1.25rem;margin-bottom:1.5rem;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:1rem;">
        <div>
            <h2 style="margin:0 0 .25rem;font-size:1.125rem;">블로그 자동화</h2>
            <p style="margin:0;color:var(--mgr-text-muted);font-size:.875rem;">광고주·콘텐츠·발행 상태를 한곳에서 관리합니다.</p>
        </div>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
            <button type="button" class="mgr-btn mgr-btn-primary" onclick="mgrCreateNewPost()">+ 새 포스팅 작성</button>
            <a class="mgr-btn" href="<?php echo SF_MANAGER_URL ?>/blog/project_form.php">프로젝트 등록</a>
            <a class="mgr-btn" href="<?php echo SF_MANAGER_URL ?>/blog/publish_job_list.php">발행 작업 보기</a>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:1rem;margin-bottom:1.5rem;">
    <?php
    echo mgr_stat_card('광고주', number_format($advertiser_count), array('icon' => 'blog', 'tone' => 'primary'));
    echo mgr_stat_card('운영 사이트', number_format($site_count), array('icon' => 'blog', 'tone' => 'primary'));
    echo mgr_stat_card('발행 예정', number_format($publish_pending_count), array('icon' => 'blog', 'tone' => 'warning'));
    echo mgr_stat_card('실패·확인 필요', number_format($publish_failed_count), array('icon' => 'blog', 'tone' => 'danger'));
    ?>
</div>

<?php if (!empty($todo_items)): ?>
<div class="mgr-card" style="padding:1.25rem;margin-bottom:1.5rem;">
    <h2 style="margin:0 0 .75rem;font-size:1rem;">오늘 할 일</h2>
    <div style="display:flex;flex-direction:column;gap:.5rem;">
        <?php foreach ($todo_items as $item): ?>
        <a href="<?php echo $item['url'] ?>" style="display:flex;justify-content:space-between;align-items:center;padding:.625rem .75rem;border-radius:10px;background:var(--mgr-surface);text-decoration:none;color:var(--mgr-text);">
            <span><?php echo htmlspecialchars($item['label']) ?></span>
            <?php echo mgr_status_badge($item['count'] . '건', $item['tone']) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php else: ?>
<div class="mgr-card" style="padding:1.25rem;margin-bottom:1.5rem;">
    <h2 style="margin:0 0 .75rem;font-size:1rem;">오늘 할 일</h2>
    <p style="margin:0;color:var(--mgr-text-muted);font-size:.875rem;">처리할 항목이 없습니다.</p>
</div>
<?php endif; ?>

<div style="margin-bottom:1.5rem;">
    <h2 style="font-size:1rem;margin:0 0 .75rem;">최근 포스팅 프로젝트</h2>
    <?php
    if (empty($recent_projects)) {
        echo '<div class="mgr-card">' . mgr_empty_state('등록된 프로젝트가 없습니다') . '</div>';
    } else {
        $rows = array();
        foreach ($recent_projects as $row) {
            $status = $row['status'];
            $tone = $status === 'published' ? 'success' : ($status === 'failed' ? 'danger' : ($status === 'pending_approval' ? 'warning' : 'muted'));
            $rows[] = array(
                'title' => '<a href="' . SF_MANAGER_URL . '/blog/project_view.php?id=' . (int) $row['id'] . '">' . htmlspecialchars($row['topic']) . '</a>',
                'advertiser' => htmlspecialchars($row['advertiser_name'] ? $row['advertiser_name'] : '-'),
                'status' => mgr_status_badge(isset($status_label[$status]) ? $status_label[$status] : $status, $tone),
                'updated_at' => htmlspecialchars($row['updated_at']),
            );
        }
        echo mgr_data_table(
            array(
                array('key' => 'title', 'label' => '제목'),
                array('key' => 'advertiser', 'label' => '광고주'),
                array('key' => 'status', 'label' => '상태'),
                array('key' => 'updated_at', 'label' => '수정일'),
            ),
            $rows
        );
        echo '<div style="text-align:right;margin-top:.5rem;"><a href="' . SF_MANAGER_URL . '/blog/project_list.php" class="mgr-btn">전체 보기</a></div>';
    }
    ?>
</div>

<div class="mgr-card" style="padding:1.25rem;">
    <h2 style="margin:0 0 .75rem;font-size:1rem;">발행 상태</h2>
    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;">
        <div><span style="color:var(--mgr-text-muted);font-size:.8125rem;">발행 성공</span><br><strong style="font-size:1.25rem;"><?php echo number_format($publish_done_count) ?></strong></div>
        <div><span style="color:var(--mgr-text-muted);font-size:.8125rem;">예약 대기</span><br><strong style="font-size:1.25rem;"><?php echo number_format($publish_pending_count) ?></strong></div>
        <div><span style="color:var(--mgr-text-muted);font-size:.8125rem;">실패</span><br><strong style="font-size:1.25rem;color:var(--mgr-danger);"><?php echo number_format($publish_failed_count) ?></strong></div>
    </div>
</div>

<script>
function mgrCreateNewPost() {
    if (!confirm('새 포스팅 작성을 시작하시겠습니까? (초안이 생성되고 작성 화면으로 이동합니다)')) {
        return;
    }
    fetch('<?php echo G5_URL; ?>/blog-studio/ajax/create_draft.php', { method: 'POST' })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            if (data.success) {
                window.location.href = '<?php echo G5_URL; ?>/blog-studio/write.php?post_id=' + data.post_id;
            } else {
                alert('초안 생성 실패: ' + data.message);
            }
        });
}
</script>

<?php include_once(__DIR__ . '/../layout/footer.php'); ?>
