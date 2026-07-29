<?php
include_once('./_common.php');

$sub_menu = '360500';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$projects_table = bp_table('content_projects');
$adv_table = bp_table('advertisers');
$posts_table = bp_table('posts');
$targets_table = bp_table('post_targets');
$candidates_table = bp_table('content_title_candidates');
$jobs_table = bp_table('publish_jobs');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$mode = isset($_POST['mode']) ? trim($_POST['mode']) : '';
$actor = bp_current_admin_id();

$project = sql_fetch(" select * from {$projects_table} where id = '{$id}' ");
if (!$project) {
    alert('콘텐츠 프로젝트를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/project_list.php');
}
if (!empty($project['deleted_at'])) {
    alert('이미 삭제된 프로젝트입니다.', G5_ADMIN_URL . '/blog/project_list.php');
}

$advertiser = sql_fetch(" select * from {$adv_table} where id = '" . (int)$project['advertiser_id'] . "' ");
if (!$advertiser) {
    alert('광고주 정보를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

$post = sql_fetch(" select * from {$posts_table} where project_id = '{$id}' order by id desc limit 1 ");
if (!$post) {
    alert('원본 포스팅을 찾을 수 없습니다(프로젝트 등록 시 자동 생성되어야 합니다).', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

function bp_sync_post_targets_title_body(string $targets_table, int $post_id, string $title, ?string $body = null): void
{
    $body_sql = $body !== null ? ", body = '" . sql_real_escape_string($body) . "'" : '';
    sql_query(" update {$targets_table}
                    set title = '" . sql_real_escape_string($title) . "'
                        {$body_sql},
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where post_id = '{$post_id}' ");
}

// 제목·본문 생성은 별도 "생성 완료" 상태를 두지 않고 draft 상태에서 이루어진다 — 진행
// 단계는 제목 후보 존재 여부·선택 여부로 판단한다(select_title/generate_body 참조).
if ($mode === 'generate_titles') {
    if ($project['status'] !== 'draft') {
        alert("'{$project['status']}' 상태에서는 제목을 생성할 수 없습니다.", G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    $params = array(
        'topic' => $project['topic'],
        'primary_keyword' => $project['primary_keyword'],
        'content_type' => $project['content_type'],
        'company_name' => $advertiser['name'],
        'service_region' => $advertiser['service_region'],
    );

    $provider_meta = bp_ai_get_provider_meta();
    $result = bp_ai_generate_titles($params, 5);
    bp_log_generation_attempt($id, 'titles', $provider_meta['provider'], $provider_meta['model'], $result);

    if (!$result['ok']) {
        // 실패 시 기존 후보·초안을 그대로 둔다 — 아무것도 쓰지 않고 오류만 안내.
        alert('제목 생성 실패: ' . $result['error'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    // 재생성이므로 이전 후보는 비우고 새로 채운다(성공을 확인한 뒤에만 지운다).
    sql_query(" delete from {$candidates_table} where project_id = '{$id}' ");
    foreach ($result['titles'] as $title) {
        sql_query(" insert into {$candidates_table}
                        set project_id = '{$id}',
                            title = '" . sql_real_escape_string($title) . "',
                            source = 'ai',
                            is_selected = 'N',
                            created_at = '" . G5_TIME_YMDHIS . "' ");
    }

    bp_log_activity($id, 'titles_generated', $actor, count($result['titles']) . '개 생성 (' . $provider_meta['provider'] . ')');
    alert('제목 후보가 생성되었습니다. 마음에 드는 제목을 선택하거나 직접 입력해 주세요.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'select_title') {
    if ($project['status'] !== 'draft') {
        alert("'{$project['status']}' 상태에서는 제목을 선택할 수 없습니다.", G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    $candidate_id = isset($_POST['title_candidate_id']) ? (int) $_POST['title_candidate_id'] : 0;
    $custom_title = isset($_POST['custom_title']) ? trim($_POST['custom_title']) : '';

    $selected_title = '';
    if ($custom_title !== '') {
        sql_query(" update {$candidates_table} set is_selected = 'N' where project_id = '{$id}' ");
        sql_query(" insert into {$candidates_table}
                        set project_id = '{$id}',
                            title = '" . sql_real_escape_string($custom_title) . "',
                            source = 'manual',
                            is_selected = 'Y',
                            created_at = '" . G5_TIME_YMDHIS . "' ");
        $selected_title = $custom_title;
    } elseif ($candidate_id > 0) {
        $candidate = sql_fetch(" select * from {$candidates_table} where id = '{$candidate_id}' and project_id = '{$id}' ");
        if (!$candidate) {
            alert('선택한 제목 후보를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
        }
        sql_query(" update {$candidates_table} set is_selected = 'N' where project_id = '{$id}' ");
        sql_query(" update {$candidates_table} set is_selected = 'Y' where id = '{$candidate_id}' ");
        $selected_title = $candidate['title'];
    } else {
        alert('제목 후보를 선택하거나 직접 입력해 주세요.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    sql_query(" update {$posts_table} set title = '" . sql_real_escape_string($selected_title) . "', updated_at = '" . G5_TIME_YMDHIS . "' where id = '" . (int)$post['id'] . "' ");
    bp_sync_post_targets_title_body($targets_table, (int) $post['id'], $selected_title);
    bp_log_activity($id, 'title_selected', $actor, $selected_title);

    alert('제목이 선택되었습니다. 이제 본문을 생성해 주세요.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'generate_body') {
    if ($project['status'] !== 'draft') {
        alert("'{$project['status']}' 상태에서는 본문을 생성할 수 없습니다.", G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    $selected = sql_fetch(" select * from {$candidates_table} where project_id = '{$id}' and is_selected = 'Y' limit 1 ");
    if (!$selected) {
        alert('먼저 제목을 선택해 주세요.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    $params = array(
        'title' => $selected['title'],
        'topic' => $project['topic'],
        'primary_keyword' => $project['primary_keyword'],
        'content_type' => $project['content_type'],
        'service_region' => $advertiser['service_region'],
        'company_name' => $advertiser['name'],
    );

    $provider_meta = bp_ai_get_provider_meta();
    $result = bp_ai_generate_body($params);
    bp_log_generation_attempt($id, 'body', $provider_meta['provider'], $provider_meta['model'], $result);

    if (!$result['ok']) {
        // 실패 시 기존 본문을 그대로 둔다.
        alert('본문 생성 실패: ' . $result['error'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    $final_body = bp_apply_business_placeholders($result['body'], $advertiser);
    $hashtags = isset($result['hashtags']) ? $result['hashtags'] : '';
    $previous_body_sql = $post['body'] !== '' && $post['body'] !== null
        ? ", previous_body = '" . sql_real_escape_string($post['body']) . "'"
        : '';

    sql_query(" update {$posts_table}
                    set title = '" . sql_real_escape_string($selected['title']) . "',
                        body = '" . sql_real_escape_string($final_body) . "',
                        hashtags = '" . sql_real_escape_string($hashtags) . "'
                        {$previous_body_sql},
                        version = version + 1,
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = '" . (int)$post['id'] . "' ");
    bp_sync_post_targets_title_body($targets_table, (int) $post['id'], $selected['title'], $final_body);

    $updated_post = sql_fetch(" select * from {$posts_table} where id = '" . (int)$post['id'] . "' ");
    bp_run_and_save_quality_check($id, (int) $post['id'], $updated_post, $advertiser, $project);

    bp_log_activity($id, 'body_generated', $actor, '버전 ' . $updated_post['version'] . ' (' . $provider_meta['provider'] . ')');
    alert('본문이 생성되고 품질 검사가 실행되었습니다. 검수 요청 전에 결과를 확인해 주세요.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'edit_title' || $mode === 'edit_body') {
    if (!in_array($project['status'], array('draft', 'pending_approval'), true)) {
        alert("'{$project['status']}' 상태에서는 직접 수정할 수 없습니다.", G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    if ($mode === 'edit_title') {
        $new_title = isset($_POST['title']) ? trim($_POST['title']) : '';
        if ($new_title === '') {
            alert('제목을 입력해 주세요.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
        }
        sql_query(" update {$posts_table} set title = '" . sql_real_escape_string($new_title) . "', updated_at = '" . G5_TIME_YMDHIS . "' where id = '" . (int)$post['id'] . "' ");
        bp_sync_post_targets_title_body($targets_table, (int) $post['id'], $new_title);
        bp_log_activity($id, 'title_edited', $actor, $new_title);
        alert('제목이 수정되었습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    $new_body = isset($_POST['body']) ? trim($_POST['body']) : '';
    if ($new_body === '') {
        alert('본문을 입력해 주세요.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    $previous_body_sql = $post['body'] !== '' && $post['body'] !== null
        ? ", previous_body = '" . sql_real_escape_string($post['body']) . "'"
        : '';
    sql_query(" update {$posts_table}
                    set body = '" . sql_real_escape_string($new_body) . "'
                        {$previous_body_sql},
                        version = version + 1,
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = '" . (int)$post['id'] . "' ");
    bp_sync_post_targets_title_body($targets_table, (int) $post['id'], $post['title'], $new_body);

    $updated_post = sql_fetch(" select * from {$posts_table} where id = '" . (int)$post['id'] . "' ");
    bp_run_and_save_quality_check($id, (int) $post['id'], $updated_post, $advertiser, $project);
    bp_log_activity($id, 'body_edited', $actor, '버전 ' . $updated_post['version']);
    alert('본문이 수정되고 품질 검사가 다시 실행되었습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'request_review') {
    if (trim((string) $post['body']) === '') {
        alert('본문이 없습니다. 먼저 본문을 생성하거나 입력해 주세요.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    $result = bp_transition_project($id, 'pending_approval', $actor, '검수 요청');
    if (!$result['ok']) {
        alert($result['error'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    alert('검수 요청으로 전환되었습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'approve') {
    $latest_checks = bp_get_latest_quality_checks($id, (int) $post['id']);
    if (bp_quality_check_has_blocking_failure($latest_checks)) {
        $failed = array();
        foreach ($latest_checks as $key => $row) {
            if ($row['status'] === 'fail') {
                $failed[] = $key;
            }
        }
        alert('품질 검사를 통과하지 못해 승인할 수 없습니다: ' . implode(', ', $failed), G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    $result = bp_transition_project($id, 'approved', $actor, '관리자 승인');
    if (!$result['ok']) {
        alert($result['error'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    alert('승인되었습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'cancel_approval') {
    if (bp_has_active_publish_job($id)) {
        alert('진행 중인 발행 작업이 있어 승인을 취소할 수 없습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    $result = bp_transition_project($id, 'pending_approval', $actor, '승인 취소');
    if (!$result['ok']) {
        alert($result['error'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    alert('승인이 취소되었습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'reject') {
    $result = bp_transition_project($id, 'draft', $actor, '반려 — 재작성 요청');
    if (!$result['ok']) {
        alert($result['error'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    alert('초안으로 반려되었습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'publish') {
    $post_target_id = isset($_POST['post_target_id']) ? (int) $_POST['post_target_id'] : 0;
    $result = bp_dispatch_publish_job($post_target_id, $actor);
    if (!$result['ok']) {
        alert('발행 실패: ' . $result['error'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    alert('워드프레스에 초안(draft)으로 발행되었습니다. URL: ' . $result['published_url'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'retry') {
    $post_target_id = isset($_POST['post_target_id']) ? (int) $_POST['post_target_id'] : 0;
    $result = bp_retry_publish_job($post_target_id, $actor);
    if (!$result['ok']) {
        alert('재시도 실패: ' . $result['error'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    alert('재시도 발행이 완료되었습니다. URL: ' . $result['published_url'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'edit_post_meta') {
    if (!in_array($project['status'], array('draft', 'pending_approval'), true)) {
        alert("'{$project['status']}' 상태에서는 직접 수정할 수 없습니다.", G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    $slug = isset($_POST['slug']) ? trim($_POST['slug']) : '';
    $slug = trim(preg_replace('/[^a-zA-Z0-9\-]+/', '-', $slug), '-');
    $slug = strtolower($slug);
    $excerpt = isset($_POST['excerpt']) ? trim($_POST['excerpt']) : '';
    $meta_title = isset($_POST['meta_title']) ? trim($_POST['meta_title']) : '';
    $meta_description = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
    $secondary_keywords = isset($_POST['secondary_keywords']) ? trim($_POST['secondary_keywords']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $tags = isset($_POST['tags']) ? trim($_POST['tags']) : '';
    $featured_image_url = isset($_POST['featured_image_url']) ? trim($_POST['featured_image_url']) : '';
    $internal_memo = isset($_POST['internal_memo']) ? trim($_POST['internal_memo']) : '';
    $review_comment = isset($_POST['review_comment']) ? trim($_POST['review_comment']) : '';

    $length_checks = array(
        'slug' => array($slug, 255, '슬러그'),
        'excerpt' => array($excerpt, 500, '요약문'),
        'meta_title' => array($meta_title, 255, '메타 제목'),
        'meta_description' => array($meta_description, 500, '메타 설명'),
        'secondary_keywords' => array($secondary_keywords, 500, '보조 키워드'),
        'category' => array($category, 100, '카테고리'),
        'tags' => array($tags, 500, '태그'),
        'featured_image_url' => array($featured_image_url, 500, '대표 이미지 URL'),
        'internal_memo' => array($internal_memo, 1000, '내부 메모'),
        'review_comment' => array($review_comment, 1000, '검수 의견'),
    );
    foreach ($length_checks as $check) {
        list($value, $max, $label) = $check;
        if (mb_strlen($value) > $max) {
            alert("{$label}이(가) 너무 깁니다(최대 {$max}자).", G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
        }
    }

    sql_query(" update {$posts_table}
                    set slug = '" . sql_real_escape_string($slug) . "',
                        excerpt = '" . sql_real_escape_string($excerpt) . "',
                        meta_title = '" . sql_real_escape_string($meta_title) . "',
                        meta_description = '" . sql_real_escape_string($meta_description) . "',
                        secondary_keywords = '" . sql_real_escape_string($secondary_keywords) . "',
                        category = '" . sql_real_escape_string($category) . "',
                        tags = '" . sql_real_escape_string($tags) . "',
                        featured_image_url = '" . sql_real_escape_string($featured_image_url) . "',
                        internal_memo = '" . sql_real_escape_string($internal_memo) . "',
                        review_comment = '" . sql_real_escape_string($review_comment) . "',
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = '" . (int) $post['id'] . "' ");

    bp_log_activity($id, 'post_meta_edited', $actor, '메타데이터 수정');
    alert('포스트 메타데이터가 수정되었습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'update_target_schedule') {
    $post_target_id = isset($_POST['post_target_id']) ? (int) $_POST['post_target_id'] : 0;
    $scheduled_at = isset($_POST['scheduled_at']) ? trim($_POST['scheduled_at']) : '';

    $target = sql_fetch(" select t.* from {$targets_table} t where t.id = '{$post_target_id}' and t.post_id = '" . (int) $post['id'] . "' ");
    if (!$target) {
        alert('발행 대상을 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    if ($target['publish_status'] === 'published') {
        alert('이미 발행 완료된 대상은 예약일을 변경할 수 없습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    $active_job = sql_fetch(" select id from {$jobs_table} where post_target_id = '{$post_target_id}' and status in ('pending','claimed','processing') limit 1 ");
    if ($active_job) {
        alert('현재 발행 작업이 진행 중인 대상은 예약일을 변경할 수 없습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    if ($scheduled_at !== '') {
        $ts = strtotime($scheduled_at);
        if ($ts === false) {
            alert('예약일 형식이 올바르지 않습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
        }
        if ($ts < time()) {
            alert('예약일은 현재 시각 이후로 설정해야 합니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
        }
        $scheduled_sql = "'" . date('Y-m-d H:i:s', $ts) . "'";
        $log_detail = "target#{$post_target_id} -> " . date('Y-m-d H:i', $ts);
    } else {
        $scheduled_sql = 'NULL';
        $log_detail = "target#{$post_target_id} -> (해제)";
    }

    sql_query(" update {$targets_table} set scheduled_at = {$scheduled_sql}, updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$post_target_id}' ");
    bp_log_activity($id, 'target_schedule_updated', $actor, $log_detail);
    alert('예약일이 저장되었습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'delete') {
    if ($project['status'] !== 'draft') {
        alert("'{$project['status']}' 상태에서는 삭제할 수 없습니다.", G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    $has_target_history = sql_fetch(" select pt.id from {$targets_table} pt
                                       where pt.post_id = '" . (int) $post['id'] . "'
                                       and (pt.publish_status != 'draft' or pt.retry_count > 0) limit 1 ");
    if ($has_target_history) {
        alert('발행 이력이 있는 발행 대상이 있어 삭제할 수 없습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    $has_jobs = sql_fetch(" select j.id from {$jobs_table} j
                             join {$targets_table} pt on pt.id = j.post_target_id
                             where pt.post_id = '" . (int) $post['id'] . "' limit 1 ");
    if ($has_jobs) {
        alert('연결된 배포 작업이 있어 삭제할 수 없습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    sql_query(" update {$projects_table}
                    set deleted_at = '" . G5_TIME_YMDHIS . "', deleted_by = '" . sql_real_escape_string($actor) . "'
                    where id = '{$id}' ");
    bp_log_activity($id, 'project_deleted', $actor, $project['topic']);
    alert('콘텐츠 프로젝트가 삭제되었습니다.', G5_ADMIN_URL . '/blog/project_list.php');
}

alert('알 수 없는 요청입니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
