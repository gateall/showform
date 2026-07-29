<?php
include_once('./_common.php');

$sub_menu = '360500';
auth_check_menu($auth, $sub_menu, 'w');
check_admin_token();

$projects_table = bp_table('content_projects');
$posts_table = bp_table('posts');
$targets_table = bp_table('post_targets');
$sites_table = bp_table('sites');

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$advertiser_id = isset($_POST['advertiser_id']) ? (int) $_POST['advertiser_id'] : 0;
$site_ids = isset($_POST['site_ids']) && is_array($_POST['site_ids']) ? array_map('intval', $_POST['site_ids']) : array();
$topic = isset($_POST['topic']) ? trim($_POST['topic']) : '';
$primary_keyword = isset($_POST['primary_keyword']) ? trim($_POST['primary_keyword']) : '';
$content_type = isset($_POST['content_type']) ? trim($_POST['content_type']) : 'info';
$target_audience = isset($_POST['target_audience']) ? trim($_POST['target_audience']) : '';
$content_length = isset($_POST['content_length']) ? trim($_POST['content_length']) : 'normal';

if ($advertiser_id < 1) {
    alert('사업체를 선택해 주세요.');
}
if ($topic === '') {
    alert('핵심 주제를 입력해 주세요.');
}

$allowed_types = array('info', 'comparison', 'case', 'faq', 'promo');
if (!in_array($content_type, $allowed_types, true)) $content_type = 'info';
$allowed_lengths = array('short', 'normal', 'detailed');
if (!in_array($content_length, $allowed_lengths, true)) $content_length = 'normal';

$primary_site_id = !empty($site_ids) ? (int) $site_ids[0] : null;
$actor = bp_current_admin_id();

// UUID v4 형태의 고유 식별자 생성 (외부 노출용, 내부 PK와 별개)
function bp_generate_uuid()
{
    $data = openssl_random_pseudo_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

// ---- 수정(id 기반) ----
if ($id > 0) {
    $existing = sql_fetch(" select id, status from {$projects_table} where id = '{$id}' ");
    if (!$existing) {
        alert('콘텐츠 프로젝트를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/project_list.php');
    }
    if (!in_array($existing['status'], array('draft', 'pending_approval'), true)) {
        alert('승인 이후에는 기본정보를 수정할 수 없습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    $site_sql = $primary_site_id !== null ? "'{$primary_site_id}'" : 'NULL';
    sql_query(" update {$projects_table}
                    set advertiser_id = '{$advertiser_id}',
                        primary_site_id = {$site_sql},
                        topic = '" . sql_real_escape_string($topic) . "',
                        primary_keyword = '" . sql_real_escape_string($primary_keyword) . "',
                        content_type = '{$content_type}',
                        target_audience = '" . sql_real_escape_string($target_audience) . "',
                        content_length = '{$content_length}',
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = '{$id}' ");

    // 사이트 선택을 최신 post_targets 구성에 동기화 — 아직 발행이 시작되지 않은(draft) 대상만 정리 대상.
    $post = sql_fetch(" select id from {$posts_table} where project_id = '{$id}' order by id desc limit 1 ");
    if ($post) {
        $post_id = (int) $post['id'];

        $existing_targets = array();
        $tresult = sql_query(" select id, site_id from {$targets_table} where post_id = '{$post_id}' ");
        while ($t = sql_fetch_array($tresult)) {
            $existing_targets[(int) $t['site_id']] = (int) $t['id'];
        }

        // 선택 해제된 사이트 중 아직 발행이 진행되지 않은 대상만 제거
        foreach ($existing_targets as $sid => $target_id) {
            if (!in_array($sid, $site_ids, true)) {
                sql_query(" delete from {$targets_table} where id = '{$target_id}' and publish_status = 'draft' ");
            }
        }

        // 새로 선택된 사이트는 post_targets 신규 추가
        foreach ($site_ids as $sid) {
            if ($sid < 1 || isset($existing_targets[$sid])) continue;
            $site_check = sql_fetch(" select id from {$sites_table} where id = '{$sid}' ");
            if (!$site_check) continue;
            sql_query(" insert into {$targets_table}
                            set post_id = '{$post_id}',
                                site_id = '{$sid}',
                                title = '',
                                publish_status = 'draft',
                                created_at = '" . G5_TIME_YMDHIS . "',
                                updated_at = '" . G5_TIME_YMDHIS . "' ");
        }
    }

    bp_log_activity($id, 'project_updated', $actor, $topic);

    alert('콘텐츠 프로젝트가 수정되었습니다.', G5_ADMIN_URL . '/blog/project_list.php');
}

// ---- 신규 등록 ----
$project_uuid = bp_generate_uuid();

$site_sql = $primary_site_id !== null ? "'{$primary_site_id}'" : 'NULL';
sql_query(" insert into {$projects_table}
                set project_uuid = '" . sql_real_escape_string($project_uuid) . "',
                    advertiser_id = '{$advertiser_id}',
                    primary_site_id = {$site_sql},
                    topic = '" . sql_real_escape_string($topic) . "',
                    primary_keyword = '" . sql_real_escape_string($primary_keyword) . "',
                    content_type = '{$content_type}',
                    target_audience = '" . sql_real_escape_string($target_audience) . "',
                    content_length = '{$content_length}',
                    status = 'draft',
                    created_by = '" . sql_real_escape_string($actor) . "',
                    created_at = '" . G5_TIME_YMDHIS . "',
                    updated_at = '" . G5_TIME_YMDHIS . "' ");
$project_id = (int) sql_insert_id();

// posts(원본) 1건 생성 — 아직 제목/본문은 비어있는 draft
sql_query(" insert into {$posts_table}
                set project_id = '{$project_id}',
                    title = '',
                    subtitle = '',
                    body = '',
                    version = 1,
                    created_at = '" . G5_TIME_YMDHIS . "',
                    updated_at = '" . G5_TIME_YMDHIS . "' ");
$post_id = (int) sql_insert_id();

// 선택된 사이트마다 post_targets 1건씩 생성 (DATA_MODEL.md 2절 — posts와 post_targets 분리)
foreach ($site_ids as $sid) {
    if ($sid < 1) continue;
    $site_check = sql_fetch(" select id from {$sites_table} where id = '{$sid}' ");
    if (!$site_check) continue;
    sql_query(" insert into {$targets_table}
                    set post_id = '{$post_id}',
                        site_id = '{$sid}',
                        title = '',
                        publish_status = 'draft',
                        created_at = '" . G5_TIME_YMDHIS . "',
                        updated_at = '" . G5_TIME_YMDHIS . "' ");
}

bp_log_activity($project_id, 'project_created', $actor, $topic);

alert('콘텐츠 프로젝트가 등록되었습니다.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $project_id);
