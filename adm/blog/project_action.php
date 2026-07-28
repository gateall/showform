<?php
include_once('./_common.php');

$sub_menu = '360400';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$projects_table = bp_table('content_projects');
$adv_table = bp_table('advertisers');
$posts_table = bp_table('posts');
$targets_table = bp_table('post_targets');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$mode = isset($_POST['mode']) ? trim($_POST['mode']) : '';
$actor = bp_current_admin_id();

$project = sql_fetch(" select p.*, a.name as company_name, a.service_region
                       from {$projects_table} p left join {$adv_table} a on a.id = p.advertiser_id
                       where p.id = '{$id}' ");
if (!$project) {
    alert('콘텐츠 프로젝트를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/project_list.php');
}

if ($mode === 'generate') {
    if ($project['status'] !== 'draft') {
        alert("'{$project['status']}' 상태에서는 생성할 수 없습니다.", G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    $params = array(
        'topic' => $project['topic'],
        'primary_keyword' => $project['primary_keyword'],
        'content_type' => $project['content_type'],
        'company_name' => $project['company_name'],
        'service_region' => $project['service_region'],
    );

    $titles = bp_ai_generate_titles($params, 5);
    $body = bp_ai_generate_body($params);
    $selected_title = !empty($titles) ? $titles[0] : $project['topic'];

    $post = sql_fetch(" select * from {$posts_table} where project_id = '{$id}' order by id desc limit 1 ");
    if ($post) {
        sql_query(" update {$posts_table}
                        set title = '" . sql_real_escape_string($selected_title) . "',
                            subtitle = '" . sql_real_escape_string(isset($titles[1]) ? $titles[1] : '') . "',
                            body = '" . sql_real_escape_string($body) . "',
                            version = version + 1,
                            updated_at = '" . G5_TIME_YMDHIS . "'
                        where id = '" . (int)$post['id'] . "' ");

        sql_query(" update {$targets_table}
                        set title = '" . sql_real_escape_string($selected_title) . "',
                            body = '" . sql_real_escape_string($body) . "',
                            updated_at = '" . G5_TIME_YMDHIS . "'
                        where post_id = '" . (int)$post['id'] . "' ");
    }

    bp_transition_project($id, 'generated', $actor, '제목 후보: ' . implode(' | ', $titles));
    alert('AI 제목·본문이 생성되었습니다(템플릿 기반 — Phase 1은 실제 외부 AI 호출을 하지 않습니다).', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'request_review') {
    $result = bp_transition_project($id, 'review_required', $actor, '검수 요청');
    if (!$result['ok']) {
        alert($result['error'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    alert('검수 요청으로 전환되었습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

if ($mode === 'approve') {
    $result = bp_transition_project($id, 'approved', $actor, '관리자 승인');
    if (!$result['ok']) {
        alert($result['error'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }
    alert('승인되었습니다. 이제 발행이 가능합니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
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
    alert('발행이 완료되었습니다 (Mock Publisher). URL: ' . $result['published_url'], G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
}

alert('알 수 없는 요청입니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
