<?php
// 통합 포스팅 제작 폼 AJAX 허브
include_once('./_common.php');

$action = isset($_POST['action']) ? $_POST['action'] : '';
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

$project_table = bp_table('content_projects');
$post_table = bp_table('posts');

header('Content-Type: application/json; charset=utf-8');

if ($is_admin != 'super') {
    die(json_encode(['error' => '권한이 없습니다.']));
}

$response = ['ok' => true, 'action' => $action];

switch($action) {
    case 'save_step':
    case 'save_all':
        $step = isset($_POST['step']) ? $_POST['step'] : 'all';
        $advertiser_id = isset($_POST['advertiser_id']) ? (int)$_POST['advertiser_id'] : 0;
        $site_id = isset($_POST['site_id']) ? (int)$_POST['site_id'] : 0;
        $builder_state = isset($_POST['builder_state']) ? $_POST['builder_state'] : '{}';
        
        // Validation for step 1
        if ($project_id === 0) {
            if ($advertiser_id === 0) {
                die(json_encode(['ok' => false, 'error' => '광고주를 선택해야 합니다.']));
            }
            
            $uuid = get_uniqid();
            
            // 1. Create Project
            sql_query(" insert into {$project_table}
                        set project_uuid = '{$uuid}',
                            advertiser_id = '{$advertiser_id}',
                            primary_site_id = " . ($site_id > 0 ? "'{$site_id}'" : "NULL") . ",
                            topic = '통합 폼 작성',
                            created_by = '{$member['mb_id']}',
                            created_at = '" . G5_TIME_YMDHIS . "' ");
            $project_id = sql_insert_id();
            
            // 2. Create Post
            sql_query(" insert into {$post_table}
                        set project_id = '{$project_id}',
                            title = '새 포스팅',
                            builder_state = '" . sql_real_escape_string($builder_state) . "',
                            created_at = '" . G5_TIME_YMDHIS . "' ");
            $post_id = sql_insert_id();
        } else {
            // Update Existing
            if ($advertiser_id > 0) {
                sql_query(" update {$project_table} set advertiser_id = '{$advertiser_id}', primary_site_id = " . ($site_id > 0 ? "'{$site_id}'" : "NULL") . " where id = '{$project_id}' ");
            }
            
            // Fetch post_id
            $post = sql_fetch(" select id from {$post_table} where project_id = '{$project_id}' ");
            if ($post) {
                $post_id = $post['id'];
                sql_query(" update {$post_table} set builder_state = '" . sql_real_escape_string($builder_state) . "', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$post_id}' ");
            }
        }
        
        $response['project_id'] = $project_id;
        $response['post_id'] = $post_id;
        $response['message'] = $step == 'all' ? "전체 임시저장 완료" : "{$step}단계 저장 완료";
        break;

    case 'load_state':
        if ($project_id === 0) {
            die(json_encode(['ok' => false, 'error' => '프로젝트 ID가 없습니다.']));
        }
        $proj = sql_fetch(" select advertiser_id, primary_site_id from {$project_table} where id = '{$project_id}' ");
        $post = sql_fetch(" select builder_state from {$post_table} where project_id = '{$project_id}' ");
        
        if ($proj && $post) {
            $response['advertiser_id'] = $proj['advertiser_id'];
            $response['site_id'] = $proj['primary_site_id'];
            $response['builder_state'] = $post['builder_state'] ? json_decode($post['builder_state'], true) : [];
        } else {
            $response['ok'] = false;
            $response['error'] = '데이터를 찾을 수 없습니다.';
        }
        break;
        
    case 'complete_post':
        if ($project_id === 0) {
            die(json_encode(['ok' => false, 'error' => '프로젝트 ID가 없습니다.']));
        }
        $builder_state = isset($_POST['builder_state']) ? $_POST['builder_state'] : '{}';
        $state = json_decode($builder_state, true);
        
        // HTML 파싱 (Phase 2 핵심로직)
        $html = "<h1>" . get_text($state['post_title'] ?? '') . "</h1>\n";
        
        if (!empty($state['intro_text'])) {
            $html .= "<div class='post-intro'>" . nl2br(get_text($state['intro_text'])) . "</div>\n";
        }
        
        if (!empty($state['body_blocks']) && is_array($state['body_blocks'])) {
            foreach ($state['body_blocks'] as $block) {
                if (!empty($block['title'])) {
                    $html .= "<h2>" . get_text($block['title']) . "</h2>\n";
                }
                if (!empty($block['content'])) {
                    $html .= "<p>" . nl2br(get_text($block['content'])) . "</p>\n";
                }
            }
        }
        
        if (!empty($state['closing_text'])) {
            $html .= "<div class='post-closing'>" . nl2br(get_text($state['closing_text'])) . "</div>\n";
        }
        
        // 업체정보 첨부
        if (!empty($state['company_name'])) {
            $html .= "<div class='post-company-info' style='margin-top:20px; padding:15px; background:#f5f5f5;'>";
            $html .= "<strong>업체정보</strong><br>";
            $html .= "상호명: " . get_text($state['company_name']) . "<br>";
            if (!empty($state['company_tel'])) $html .= "전화번호: " . get_text($state['company_tel']) . "<br>";
            if (!empty($state['company_addr'])) $html .= "주소: " . get_text($state['company_addr']) . "<br>";
            if (!empty($state['company_link'])) $html .= "링크: <a href='".get_text($state['company_link'])."'>".get_text($state['company_link'])."</a>";
            $html .= "</div>\n";
        }

        // DB에 저장
        sql_query(" update {$post_table} 
                    set title = '" . sql_real_escape_string($state['post_title'] ?? '') . "',
                        body = '" . sql_real_escape_string($html) . "',
                        builder_state = '" . sql_real_escape_string($builder_state) . "',
                        updated_at = '" . G5_TIME_YMDHIS . "' 
                    where project_id = '{$project_id}' ");
                    
        $response['html'] = $html;
        $response['message'] = "포스팅이 성공적으로 완성되었습니다.";
        break;

    case 'upload_images':
        if ($project_id === 0) {
            die(json_encode(['ok' => false, 'error' => '프로젝트를 먼저 생성(1단계 저장)해 주세요.']));
        }
        $post = sql_fetch(" select id from {$post_table} where project_id = '{$project_id}' ");
        if (!$post) {
            die(json_encode(['ok' => false, 'error' => '포스트 정보가 없습니다.']));
        }
        $post_id_val = $post['id'];
        
        $upload_dir = G5_DATA_PATH . '/blog_images';
        @mkdir($upload_dir, G5_DIR_PERMISSION);
        @chmod($upload_dir, G5_DIR_PERMISSION);

        $uploaded = [];
        if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
            $image_table = bp_table('images');
            foreach ($_FILES['images']['name'] as $i => $name) {
                if ($_FILES['images']['error'][$i] === 0) {
                    $tmp_name = $_FILES['images']['tmp_name'][$i];
                    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) continue;
                    
                    // WebP로 강제 변환 (간단 처리, GD 필요)
                    $new_filename = 'pb_' . time() . '_' . md5(uniqid()) . '.webp';
                    $dest = $upload_dir . '/' . $new_filename;
                    
                    $img = null;
                    if ($ext == 'jpg' || $ext == 'jpeg') $img = @imagecreatefromjpeg($tmp_name);
                    elseif ($ext == 'png') $img = @imagecreatefrompng($tmp_name);
                    elseif ($ext == 'gif') $img = @imagecreatefromgif($tmp_name);
                    elseif ($ext == 'webp') $img = @imagecreatefromwebp($tmp_name);
                    
                    if ($img) {
                        imagewebp($img, $dest, 85);
                        imagedestroy($img);
                        
                        $filesize = filesize($dest);
                        $file_hash = md5_file($dest);
                        
                        sql_query(" insert into {$image_table}
                                    set post_id = '{$post_id_val}',
                                        original_name = '" . sql_real_escape_string($name) . "',
                                        saved_name = '{$new_filename}',
                                        image_type = 'content',
                                        filesize = '{$filesize}',
                                        file_hash = '{$file_hash}',
                                        description = '',
                                        created_at = '" . G5_TIME_YMDHIS . "' ");
                        
                        $img_id = sql_insert_id();
                        $uploaded[] = [
                            'id' => $img_id,
                            'url' => G5_DATA_URL . '/blog_images/' . $new_filename,
                            'name' => $name
                        ];
                    }
                }
            }
        }
        $response['images'] = $uploaded;
        $response['message'] = count($uploaded) . "장의 이미지가 업로드 되었습니다.";
        break;

    case 'publish_post':
        if ($project_id === 0 || $post_id === 0) {
            die(json_encode(['ok' => false, 'error' => '포스팅을 먼저 완성해 주세요.']));
        }
        $site_id = isset($_POST['site_id']) ? (int)$_POST['site_id'] : 0;
        if ($site_id === 0) {
            die(json_encode(['ok' => false, 'error' => '사이트가 선택되지 않았습니다.']));
        }
        
        $post = sql_fetch(" select * from {$post_table} where id = '{$post_id}' ");
        $target_table = bp_table('post_targets');
        $job_table = bp_table('publish_jobs');
        
        // Target 확인/생성
        $target = sql_fetch(" select id from {$target_table} where post_id = '{$post_id}' and site_id = '{$site_id}' ");
        if (!$target) {
            sql_query(" insert into {$target_table}
                        set post_id = '{$post_id}',
                            site_id = '{$site_id}',
                            title = '" . sql_real_escape_string($post['title']) . "',
                            body = '" . sql_real_escape_string($post['body']) . "',
                            publish_status = 'draft',
                            created_at = '" . G5_TIME_YMDHIS . "' ");
            $target_id = sql_insert_id();
        } else {
            $target_id = $target['id'];
            sql_query(" update {$target_table}
                        set title = '" . sql_real_escape_string($post['title']) . "',
                            body = '" . sql_real_escape_string($post['body']) . "',
                            updated_at = '" . G5_TIME_YMDHIS . "'
                        where id = '{$target_id}' ");
        }
        
        // Job 등록
        sql_query(" insert into {$job_table}
                    set post_target_id = '{$target_id}',
                        status = 'pending',
                        created_at = '" . G5_TIME_YMDHIS . "' ");
                        
        // 프로젝트 상태 업데이트
        sql_query(" update {$project_table} set status = 'publish_pending' where id = '{$project_id}' ");
        
        $response['message'] = "발행 대기열(Queue)에 등록되었습니다. 스케줄러가 곧 처리합니다.";
        break;

    default:
        $response['error'] = 'Unknown action';
        $response['ok'] = false;
        break;
}

echo json_encode($response);
exit;
