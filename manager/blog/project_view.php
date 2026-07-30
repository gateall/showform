<?php
include_once('./_common.php');

$sub_menu = '360500';
auth_check_menu($auth, $sub_menu, 'r');

$projects_table = bp_table('content_projects');
$adv_table = bp_table('advertisers');
$posts_table = bp_table('posts');
$targets_table = bp_table('post_targets');
$sites_table = bp_table('sites');
$logs_table = bp_table('content_activity_logs');
$candidates_table = bp_table('content_title_candidates');
$gen_logs_table = bp_table('content_generation_logs');

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

$candidates = sql_query(" select * from {$candidates_table} where project_id = '{$id}' order by id asc ");
$candidate_rows = array();
$has_selected_candidate = false;
while ($c = sql_fetch_array($candidates)) {
    $candidate_rows[] = $c;
    if ($c['is_selected'] === 'Y') {
        $has_selected_candidate = true;
    }
}

$quality_checks = $post ? bp_get_latest_quality_checks($id, (int) $post['id']) : array();
$quality_check_labels = array(
    'title_length' => '제목 길이', 'body_length' => '본문 길이', 'forbidden_words' => '금칙어',
    'contact_missing' => '연락처 누락', 'business_mismatch' => '업체정보 일치', 'duplicate_sentences' => '중복 문장',
    'keyword_stuffing' => '키워드 반복도',
);
$quality_status_label = array('pass' => '정상', 'warn' => '조건부', 'fail' => '차단');
$has_blocking_quality_failure = bp_quality_check_has_blocking_failure($quality_checks);

$logs = sql_query(" select * from {$logs_table} where project_id = '{$id}' order by id desc limit 20 ");
$gen_logs = sql_query(" select * from {$gen_logs_table} where project_id = '{$id}' order by id desc limit 10 ");

$status_label = array(
    'draft' => '초안', 'pending_approval' => '승인대기', 'approved' => '승인됨',
    'publish_pending' => '발행대기', 'publishing' => '발행중', 'published' => '발행완료', 'failed' => '발행실패',
);
$action_label = array('titles' => '제목 생성', 'body' => '본문 생성');
$gen_status_label = array('success' => '성공', 'fail' => '실패');

include_once(__DIR__ . '/../layout/header.php');
?>
<div class="local_desc01 local_desc">
    <p>상태: <strong><?php echo isset($status_label[$project['status']]) ? $status_label[$project['status']] : get_text($project['status']); ?></strong>
       (<?php echo get_text($project['status']); ?>) — 광고주: <?php echo get_text($project['advertiser_name']); ?>
       <?php if ($project['quality_score'] !== null) { ?> — 품질 점수: <?php echo (int) $project['quality_score']; ?>점<?php } ?></p>
    <p><?php if (in_array($project['status'], array('draft', 'pending_approval'), true)) { ?>
       <a href="./project_form.php?id=<?php echo (int)$id; ?>" class="btn btn_02">기본정보 수정</a>
       <?php } ?>
       <a href="./keyword_list.php?project_id=<?php echo (int)$id; ?>" class="btn btn_02">키워드 관리</a>
       <a href="./project_list.php" class="btn btn_02">목록으로</a>
       <?php if ($project['status'] === 'draft') { ?>
       <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:inline;" onsubmit="return confirm('이 콘텐츠 프로젝트를 삭제하시겠습니까? 삭제 후에는 목록에 표시되지 않습니다.');">
           <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
           <input type="hidden" name="mode" value="delete">
           <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
           <button type="submit" class="bp-btn-danger">삭제</button>
       </form>
       <?php } ?></p>
</div>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>프로젝트 정보</caption>
        <tbody>
            <tr><th scope="row">주제</th><td><?php echo get_text($project['topic']); ?></td></tr>
            <tr><th scope="row">대표 키워드</th><td><?php echo get_text($project['primary_keyword']); ?></td></tr>
            <tr><th scope="row">글 목적 / 길이</th><td><?php echo get_text($project['content_type']); ?> / <?php echo get_text($project['content_length']); ?></td></tr>
        </tbody>
    </table>
</div>

<div class="btn_confirm01 btn_confirm" style="margin-top:15px;">
    <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <input type="hidden" name="mode" value="generate_titles">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <button type="submit" class="btn_submit btn" <?php echo $project['status'] !== 'draft' ? 'disabled' : ''; ?>>① 제목 후보 생성</button>
    </form>
    <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <input type="hidden" name="mode" value="generate_body">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <button type="submit" class="btn_submit btn" <?php echo !($project['status'] === 'draft' && $has_selected_candidate) ? 'disabled' : ''; ?>>② 본문 생성</button>
    </form>
    <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <input type="hidden" name="mode" value="optimize_images">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <button type="submit" class="btn btn_02" <?php echo !($project['status'] === 'draft' && $post && $post['body']) ? 'disabled' : ''; ?>>③ 이미지 최적화</button>
    </form>
    <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <input type="hidden" name="mode" value="request_review">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <button type="submit" class="btn btn_02" <?php echo !($project['status'] === 'draft' && $post && $post['body']) ? 'disabled' : ''; ?>>④ 검수 요청</button>
    </form>
    <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:inline;" onsubmit="return !<?php echo $has_blocking_quality_failure ? 'true' : 'false'; ?> || confirm('품질 검사 실패 항목이 있어 승인이 차단됩니다. 계속하시겠습니까?');">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <input type="hidden" name="mode" value="approve">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <button type="submit" class="btn btn_01" <?php echo ($project['status'] !== 'pending_approval' || $has_blocking_quality_failure) ? 'disabled' : ''; ?>>⑤ 관리자 승인</button>
    </form>
    <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <input type="hidden" name="mode" value="reject">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <button type="submit" class="btn btn_02" <?php echo $project['status'] !== 'pending_approval' ? 'disabled' : ''; ?> onclick="return confirm('초안으로 반려하시겠습니까?');">반려(재작성)</button>
    </form>
    <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:inline;">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <input type="hidden" name="mode" value="cancel_approval">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <button type="submit" class="btn btn_02" <?php echo $project['status'] !== 'approved' ? 'disabled' : ''; ?> onclick="return confirm('승인을 취소하시겠습니까?');">승인 취소</button>
    </form>
</div>

<?php if ($project['status'] === 'draft' || !empty($candidate_rows)) { ?>
<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>제목 후보</caption>
        <thead><tr><th scope="col">선택</th><th scope="col">제목</th><th scope="col">글자 수</th><th scope="col">출처</th></tr></thead>
        <tbody>
            <?php if (!empty($candidate_rows)) { ?>
                <?php foreach ($candidate_rows as $c) { ?>
                    <tr>
                        <td>
                            <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                                <input type="hidden" name="mode" value="select_title">
                                <input type="hidden" name="title_candidate_id" value="<?php echo (int)$c['id']; ?>">
                                <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
                                <button type="submit" class="btn btn_02" <?php echo ($project['status'] !== 'draft' || $c['is_selected'] === 'Y') ? 'disabled' : ''; ?>><?php echo $c['is_selected'] === 'Y' ? '● 선택됨' : '○ 선택'; ?></button>
                            </form>
                        </td>
                        <td style="text-align:left;"><?php echo get_text($c['title']); ?></td>
                        <td><?php echo mb_strlen($c['title']); ?>/60</td>
                        <td><?php echo $c['source'] === 'ai' ? 'AI' : '직접입력'; ?></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="4" class="empty_table">아직 생성된 제목 후보가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php if ($project['status'] === 'draft') { ?>
<form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="margin-top:10px;">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
    <input type="hidden" name="mode" value="select_title">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <input type="text" name="custom_title" class="frm_input" maxlength="255" placeholder="+ 제목 직접 추가" style="width:60%;">
    <button type="submit" class="btn btn_submit btn">선택 제목 적용</button>
</form>
<form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:inline;">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
    <input type="hidden" name="mode" value="generate_titles">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <button type="submit" class="btn btn_02" style="margin-top:5px;">5개 다시 생성</button>
</form>
<?php } ?>
<?php } ?>

<div class="tbl_frm01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>본문</caption>
        <tbody>
            <tr><th scope="row">제목</th>
                <td>
                    <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" onsubmit="return confirm('제목을 수정하시겠습니까?');">
                        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                        <input type="hidden" name="mode" value="edit_title">
                        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
                        <input type="text" name="title" class="frm_input" maxlength="255" style="width:60%;" value="<?php echo $post ? get_text($post['title']) : ''; ?>" <?php echo !in_array($project['status'], array('draft', 'pending_approval'), true) ? 'disabled' : ''; ?>>
                        <button type="submit" class="btn btn_02" <?php echo !in_array($project['status'], array('draft', 'pending_approval'), true) ? 'disabled' : ''; ?>>제목 수정 저장</button>
                    </form>
                </td></tr>
            <tr><th scope="row">본문(현재)</th>
                <td>
                    <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" onsubmit="return confirm('본문을 수정하시겠습니까?');">
                        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                        <input type="hidden" name="mode" value="edit_body">
                        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
                        <textarea name="body" rows="14" style="width:100%;" <?php echo !in_array($project['status'], array('draft', 'pending_approval'), true) ? 'disabled' : ''; ?>><?php echo $post && $post['body'] ? get_text($post['body']) : ''; ?></textarea>
                        <div class="btn_confirm01 btn_confirm">
                            <button type="submit" class="btn btn_submit btn" <?php echo !in_array($project['status'], array('draft', 'pending_approval'), true) ? 'disabled' : ''; ?>>본문 수정 저장(품질 재검사)</button>
                        </div>
                    </form>
                </td></tr>
            <tr><th scope="row">해시태그</th>
                <td><?php echo $post && !empty($post['hashtags']) ? get_text($post['hashtags']) : '<span style="color:#999;">아직 생성되지 않음</span>'; ?></td></tr>
            <?php if ($post && !empty($post['previous_body'])) { ?>
            <tr><th scope="row">본문(직전 버전)</th>
                <td><pre style="white-space:pre-wrap;font-family:inherit;max-height:300px;overflow:auto;color:#666;"><?php echo get_text($post['previous_body']); ?></pre></td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php $meta_editable = in_array($project['status'], array('draft', 'pending_approval'), true); ?>
<div class="tbl_frm01 tbl_wrap" style="margin-top:20px;">
    <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php">
        <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
        <input type="hidden" name="mode" value="edit_post_meta">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <table>
            <caption>SEO·편집 메타데이터</caption>
            <tbody>
                <tr><th scope="row"><label for="slug">슬러그</label></th>
                    <td><input type="text" name="slug" id="slug" class="frm_input" maxlength="255" value="<?php echo $post ? get_text($post['slug']) : ''; ?>" <?php echo !$meta_editable ? 'disabled' : ''; ?>></td></tr>
                <tr><th scope="row"><label for="excerpt">요약문</label></th>
                    <td><textarea name="excerpt" id="excerpt" rows="2" style="width:100%;" maxlength="500" <?php echo !$meta_editable ? 'disabled' : ''; ?>><?php echo $post ? get_text($post['excerpt']) : ''; ?></textarea></td></tr>
                <tr><th scope="row"><label for="meta_title">메타 제목</label></th>
                    <td><input type="text" name="meta_title" id="meta_title" class="frm_input" maxlength="255" value="<?php echo $post ? get_text($post['meta_title']) : ''; ?>" <?php echo !$meta_editable ? 'disabled' : ''; ?>></td></tr>
                <tr><th scope="row"><label for="meta_description">메타 설명</label></th>
                    <td><textarea name="meta_description" id="meta_description" rows="2" style="width:100%;" maxlength="500" <?php echo !$meta_editable ? 'disabled' : ''; ?>><?php echo $post ? get_text($post['meta_description']) : ''; ?></textarea></td></tr>
                <tr><th scope="row"><label for="secondary_keywords">보조 키워드</label></th>
                    <td><input type="text" name="secondary_keywords" id="secondary_keywords" class="frm_input" maxlength="500" value="<?php echo $post ? get_text($post['secondary_keywords']) : ''; ?>" <?php echo !$meta_editable ? 'disabled' : ''; ?>></td></tr>
                <tr><th scope="row"><label for="category">카테고리</label></th>
                    <td><input type="text" name="category" id="category" class="frm_input" maxlength="100" value="<?php echo $post ? get_text($post['category']) : ''; ?>" <?php echo !$meta_editable ? 'disabled' : ''; ?>></td></tr>
                <tr><th scope="row"><label for="tags">태그</label></th>
                    <td><input type="text" name="tags" id="tags" class="frm_input" maxlength="500" value="<?php echo $post ? get_text($post['tags']) : ''; ?>" <?php echo !$meta_editable ? 'disabled' : ''; ?>></td></tr>
                <tr><th scope="row"><label for="featured_image_url">대표 이미지 URL</label></th>
                    <td><input type="text" name="featured_image_url" id="featured_image_url" class="frm_input" maxlength="500" value="<?php echo $post ? get_text($post['featured_image_url']) : ''; ?>" <?php echo !$meta_editable ? 'disabled' : ''; ?>></td></tr>
                <tr><th scope="row"><label for="internal_memo">내부 메모</label></th>
                    <td><textarea name="internal_memo" id="internal_memo" rows="2" style="width:100%;" maxlength="1000" <?php echo !$meta_editable ? 'disabled' : ''; ?>><?php echo $post ? get_text($post['internal_memo']) : ''; ?></textarea></td></tr>
                <tr><th scope="row"><label for="review_comment">검수 의견</label></th>
                    <td><textarea name="review_comment" id="review_comment" rows="2" style="width:100%;" maxlength="1000" <?php echo !$meta_editable ? 'disabled' : ''; ?>><?php echo $post ? get_text($post['review_comment']) : ''; ?></textarea></td></tr>
            </tbody>
        </table>
        <?php if ($meta_editable) { ?>
        <div class="btn_confirm01 btn_confirm">
            <button type="submit" class="btn btn_submit btn">메타데이터 저장</button>
        </div>
        <?php } ?>
    </form>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>품질 검사 결과<?php echo $has_blocking_quality_failure ? ' — 승인 차단 중' : ''; ?></caption>
        <thead><tr><th scope="col">항목</th><th scope="col">결과</th><th scope="col">상세</th></tr></thead>
        <tbody>
            <?php if (!empty($quality_checks)) { ?>
                <?php foreach ($quality_checks as $key => $row) { ?>
                    <tr>
                        <td><?php echo isset($quality_check_labels[$key]) ? $quality_check_labels[$key] : get_text($key); ?></td>
                        <td style="color:<?php echo $row['status'] === 'fail' ? '#c00' : ($row['status'] === 'warn' ? '#c90' : '#0a0'); ?>;"><?php echo isset($quality_status_label[$row['status']]) ? $quality_status_label[$row['status']] : get_text($row['status']); ?></td>
                        <td style="text-align:left;"><?php echo get_text($row['detail']); ?></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="3" class="empty_table">아직 품질 검사가 실행되지 않았습니다(본문 생성 시 자동 실행).</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>AI 생성 로그(최근 10건 — 토큰·비용은 추정치)</caption>
        <thead><tr><th scope="col">시각</th><th scope="col">종류</th><th scope="col">공급자/모델</th><th scope="col">토큰(프롬프트/응답)</th><th scope="col">비용 추정</th><th scope="col">결과</th></tr></thead>
        <tbody>
            <?php if ($gen_logs && sql_num_rows($gen_logs) > 0) { ?>
                <?php while ($gl = sql_fetch_array($gen_logs)) { ?>
                    <tr>
                        <td><?php echo get_text($gl['created_at']); ?></td>
                        <td><?php echo isset($action_label[$gl['action']]) ? $action_label[$gl['action']] : get_text($gl['action']); ?></td>
                        <td><?php echo get_text($gl['provider']) . ($gl['model'] ? ' / ' . get_text($gl['model']) : ''); ?></td>
                        <td><?php echo $gl['tokens_prompt'] !== null ? (int)$gl['tokens_prompt'] . ' / ' . (int)$gl['tokens_completion'] : '-'; ?></td>
                        <td><?php echo $gl['cost_estimate'] !== null ? '$' . number_format((float)$gl['cost_estimate'], 4) : '-'; ?></td>
                        <td style="color:<?php echo $gl['status'] === 'fail' ? '#c00' : '#0a0'; ?>;">
                            <?php echo isset($gen_status_label[$gl['status']]) ? $gen_status_label[$gl['status']] : get_text($gl['status']); ?>
                            <?php echo $gl['error_message'] ? '<div style="font-size:12px;">' . get_text($gl['error_message']) . '</div>' : ''; ?>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="6" class="empty_table">아직 생성 로그가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<div class="tbl_head01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>사이트별 발행 대상 (post_targets)</caption>
        <thead><tr><th scope="col">사이트</th><th scope="col">플랫폼</th><th scope="col">발행 상태</th><th scope="col">예약 발행일</th><th scope="col">외부 URL</th><th scope="col">재시도</th><th scope="col">발행/재시도</th></tr></thead>
        <tbody>
            <?php if ($targets && sql_num_rows($targets) > 0) { ?>
                <?php while ($t = sql_fetch_array($targets)) {
                    $target_schedule_editable = $t['publish_status'] !== 'published';
                ?>
                    <tr>
                        <td><?php echo get_text($t['site_name']); ?></td>
                        <td><?php echo get_text($t['platform']); ?></td>
                        <td><?php echo get_text($t['publish_status']); ?><?php echo $t['last_error'] ? '<div style="color:#c00;font-size:12px;">' . get_text($t['last_error']) . '</div>' : ''; ?></td>
                        <td>
                            <?php if ($target_schedule_editable) { ?>
                            <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:flex;gap:4px;align-items:center;">
                                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                                <input type="hidden" name="mode" value="update_target_schedule">
                                <input type="hidden" name="post_target_id" value="<?php echo (int)$t['id']; ?>">
                                <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
                                <input type="datetime-local" name="scheduled_at" value="<?php echo $t['scheduled_at'] ? date('Y-m-d\TH:i', strtotime($t['scheduled_at'])) : ''; ?>" class="frm_input">
                                <button type="submit" class="btn btn_02">저장</button>
                            </form>
                            <?php } else { ?>
                                <?php echo $t['scheduled_at'] ? get_text($t['scheduled_at']) : '-'; ?>
                            <?php } ?>
                        </td>
                        <td><?php echo $t['published_url'] ? '<a href="' . get_text($t['published_url']) . '" target="_blank">' . get_text($t['published_url']) . '</a>' : '-'; ?></td>
                        <td><?php echo (int)$t['retry_count']; ?> / <?php echo BP_MAX_PUBLISH_ATTEMPTS; ?></td>
                        <td>
                            <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                                <input type="hidden" name="mode" value="publish">
                                <input type="hidden" name="post_target_id" value="<?php echo (int)$t['id']; ?>">
                                <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
                                <button type="submit" class="btn btn_submit btn" <?php echo !in_array($project['status'], array('approved','publish_pending','publishing','failed'), true) ? 'disabled' : ''; ?>>발행(초안)</button>
                            </form>
                            <form method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/project_action.php" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
                                <input type="hidden" name="mode" value="retry">
                                <input type="hidden" name="post_target_id" value="<?php echo (int)$t['id']; ?>">
                                <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
                                <button type="submit" class="btn btn_02" <?php echo !($project['status'] === 'failed' && (int)$t['retry_count'] < BP_MAX_PUBLISH_ATTEMPTS) ? 'disabled' : ''; ?>>재시도</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="7" class="empty_table">발행 대상 사이트가 없습니다.</td></tr>
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

<?php include_once(__DIR__ . '/../layout/footer.php');
