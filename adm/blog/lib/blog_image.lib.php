<?php
if (!defined('_GNUBOARD_')) exit;

/**
 * 블로그 자동화: 이미지 생성, 최적화 및 파이프라인 관리 모듈
 * Stage 6: Image Pipeline
 */

// AI 이미지 생성 요청 (Mock / Stub for Flux etc.)
function bp_generate_ai_image(int $project_id, int $preset_id, string $topic): array
{
    $tbl_presets = bp_table('image_presets');
    $preset = sql_fetch(" select * from {$tbl_presets} where id = '{$preset_id}' ");
    if (!$preset) {
        return array('ok' => false, 'error' => '프리셋을 찾을 수 없습니다.');
    }

    $provider_code = $preset['provider_code'];
    $tbl_providers = bp_table('ai_providers');
    $provider = sql_fetch(" select * from {$tbl_providers} where provider_code = '" . sql_real_escape_string($provider_code) . "' and supports_image = 'Y' ");
    
    if (!$provider) {
        return array('ok' => false, 'error' => "이미지 생성 지원 공급자 '{$provider_code}' 설정을 찾을 수 없습니다.");
    }

    $prompt = str_replace('{topic}', $topic, $preset['prompt_template']);
    
    // TODO: 실제 API 호출 (예: OpenAI DALL-E, BFL Flux 등)
    // 현재 MVP에서는 생성되었다고 가정하고 임시 더미 이미지를 반환
    $dummy_url = 'https://picsum.photos/' . $preset['width'] . '/' . $preset['height'] . '?random=' . time();
    
    // 다운로드 및 로컬 최적화
    $res = bp_download_and_optimize_image($dummy_url, $project_id);
    if ($res['ok']) {
        // AI 메타데이터 업데이트
        $tbl_img = bp_table('images');
        sql_query(" update {$tbl_img} set is_ai_generated = 1, ai_prompt = '" . sql_real_escape_string($prompt) . "' where id = '{$res['image_id']}' ");
    }
    
    return $res;
}

// GD 확장이 필요한 이미지 최적화 함수
function bp_optimize_local_file(string $filepath, int $project_id = 0, string $original_name = '', int $is_ai_generated = 0, string $ai_prompt = ''): array
{
    if (!is_file($filepath)) {
        return array('ok' => false, 'error' => '파일이 존재하지 않습니다.');
    }

    $hash = hash_file('md5', $filepath); // md5 기반 해시 처리 (SHA256도 가능)
    
    // 중복 체크
    $tbl_img = bp_table('images');
    $safe_hash = sql_real_escape_string($hash);
    $existing = sql_fetch(" select * from {$tbl_img} where file_hash = '{$safe_hash}' limit 1 ");
    
    if ($existing) {
        @unlink($filepath); // 중복이므로 새로 다운받은 임시 파일 삭제
        return array('ok' => true, 'image_id' => $existing['id'], 'url' => G5_URL . '/' . $existing['file_path']);
    }

    $mime = mime_content_type($filepath);
    $ext = 'jpg';
    if (strpos($mime, 'png') !== false) $ext = 'png';
    elseif (strpos($mime, 'webp') !== false) $ext = 'webp';
    elseif (strpos($mime, 'gif') !== false) $ext = 'gif';

    $img_dir = G5_DATA_PATH . '/blog_images';
    if (!is_dir($img_dir)) {
        @mkdir($img_dir, G5_DIR_PERMISSION, true);
    }
    
    $new_filename = time() . '_' . substr(md5((string) time() . $filepath), 0, 8) . '.webp';
    $new_filepath = $img_dir . '/' . $new_filename;
    
    // WebP 변환 시도
    $converted = false;
    if (function_exists('imagecreatefromjpeg') && function_exists('imagewebp')) {
        $img = null;
        if ($ext == 'jpg') $img = @imagecreatefromjpeg($filepath);
        elseif ($ext == 'png') {
            $img = @imagecreatefrompng($filepath);
            if ($img) {
                imagepalettetotruecolor($img);
                imagealphablending($img, true);
                imagesavealpha($img, true);
            }
        }
        elseif ($ext == 'gif') $img = @imagecreatefromgif($filepath);
        elseif ($ext == 'webp') $img = @imagecreatefromwebp($filepath);
        
        if ($img) {
            $converted = @imagewebp($img, $new_filepath, 80);
            imagedestroy($img);
        }
    }

    if ($converted) {
        @unlink($filepath); // 원본 삭제
        $final_path = $new_filepath;
        $final_name = $new_filename;
        $final_mime = 'image/webp';
    } else {
        // 변환 실패 시 원본 그대로 이동
        $final_name = time() . '_' . substr(md5((string) time()), 0, 8) . '.' . $ext;
        $final_path = $img_dir . '/' . $final_name;
        @rename($filepath, $final_path);
        $final_mime = $mime;
    }

    $rel_path = 'data/blog_images/' . $final_name;
    $filesize = filesize($final_path);
    if (!$original_name) {
        $original_name = basename($filepath);
    }

    // 새 이미지 DB 등록
    sql_query(" insert into {$tbl_img} 
                set project_id = '{$project_id}',
                    filename = '" . sql_real_escape_string($final_name) . "',
                    original_name = '" . sql_real_escape_string($original_name) . "',
                    file_path = '" . sql_real_escape_string($rel_path) . "',
                    file_size = '{$filesize}',
                    file_hash = '{$safe_hash}',
                    mime_type = '" . sql_real_escape_string($final_mime) . "',
                    is_ai_generated = '{$is_ai_generated}',
                    ai_prompt = '" . sql_real_escape_string($ai_prompt) . "',
                    created_by = 'system',
                    created_at = NOW() ");
                    
    $image_id = sql_insert_id();

    return array('ok' => true, 'image_id' => $image_id, 'url' => G5_URL . '/' . $rel_path);
}

function bp_download_and_optimize_image(string $url, int $project_id = 0): array
{
    if (!function_exists('curl_init')) {
        return array('ok' => false, 'error' => 'curl 모듈이 없습니다.');
    }
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $data = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || !$data) {
        return array('ok' => false, 'error' => '이미지 다운로드 실패 (HTTP ' . $http_code . ')');
    }
    
    $tmp_file = G5_DATA_PATH . '/blog_images/tmp_' . md5($url) . '.img';
    file_put_contents($tmp_file, $data);
    
    return bp_optimize_local_file($tmp_file, $project_id, basename($url));
}

// 포스트 본문의 외부 이미지를 로컬로 가져와 변환하고 치환한다.
function bp_replace_post_images(int $post_id): array
{
    $tbl_posts = bp_table('posts');
    $tbl_post_images = bp_table('post_images');
    
    $post = sql_fetch(" select * from {$tbl_posts} where id = '{$post_id}' ");
    if (!$post) {
        return array('ok' => false, 'error' => '포스트를 찾을 수 없습니다.');
    }
    
    $body = $post['body'];
    $project_id = (int)$post['project_id'];
    
    preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $body, $matches);
    if (empty($matches[1])) {
        return array('ok' => true, 'count' => 0); // 이미지 없음
    }
    
    $replaced_count = 0;
    foreach ($matches[1] as $src) {
        if (strpos($src, G5_URL) === 0 || strpos($src, '/') === 0) {
            continue; // 이미 로컬이거나 상대경로면 패스
        }
        
        $res = bp_download_and_optimize_image($src, $project_id);
        if ($res['ok']) {
            $body = str_replace($src, $res['url'], $body);
            $replaced_count++;
            
            // post_images 매핑 추가
            $img_id = $res['image_id'];
            sql_query(" insert ignore into {$tbl_post_images} set post_id = '{$post_id}', image_id = '{$img_id}', usage_type = 'body' ");
        }
    }
    
    if ($replaced_count > 0) {
        $safe_body = sql_real_escape_string($body);
        sql_query(" update {$tbl_posts} set body = '{$safe_body}', updated_at = NOW() where id = '{$post_id}' ");
    }
    
    return array('ok' => true, 'count' => $replaced_count);
}
