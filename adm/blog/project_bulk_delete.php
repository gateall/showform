<?php
include_once('./_common.php');

$sub_menu = '360500';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

// project_action.php의 mode=delete와 정확히 같은 조건(초안 상태, 발행 이력 없음, 연결된
// 배포 작업 없음)을 그대로 적용한다 - 그 파일은 한 번에 하나의 $id만 다루는 구조라
// 여기서는 같은 검사를 각 id마다 반복한다(재구성해서 공유하면 이미 여러 곳에서 쓰는
// 그 파일의 단일 id 흐름을 건드리게 되어 위험이 더 크다고 판단).
$projects_table = bp_table('content_projects');
$posts_table = bp_table('posts');
$targets_table = bp_table('post_targets');
$jobs_table = bp_table('publish_jobs');
$actor = bp_current_admin_id();

$ids = isset($_POST['ids']) && is_array($_POST['ids']) ? array_map('intval', $_POST['ids']) : array();
$ids = array_values(array_unique(array_filter($ids, function ($v) { return $v > 0; })));

if (empty($ids)) {
    alert('삭제할 항목을 선택해 주세요.', SF_MANAGER_URL . '/blog/content_management.php?tab=posts');
}

$deleted = array();
$skipped = array();

foreach ($ids as $pid) {
    $project = sql_fetch(" select id, topic, status, deleted_at from {$projects_table} where id = '{$pid}' ");
    if (!$project || !empty($project['deleted_at'])) {
        $skipped[] = "#{$pid}(없음/이미 삭제됨)";
        continue;
    }
    if ($project['status'] !== 'draft') {
        $skipped[] = "#{$pid}({$project['status']} 상태라 삭제 불가)";
        continue;
    }

    $post = sql_fetch(" select id from {$posts_table} where project_id = '{$pid}' order by id desc limit 1 ");
    if ($post) {
        $has_target_history = sql_fetch(" select pt.id from {$targets_table} pt
                                           where pt.post_id = '" . (int) $post['id'] . "'
                                           and (pt.publish_status != 'draft' or pt.retry_count > 0) limit 1 ");
        if ($has_target_history) {
            $skipped[] = "#{$pid}(발행 이력 있음)";
            continue;
        }
        $has_jobs = sql_fetch(" select j.id from {$jobs_table} j
                                 join {$targets_table} pt on pt.id = j.post_target_id
                                 where pt.post_id = '" . (int) $post['id'] . "' limit 1 ");
        if ($has_jobs) {
            $skipped[] = "#{$pid}(연결된 배포 작업 있음)";
            continue;
        }
    }

    $ok = sql_query(" update {$projects_table}
                        set deleted_at = '" . G5_TIME_YMDHIS . "', deleted_by = '" . sql_real_escape_string($actor) . "'
                        where id = '{$pid}' ");
    if (!$ok) {
        $skipped[] = "#{$pid}(저장 실패)";
        continue;
    }
    bp_log_activity($pid, 'project_deleted', $actor, get_text($project['topic']) . ' (일괄 삭제)');
    $deleted[] = "#{$pid}";
}

$summary = count($deleted) . '건 삭제됨';
if (!empty($skipped)) {
    $summary .= ', ' . count($skipped) . '건 건너뜀(' . implode(', ', $skipped) . ')';
}

alert($summary, SF_MANAGER_URL . '/blog/content_management.php?tab=posts');
