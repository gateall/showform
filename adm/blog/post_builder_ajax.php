<?php
// 통합 포스팅 제작 폼 AJAX 허브
include_once('./_common.php');
include_once(G5_ADMIN_PATH . '/blog/lib/blog_ai_service.lib.php');

$action = isset($_POST['action']) ? $_POST['action'] : '';
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

$project_table = bp_table('content_projects');
$post_table = bp_table('posts');

// complete_post와 export_file(html/txt)이 공유하는 HTML 조립 로직 - 한 곳에서만 관리한다.
function pb_build_post_html(array $state): string
{
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

    if (!empty($state['company_name'])) {
        $html .= "<div class='post-company-info' style='margin-top:20px; padding:15px; background:#f5f5f5;'>";
        $html .= "<strong>업체정보</strong><br>";
        $html .= "상호명: " . get_text($state['company_name']) . "<br>";
        if (!empty($state['company_tel'])) $html .= "전화번호: " . get_text($state['company_tel']) . "<br>";
        if (!empty($state['company_addr'])) $html .= "주소: " . get_text($state['company_addr']) . "<br>";
        if (!empty($state['company_link'])) $html .= "링크: <a href='" . get_text($state['company_link']) . "'>" . get_text($state['company_link']) . "</a>";
        $html .= "</div>\n";
    }

    return $html;
}

// 텍스트 다운로드용 - HTML 태그 없이 제목/도입/본문/마무리를 순서대로 이어붙인다.
function pb_build_post_text(array $state): string
{
    $lines = array();
    $lines[] = (string) ($state['post_title'] ?? '');
    $lines[] = '';
    if (!empty($state['intro_text'])) {
        $lines[] = $state['intro_text'];
        $lines[] = '';
    }
    if (!empty($state['body_blocks']) && is_array($state['body_blocks'])) {
        foreach ($state['body_blocks'] as $block) {
            if (!empty($block['title'])) {
                $lines[] = $block['title'];
            }
            if (!empty($block['content'])) {
                $lines[] = $block['content'];
            }
            $lines[] = '';
        }
    }
    if (!empty($state['closing_text'])) {
        $lines[] = $state['closing_text'];
    }
    return implode("\n", $lines);
}

header('Content-Type: application/json; charset=utf-8');

// 이 프로젝트 전체가 쓰는 권한체계(auth_check_menu, 코드 360050)와 통일한다 - super
// 전용으로 하드코딩되어 있으면 위임받은 제한관리자가 전부 차단된다.
$pb_auth_msg = auth_check_menu($auth, '360050', 'w', true);
if ($pb_auth_msg) {
    die(json_encode(['ok' => false, 'error' => $pb_auth_msg]));
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
        if (!is_array($state)) {
            $state = array();
        }

        $html = pb_build_post_html($state);

        // DB에 저장
        sql_query(" update {$post_table} 
                    set title = '" . sql_real_escape_string($state['post_title'] ?? '') . "',
                        body = '" . sql_real_escape_string($html) . "',
                        builder_state = '" . sql_real_escape_string($builder_state) . "',
                        updated_at = '" . G5_TIME_YMDHIS . "' 
                    where project_id = '{$project_id}' ");
                    
        $response['html'] = $html;
        $response['text'] = pb_build_post_text($state);
        $response['message'] = "포스팅이 성공적으로 완성되었습니다.";
        bp_log_activity($project_id, 'post_completed', bp_current_admin_id(), (string) ($state['post_title'] ?? ''));
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

    case 'analyze_material':
        // 글감(자유 텍스트)을 분석해 기획 항목을 자동 채움용으로 추출한다.
        $material = isset($_POST['material']) ? trim($_POST['material']) : '';
        if ($material === '') {
            die(json_encode(['ok' => false, 'error' => '분석할 글감을 입력해 주세요.']));
        }
        if (!function_exists('bp_ai_chat_request')) {
            die(json_encode(['ok' => false, 'error' => 'AI 서비스가 활성화되지 않았습니다.']));
        }

        $sys_prompt = "당신은 블로그 포스팅 기획을 돕는 편집자입니다. 반드시 JSON 형식으로만 답하세요.";
        $prompt = "다음 글감(메모, 상품정보, 참고문장 등)을 분석해서 블로그 포스팅 기획에 필요한 항목을 추출해 주세요.\n\n"
            . "[글감]\n{$material}\n\n"
            . "반드시 아래 JSON 형식만 출력하세요(다른 텍스트 없이): "
            . "{\"topic\": \"핵심 주제\", \"purpose\": \"글의 목적\", \"target_audience\": \"예상 독자\", "
            . "\"recommended_format\": \"정보형|상품소개형|후기형|FAQ형 중 하나\", \"main_keyword\": \"대표 키워드\", "
            . "\"sub_keywords\": \"보조 키워드(쉼표 구분)\", \"recommended_length\": \"예상 분량(예: 2000~2500자)\"}";

        $ai_result = bp_ai_chat_request($prompt, $sys_prompt);
        if (!$ai_result['ok']) {
            die(json_encode(['ok' => false, 'error' => '글감 분석 실패: ' . $ai_result['error']]));
        }

        $parsed = json_decode(trim($ai_result['message']), true);
        if (!is_array($parsed) || empty($parsed['topic'])) {
            die(json_encode(['ok' => false, 'error' => 'AI가 올바른 분석 결과를 반환하지 않았습니다. 다시 시도해 주세요.']));
        }

        $response['analysis'] = array(
            'topic' => isset($parsed['topic']) ? (string) $parsed['topic'] : '',
            'purpose' => isset($parsed['purpose']) ? (string) $parsed['purpose'] : '',
            'target_audience' => isset($parsed['target_audience']) ? (string) $parsed['target_audience'] : '',
            'recommended_format' => isset($parsed['recommended_format']) ? (string) $parsed['recommended_format'] : '',
            'main_keyword' => isset($parsed['main_keyword']) ? (string) $parsed['main_keyword'] : '',
            'sub_keywords' => isset($parsed['sub_keywords']) ? (string) $parsed['sub_keywords'] : '',
            'recommended_length' => isset($parsed['recommended_length']) ? (string) $parsed['recommended_length'] : '',
        );
        bp_log_activity($project_id, 'material_analyzed', bp_current_admin_id(), $response['analysis']['topic']);
        break;

    case 'ai_generate':
        // 통합 AI 호출 처리
        $type = isset($_POST['type']) ? $_POST['type'] : '';
        $builder_state = isset($_POST['builder_state']) ? json_decode($_POST['builder_state'], true) : [];
        
        // bp_ai_service.lib.php 가 include 되어있다고 가정 (_common.php 에서)
        if (!function_exists('bp_ai_chat_request')) {
            die(json_encode(['ok' => false, 'error' => 'AI 서비스가 활성화되지 않았습니다.']));
        }
        
        $prompt = '';
        $sys_prompt = "당신은 전문 블로그 마케터이자 숙련된 카피라이터입니다.";
        
        if ($type === 'direction') {
            $prompt = "다음 조건으로 블로그 포스팅 기획안(독자 타겟과 글의 방향)을 200자 이내로 1문단으로 작성해 주세요.\n";
            $prompt .= "- 광고주/서비스: " . ($builder_state['company_name'] ?? '알 수 없음') . "\n";
            $prompt .= "- 말투: " . ($builder_state['tone'] ?? '전문적') . "\n";
        } else if ($type === 'titles') {
            $prompt = "다음 키워드를 포함하여 매력적인 블로그 포스팅 제목 후보 5개를 번호 매겨 작성해 주세요.\n";
            $prompt .= "- 핵심 키워드: " . ($builder_state['main_keyword'] ?? '') . "\n";
            $prompt .= "- 보조 키워드: " . ($builder_state['sub_keywords'] ?? '') . "\n";
            $prompt .= "- 타겟 독자: " . ($builder_state['target_audience'] ?? '') . "\n";
        } else if ($type === 'intro') {
            $prompt = "다음 제목과 키워드를 바탕으로 독자의 공감을 이끌어내는 블로그 도입부(첫 1~2문단)를 작성해 주세요.\n";
            $prompt .= "- 제목: " . ($builder_state['post_title'] ?? '') . "\n";
            $prompt .= "- 핵심 키워드: " . ($builder_state['main_keyword'] ?? '') . "\n";
        } else if ($type === 'block') {
            $block_title = isset($_POST['block_title']) ? $_POST['block_title'] : '';
            $prompt = "전체 포스팅 중 다음 소제목에 해당하는 본문 구간(1~2문단)을 상세하고 자연스럽게 작성해 주세요.\n";
            $prompt .= "- 소제목: {$block_title}\n";
            $prompt .= "- 핵심 키워드: " . ($builder_state['main_keyword'] ?? '') . "\n";
            $prompt .= "- 말투: " . ($builder_state['tone'] ?? '전문적') . "\n";
        }
        
        // 공통 AI 요청 실행 (bp_ai_chat_request 사용)
        $ai_result = bp_ai_chat_request($prompt, $sys_prompt);
        if (!$ai_result['ok']) {
            die(json_encode(['ok' => false, 'error' => 'AI 생성 실패: ' . $ai_result['error']]));
        }
        
        $response['generated_text'] = trim($ai_result['message']);
        bp_log_activity($project_id, 'builder_ai_generated', bp_current_admin_id(), $type);
        break;

    case 'seo_check':
        $builder_state = isset($_POST['builder_state']) ? json_decode($_POST['builder_state'], true) : [];
        $main_kw = $builder_state['main_keyword'] ?? '';
        $title = $builder_state['post_title'] ?? '';
        
        $score = 100;
        $messages = [];
        
        if (empty($main_kw)) {
            $score -= 30;
            $messages[] = "대표 키워드가 설정되지 않았습니다.";
        } else if (mb_strpos($title, $main_kw) === false) {
            $score -= 20;
            $messages[] = "제목에 대표 키워드('{$main_kw}')가 포함되지 않았습니다.";
        } else {
            $messages[] = "제목 최적화 양호 (대표 키워드 포함됨).";
        }
        
        $body_len = 0;
        if (!empty($builder_state['body_blocks'])) {
            foreach ($builder_state['body_blocks'] as $b) {
                $body_len += mb_strlen($b['content']);
            }
        }
        if ($body_len < 500) {
            $score -= 20;
            $messages[] = "본문 길이가 너무 짧습니다 (현재 {$body_len}자, 권장 500자 이상).";
        } else {
            $messages[] = "본문 분량 양호 ({$body_len}자).";
        }
        
        $color = $score >= 80 ? 'green' : ($score >= 50 ? 'orange' : 'red');
        $html = "<div style='color:{$color}; font-weight:bold; font-size:1.2rem; margin-bottom:10px;'>SEO 점수: {$score}점</div>";
        $html .= "<ul>";
        foreach ($messages as $msg) {
            $html .= "<li>{$msg}</li>";
        }
        $html .= "</ul>";
        
        $response['html'] = $html;
        break;

    case 'export_file':
        // 서버에 파일로 남기지 않고 즉시 다운로드만 스트리밍한다.
        $export_type = isset($_POST['type']) ? $_POST['type'] : 'txt';
        $export_state = isset($_POST['builder_state']) ? json_decode($_POST['builder_state'], true) : [];
        if (!is_array($export_state)) {
            $export_state = array();
        }
        $filename_base = 'post-' . date('Ymd-His');

        if ($export_type === 'html') {
            $content = pb_build_post_html($export_state);
            header('Content-Type: text/html; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename_base . '.html"');
        } else if ($export_type === 'json') {
            $content = json_encode($export_state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename_base . '.json"');
        } else {
            // 윈도우 메모장이 BOM 없는 UTF-8 한글을 잘못된 인코딩으로 오인하는 경우가 있어 TXT만 BOM을 붙인다.
            // JSON은 RFC 8259상 BOM 금지, HTML은 Content-Type으로 charset이 전달되므로 불필요.
            $content = "\xEF\xBB\xBF" . pb_build_post_text($export_state);
            header('Content-Type: text/plain; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename_base . '.txt"');
        }

        bp_log_activity($project_id, 'post_exported', bp_current_admin_id(), $export_type);
        echo $content;
        exit;

    case 'load_seo_meta':
        // 기존 값이 있으면 화면에 먼저 보여주기 위함 - generate_seo_meta가 자동으로 덮어쓰지 않도록
        // (덮어쓰기는 프론트에서 사용자 확인을 받은 뒤에만 일어난다).
        if ($post_id === 0) {
            die(json_encode(['ok' => false, 'error' => '포스트 ID가 없습니다.']));
        }
        $seo_row = sql_fetch(" select meta_title, meta_description from {$post_table} where id = '{$post_id}' ");
        if (!$seo_row) {
            die(json_encode(['ok' => false, 'error' => '포스트를 찾을 수 없습니다.']));
        }
        $response['meta_title'] = $seo_row['meta_title'];
        $response['meta_description'] = $seo_row['meta_description'];
        break;

    case 'generate_seo_meta':
        if (!function_exists('bp_ai_chat_request')) {
            die(json_encode(['ok' => false, 'error' => 'AI 서비스가 활성화되지 않았습니다.']));
        }
        $seo_title = isset($_POST['post_title']) ? trim($_POST['post_title']) : '';
        $seo_body = isset($_POST['body_content']) ? trim($_POST['body_content']) : '';
        $seo_keyword = isset($_POST['main_keyword']) ? trim($_POST['main_keyword']) : '';
        if ($seo_title === '' && $seo_body === '') {
            die(json_encode(['ok' => false, 'error' => '제목이나 본문을 먼저 입력해 주세요.']));
        }

        $sys_prompt = "당신은 SEO 메타 설명 작성 전문가입니다. 반드시 JSON 형식으로만 답하세요.";
        $prompt = "다음 블로그 포스팅의 제목과 본문을 분석해서 검색결과에 노출될 메타 설명(meta description)을 작성해 주세요.\n\n"
            . "[제목]\n{$seo_title}\n\n"
            . "[본문 일부]\n" . mb_substr($seo_body, 0, 1000) . "\n\n"
            . ($seo_keyword !== '' ? "[대표 키워드]\n{$seo_keyword}\n\n" : '')
            . "조건: 반드시 한글 기준 80자 이상 160자 이하로 작성하고, "
            . ($seo_keyword !== '' ? "대표 키워드 '{$seo_keyword}'를 자연스럽게 1회 포함하며, " : '')
            . "클릭을 유도하는 자연스러운 문장으로 작성하세요. "
            . "반드시 아래 JSON 형식만 출력하세요(다른 텍스트 없이): {\"meta_description\": \"내용\"}";

        $ai_result = bp_ai_chat_request($prompt, $sys_prompt);
        if (!$ai_result['ok']) {
            die(json_encode(['ok' => false, 'error' => 'AI 생성 실패: ' . $ai_result['error']]));
        }
        $parsed = json_decode(trim($ai_result['message']), true);
        if (!is_array($parsed) || empty($parsed['meta_description'])) {
            die(json_encode(['ok' => false, 'error' => 'AI가 올바른 형식을 반환하지 않았습니다. 다시 시도해 주세요.']));
        }

        $meta_desc = trim((string) $parsed['meta_description']);
        $len = mb_strlen($meta_desc);
        $response['meta_description'] = $meta_desc;
        $response['length'] = $len;
        $response['length_ok'] = ($len >= 80 && $len <= 160);
        break;

    case 'save_seo_meta':
        if ($post_id === 0) {
            die(json_encode(['ok' => false, 'error' => '포스트 ID가 없습니다.']));
        }
        $meta_desc_save = isset($_POST['meta_description']) ? trim($_POST['meta_description']) : '';
        $len_save = mb_strlen($meta_desc_save);
        if ($len_save < 80 || $len_save > 160) {
            die(json_encode(['ok' => false, 'error' => "메타 설명은 80~160자 사이여야 합니다(현재 {$len_save}자)."]));
        }
        sql_query(" update {$post_table} set meta_description = '" . sql_real_escape_string($meta_desc_save) . "', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$post_id}' ");
        bp_log_activity($project_id, 'seo_meta_saved', bp_current_admin_id(), mb_substr($meta_desc_save, 0, 50));
        $response['message'] = '메타 설명이 저장되었습니다.';
        break;

    case 'chat_assistant':
        // 챗봇 비서 응답 처리
        if (!function_exists('bp_ai_chat_with_history')) {
            die(json_encode(['ok' => false, 'error' => 'AI 서비스가 활성화되지 않았습니다.']));
        }
        
        $builder_state = isset($_POST['builder_state']) ? json_decode($_POST['builder_state'], true) : [];
        if (!is_array($builder_state)) $builder_state = [];
        
        $step = isset($_POST['step']) ? (int)$_POST['step'] : 1;
        $chat_history_json = isset($_POST['chat_history']) ? $_POST['chat_history'] : '[]';
        $chat_history = json_decode($chat_history_json, true);
        if (!is_array($chat_history)) $chat_history = [];
        
        $user_message = isset($_POST['user_message']) ? trim($_POST['user_message']) : '';
        
        if (empty($user_message) && empty($chat_history)) {
            die(json_encode(['ok' => false, 'error' => '전송된 메시지가 없습니다.']));
        }

        // 컨텍스트 수집
        $title = $builder_state['post_title'] ?? '';
        $body = $builder_state['body_content'] ?? ''; // JS에서 body_content 로 넘긴다고 가정
        $keywords = ($builder_state['main_keyword'] ?? '') . ', ' . ($builder_state['sub_keywords'] ?? '');
        $material = $builder_state['material'] ?? '';
        $tone = $builder_state['tone'] ?? '전문적';
        
        $sys_prompt = "당신은 블로그 포스팅 작성을 돕는 전문 AI 비서입니다.\n";
        $sys_prompt .= "사용자의 요청을 분석하고, 대화에 응답하세요. \n";
        $sys_prompt .= "명령에 따라 작성/수정된 텍스트가 있다면 적용 대상(target)과 적용 방법(action)을 지정하여 반환해야 합니다.\n\n";
        $sys_prompt .= "[현재 작업 컨텍스트]\n";
        $sys_prompt .= "- 현재 단계: {$step}단계\n";
        $sys_prompt .= "- 현재 제목: {$title}\n";
        $sys_prompt .= "- 현재 본문: " . mb_substr($body, 0, 1000) . "...\n";
        $sys_prompt .= "- 키워드: {$keywords}\n";
        $sys_prompt .= "- 참고 글감: " . mb_substr($material, 0, 500) . "\n";
        $sys_prompt .= "- 작성 말투: {$tone}\n\n";
        $sys_prompt .= "[응답 형식]\n";
        $sys_prompt .= "반드시 아래 JSON 형식으로만 응답하세요.\n";
        $sys_prompt .= "{\n";
        $sys_prompt .= "  \"success\": true,\n";
        $sys_prompt .= "  \"action\": \"replace_title|append_body|replace_body|chat 중 택1\",\n";
        $sys_prompt .= "  \"target\": \"적용 대상 폼 요소 ID (pb_post_title, pb_body_content 등)\",\n";
        $sys_prompt .= "  \"content\": \"생성/수정된 텍스트 (단순 대화인 경우 빈 문자열)\",\n";
        $sys_prompt .= "  \"message\": \"사용자 채팅창에 보여질 친절한 안내 메시지 (줄바꿈 <br> 포함 가능)\"\n";
        $sys_prompt .= "}\n";
        
        $messages = [];
        $messages[] = ['role' => 'system', 'content' => $sys_prompt];
        
        // 이전 대화 기록 삽입 (최대 최근 10개로 제한하여 토큰 낭비 방지)
        $chat_history = array_slice($chat_history, -10);
        foreach ($chat_history as $msg) {
            // role: user or assistant
            $role = isset($msg['role']) && $msg['role'] === 'assistant' ? 'assistant' : 'user';
            $content = isset($msg['content']) ? $msg['content'] : '';
            if ($content) {
                $messages[] = ['role' => $role, 'content' => $content];
            }
        }
        
        if (!empty($user_message)) {
            $messages[] = ['role' => 'user', 'content' => $user_message];
        }
        
        $ai_result = bp_ai_chat_with_history($messages, true); // jsonMode = true
        
        if (!$ai_result['ok']) {
            die(json_encode(['ok' => false, 'error' => $ai_result['error']]));
        }
        
        $parsed = json_decode(trim($ai_result['content']), true);
        if (!is_array($parsed) || !isset($parsed['message'])) {
            die(json_encode(['ok' => false, 'error' => 'AI가 올바른 JSON 형식을 반환하지 않았습니다. 다시 시도해 주세요.']));
        }
        
        $response['ai_response'] = $parsed;
        break;

    default:
        $response['error'] = 'Unknown action';
        $response['ok'] = false;
        break;
}

echo json_encode($response);
exit;
