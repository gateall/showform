<?php
include_once('./_common.php');

$sub_menu = '360500';
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '콘텐츠 프로젝트';

$projects_table = bp_table('content_projects');
$adv_table = bp_table('advertisers');
$sites_table = bp_table('sites');
$posts_table = bp_table('posts');
$targets_table = bp_table('post_targets');
$jobs_table = bp_table('publish_jobs');
$gen_logs_table = bp_table('content_generation_logs');

$status_label = array(
    'draft' => '초안', 'pending_approval' => '승인대기', 'approved' => '승인됨',
    'publish_pending' => '발행대기', 'publishing' => '발행중', 'published' => '발행완료', 'failed' => '발행실패',
);
$job_status_label = array(
    'pending' => '대기', 'claimed' => '할당됨', 'processing' => '처리중', 'published' => '완료', 'failed' => '실패',
);
$valid_status = array_keys($status_label);

$stx_title = isset($_GET['stx_title']) ? trim($_GET['stx_title']) : '';
$stx_advertiser = isset($_GET['stx_advertiser']) ? trim($_GET['stx_advertiser']) : '';
$stx_site = isset($_GET['stx_site']) ? trim($_GET['stx_site']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$ai_provider = isset($_GET['ai_provider']) ? trim($_GET['ai_provider']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

$where = array('p.deleted_at is null');
if ($stx_title !== '') {
    $where[] = "(po.title like '%" . sql_real_escape_string($stx_title) . "%' or p.topic like '%" . sql_real_escape_string($stx_title) . "%')";
}
if ($stx_advertiser !== '') {
    $where[] = "a.name like '%" . sql_real_escape_string($stx_advertiser) . "%'";
}
if ($stx_site !== '') {
    $where[] = "s.name like '%" . sql_real_escape_string($stx_site) . "%'";
}
if (in_array($status, $valid_status, true)) {
    $where[] = "p.status = '" . sql_real_escape_string($status) . "'";
}
if ($date_from !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_from)) {
    $where[] = "p.created_at >= '" . sql_real_escape_string($date_from) . " 00:00:00'";
}
if ($date_to !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_to)) {
    $where[] = "p.created_at <= '" . sql_real_escape_string($date_to) . " 23:59:59'";
}
$where_sql = ' where ' . implode(' and ', $where);

$having_sql = '';
if ($ai_provider !== '' && in_array($ai_provider, array('openai', 'template'), true)) {
    $having_sql = " having last_ai_provider = '" . sql_real_escape_string($ai_provider) . "' ";
}

$result = sql_query(" select p.*, a.name as advertiser_name, s.name as site_name, po.id as post_id, po.title as post_title,
                      (select count(*) from {$targets_table} pt where pt.post_id = po.id) as target_count,
                      (select group_concat(distinct pj.status) from {$jobs_table} pj
                        join {$targets_table} pt2 on pt2.id = pj.post_target_id where pt2.post_id = po.id) as job_statuses,
                      (select g.provider from {$gen_logs_table} g where g.project_id = p.id order by g.id desc limit 1) as last_ai_provider
                      from {$projects_table} p
                      left join {$adv_table} a on a.id = p.advertiser_id
                      left join {$sites_table} s on s.id = p.primary_site_id
                      left join {$posts_table} po on po.project_id = p.id
                      {$where_sql}
                      {$having_sql}
                      order by p.id desc limit 100 ");

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>광고주·계약에 종속된 블로그 콘텐츠 프로젝트를 관리합니다. 승인(approved) 이전에는 발행할 수 없습니다.</p>
</div>

<div class="bp-list-toolbar" style="display: flex; gap: 10px;">
    <a href="./project_form.php" class="bp-btn-primary-lg" style="background:#4a5568;">+ (구버전) 포스팅 프로젝트 등록</a>
    <button type="button" class="bp-btn-primary-lg" style="background:#3182ce; color:#fff; border:none; cursor:pointer;" onclick="createNewPost()">+ 🚀 새 프론트 스튜디오에서 포스팅 작성</button>
</div>
<script>
function createNewPost() {
    if(confirm('새 포스팅 작성을 시작하시겠습니까? (초안이 생성되고 넓은 작성 화면으로 이동합니다)')) {
        fetch('<?php echo G5_URL; ?>/blog-studio/ajax/create_draft.php', { method: 'POST' })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                window.location.href = '<?php echo G5_URL; ?>/blog-studio/write.php?post_id=' + data.post_id;
            } else {
                alert('초안 생성 실패: ' + data.message);
            }
        });
    }
}
</script>

<details class="bp-filter-wrap" <?php echo ($stx_title || $stx_advertiser || $stx_site || $status || $ai_provider || $date_from || $date_to) ? 'open' : ''; ?>>
    <summary class="bp-filter-summary">검색·필터</summary>
    <form method="get" class="bp-filter-form">
        <div class="bp-filter-row">
            <label>제목 검색<input type="text" name="stx_title" value="<?php echo get_text($stx_title); ?>" class="frm_input"></label>
            <label>광고주<input type="text" name="stx_advertiser" value="<?php echo get_text($stx_advertiser); ?>" class="frm_input"></label>
            <label>사이트<input type="text" name="stx_site" value="<?php echo get_text($stx_site); ?>" class="frm_input"></label>
        </div>
        <div class="bp-filter-row">
            <label>상태
                <select name="status">
                    <option value="">전체</option>
                    <?php foreach ($status_label as $code => $label) { ?>
                        <option value="<?php echo $code; ?>" <?php echo $status === $code ? 'selected' : ''; ?>><?php echo get_text($label); ?></option>
                    <?php } ?>
                </select>
            </label>
            <label>AI 제공자
                <select name="ai_provider">
                    <option value="">전체</option>
                    <option value="openai" <?php echo $ai_provider === 'openai' ? 'selected' : ''; ?>>OpenAI</option>
                    <option value="template" <?php echo $ai_provider === 'template' ? 'selected' : ''; ?>>템플릿</option>
                </select>
            </label>
        </div>
        <div class="bp-filter-row">
            <label>생성일(부터)<input type="date" name="date_from" value="<?php echo get_text($date_from); ?>" class="frm_input"></label>
            <label>생성일(까지)<input type="date" name="date_to" value="<?php echo get_text($date_to); ?>" class="frm_input"></label>
        </div>
        <div class="bp-filter-actions">
            <button type="submit" class="btn btn_submit btn">검색</button>
            <a href="./project_list.php" class="btn btn_02">초기화</a>
        </div>
    </form>
</details>

<?php
$list = array();
if ($result && sql_num_rows($result) > 0) {
    while ($row = sql_fetch_array($result)) {
        $st = $row['status'];
        $st_label = isset($status_label[$st]) ? $status_label[$st] : get_text($st);
        $job_summary = '-';
        if (!empty($row['job_statuses'])) {
            $parts = array();
            foreach (explode(',', $row['job_statuses']) as $js) {
                $parts[] = isset($job_status_label[$js]) ? $job_status_label[$js] : get_text($js);
            }
            $job_summary = implode(', ', $parts);
        }
        $ai_label = $row['last_ai_provider'] === 'openai' ? 'OpenAI' : ($row['last_ai_provider'] === 'template' ? '템플릿' : '-');
        
        $row['st_label'] = $st_label;
        $row['job_summary'] = $job_summary;
        $row['ai_label'] = $ai_label;
        $list[] = $row;
    }
}
?>

<!-- 모바일: 카드 뷰 -->
<div class="bp-project-cards" id="mobile_card_view" style="margin-top:15px;">
    <?php if (count($list) > 0) { ?>
        <?php foreach ($list as $row) { ?>
        <div class="bp-project-card">
            <div class="bp-project-card-head">
                <a href="./project_view.php?id=<?php echo (int) $row['id']; ?>" class="bp-project-title">
                    <?php echo get_text($row['post_title'] ? $row['post_title'] : $row['topic']); ?>
                </a>
                <span class="bp-status-badge bp-status-badge-<?php echo get_text($row['status']); ?>"><?php echo $row['st_label']; ?></span>
            </div>
            <div class="bp-project-meta">
                <span>번호: <?php echo (int) $row['id']; ?></span>
                <span>광고주: <?php echo get_text($row['advertiser_name']); ?></span>
                <span>사이트: <?php echo $row['site_name'] ? get_text($row['site_name']) : '-'; ?></span>
                <span>AI 제공자: <?php echo $row['ai_label']; ?></span>
                <span>승인자: <?php echo $row['reviewed_by'] ? get_text($row['reviewed_by']) : '-'; ?></span>
                <span>승인일: <?php echo $row['approved_at'] ? get_text($row['approved_at']) : '-'; ?></span>
                <span>발행 대상: <?php echo (int) $row['target_count']; ?>건</span>
                <span>배포 작업: <?php echo $row['job_summary']; ?></span>
                <span>생성일: <?php echo get_text($row['created_at']); ?></span>
                <span>수정일: <?php echo $row['updated_at'] ? get_text($row['updated_at']) : '-'; ?></span>
            </div>
            <div class="bp-project-actions">
                <a href="./project_view.php?id=<?php echo (int) $row['id']; ?>" class="btn btn_submit btn">열기</a>
            </div>
        </div>
        <?php } ?>
    <?php } else { ?>
        <div class="bp-empty">조건에 맞는 콘텐츠 프로젝트가 없습니다.</div>
    <?php } ?>
</div>

<!-- PC: 테이블 뷰 -->
<div class="tbl_head01 tbl_wrap" id="pc_table_view" style="margin-top:15px;">
    <table>
        <caption>콘텐츠 프로젝트 목록</caption>
        <thead>
            <tr>
                <th scope="col" style="width:60px;">번호</th>
                <th scope="col">프로젝트 주제 / 제목</th>
                <th scope="col" style="width:120px;">광고주/사이트</th>
                <th scope="col" style="width:100px;">상태</th>
                <th scope="col" style="width:120px;">AI 제공자</th>
                <th scope="col" style="width:180px;">발행 정보</th>
                <th scope="col" style="width:150px;">등록일시</th>
                <th scope="col" style="width:80px;">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($list) > 0) { ?>
                <?php foreach ($list as $row) { ?>
                <tr>
                    <td class="td_num"><?php echo (int) $row['id']; ?></td>
                    <td>
                        <a href="./project_view.php?id=<?php echo (int) $row['id']; ?>">
                            <strong><?php echo get_text($row['post_title'] ? $row['post_title'] : $row['topic']); ?></strong>
                        </a>
                    </td>
                    <td class="td_center">
                        <?php echo get_text($row['advertiser_name']); ?><br>
                        <span style="color:#888; font-size:11px;"><?php echo $row['site_name'] ? get_text($row['site_name']) : '-'; ?></span>
                    </td>
                    <td class="td_center">
                        <span class="bp-status-badge bp-status-badge-<?php echo get_text($row['status']); ?>"><?php echo $row['st_label']; ?></span>
                    </td>
                    <td class="td_center"><?php echo $row['ai_label']; ?></td>
                    <td class="td_center">
                        대상: <?php echo (int) $row['target_count']; ?>건<br>
                        <span style="color:#888; font-size:11px;"><?php echo $row['job_summary']; ?></span>
                    </td>
                    <td class="td_center"><?php echo substr($row['created_at'], 0, 16); ?></td>
                    <td class="td_center">
                        <a href="./project_view.php?id=<?php echo (int) $row['id']; ?>" class="btn btn_02">열기</a>
                    </td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="8" class="empty_table">조건에 맞는 콘텐츠 프로젝트가 없습니다.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
