<?php
// 통합 포스팅 제작 폼 AJAX 허브 (카드 기반 스튜디오 UI)
include_once('./_common.php');
include_once(G5_ADMIN_PATH . '/blog/lib/blog_ai_service.lib.php');
include_once(G5_ADMIN_PATH . '/blog/lib/blog_quality.lib.php');
include_once(G5_ADMIN_PATH . '/blog/lib/blog_image.lib.php');

$action = isset($_POST['action']) ? $_POST['action'] : '';
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;

$project_table = bp_table('content_projects');
$post_table = bp_table('posts');
$title_candidates_table = bp_table('content_title_candidates');
$post_sections_table = bp_table('post_sections');

header('Content-Type: application/json; charset=utf-8');

$pb_auth_msg = auth_check_menu($auth, '360050', 'w', true);
if ($pb_auth_msg) {
    die(json_encode(['ok' => false, 'error' => $pb_auth_msg]));
}

$response = ['ok' => true, 'action' => $action];

// AI에게 {"items": [...]} 형태로 문자열 목록을 요청했을 때, json_object 응답 모드가
// 강제하는 "최상위는 객체" 제약 때문에 모델이 items가 아닌 다른 키로 감싸거나 그냥
// 배열만 주는 경우까지 대비해서 실제 목록을 뽑아낸다(generate_tags/generate_image_prompts
// 둘 다 같은 모양의 응답을 기대하므로 공용으로 뺐다).
// generate_all_cards가 쓰는 본문 구조 템플릿 목록 - 1단계 컨트롤 패널에서 사용자가
// 골라서 structure_template으로 넘긴다. 각 항목의 sections는 마지막 "방문팁류" 마무리
// 섹션을 제외한 본문 섹션들이고, closing_title은 업체 정보가 들어갈 마지막 섹션 제목이다.
function bp_get_structure_templates(): array
{
    return array(
        'kiseungjeonggyeol' => array(
            'label' => '기승전결형',
            'sections' => array(
                array('type' => 'intro', 'title' => '기(도입부)'),
                array('type' => 'section', 'title' => '승(전개)'),
                array('type' => 'section', 'title' => '전(전환)'),
                array('type' => 'section', 'title' => '결(마무리)'),
            ),
            'closing_title' => '방문팁',
        ),
        'visit_flow' => array(
            'label' => '방문형(계기·특징·상세) - 음식점·카페·숙박 등 방문 업종에 적합',
            'sections' => array(
                array('type' => 'intro', 'title' => '도입부'),
                array('type' => 'section', 'title' => '계기'),
                array('type' => 'section', 'title' => '주요 특징'),
                array('type' => 'section', 'title' => '상세 내용'),
                array('type' => 'section', 'title' => '총평'),
            ),
            'closing_title' => '방문 팁',
        ),
        'problem_solution' => array(
            'label' => '문제해결형 - 병원·법률·인테리어 등 전문 서비스 업종에 적합',
            'sections' => array(
                array('type' => 'intro', 'title' => '문제 제기'),
                array('type' => 'section', 'title' => '원인·배경'),
                array('type' => 'section', 'title' => '해결 방법'),
                array('type' => 'section', 'title' => '사례·근거'),
            ),
            'closing_title' => '상담·문의 안내',
        ),
    );
}

function bp_extract_json_item_list(string $raw): ?array
{
    $parsed = json_decode(trim($raw), true);
    if (is_array($parsed) && isset($parsed['items']) && is_array($parsed['items'])) {
        return $parsed['items'];
    }
    if (is_array($parsed) && array_keys($parsed) === range(0, count($parsed) - 1)) {
        return $parsed; // 모델이 바로 배열로 준 경우
    }
    if (is_array($parsed)) {
        foreach ($parsed as $maybe_list) {
            if (is_array($maybe_list) && ($maybe_list === array() || array_keys($maybe_list) === range(0, count($maybe_list) - 1))) {
                return $maybe_list;
            }
        }
    }
    return null;
}

switch($action) {
    case 'save_step':
    case 'save_state':
        $builder_state = isset($_POST['builder_state']) ? $_POST['builder_state'] : '{}';
        
        if ($project_id === 0) {
            die(json_encode(['ok' => false, 'error' => '프로젝트 ID가 없습니다.']));
        }
        
        $post = sql_fetch(" select id from {$post_table} where project_id = '{$project_id}' ");
        if ($post) {
            $post_id = $post['id'];
            sql_query(" update {$post_table} set builder_state = '" . sql_real_escape_string($builder_state) . "', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$post_id}' ");
        } else {
            sql_query(" insert into {$post_table}
                        set project_id = '{$project_id}',
                            title = '새 포스팅',
                            builder_state = '" . sql_real_escape_string($builder_state) . "',
                            created_at = '" . G5_TIME_YMDHIS . "' ");
            $post_id = sql_insert_id();
        }
        
        // 정규화 데이터 적재 (점진적 전환 - JSON과 동시 저장)
        $state = json_decode($builder_state, true);
        if (is_array($state) && isset($state['cards']) && is_array($state['cards'])) {
            foreach ($state['cards'] as $idx => $card) {
                if (!isset($card['id'])) continue;
                $card_id = sql_real_escape_string($card['id']);
                $type = $card['type'] ?? 'section';
                $content = sql_real_escape_string($card['content'] ?? '');
                $state_val = $card['state'] ?? 'draft';
                // 상태 확장을 통한 메타 저장
                $locked_flag = !empty($card['locked']) ? 1 : 0;
                $status = $state_val;
                if ($locked_flag) $status .= '_locked';
                
                if ($type === 'title') {
                    $is_selected = ($state_val === 'primary' || $state_val === 'selected') ? 1 : 0;
                    $exists = sql_fetch(" select id from {$title_candidates_table} where project_id = '{$project_id}' and title = '{$content}' ");
                    if (!$exists) {
                        sql_query(" insert into {$title_candidates_table} 
                                    set project_id = '{$project_id}', title = '{$content}', source = 'ai', is_selected = '{$is_selected}', created_at = '" . G5_TIME_YMDHIS . "' ");
                    } else {
                        sql_query(" update {$title_candidates_table} set is_selected = '{$is_selected}' where id = '{$exists['id']}' ");
                    }
                } else {
                    $sort_order = $idx;
                    // content_id로 식별 불가하므로 임시적으로 builder_state에 의존.
                    // 완벽한 정규화 전환을 위해 임시로만 동작시킴
                }
            }
        }
        
        $response['project_id'] = $project_id;
        $response['post_id'] = $post_id;
        $response['message'] = "임시저장 완료";
        break;

    case 'save_card':
        if ($post_id === 0) {
            die(json_encode(['ok' => false, 'error' => '포스트 ID가 없습니다.']));
        }
        $card_id = isset($_POST['card_id']) ? $_POST['card_id'] : '';
        $card_type = isset($_POST['card_type']) ? $_POST['card_type'] : 'section';
        $content = isset($_POST['content']) ? $_POST['content'] : '';
        $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
        $status = isset($_POST['status']) ? $_POST['status'] : 'draft';
        $locked = isset($_POST['locked']) && $_POST['locked'] == 'true' ? 1 : 0;
        
        if ($locked) $status .= '_locked';

        // 카드 단위 저장 (본문 섹션)
        if ($card_type !== 'title') {
            // DB에 고유 식별자가 없으므로(기존 JSON 구조), 여기서는 post_sections에 insert/update.
            // MVP 범위 내에서는 기존처럼 builder_state를 주력으로 쓰면서, 부분 저장을 위해 DB에도 남김.
            // (차후 JSON fallback 제거 시 활용)
            sql_query(" insert into {$post_sections_table} 
                        set post_id = '{$post_id}', sort_order = '{$sort_order}', section_type = '" . sql_real_escape_string($card_type) . "', 
                            content = '" . sql_real_escape_string($content) . "', status = '" . sql_real_escape_string($status) . "' 
                        ON DUPLICATE KEY UPDATE content = VALUES(content), status = VALUES(status), sort_order = VALUES(sort_order) ");
        }
        
        $response['message'] = '카드 저장 성공';
        break;

    case 'update_card_state':
    case 'delete_card':
        // 클라이언트에서 부분 저장용으로 호출, 실제 DB 분리 전까지는 OK 응답만 줌.
        // 현재는 builder_state 전체 저장이 병행되고 있으므로 데이터가 보존됨.
        $response['message'] = '상태 변경 완료';
        break;

    case 'load_state':
        if ($project_id === 0) {
            die(json_encode(['ok' => false, 'error' => '프로젝트 ID가 없습니다.']));
        }
        $proj = sql_fetch(" select advertiser_id, primary_site_id from {$project_table} where id = '{$project_id}' ");
        $post = sql_fetch(" select builder_state, tags, hashtags from {$post_table} where project_id = '{$project_id}' ");

        if ($proj && $post) {
            $response['advertiser_id'] = $proj['advertiser_id'];
            $response['site_id'] = $proj['primary_site_id'];
            $response['builder_state'] = $post['builder_state'] ? json_decode($post['builder_state'], true) : [];
            // tags는 콤마구분(기존 관례), hashtags는 "#태그" 공백구분(기존 관례) - bp_table 등에서
            // 이미 쓰던 저장 형식 그대로 읽어서 배열로만 풀어준다.
            $response['keywords'] = $post['tags'] !== '' ? array_values(array_filter(array_map('trim', explode(',', $post['tags'])))) : [];
            $response['hashtags'] = $post['hashtags'] !== '' ? array_values(array_filter(array_map('trim', explode(' ', $post['hashtags'])))) : [];
        } else {
            $response['ok'] = false;
            $response['error'] = '데이터를 찾을 수 없습니다.';
        }
        break;

    case 'generate_all_cards':
        if (!function_exists('bp_ai_chat_request')) {
            die(json_encode(['ok' => false, 'error' => 'AI 서비스가 활성화되지 않았습니다.']));
        }
        if (!bp_ai_global_enabled()) {
            die(json_encode(['ok' => false, 'error' => '관리자가 AI 기능을 전체적으로 꺼두었습니다. 설정 > AI 설정에서 켜주세요.']));
        }
        if (bp_ai_is_disabled_for_project($project_id)) {
            die(json_encode(['ok' => false, 'error' => '이 프로젝트는 AI 사용이 꺼져 있습니다. 1단계에서 AI 사용 안 함을 해제하거나, 카드를 직접 작성해 주세요.']));
        }

        $raw_material = isset($_POST['raw_material']) ? trim($_POST['raw_material']) : '';
        if (empty($raw_material)) {
            die(json_encode(['ok' => false, 'error' => '글감이 제공되지 않았습니다.']));
        }
        
        $locked_cards = isset($_POST['locked_cards']) ? json_decode($_POST['locked_cards'], true) : [];
        $locked_info = "";
        if (!empty($locked_cards) && is_array($locked_cards)) {
            $locked_info = "아래는 이미 확정되어 잠금(locked) 처리된 내용입니다. 새로 생성할 초안 구조에서 이 부분들은 기존 내용 그대로 배치되도록 하세요.\n";
            foreach($locked_cards as $lc) {
                $locked_info .= "[{$lc['type']}] " . ($lc['title'] ?? '') . "\n" . $lc['content'] . "\n\n";
            }
        }

        // "방문팁" 마무리 섹션에 실제 업체 정보를 넣게 하려면 광고주 연락처가 필요하다.
        $adv_table = bp_table('advertisers');
        $advertiser = sql_fetch(" select a.name, a.phone, a.email, a.address, a.domain, a.consult_url
                                   from {$project_table} p
                                   join {$adv_table} a on a.id = p.advertiser_id
                                   where p.id = '{$project_id}' ");
        // 1단계 컨트롤 패널의 "업체 정보 삽입" 체크박스가 고른 항목만 포함한다. 이 파라미터
        // 자체가 없으면(옛 프론트엔드 캐시 등) 기존처럼 있는 값 전부를 포함해 하위호환을 지킨다.
        $contact_field_labels = array(
            'name' => '상호', 'address' => '주소', 'phone' => '전화',
            'email' => '이메일', 'domain' => '웹사이트', 'consult_url' => '상담/SNS 주소',
        );
        if (isset($_POST['contact_fields'])) {
            $allowed_fields = json_decode($_POST['contact_fields'], true);
            if (!is_array($allowed_fields)) $allowed_fields = array();
        } else {
            $allowed_fields = array_keys($contact_field_labels);
        }

        $contact_lines = "";
        if ($advertiser) {
            foreach ($contact_field_labels as $field_key => $field_label) {
                if (!empty($advertiser[$field_key]) && in_array($field_key, $allowed_fields, true)) {
                    $contact_lines .= "- {$field_label}: {$advertiser[$field_key]}\n";
                }
            }
        }

        // 업종별로 글 전개 구도가 다를 수 있어(예: 음식점은 방문형, 병원은 문제해결형) 구조를
        // bp_get_structure_templates()에서 선택 가능한 샘플로 정의해두고, 1단계 컨트롤 패널에서
        // 고른 키를 받아 그 구조에 맞춰 섹션 목록과 JSON 예시를 동적으로 만든다. 예전에는
        // 기승전결 구조 하나만 하드코딩되어 있었다.
        $structure_templates = bp_get_structure_templates();
        $structure_key = isset($_POST['structure_template']) ? trim($_POST['structure_template']) : '';
        if (!isset($structure_templates[$structure_key])) {
            $structure_key = 'kiseungjeonggyeol';
        }
        $structure = $structure_templates[$structure_key];

        $sys_prompt = "당신은 블로그 포스팅 초안을 설계하고 작성하는 수석 에디터입니다.\n";
        $sys_prompt .= "제공된 글감을 분석하여, 아래 \"{$structure['label']}\" 구조로 블로그 포스팅 전체를 한 번에 작성해야 합니다.\n\n";
        $sys_prompt .= "- 제목 5개 (매력적인 제목 후보)\n";
        foreach ($structure['sections'] as $sec) {
            $sys_prompt .= "- {$sec['title']}: 상부/하부 2개 문단으로 구성\n";
        }
        $sys_prompt .= "- {$structure['closing_title']}: 마지막 안내 멘트 다음에 아래 업체 정보를 자연스럽게 포함\n";
        if ($contact_lines !== "") {
            $sys_prompt .= $contact_lines;
        } else {
            $sys_prompt .= "  (등록된 업체 정보가 없으니 일반적인 방문 안내 멘트로만 마무리하세요)\n";
        }
        $target_length = isset($_POST['target_length']) ? max(200, (int) $_POST['target_length']) : 2000;
        $sys_prompt .= "\n제목을 제외한 본문 전체(모든 섹션 + {$structure['closing_title']})의 분량은 약 {$target_length}자를 목표로 하세요.\n";
        if ($locked_info !== "") {
            $sys_prompt .= "단, 다음 잠긴(locked) 내용들은 새 초안에 반드시 포함하고 내용을 덮어쓰지 마십시오.\n{$locked_info}\n";
        }

        // AI 글 생성 조건 - post_builder.php 1단계에서 체크한 조건(blog_generation_rules)의
        // rule_instruction만 골라 프롬프트에 그대로 얹는다. 조건 "이름"만 보내면 AI가 정확히
        // 못 지킬 수 있어(사용자 지적), 반드시 상세 지시문 본문을 전달한다.
        $selected_rule_ids = isset($_POST['selected_rule_ids']) ? json_decode($_POST['selected_rule_ids'], true) : [];
        if (is_array($selected_rule_ids) && !empty($selected_rule_ids)) {
            $selected_rule_ids = array_values(array_filter(array_map('intval', $selected_rule_ids)));
        } else {
            $selected_rule_ids = [];
        }
        if (!empty($selected_rule_ids)) {
            $rules_table = bp_table('generation_rules');
            $rule_ids_sql = implode(',', $selected_rule_ids);
            $rules_res = sql_query(" select rule_instruction from {$rules_table}
                                      where id in ({$rule_ids_sql}) and is_active = 'Y'
                                      order by rule_type, sort_order, id ");
            $rule_lines = [];
            while ($rule_row = sql_fetch_array($rules_res)) {
                $rule_lines[] = trim($rule_row['rule_instruction']);
            }
            if (!empty($rule_lines)) {
                $sys_prompt .= "\n[글 작성 조건] 아래 조건을 모두 준수하여 글을 작성하세요. 조건끼리 충돌하면 먼저 나온 조건을 우선하세요.\n";
                foreach ($rule_lines as $i => $line) {
                    $sys_prompt .= ($i + 1) . ". {$line}\n";
                }
            }
        }
        $extra_instruction = isset($_POST['extra_instruction']) ? trim($_POST['extra_instruction']) : '';
        if ($extra_instruction !== '') {
            $sys_prompt .= "\n[이번 글 추가 지시]\n{$extra_instruction}\n";
        }

        // OpenAI json_object 응답 모드는 최상위가 반드시 객체여야 한다(배열 자체를 최상위로
        // 반환할 수 없음) - 그래서 배열을 예시로 보여주면 모델이 임의의 키로 감싸 버려서(예:
        // {"posts":[...]}) 아래 unwrap 로직(cards 키만 확인)이 못 찾는 경우가 있었다. 예시 자체를
        // {"cards":[...]} 형태로 줘서 모델이 실제로 쓸 키 이름을 명시적으로 고정시킨다.
        // type은 반드시 intro/section/cta 중 하나로 고정한다 - 다른 값을 주면 화면에서
        // 카드 제목이 "기타"로만 표시되던 문제가 있었다. title도 예시와 동일하게 써야
        // 프론트가 카드 미니맵에서 구조를 구분해 보여줄 수 있다.
        $sys_prompt .= "반드시 아래 JSON 객체 형식으로만 응답하세요. 최상위는 객체이고, 그 안의 \"cards\" 키에 배열을 담습니다.\n";
        $sys_prompt .= "type은 반드시 title, intro, section, cta 중 하나여야 합니다(다른 값 금지). 각 카드의 \"title\"은 아래 예시에 쓰인 제목을 그대로 사용하세요.\n\n";
        $sys_prompt .= "{\n";
        $sys_prompt .= "  \"cards\": [\n";
        $sys_prompt .= "    { \"id\": \"t1\", \"type\": \"title\", \"content\": \"매력적인 제목 후보 1\", \"state\": \"primary\", \"locked\": false },\n";
        $card_num = 1;
        foreach ($structure['sections'] as $i => $sec) {
            $card_type = $i === 0 ? 'intro' : 'section';
            $sys_prompt .= "    { \"id\": \"c{$card_num}\", \"type\": \"{$card_type}\", \"title\": \"{$sec['title']}\", \"content\": \"(상부 문단)\\n\\n(하부 문단)\", \"state\": \"selected\", \"locked\": false },\n";
            $card_num++;
        }
        $sys_prompt .= "    { \"id\": \"c{$card_num}\", \"type\": \"cta\", \"title\": \"{$structure['closing_title']}\", \"content\": \"(마지막 멘트 + 업체 정보)\", \"state\": \"selected\", \"locked\": false }\n";
        $sys_prompt .= "  ]\n";
        $sys_prompt .= "}\n";

        $prompt = "다음 글감을 바탕으로 블로그 초안을 작성하세요.\n\n[글감]\n{$raw_material}";

        $provider_meta = bp_ai_get_provider_meta($project_id);
        if ($provider_meta['provider'] === 'template' && (!isset($_POST['accept_template_fallback']) || $_POST['accept_template_fallback'] !== '1')) {
            die(json_encode(bp_build_template_fallback_confirm($project_id)));
        }
        $ai_result = bp_ai_chat_request($prompt, $sys_prompt, $project_id);

        // AI 호출 로깅 (공통)
        bp_log_generation_attempt($project_id, 'generate_all_cards', $provider_meta['provider'], $provider_meta['model'], $ai_result);
        
        if (!$ai_result['ok']) {
            die(json_encode(['ok' => false, 'error' => $ai_result['error']]));
        }

        $cards = json_decode(trim($ai_result['message']), true);
        if (is_array($cards) && isset($cards['cards']) && is_array($cards['cards'])) {
            $cards = $cards['cards'];
        } elseif (is_array($cards) && array_keys($cards) !== range(0, count($cards) - 1)) {
            // "cards" 키가 아닌 다른 이름으로 감싸서 응답한 경우(모델이 임의의 키를 고른 경우)
            // 대비 - 값이 리스트(순차 배열)인 첫 번째 키를 찾아 그걸 실제 카드 배열로 쓴다.
            foreach ($cards as $maybe_list) {
                if (is_array($maybe_list) && ($maybe_list === array() || array_keys($maybe_list) === range(0, count($maybe_list) - 1))) {
                    $cards = $maybe_list;
                    break;
                }
            }
        }

        if (!is_array($cards) || count($cards) === 0 || array_keys($cards) !== range(0, count($cards) - 1)) {
            die(json_encode(['ok' => false, 'error' => 'AI가 올바른 JSON 카드를 생성하지 못했습니다.']));
        }

        $uid = time();
        foreach ($cards as $idx => &$c) {
            if (!isset($c['id']) || empty($c['id'])) {
                $c['id'] = 'card_' . $uid . '_' . $idx;
            }
        }

        $response['cards'] = $cards;
        break;

    case 'regenerate_card':
        if (!function_exists('bp_ai_chat_request')) {
            die(json_encode(['ok' => false, 'error' => 'AI 서비스가 활성화되지 않았습니다.']));
        }
        if (!bp_ai_global_enabled()) {
            die(json_encode(['ok' => false, 'error' => '관리자가 AI 기능을 전체적으로 꺼두었습니다. 설정 > AI 설정에서 켜주세요.']));
        }
        if (bp_ai_is_disabled_for_project($project_id)) {
            die(json_encode(['ok' => false, 'error' => '이 프로젝트는 AI 사용이 꺼져 있습니다. 1단계에서 AI 사용 안 함을 해제하거나, 직접 수정해 주세요.']));
        }

        $card_type = isset($_POST['card_type']) ? $_POST['card_type'] : '';
        $card_title = isset($_POST['card_title']) ? $_POST['card_title'] : '';
        $current_content = isset($_POST['current_content']) ? $_POST['current_content'] : '';
        $instruction = isset($_POST['instruction']) ? $_POST['instruction'] : '';
        $raw_material = isset($_POST['raw_material']) ? $_POST['raw_material'] : '';

        if ($card_type === 'title') {
            // 제목 카드에 일반 수정 프롬프트를 쓰면 모델이 "설명"이나 여러 줄로 풀어써서
            // 돌려주는 경우가 있었다(제목 후보 화면에 그대로 들어가면 어색해짐) - 길이와
            // 형식을 명시적으로 못박아 제목 한 줄만 나오게 한다.
            $sys_prompt = "당신은 한국어 블로그 제목만 작성하는 전문가입니다.\n";
            $sys_prompt .= "반드시 60자 이내의 제목 한 줄만 반환하세요.\n";
            $sys_prompt .= "설명, 인사말, 따옴표, 줄바꿈, 부가 설명 없이 제목 텍스트 그 자체만 출력해야 합니다.\n";
        } else {
            $sys_prompt = "당신은 블로그 콘텐츠 수정 전문가입니다.\n";
            $sys_prompt .= "사용자의 지시사항에 따라 주어진 텍스트를 재작성하여 반환하세요.\n";
            $sys_prompt .= "설명이나 부가적인 말 없이 재작성된 텍스트 원본만 반환해야 합니다.\n";
        }

        $prompt = "[기존 텍스트 유형]\n{$card_type} ({$card_title})\n\n";
        $prompt .= "[기존 텍스트]\n{$current_content}\n\n";
        $prompt .= "[전체 글감 컨텍스트]\n" . mb_substr($raw_material, 0, 500) . "\n\n";
        $prompt .= "[수정 지시사항]\n{$instruction}\n\n";
        $prompt .= "위 지시사항을 반영하여 기존 텍스트를 재작성하세요.";

        $provider_meta = bp_ai_get_provider_meta($project_id);
        if ($provider_meta['provider'] === 'template' && (!isset($_POST['accept_template_fallback']) || $_POST['accept_template_fallback'] !== '1')) {
            die(json_encode(bp_build_template_fallback_confirm($project_id)));
        }
        $ai_result = bp_ai_chat_request($prompt, $sys_prompt, $project_id);

        // AI 호출 로깅 (공통)
        bp_log_generation_attempt($project_id, 'regenerate_card', $provider_meta['provider'], $provider_meta['model'], $ai_result);
        
        if (!$ai_result['ok']) {
            die(json_encode(['ok' => false, 'error' => $ai_result['error']]));
        }

        $response['new_content'] = trim($ai_result['message']);
        break;

    case 'run_quality_check':
        if ($project_id === 0 || $post_id === 0) {
            die(json_encode(['ok' => false, 'error' => '프로젝트 및 포스트 정보가 없습니다.']));
        }
        
        // 광고주 및 프로젝트 정보 가져오기
        $proj = sql_fetch(" select advertiser_id, primary_keyword from {$project_table} where id = '{$project_id}' ");
        $advertiser = array();
        if ($proj && $proj['advertiser_id']) {
            $adv_table = bp_table('advertisers');
            $advertiser = sql_fetch(" select * from {$adv_table} where id = '{$proj['advertiser_id']}' ");
        }
        if (!$advertiser) $advertiser = array('id' => 0, 'name' => '', 'phone' => '', 'address' => '', 'service_region' => '', 'consult_url' => '', 'forbidden_words' => '');

        // 클라이언트에서 넘긴 현재 조립된 카드 텍스트 정보 (최신 상태 검사)
        $title_text = isset($_POST['title_text']) ? $_POST['title_text'] : '';
        $body_text = isset($_POST['body_text']) ? $_POST['body_text'] : '';
        
        $post_data = array(
            'title' => $title_text,
            'body' => $body_text
        );
        
        $checks = bp_run_and_save_quality_check($project_id, $post_id, $post_data, $advertiser, $proj ? $proj : array());
        
        $pass_count = 0;
        $total = count($checks);
        $summary = array('error' => 0, 'warning' => 0, 'info' => 0);
        
        $check_results = [];
        foreach ($checks as $key => $res) {
            if ($res['status'] === 'pass') {
                $pass_count++;
                $summary['info']++;
            } else if ($res['status'] === 'warn') {
                $summary['warning']++;
            } else {
                $summary['error']++;
            }
            $check_results[] = array(
                'key' => $key,
                'status' => $res['status'],
                'detail' => $res['detail']
            );
        }
        
        $score = $total > 0 ? (int)round(($pass_count / $total) * 100) : 0;
        
        $response['score'] = $score;
        $response['summary'] = $summary;
        $response['checks'] = $check_results;
        $response['message'] = '품질 검사 완료';
        break;

    // 키워드(posts.tags, 콤마구분)/해시태그(posts.hashtags, "#태그" 공백구분) 입력창을
    // 그대로 저장한다 - 두 컬럼 다 project_view.php의 SEO 메타 편집 폼이 이미 쓰던
    // 기존 저장 형식(v5/v3)을 그대로 따른다.
    case 'save_tags':
        if ($project_id === 0) {
            die(json_encode(['ok' => false, 'error' => '프로젝트 ID가 없습니다.']));
        }
        $tag_type = isset($_POST['tag_type']) ? $_POST['tag_type'] : '';
        if ($tag_type !== 'keywords' && $tag_type !== 'hashtags') {
            die(json_encode(['ok' => false, 'error' => '알 수 없는 태그 종류입니다.']));
        }
        $values = isset($_POST['values']) ? json_decode($_POST['values'], true) : [];
        if (!is_array($values)) $values = [];
        $values = array_values(array_filter(array_map('trim', $values), function ($v) { return $v !== ''; }));

        if ($tag_type === 'hashtags') {
            $values = array_map(function ($v) { return (mb_substr($v, 0, 1) === '#') ? $v : ('#' . $v); }, $values);
            $glue = ' ';
            $column = 'hashtags';
        } else {
            $glue = ', ';
            $column = 'tags';
        }

        // posts.tags/hashtags는 VARCHAR(500) - 최대 30개까지 입력을 허용하므로 항목이 길면
        // 넘칠 수 있다. 문자열을 자르면 마지막 항목이 깨진 채로 저장되니, 넘치는 항목을
        // 뒤에서부터 통째로 빼서 500자 안에 들어오는 만큼만 저장한다.
        $joined = implode($glue, $values);
        while (mb_strlen($joined) > 500 && count($values) > 0) {
            array_pop($values);
            $joined = implode($glue, $values);
        }

        $post = sql_fetch(" select id from {$post_table} where project_id = '{$project_id}' ");
        if ($post) {
            $post_id = $post['id'];
            sql_query(" update {$post_table} set {$column} = '" . sql_real_escape_string($joined) . "', updated_at = '" . G5_TIME_YMDHIS . "' where id = '{$post_id}' ");
        } else {
            sql_query(" insert into {$post_table}
                        set project_id = '{$project_id}', title = '새 포스팅', {$column} = '" . sql_real_escape_string($joined) . "',
                            created_at = '" . G5_TIME_YMDHIS . "' ");
            $post_id = sql_insert_id();
        }
        $response['post_id'] = $post_id;
        break;

    // 입력창에 이미 채워진 값(있으면 그대로 유지)을 참고해서, 지정한 개수가 될 때까지
    // AI가 나머지를 새로 만들어 채운다.
    case 'generate_tags':
        if (!function_exists('bp_ai_chat_request')) {
            die(json_encode(['ok' => false, 'error' => 'AI 서비스가 활성화되지 않았습니다.']));
        }
        if (!bp_ai_global_enabled()) {
            die(json_encode(['ok' => false, 'error' => '관리자가 AI 기능을 전체적으로 꺼두었습니다.']));
        }
        $tag_type = isset($_POST['tag_type']) ? $_POST['tag_type'] : '';
        if ($tag_type !== 'keywords' && $tag_type !== 'hashtags') {
            die(json_encode(['ok' => false, 'error' => '알 수 없는 태그 종류입니다.']));
        }
        $count = isset($_POST['count']) ? max(1, min(30, (int) $_POST['count'])) : 5;
        $raw_material = isset($_POST['raw_material']) ? trim($_POST['raw_material']) : '';
        $seed_values = isset($_POST['seed_values']) ? json_decode($_POST['seed_values'], true) : [];
        if (!is_array($seed_values)) $seed_values = [];
        $seed_values = array_values(array_filter(array_map('trim', $seed_values), function ($v) { return $v !== ''; }));

        $noun = $tag_type === 'hashtags' ? '해시태그' : '키워드';
        $sys_prompt = "당신은 블로그 SEO {$noun} 전문가입니다.\n";
        $sys_prompt .= "반드시 아래 JSON 객체 형식으로만 응답하세요: {\"items\": [\"...\", \"...\"]}\n";
        $sys_prompt .= "items 배열의 길이는 정확히 {$count}개여야 합니다.\n";
        if ($tag_type === 'hashtags') {
            $sys_prompt .= "각 항목은 \"#\"로 시작하는 짧은 해시태그 한 단어/구여야 합니다(공백 없이).\n";
        } else {
            $sys_prompt .= "각 항목은 짧은 키워드 한 단어/구여야 합니다.\n";
        }
        if (!empty($seed_values)) {
            $sys_prompt .= "다음은 이미 정해진 항목입니다. 이 값들은 그대로 결과에 포함하고, 부족한 개수만 새로 만들어 채우세요.\n";
            $sys_prompt .= "[" . implode(', ', $seed_values) . "]\n";
        }
        $sys_prompt .= "설명이나 다른 텍스트 없이 위 JSON 객체만 반환하세요.";

        $prompt = "다음 글감을 참고해서 {$noun}을 만들어 주세요.\n\n[글감]\n" . mb_substr($raw_material, 0, 1500);

        $ai_result = bp_ai_chat_request($prompt, $sys_prompt, $project_id);
        if (!$ai_result['ok']) {
            die(json_encode(['ok' => false, 'error' => $ai_result['error']]));
        }

        $items = bp_extract_json_item_list($ai_result['message']);
        if (!is_array($items)) {
            die(json_encode(['ok' => false, 'error' => 'AI가 올바른 형식을 반환하지 못했습니다.']));
        }

        $items = array_values(array_filter(array_map('trim', $items), function ($v) { return $v !== ''; }));
        $response['values'] = array_slice($items, 0, $count);
        break;

    // 카드(도입부/본문 섹션) 내용에 어울리는 이미지 생성용 프롬프트를 N개 만든다. 실제
    // 이미지 API 호출은 하지 않는다 - 사용자가 별도 이미지 생성 도구(달리/미드저니 등)에
    // 그대로 붙여 쓸 영문 프롬프트 텍스트만 만들어 준다.
    case 'generate_image_prompts':
        if (!function_exists('bp_ai_chat_request')) {
            die(json_encode(['ok' => false, 'error' => 'AI 서비스가 활성화되지 않았습니다.']));
        }
        if (!bp_ai_global_enabled()) {
            die(json_encode(['ok' => false, 'error' => '관리자가 AI 기능을 전체적으로 꺼두었습니다.']));
        }
        $count = isset($_POST['count']) ? max(1, min(5, (int) $_POST['count'])) : 3;
        $content_text = isset($_POST['content_text']) ? trim($_POST['content_text']) : '';
        if ($content_text === '') {
            die(json_encode(['ok' => false, 'error' => '내용이 비어 있습니다.']));
        }

        $sys_prompt = "당신은 AI 이미지 생성 도구(DALL-E, Midjourney 등)에 쓸 프롬프트를 작성하는 전문가입니다.\n";
        $sys_prompt .= "반드시 아래 JSON 객체 형식으로만 응답하세요: {\"items\": [\"...\", \"...\"]}\n";
        $sys_prompt .= "items 배열의 길이는 정확히 {$count}개여야 합니다.\n";
        $sys_prompt .= "각 항목은 이미지 생성 도구가 바로 사용할 수 있는 영어 프롬프트 한 문단이어야 합니다(구체적인 장면, 스타일, 분위기 묘사 포함).\n";
        $sys_prompt .= "설명이나 다른 텍스트 없이 위 JSON 객체만 반환하세요.";

        $prompt = "다음 블로그 본문 내용에 어울리는 이미지 생성 프롬프트를 만들어 주세요.\n\n[본문 내용]\n" . mb_substr($content_text, 0, 1500);

        $ai_result = bp_ai_chat_request($prompt, $sys_prompt, $project_id);
        if (!$ai_result['ok']) {
            die(json_encode(['ok' => false, 'error' => $ai_result['error']]));
        }

        $items = bp_extract_json_item_list($ai_result['message']);
        if (!is_array($items)) {
            die(json_encode(['ok' => false, 'error' => 'AI가 올바른 형식을 반환하지 못했습니다.']));
        }

        $items = array_values(array_filter(array_map('trim', $items), function ($v) { return $v !== ''; }));
        $response['prompts'] = array_slice($items, 0, $count);
        break;

    // 카드 하단 "첨부파일"에서 올린 이미지를 저장한다. 실제 저장/변환/중복검사는
    // 이미지 라이브러리가 이미 하는 bp_optimize_local_file()을 그대로 재사용한다 -
    // blog_images에 등록해서 이미지 라이브러리 화면에서도 같이 보인다.
    case 'upload_card_image':
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            die(json_encode(['ok' => false, 'error' => '파일 업로드에 실패했습니다.']));
        }
        $upload = $_FILES['file'];
        if ($upload['size'] > 10 * 1024 * 1024) {
            die(json_encode(['ok' => false, 'error' => '파일 크기는 10MB 이하만 가능합니다.']));
        }
        $mime = mime_content_type($upload['tmp_name']);
        if (strpos((string) $mime, 'image/') !== 0) {
            die(json_encode(['ok' => false, 'error' => '이미지 파일만 첨부할 수 있습니다.']));
        }

        $tmp_dir = G5_DATA_PATH . '/blog_images';
        if (!is_dir($tmp_dir)) {
            @mkdir($tmp_dir, G5_DIR_PERMISSION, true);
        }
        $tmp_path = $tmp_dir . '/upload_tmp_' . uniqid() . '_' . basename($upload['name']);
        if (!move_uploaded_file($upload['tmp_name'], $tmp_path)) {
            die(json_encode(['ok' => false, 'error' => '파일을 저장하지 못했습니다.']));
        }

        $result = bp_optimize_local_file($tmp_path, $project_id, $upload['name']);
        if (!$result['ok']) {
            die(json_encode(['ok' => false, 'error' => $result['error']]));
        }
        $response['image_id'] = $result['image_id'];
        $response['url'] = $result['url'];
        $response['filename'] = $upload['name'];
        break;

    default:
        $response['error'] = 'Unknown action';
        $response['ok'] = false;
        break;
}

echo json_encode($response);
exit;
