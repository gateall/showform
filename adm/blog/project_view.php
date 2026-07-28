<?php
include_once('./_common.php');

$sub_menu = '360400';
auth_check_menu($auth, $sub_menu, 'r');

$projects_table = bp_table('content_projects');
$adv_table = bp_table('advertisers');
$posts_table = bp_table('posts');
$targets_table = bp_table('post_targets');
$sites_table = bp_table('sites');
$logs_table = bp_table('content_activity_logs');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$project = sql_fetch(" select p.*, a.name as advertiser_name, a.phone, a.consult_url
                       from {$projects_table} p
                       left join {$adv_table} a on a.id = p.advertiser_id
                       where p.id = '{$id}' ");
if (!$project) {
    alert('콘텐츠 프로젝트를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/project_list.php');
}

$g5['title'] = '콘텐츠 프로젝트 — ' . $project['topic'];

$post = sql_fetch(" select * from {$posts_table} where project_id = '{$id}' order by id desc limit 1 ");
$targets = sql_query(" select t.*, s.name as site_name, s.platform
                       from {$targets_table} t left join {$sites_table} s on s.id = t.site_id
                       where t.post_id = '" . (int)($post ? $post['id'] : 0) . "'
                       order by t.id asc ");

$logs = sql_query(" select * from {$logs_table} where project_id = '{$id}' order by id desc limit 20 ");

$status_label = array(
    'draft' => '초안', 'generated' => 'AI생성완료', 'review_required' => '검수대기',
    'approved' => '승인됨', 'publish_pending' => '발행대기', 'publishing' => '발행중',
    'published' => '발행완료', 'failed' => '발행실패',
);

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>상태: <strong><?php echo isset($status_label[$project['status']]) ? $status_label[$project['status']] : get_text($project['status']); ?></strong>
       (<?php echo get_text($project['status']); ?>) — 광고주: <?php echo get_text($project['advertiser_name']); ?></p>
    <p><a href="./keyword_list.php?project_id=<?php echo (int)$id; ?>" class="btn btn_02">키워드 관리</a>
       <a href="./project_list.php" class="btn btn_02">목록으로</a></p>
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>프로젝트 정보</caption>
        <tbody>
            <tr><th scope="row">주제</th><td><?php echo get_text($project['topic']); ?></td></tr>
            <tr><th scope="row">대표 키워드</th><td><?php echo get_text($project['primary_keyword']); ?></td></tr>
            <tr><th scope="row">글 목적 / 길이</th><td><?php echo get_text($project['content_type']); ?> / <?php echo get_text($project['content_length']); ?></td></tr>
            <tr><th scope="row">제목</th><td><?php echo $post && $post['title'] ? get_text($post['title']) : '<span style="color:#999;">아직 생성되지 않음</span>'; ?></td></tr>
            <tr><th scope="row">본문</th><td><pre style="white-space:pre-wrap;font-family:inherit;"><?php echo $post && $post['body'] ? get_text($post['body']) : '아직 생성되지 않음'; ?></pre></td></tr>
        </tbody>
    </table>
</div>

<div class="btn_confirm01 btn_confirm" style="margin-top:15px;">
    <form method="post" action="./project_action.php" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <input type="hidden" name="mode" value="generate">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <button type="submit" class="btn_submit btn" <?php echo $project['status'] !== 'draft' ? 'disabled' : ''; ?>>① AI 제목·본문 생성</button>
    </form>
    <form method="post" action="./project_action.php" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <input type="hidden" name="mode" value="request_review">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <button type="submit" class="btn btn_02" <?php echo $project['status'] !== 'generated' ? 'disabled' : ''; ?>>② 검수 요청</button>
    </form>
    <form method="post" action="./project_action.php" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <input type="hidden" name="mode" value="approve">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <button type="submit" class="btn btn_01" <?php echo $project['status'] !== 'review_required' ? 'disabled' : ''; ?>>③ 관리자 승인</button>
    </form>
    <form method="post" action="./project_action.php" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <input type="hidden" name="mode" value="reject">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <button type="submit" class="btn btn_02" <?php echo $project['status'] !== 'review_required' ? 'disabled' : ''; ?> onclick="return confirm('초안으로 반려하시겠습니까?');">반려(재작성)</button>
    </form>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>사이트별 발행 대상 (post_targets)</caption>
        <thead><tr><th scope="col">사이트</th><th scope="col">플랫폼</th><th scope="col">발행 상태</th><th scope="col">외부 URL</th><th scope="col">재시도</th><th scope="col">발행</th></tr></thead>
        <tbody>
            <?php if ($targets && sql_num_rows($targets) > 0) { ?>
                <?php while ($t = sql_fetch_array($targets)) { ?>
                    <tr>
                        <td><?php echo get_text($t['site_name']); ?></td>
                        <td><?php echo get_text($t['platform']); ?></td>
                        <td><?php echo get_text($t['publish_status']); ?><?php echo $t['last_error'] ? '<div style="color:#c00;font-size:12px;">' . get_text($t['last_error']) . '</div>' : ''; ?></td>
                        <td><?php echo $t['published_url'] ? '<a href="' . get_text($t['published_url']) . '" target="_blank">' . get_text($t['published_url']) . '</a>' : '-'; ?></td>
                        <td><?php echo (int)$t['retry_count']; ?></td>
                        <td>
                            <form method="post" action="./project_action.php">
                                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                                <input type="hidden" name="mode" value="publish">
                                <input type="hidden" name="post_target_id" value="<?php echo (int)$t['id']; ?>">
                                <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
                                <button type="submit" class="btn btn_submit btn" <?php echo !in_array($project['status'], array('approved','publish_pending','publishing','failed'), true) ? 'disabled' : ''; ?>>발행 실행</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="6" class="empty_table">발행 대상 사이트가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>활동 로그</caption>
        <thead><tr><th scope="col">시각</th><th scope="col">동작</th><th scope="col">담당자</th><th scope="col">상세</th></tr></thead>
        <tbody>
            <?php if ($logs && sql_num_rows($logs) > 0) { ?>
                <?php while ($l = sql_fetch_array($logs)) { ?>
                    <tr>
                        <td><?php echo get_text($l['created_at']); ?></td>
                        <td><?php echo get_text($l['action']); ?></td>
                        <td><?php echo get_text($l['actor']); ?></td>
                        <td style="text-align:left;"><?php echo get_text($l['detail']); ?></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="4" class="empty_table">활동 로그가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
