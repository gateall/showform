<?php
include_once('./_common.php');
if (!$is_admin) {
    alert('관리자만 접근 가능합니다.');
}
auth_check_menu($auth, '360000', 'w');

$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'create_draft') {
    // 임시 프로젝트부터 만들어야 함 (project_id 필수)
    // 현재는 광고주(advertiser_id)와 프로젝트(project_id)가 연결되어야 하는데,
    // 최소한의 임시 프로젝트를 생성하거나, 혹은 폼에서 받아야 함.

    // advertiser_id를 하드코딩(1)하지 않고 실제 존재하는 광고주를 조회 (FK 제약 위반으로 인한 무음 실패 방지)
    $advertiser = sql_fetch("SELECT id FROM {$g5['table_prefix']}blog_advertisers ORDER BY id ASC LIMIT 1");
    if (!$advertiser) {
        alert('등록된 광고주가 없습니다. 먼저 광고주를 등록해 주세요.', G5_URL . '/manager' . '/blog/advertiser_form.php');
    }
    $advertiser_id = (int)$advertiser['id'];

    // 임시 프로젝트 생성
    $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );

    $sql = "INSERT INTO {$g5['table_prefix']}blog_content_projects
            SET project_uuid = '{$uuid}', advertiser_id = '{$advertiser_id}', topic = '임시 프로젝트', status = 'draft', created_by = '{$member['mb_id']}', created_at = NOW()";
    sql_query($sql);
    $project_id = sql_insert_id();
    if (!$project_id) {
        alert('임시 프로젝트 생성에 실패했습니다.', G5_URL . '/manager' . '/blog/blog_dashboard.php');
    }
    
    // 포스팅 생성
    $sql = "INSERT INTO {$g5['table_prefix']}blog_posts
            SET project_id = '{$project_id}', title = '새로운 포스팅', created_at = NOW()";
    sql_query($sql);
    $post_id = sql_insert_id();
    if (!$post_id) {
        alert('포스팅 생성에 실패했습니다.', G5_URL . '/manager' . '/blog/blog_dashboard.php');
    }
    
    goto_url(G5_URL . '/blog-studio/write.php?post_id=' . $post_id);
}

alert('잘못된 요청입니다.');
