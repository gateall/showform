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

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$mode = isset($_POST['mode']) ? trim($_POST['mode']) : '';
$actor = bp_current_admin_id();

$project = sql_fetch(" select * from {$projects_table} where id = '{$id}' ");
if (!$project) {
    alert('콘텐츠 프로젝트를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/project_list.php');
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

alert('알 수 없는 요청입니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
