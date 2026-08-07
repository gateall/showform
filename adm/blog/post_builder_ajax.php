<?php
// 통합 포스팅 제작 폼 AJAX 허브 (카드 기반 스튜디오 UI)

// admin.lib.php의 alert() HTML 리다이렉션을 막기 위해 common.php를 먼저 로드하고 세션을 확인합니다.
define('G5_IS_ADMIN', true);
require_once('../../common.php');

$is_ajax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') 
        || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
        || (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);

if ($is_ajax) {
    // PHP 8.4 Warning/Deprecated 로그가 JSON 응답에 혼입되어 프론트엔드 파싱이 깨지는 것을 방지
    ini_set('display_errors', '0');
    error_reporting(E_ALL); // 로그로는 남긴다
}

// 세션이 끊긴 AJAX 요청은 여기서 JSON 401로 끝낸다. 아래 _common.php가 로드되면
// admin.lib.php의 verify_mb_key() 실패 경로가 alert_close() HTML을 출력해 버려서,
// 프런트의 response.json()이 깨지고 원인 파악이 불가능해지기 때문이다.
// 진단 로그는 "실패한 요청"에만 남긴다 - 모든 AJAX마다 남기면 자동저장 때문에
// 운영 로그가 폭증해서 정작 필요한 실패 기록을 찾기 어려워진다.
// 세션 ID 원문은 유출 시 세션 탈취로 이어질 수 있으므로 축약 해시만 기록한다.
if ($is_ajax && empty($member['mb_id'])) {
    $sessionId = session_id();
    error_log(json_encode([
        'event' => 'auth_debug',
        'reason' => 'no_member_before_common',
        'session_hash' => $sessionId !== '' ? substr(hash('sha256', $sessionId), 0, 16) : '',
        'member_id' => $member['mb_id'] ?? '',
        'is_admin' => $is_admin ?? '',
        'host' => $_SERVER['HTTP_HOST'] ?? '',
        'https' => $_SERVER['HTTPS'] ?? '',
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'cookie_names' => array_keys($_COOKIE),
    ], JSON_UNESCAPED_UNICODE));

    header('HTTP/1.1 401 Unauthorized');
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => 'LOGIN_REQUIRED', 'message' => '로그인이 만료되었습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// AJAX 응답은 순수 JSON이어야 한다. include 과정에서 PHP 경고문(Warning/Notice/
// Deprecated)이나 파일 끝 공백/BOM이 출력되면 JSON 앞에 섞여 나가 프런트의
// JSON.parse가 깨진다("서버 JSON 응답 형식이 손상되었습니다"의 원인).
// 화면 출력만 막고 로그로는 남겨서, 오류 자체를 은폐하지 않는다.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start();

include_once('./_common.php');
include_once(G5_ADMIN_PATH . '/blog/lib/blog_ai_service.lib.php');
include_once(G5_ADMIN_PATH . '/blog/lib/blog_quality.lib.php');
include_once(G5_ADMIN_PATH . '/blog/lib/blog_image.lib.php');

// include 중 새어나온 출력이 있으면 버리되, 반드시 로그에 남겨 원인을 추적할 수 있게 한다
// (그냥 ob_end_clean()만 하면 진짜 오류가 조용히 사라져 디버깅이 불가능해진다).
$pb_stray_output = ob_get_clean();
if ($pb_stray_output !== '' && $pb_stray_output !== false) {
    error_log(json_encode([
        'event' => 'stray_output_before_json',
        'length' => strlen($pb_stray_output),
        'preview' => mb_substr($pb_stray_output, 0, 500, 'UTF-8'),
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
    ], JSON_UNESCAPED_UNICODE));
}

$rawBody = file_get_contents('php://input');
$contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : (isset($_SERVER['HTTP_CONTENT_TYPE']) ? $_SERVER['HTTP_CONTENT_TYPE'] : '');

$request = [];
// $request가 $request에서 왔는지 표시해 둔다 - common.php가 SQL Injection 방지를 위해
// $request의 모든 값에 addslashes 기반 이스케이프를 무조건 적용해서, 그 안에 담긴
// JSON 문자열(cards 등)의 큰따옴표(")가 전부 \"로 바뀌어 있다. JSON 바디로 직접
// 받은 값은 이 이스케이프를 거치지 않으므로 구분이 필요하다.
$request_from_post = false;
if (stripos($contentType, 'application/json') !== false && is_string($rawBody) && trim($rawBody) !== '') {
    $request = json_decode($rawBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'ok' => false,
            'error' => '요청 JSON 해석에 실패했습니다.',
            'json_error' => json_last_error_msg(),
            'raw_length' => strlen($rawBody),
            'raw_head' => mb_substr($rawBody, 0, 300, 'UTF-8')
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
} else {
    $request = $_POST;
    $request_from_post = true;
}

// 이 파일의 오류 응답 규격은 {"ok":false,"error":"..."} 이다(성공은 "ok":true).
// 실측 기준 'ok' 63건 / 'success' 0건이므로 'ok'가 이 프로젝트의 단일 표준이다.
// 한글이 \uXXXX로 이스케이프되지 않도록 플래그를 항상 함께 적용한다.
function bp_json_response(array $payload, int $status = 200)
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
    }

    echo json_encode(
        $payload,
        JSON_UNESCAPED_UNICODE |
        JSON_UNESCAPED_SLASHES |
        JSON_INVALID_UTF8_SUBSTITUTE
    );
    exit;
}

function bp_json_die(array $payload): void
{
    bp_json_response($payload);
}

function bp_json_die_error(string $message, string $code = ''): void
{
    $payload = ['ok' => false, 'error' => $message];
    if ($code !== '') {
        $payload['code'] = $code;
    }
    bp_json_response($payload);
}

// common.php가 $_POST 전체에 addslashes 기반 이스케이프를 무조건 적용하므로, cards 같은
// JSON 문자열 필드를 그대로 json_decode()하면 큰따옴표가 \"로 남아 Syntax error가 난다.
// 그렇다고 무조건 stripslashes()부터 적용하면, 본문에 실제로 들어있는 정상적인 역슬래시
// (Windows 경로 C:\Users\..., 정규식 \d+, 이스케이프된 개행 \n 등)까지 함께 손상된다.
// 그래서 "원본 그대로 먼저 시도 → 실패했을 때만 stripslashes 후 재시도" 순서로 처리한다.
// JSON 본문(application/json)으로 온 값은애초에 배열로 넘어오므로 그대로 반환한다.
function bp_json_decode_post($value, $default = [], string $fieldName = '')
{
    if (is_array($value)) {
        return $value;
    }
    if (!is_string($value) || trim($value) === '') {
        return $default;
    }

    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }
    $firstError = json_last_error_msg();

    $decoded = json_decode(stripslashes($value), true);
    if (json_last_error() === JSON_ERROR_NONE) {
        return $decoded;
    }

    // 두 시도 모두 실패하면 조용히 빈 배열로 바꾸지 않고 원인을 로그에 남긴다 -
    // 그래야 "카드 데이터가 누락되었습니다" 같은 오해의 소지가 있는 메시지 대신
    // 실제 원인(JSON 문법 오류)을 추적할 수 있다.
    error_log(json_encode([
        'event' => 'json_decode_failed',
        'field' => $fieldName,
        'first_error' => $firstError,
        'second_error' => json_last_error_msg(),
        'length' => strlen($value),
        'preview' => mb_substr($value, 0, 300),
    ], JSON_UNESCAPED_UNICODE));

    return $default;
}

$action = isset($request['action']) ? $request['action'] : '';
$project_id = isset($request['project_id']) ? (int)$request['project_id'] : 0;
$post_id = isset($request['post_id']) ? (int)$request['post_id'] : 0;

$project_table = bp_table('content_projects');
$post_table = bp_table('posts');
$title_candidates_table = bp_table('content_title_candidates');
$post_sections_table = bp_table('post_sections');

// $g5['table_prefix']는 Gnuboard 코어에 정의돼 있지 않다(코어는 G5_TABLE_PREFIX 상수를
// 쓴다). 이 파일이 라이브러리 테이블 쿼리에서 $g5['table_prefix']를 직접 참조하는데,
// 정의되지 않은 배열키 접근 경고가 화면에 출력되는 설정이면 그 경고 텍스트가 JSON
// 응답 앞에 섞여 나가 프론트의 fetch().json()이 깨진다.
if (!isset($g5['table_prefix']) || $g5['table_prefix'] === '') {
    $g5['table_prefix'] = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_';
}

header('Content-Type: application/json; charset=utf-8');

$pb_auth_msg = auth_check_menu($auth, '360050', 'w', true);
if ($pb_auth_msg) {
    // 세션이 자주 끊기는 문제의 근본 원인(공용 verify_mb_key() 실패 경로, 도메인/
    // 프로토콜 혼용, 쿠키 누락 등)을 특정하기 위한 진단 로그. 쿠키 값은 이름만 남기고,
    // 세션 ID는 원문을 남기면 로그 유출 시 세션 탈취로 이어질 수 있으므로 축약 해시만
    // 남긴다(같은 세션인지 비교하는 용도로는 해시로 충분함).
    $pb_sid = session_id();
    error_log(json_encode([
        'event' => 'auth_debug',
        'action' => $action,
        'auth_msg' => $pb_auth_msg,
        'session_hash' => $pb_sid !== '' ? substr(hash('sha256', $pb_sid), 0, 16) : '',
        'member_id' => isset($member['mb_id']) ? $member['mb_id'] : '',
        'is_admin' => isset($is_admin) ? $is_admin : '',
        'host' => $_SERVER['HTTP_HOST'] ?? '',
        'https' => $_SERVER['HTTPS'] ?? '',
        'uri' => $_SERVER['REQUEST_URI'] ?? '',
        'cookie_names' => array_keys($_COOKIE),
    ], JSON_UNESCAPED_UNICODE));
    die(json_encode(['ok' => false, 'error' => $pb_auth_msg]));
}

$response = ['ok' => true, 'action' => $action];

// v30(blog_post_prompts) 설치 전에 코드만 먼저 배포되는 순서로 진행될 수 있어,
// 저장/불러오기/최종조립 액션이 실행되기 전에 테이블 존재를 먼저 확인한다.
// 테이블이 없는 상태에서 그냥 쿼리를 날리면 SQL 오류가 JSON 응답을 깨뜨린다.
function bp_post_prompts_table_ready(): bool
{
    static $ready = null;
    if ($ready === null) {
        $like = sql_real_escape_string(bp_table('post_prompts'));
        $result = sql_query(" show tables like '{$like}' ", false);
        $ready = ($result && sql_num_rows($result) > 0);
    }
    return $ready;
}

// AI에게 {"items": [...]} 형태로 문자열 목록을 요청했을 때, json_object 응답 모드가
// 강제하는 "최상위는 객체" 제약 때문에 모델이 items가 아닌 다른 키로 감싸거나 그냥
// 배열만 주는 경우까지 대비해서 실제 목록을 뽑아낸다(generate_tags/generate_image_prompts
// 둘 다 같은 모양의 응답을 기대하므로 공용으로 뺐다).
// generate_all_cards가 쓰는 본문 구조 템플릿 목록 - 1단계 컨트롤 패널에서 사용자가
// 골라서 structure_template(ID)으로 넘긴다. 각 항목의 sections는 마지막 "방문팁류" 마무리
// 섹션을 제외한 본문 섹션들이고, closing_title은 업체 정보가 들어갈 마지막 섹션 제목이다.
function bp_get_structure_templates(): array
{
    global $g5;
    $templates = array();
    
    // DB에서 구조 템플릿 로드
    $sql = "SELECT id, item_name as title, instruction_content as content FROM {$g5['table_prefix']}blog_library_items WHERE library_type = 'structure' ORDER BY id ASC";
    $result = sql_query($sql, false);
    if ($result) {
        while ($row = sql_fetch_array($result)) {
            $json = json_decode($row['content'], true);
            if (is_array($json)) {
                $templates[$row['id']] = array(
                    'label' => $row['title'],
                    'sections' => isset($json['sections']) ? $json['sections'] : array(),
                    'closing_title' => isset($json['closing_title']) ? $json['closing_title'] : '마무리',
                );
            }
        }
    }
    
    // DB에 하나도 없거나 오류가 난 경우 기본값
    if (empty($templates)) {
        $templates[0] = array(
            'label' => '기승전결형',
            'sections' => array(
                array('type' => 'intro', 'title' => '기(도입부)'),
                array('type' => 'section', 'title' => '승(전개)'),
                array('type' => 'section', 'title' => '전(전환)'),
                array('type' => 'section', 'title' => '결(마무리)'),
            ),
            'closing_title' => '방문팁',
        );
    }
    return $templates;
}

// preview_prompt(미리보기)와 generate_all_cards(실제 생성)가 서로 다른 프롬프트를
// 조립하면 "미리보기엔 있는데 실제 생성에는 반영 안 됐다"는 신뢰 문제가 재발한다.
// 두 액션 모두 반드시 이 함수 하나만 거치도록 한다 - 조립 로직을 바꿀 때도 이 함수만
// 고치면 두 곳 모두에 동시에 반영된다.
function bp_build_generation_prompt(int $project_id, string $project_table, array $g5, array $params): array
{
    $raw_material = isset($params['raw_material']) ? trim($params['raw_material']) : '';

    $locked_cards = $params['locked_cards'] ?? [];
    $locked_info = "";
    if (!empty($locked_cards) && is_array($locked_cards)) {
        $locked_info = "아래는 이미 확정되어 잠금(locked) 처리된 내용입니다. 새로 생성할 초안 구조에서 이 부분들은 기존 내용 그대로 배치되도록 하세요.\n";
        foreach ($locked_cards as $lc) {
            $locked_info .= "[{$lc['type']}] " . ($lc['title'] ?? '') . "\n" . $lc['content'] . "\n\n";
        }
    }

    // "방문팁" 마무리 섹션에 실제 업체 정보를 넣게 하려면 광고주 연락처가 필요하다.
    $adv_table = bp_table('advertisers');
    $advertiser = sql_fetch(" select a.name, a.phone, a.email, a.address, a.domain, a.consult_url
                               from {$project_table} p
                               join {$adv_table} a on a.id = p.advertiser_id
                               where p.id = '{$project_id}' ");
    $contact_field_labels = array(
        'name' => '상호', 'address' => '주소', 'phone' => '전화',
        'email' => '이메일', 'domain' => '웹사이트', 'consult_url' => '상담/SNS 주소',
    );
    $allowed_fields = isset($params['contact_fields']) && is_array($params['contact_fields'])
        ? $params['contact_fields']
        : array_keys($contact_field_labels);

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
    // 고른 키를 받아 그 구조에 맞춰 섹션 목록과 JSON 예시를 동적으로 만든다.
    $structure_templates = bp_get_structure_templates();
    $structure_raw = isset($params['structure_template']) ? $params['structure_template'] : '0';
    $structure_keys = array_filter(array_map('trim', explode(',', $structure_raw)));
    
    $selected_structures = [];
    foreach ($structure_keys as $sk) {
        $sk = intval($sk);
        if (isset($structure_templates[$sk])) {
            $selected_structures[] = $structure_templates[$sk];
        }
    }

    if (empty($selected_structures)) {
        $default_sql = "SELECT id FROM {$g5['table_prefix']}blog_library_items WHERE library_type = 'structure' AND is_default = 1 ORDER BY id ASC LIMIT 1";
        $default_row = sql_fetch($default_sql);
        if ($default_row && isset($structure_templates[$default_row['id']])) {
            $selected_structures[] = $structure_templates[$default_row['id']];
        } else {
            reset($structure_templates);
            $first = current($structure_templates);
            if ($first) {
                $selected_structures[] = $first;
            }
        }
    }

    $combined_structure = [
        'label' => implode(' + ', array_column($selected_structures, 'label')),
        'sections' => [],
        'closing_title' => isset($selected_structures[0]['closing_title']) ? $selected_structures[0]['closing_title'] : '마무리 멘트'
    ];
    
    foreach ($selected_structures as $st) {
        if (!empty($st['sections']) && is_array($st['sections'])) {
            $combined_structure['sections'] = array_merge($combined_structure['sections'], $st['sections']);
        }
    }
    $structure = $combined_structure;

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
    $target_length = isset($params['target_length']) ? max(200, (int) $params['target_length']) : 2000;
    $sys_prompt .= "\n제목을 제외한 본문 전체(모든 섹션 + {$structure['closing_title']})의 분량은 약 {$target_length}자를 목표로 하세요.\n";

    $char_per_line = isset($params['char_per_line']) ? (int) $params['char_per_line'] : 60;
    $char_per_line = max(20, min(120, $char_per_line ?: 60));
    $sys_prompt .= "본문은 한 줄에 약 {$char_per_line}글자가 되도록 문단 안에서 자연스럽게 줄바꿈(개행)하세요. ";
    $sys_prompt .= "단어나 조사 중간에서 기계적으로 자르지 말고, 문장부호와 의미 단위를 기준으로 줄을 나누세요. ";
    $sys_prompt .= "제목, URL, 전화번호, 이메일에는 이 줄바꿈 기준을 적용하지 마세요.\n";
    if ($locked_info !== "") {
        $sys_prompt .= "단, 다음 잠긴(locked) 내용들은 새 초안에 반드시 포함하고 내용을 덮어쓰지 마십시오.\n{$locked_info}\n";
    }

    // AI 글 생성 조건 - 조건 "이름"만 보내면 AI가 정확히 못 지킬 수 있어(사용자 지적),
    // 반드시 상세 지시문 본문을 전달한다.
    $selected_rule_ids = $params['selected_rule_ids'] ?? [];
    if (is_array($selected_rule_ids) && !empty($selected_rule_ids)) {
        $selected_rule_ids = array_values(array_filter(array_map('intval', $selected_rule_ids)));
    } else {
        $selected_rule_ids = [];
    }
    $rule_snapshots = [];
    if (!empty($selected_rule_ids)) {
        $rule_ids_sql = implode(',', $selected_rule_ids);
        $rules_res = sql_query(" SELECT id, item_name as title, instruction_content as content FROM {$g5['table_prefix']}blog_library_items
                                  WHERE id IN ({$rule_ids_sql}) AND library_type = 'generation_condition'
                                  ORDER BY id ASC ");
        $rule_lines = [];
        while ($rule_row = sql_fetch_array($rules_res)) {
            $rule_lines[] = trim($rule_row['content']);
            $rule_snapshots[] = array(
                'id' => $rule_row['id'],
                'title' => $rule_row['title'],
                'content' => $rule_row['content'],
            );
        }
        if (!empty($rule_lines)) {
            $sys_prompt .= "\n[글 작성 조건] 아래 조건을 모두 준수하여 글을 작성하세요. 조건끼리 충돌하면 먼저 나온 조건을 우선하세요.\n";
            foreach ($rule_lines as $i => $line) {
                $sys_prompt .= ($i + 1) . ". {$line}\n";
            }
        }
    }
    $extra_instruction = isset($params['extra_instruction']) ? trim($params['extra_instruction']) : '';
    if ($extra_instruction !== '') {
        $sys_prompt .= "\n[이번 글 추가 지시]\n{$extra_instruction}\n";
    }

    // OpenAI json_object 응답 모드는 최상위가 반드시 객체여야 한다 - 예시 자체를
    // {"cards":[...]} 형태로 줘서 모델이 실제로 쓸 키 이름을 명시적으로 고정시킨다.
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

    $user_prompt = "다음 글감을 바탕으로 블로그 초안을 작성하세요.\n\n[글감]\n{$raw_material}";

    return array(
        'sys_prompt' => $sys_prompt,
        'user_prompt' => $user_prompt,
        'structure' => $structure,
        'structure_key' => $structure_key,
        'rule_snapshots' => $rule_snapshots,
        'rule_count' => count($rule_snapshots),
        'extra_instruction' => $extra_instruction,
        'raw_material' => $raw_material,
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
        $builder_state_raw = isset($request['builder_state']) ? $request['builder_state'] : '{}';
        if (is_array($builder_state_raw)) {
            $builder_state = json_encode($builder_state_raw, JSON_UNESCAPED_UNICODE);
        } else {
            $builder_state = (string)$builder_state_raw;
        }
        
        if ($project_id === 0) {
            die(json_encode(['ok' => false, 'error' => '프로젝트 ID가 없습니다.']));
        }
        
        // CTA 필드 업데이트
        $cta_enabled = isset($request['cta_enabled']) ? (int)$request['cta_enabled'] : null;
        if ($cta_enabled !== null) {
            $cta_content = isset($request['cta_content']) ? trim($request['cta_content']) : '';
            $cta_company_fields = isset($request['cta_company_fields']) ? trim($request['cta_company_fields']) : '';
            sql_query(" update {$project_table} 
                        set cta_enabled = '{$cta_enabled}',
                            cta_content = '" . sql_real_escape_string($cta_content) . "',
                            cta_company_fields = '" . sql_real_escape_string($cta_company_fields) . "',
                            cta_updated_at = '" . G5_TIME_YMDHIS . "'
                        where id = '{$project_id}' ");
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
        $state = bp_json_decode_post($builder_state, [], 'builder_state');
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

    // "완료" 버튼(finishPost)에서만 호출된다 - save_state/save_step은 builder_state(카드
    // JSON)만 저장하고 posts.title/body는 절대 건드리지 않아서, 카드 빌더로 완성한 글이
    // project_view.php의 "제목"/"본문(현재)"에도, 실제 발행 파이프라인(blog_publisher.lib.php,
    // posts.title/body를 직접 읽음)에도 반영되지 않던 문제가 있었다 - 여기서 명시적으로
    // 채워준다. body_html은 클라이언트가 이미 조립한 그대로(제목 h1 제외)를 신뢰한다 -
    // 서버에서 카드 목록을 다시 조립하면 조립 로직이 두 군데(JS/PHP)로 갈라져 어긋날 위험이 있다.
    case 'finalize_post':
        if ($project_id === 0 || $post_id === 0) {
            die(json_encode(['ok' => false, 'error' => '프로젝트/포스트 정보가 없습니다.']));
        }
        $final_title = isset($request['title']) ? trim($request['title']) : '';
        $final_body_html = isset($request['body_html']) ? $request['body_html'] : '';
        if ($final_title === '') {
            die(json_encode(['ok' => false, 'error' => '제목이 없습니다.']));
        }
        sql_query(" update {$post_table}
                    set title = '" . sql_real_escape_string($final_title) . "',
                        body = '" . sql_real_escape_string($final_body_html) . "',
                        updated_at = '" . G5_TIME_YMDHIS . "'
                    where id = '{$post_id}' and project_id = '{$project_id}' ");
        $response['message'] = '완성본이 프로젝트에 반영되었습니다.';
        break;

    case 'save_card':
        if ($post_id === 0) {
            die(json_encode(['ok' => false, 'error' => '포스트 ID가 없습니다.']));
        }
        $card_id = isset($request['card_id']) ? $request['card_id'] : '';
        $card_type = isset($request['card_type']) ? $request['card_type'] : 'section';
        $content = isset($request['content']) ? $request['content'] : '';
        $sort_order = isset($request['sort_order']) ? (int)$request['sort_order'] : 0;
        $status = isset($request['status']) ? $request['status'] : 'draft';
        $locked = isset($request['locked']) && $request['locked'] == 'true' ? 1 : 0;
        
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
        $post = sql_fetch(" select id, builder_state, tags, hashtags from {$post_table} where project_id = '{$project_id}' ");

        if ($proj && $post) {
            $response['advertiser_id'] = $proj['advertiser_id'];
            $response['site_id'] = $proj['primary_site_id'];
            $response['builder_state'] = $post['builder_state'] ? json_decode($post['builder_state'], true) : [];
            // tags는 콤마구분(기존 관례), hashtags는 "#태그" 공백구분(기존 관례) - bp_table 등에서
            // 이미 쓰던 저장 형식 그대로 읽어서 배열로만 풀어준다.
            $response['keywords'] = $post['tags'] !== '' ? array_values(array_filter(array_map('trim', explode(',', $post['tags'])))) : [];
            $response['hashtags'] = $post['hashtags'] !== '' ? array_values(array_filter(array_map('trim', explode(' ', $post['hashtags'])))) : [];
            
            // 방안 B: CTA 스냅샷 로드
            $snap = sql_fetch(" select snapshot_json from {$g5['table_prefix']}blog_post_cta_snapshots where post_id = '{$post['id']}' ");
            if ($snap && $snap['snapshot_json']) {
                $response['cta_snapshot'] = json_decode($snap['snapshot_json'], true);
            }
        } else {
            $response['ok'] = false;
            $response['error'] = '데이터를 찾을 수 없습니다.';
        }
        break;

    // "체크했는데 AI가 반영 안 했다"는 문제를 관리자가 직접 확인할 수 있도록, generate_all_cards가
    // 실제로 조립하는 것과 동일한 sys_prompt를 AI 호출 없이 미리 보여준다. 로직은 아래
    // generate_all_cards와 반드시 동일하게 유지해야 한다(두 곳이 갈라지면 "미리보기엔 있는데
    // 실제 생성에는 안 들어갔다"는 새로운 신뢰 문제가 생긴다).
    // 실제 생성(generate_all_cards)과 반드시 같은 bp_build_generation_prompt()를 거친다.
    // AI는 호출하지 않는다 - 토큰 비용/생성 이력/카드 생성/자동저장 변조가 전혀 없어야 한다.
    case 'preview_prompt':
        $contact_fields_param = null;
        if (isset($request['contact_fields'])) {
            $decoded = bp_json_decode_post($request['contact_fields'], [], 'contact_fields');
            $contact_fields_param = is_array($decoded) ? $decoded : array();
        }
        $selected_rule_ids_param = bp_json_decode_post($request['selected_rule_ids'] ?? [], [], 'selected_rule_ids');

        $built = bp_build_generation_prompt($project_id, $project_table, $g5, [
            'raw_material'       => isset($request['raw_material']) ? $request['raw_material'] : '',
            'locked_cards'       => bp_json_decode_post($request['locked_cards'] ?? [], [], 'locked_cards'),
            'contact_fields'     => $contact_fields_param,
            'structure_template' => isset($request['structure_template']) ? $request['structure_template'] : 0,
            'target_length'      => isset($request['target_length']) ? $request['target_length'] : 2000,
            'selected_rule_ids'  => $selected_rule_ids_param,
            'extra_instruction'  => isset($request['extra_instruction']) ? $request['extra_instruction'] : '',
            'char_per_line'      => isset($request['char_per_line']) ? $request['char_per_line'] : 60,
        ]);

        $full_prompt = $built['sys_prompt'] . "\n\n---\n\n" . $built['user_prompt'];

        die(json_encode([
            'ok' => true,
            'summary' => [
                'structure' => 1,
                'structure_name' => $built['structure']['label'],
                'generation_conditions' => $built['rule_count'],
                'additional_instruction' => $built['extra_instruction'] !== '' ? 1 : 0,
                'raw_material_provided' => $built['raw_material'] !== '' ? 1 : 0,
                'total_length' => mb_strlen($full_prompt),
            ],
            'sys_prompt' => $built['sys_prompt'],
            'user_prompt' => $built['user_prompt'],
            'full_prompt' => $full_prompt,
        ], JSON_UNESCAPED_UNICODE));

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

        $raw_material = isset($request['raw_material']) ? trim($request['raw_material']) : '';
        if (empty($raw_material)) {
            die(json_encode(['ok' => false, 'error' => '글감이 제공되지 않았습니다.']));
        }
        
        $contact_fields_param = null;
        if (isset($request['contact_fields'])) {
            $decoded = bp_json_decode_post($request['contact_fields'], [], 'contact_fields');
            $contact_fields_param = is_array($decoded) ? $decoded : array();
        }
        $selected_rule_ids_param = bp_json_decode_post($request['selected_rule_ids'] ?? [], [], 'selected_rule_ids');
        $locked_cards_param = bp_json_decode_post($request['locked_cards'] ?? [], [], 'locked_cards');

        $built = bp_build_generation_prompt($project_id, $project_table, $g5, [
            'raw_material'       => $raw_material,
            'locked_cards'       => is_array($locked_cards_param) ? $locked_cards_param : [],
            'contact_fields'     => $contact_fields_param,
            'structure_template' => isset($request['structure_template']) ? $request['structure_template'] : 0,
            'target_length'      => isset($request['target_length']) ? $request['target_length'] : 2000,
            'selected_rule_ids'  => $selected_rule_ids_param,
            'extra_instruction'  => isset($request['extra_instruction']) ? $request['extra_instruction'] : '',
            'char_per_line'      => isset($request['char_per_line']) ? $request['char_per_line'] : 60,
        ]);

        $sys_prompt = $built['sys_prompt'];
        $prompt = $built['user_prompt'];
        $structure = $built['structure'];
        $structure_key = $built['structure_key'];
        $rule_snapshots = $built['rule_snapshots'];
        $extra_instruction = $built['extra_instruction'];

        $provider_meta = bp_ai_get_provider_meta($project_id);
        if ($provider_meta['provider'] === 'template' && (!isset($request['accept_template_fallback']) || $request['accept_template_fallback'] !== '1')) {
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

        // 스냅샷 보존 (원본 지시문 보호)
        $post = sql_fetch(" select id from {$post_table} where project_id = '{$project_id}' ");
        if ($post) {
            $post_id_val = $post['id'];
            $selections_table = $g5['table_prefix'] . 'blog_post_library_selections';
            
            // 기존 선택 내역 삭제 후 재삽입 (재생성 대비)
            sql_query(" delete from {$selections_table} where post_id = '{$post_id_val}' ");
            
            // 1) 구조 템플릿 저장
            $structure_content_json = json_encode($structure, JSON_UNESCAPED_UNICODE);
            sql_query(" insert into {$selections_table} 
                        set post_id = '{$post_id_val}',
                            library_item_id = '{$structure_key}',
                            library_type = 'structure',
                            applied_content = '" . sql_real_escape_string($structure_content_json) . "',
                            apply_order = 0,
                            created_at = '" . G5_TIME_YMDHIS . "' ");
                            
            // 2) 글 작성 조건 저장
            if (!empty($rule_snapshots)) {
                $order = 1;
                foreach ($rule_snapshots as $rs) {
                    $rule_json = json_encode($rs, JSON_UNESCAPED_UNICODE);
                    sql_query(" insert into {$selections_table} 
                                set post_id = '{$post_id_val}',
                                    library_item_id = '{$rs['id']}',
                                    library_type = 'generation_condition',
                                    applied_content = '" . sql_real_escape_string($rule_json) . "',
                                    apply_order = {$order},
                                    created_at = '" . G5_TIME_YMDHIS . "' ");
                    $order++;
                }
            }
            
            // 3) 사용자 임의 추가 지시 보존
            if ($extra_instruction !== '') {
                sql_query(" insert into {$selections_table} 
                            set post_id = '{$post_id_val}',
                                library_item_id = 0,
                                library_type = 'custom_instruction',
                                applied_content = '" . sql_real_escape_string($extra_instruction) . "',
                                apply_order = 99,
                                created_at = '" . G5_TIME_YMDHIS . "' ");
            }
        }

        $response['cards'] = $cards;
        break;

    // 글감이 전혀 없는 사용자를 위한 "AI로 글감 찾기" - 업종/지역/상품/목적 등
    // 조건만으로 제목 후보 여러 개를 만든다. generate_all_cards와 별개의 훨씬 가벼운
    // 요청이라 bp_build_generation_prompt()(카드 본문 생성용, 구조/생성조건 라이브러리를
    // 전제로 함)를 재사용하지 않고 이 액션 전용으로 조립한다 - 카드 생성 프롬프트와
    // 섞이면 서로 다른 목적의 조건이 뒤섞인다.
    case 'generate_topic_ideas':
        if (!function_exists('bp_ai_chat_request')) {
            die(json_encode(['ok' => false, 'error' => 'AI 서비스가 활성화되지 않았습니다.']));
        }
        if (!bp_ai_global_enabled()) {
            die(json_encode(['ok' => false, 'error' => '관리자가 AI 기능을 전체적으로 꺼두었습니다.']));
        }
        if (bp_ai_is_disabled_for_project($project_id)) {
            die(json_encode(['ok' => false, 'error' => '이 프로젝트는 AI 사용이 꺼져 있습니다.']));
        }

        $industry = trim((string)($request['industry'] ?? ''));
        $region = trim((string)($request['region'] ?? ''));
        $product = trim((string)($request['product'] ?? ''));
        $purpose = trim((string)($request['purpose'] ?? ''));
        $target_customer = trim((string)($request['target_customer'] ?? ''));
        $content_type = trim((string)($request['content_type'] ?? ''));
        $season = trim((string)($request['season'] ?? ''));
        $brand_name = trim((string)($request['brand_name'] ?? ''));
        $must_keywords = trim((string)($request['must_keywords'] ?? ''));
        $exclude = trim((string)($request['exclude'] ?? ''));
        $count = (int)($request['count'] ?? 10);
        if (!in_array($count, [5, 10, 20], true)) $count = 10;
        $exclude_titles_param = bp_json_decode_post($request['exclude_titles'] ?? [], [], 'exclude_titles');
        $exclude_titles = is_array($exclude_titles_param) ? array_filter($exclude_titles_param) : [];

        if ($industry === '' && $product === '' && $must_keywords === '') {
            die(json_encode(['ok' => false, 'error' => '업종, 상품·서비스, 핵심 키워드 중 최소 1개는 입력해 주세요.']));
        }

        $lines = [];
        if ($industry !== '') $lines[] = "업종: {$industry}";
        if ($region !== '') $lines[] = "지역: {$region}";
        if ($product !== '') $lines[] = "홍보할 상품·서비스: {$product}";
        if ($purpose !== '') $lines[] = "글 목적: {$purpose}";
        if ($target_customer !== '') $lines[] = "예상 독자: {$target_customer}";
        if ($content_type !== '') $lines[] = "글 유형: {$content_type}";
        if ($season !== '') $lines[] = "계절·이슈: {$season}";
        if ($brand_name !== '') $lines[] = "브랜드명: {$brand_name}";
        if ($must_keywords !== '') $lines[] = "필수 포함 키워드: {$must_keywords}";
        if ($exclude !== '') $lines[] = "제외할 내용: {$exclude}";
        if (!empty($exclude_titles)) {
            $lines[] = "이미 사용했으므로 아래와 중복되거나 비슷한 제목은 제안하지 마십시오:\n- " . implode("\n- ", $exclude_titles);
        }

        $sys_prompt = "당신은 블로그 마케팅 콘텐츠 기획자입니다. 주어진 조건만으로 실제 방문·문의 유도에 도움이 되는 블로그 글감 후보를 제안합니다. 반드시 아래 JSON 형식으로만 응답하십시오. 서로 중복되는 제목과 내용은 제외하십시오.\n\n" .
            "{\"ideas\": [{\"title\": \"...\", \"topic\": \"...\", \"keywords\": [\"...\"], \"target_reader\": \"...\", \"purpose\": \"...\", \"structure\": \"...\", \"differentiator\": \"...\"}]}";
        $user_prompt = "다음 조건으로 블로그 글감 {$count}개를 제안하십시오.\n\n" . implode("\n", $lines);

        $provider_meta = bp_ai_get_provider_meta($project_id);
        if ($provider_meta['provider'] === 'template' && (!isset($request['accept_template_fallback']) || $request['accept_template_fallback'] !== '1')) {
            die(json_encode(bp_build_template_fallback_confirm($project_id)));
        }
        $ai_result = bp_ai_chat_request($user_prompt, $sys_prompt, $project_id);
        bp_log_generation_attempt($project_id, 'generate_topic_ideas', $provider_meta['provider'], $provider_meta['model'], $ai_result);

        if (!$ai_result['ok']) {
            die(json_encode(['ok' => false, 'error' => $ai_result['error']]));
        }

        $parsed = json_decode(trim($ai_result['message']), true);
        $ideas = [];
        if (is_array($parsed) && isset($parsed['ideas']) && is_array($parsed['ideas'])) {
            $ideas = $parsed['ideas'];
        } elseif (is_array($parsed) && array_keys($parsed) === range(0, count($parsed) - 1)) {
            $ideas = $parsed;
        }

        if (empty($ideas)) {
            die(json_encode(['ok' => false, 'error' => 'AI가 올바른 글감 후보를 생성하지 못했습니다.']));
        }

        $uid = time();
        foreach ($ideas as $idx => &$idea) {
            $idea['id'] = 'idea_' . $uid . '_' . $idx;
            if (!isset($idea['keywords']) || !is_array($idea['keywords'])) $idea['keywords'] = [];
        }
        unset($idea);

        $response['ideas'] = $ideas;
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

        $card_type = isset($request['card_type']) ? $request['card_type'] : '';
        $card_title = isset($request['card_title']) ? $request['card_title'] : '';
        $current_content = isset($request['current_content']) ? $request['current_content'] : '';
        $instruction = isset($request['instruction']) ? $request['instruction'] : '';
        $raw_material = isset($request['raw_material']) ? $request['raw_material'] : '';

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
        if ($provider_meta['provider'] === 'template' && (!isset($request['accept_template_fallback']) || $request['accept_template_fallback'] !== '1')) {
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

        // "연락처 누락" 검사는 원래 advertisers.phone(광고주 기본 전화)을 기준으로 했는데,
        // 이후 CTA 프로필 시스템(blog_cta_profiles)이 별도로 생겨서 실제 본문에 넣기로
        // 한 전화번호가 advertisers.phone과 다르거나(또는 advertisers.phone은 있는데
        // CTA에서 전화번호 항목 자체를 빼기로 한 경우) 항상 "누락"으로 오탐되는 문제가
        // 있었다. 클라이언트(inspectAll)가 실제 CTA 설정 기준의 전화번호를 넘겨주면
        // 그 값을 우선한다 - 이 액션을 거치지 않는 다른 호출부(project_action.php 등)는
        // 기존처럼 advertisers.phone을 그대로 쓴다.
        if (isset($request['expected_phone'])) {
            $advertiser['phone'] = trim((string) $request['expected_phone']);
        }

        // 클라이언트에서 넘긴 현재 조립된 카드 텍스트 정보 (최신 상태 검사)
        $title_text = isset($request['title_text']) ? $request['title_text'] : '';
        $body_text = isset($request['body_text']) ? $request['body_text'] : '';
        
        $post_data = array(
            'title' => $title_text,
            'body' => $body_text
        );

        // 중복 문장 검사에만 쓰는, 카드 제목을 뺀 본문. 클라이언트가 보내지 않으면
        // bp_quality_check()가 $body로 폴백하므로 이 액션을 거치지 않는 예전 호출부
        // (project_action.php 등)는 그대로 동작한다.
        if (isset($request['duplicate_check_body'])) {
            $post_data['duplicate_check_body'] = (string) $request['duplicate_check_body'];
        }

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
        $tag_type = isset($request['tag_type']) ? $request['tag_type'] : '';
        if ($tag_type !== 'keywords' && $tag_type !== 'hashtags') {
            die(json_encode(['ok' => false, 'error' => '알 수 없는 태그 종류입니다.']));
        }
        $values = bp_json_decode_post($request['values'] ?? [], [], 'values');
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

        $post = $post_id > 0 ? sql_fetch(" select id from {$post_table} where id = '{$post_id}' and project_id = '{$project_id}' ") : null;
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
        $tag_type = isset($request['tag_type']) ? $request['tag_type'] : '';
        if ($tag_type !== 'keywords' && $tag_type !== 'hashtags') {
            die(json_encode(['ok' => false, 'error' => '알 수 없는 태그 종류입니다.']));
        }
        $count = isset($request['count']) ? max(1, min(30, (int) $request['count'])) : 5;
        $raw_material = isset($request['raw_material']) ? trim($request['raw_material']) : '';
        $seed_values = bp_json_decode_post($request['seed_values'] ?? [], [], 'seed_values');
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
        $count = isset($request['count']) ? max(1, min(5, (int) $request['count'])) : 3;
        $content_text = isset($request['content_text']) ? trim($request['content_text']) : '';
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

    // ─── 최종 조립 (버전 신설) ─────────────────────────────────────────
    case 'assemble_final':
        $post_id = isset($request['post_id']) ? (int)$request['post_id'] : 0;
        $project_id_param = isset($request['project_id']) ? (int)$request['project_id'] : 0;
        $char_per_line = isset($request['char_per_line']) ? (int)$request['char_per_line'] : 60;
        
        $cardsInput = isset($request['cards']) ? $request['cards'] : null;

        if (is_string($cardsInput) && !mb_check_encoding($cardsInput, 'UTF-8')) {
            bp_json_die_error('카드 데이터에 잘못된 UTF-8 문자가 포함되어 있습니다.', 'CARDS_INVALID_UTF8');
        }

        // 공통 헬퍼 사용(원본 우선 디코드 → 실패 시에만 stripslashes 재시도, 이중 실패 시
        // 필드명 포함 서버 로그). 실패를 구분하기 위해 기본값을 null로 준다 - 빈 배열 []은
        // "카드 0개"라는 정상 입력일 수 있어 기본값으로 쓰면 실패와 구분이 안 된다.
        $cards = bp_json_decode_post($cardsInput, null, 'cards');

        // 파싱 실패($cards === null)면 여기서 즉시 중단한다 - 아래 DB 저장/버전 생성이
        // 절대 진행되지 않아야 한다. 빈 배열([])은 "카드 0개"라는 정상 입력이므로
        // 파싱 실패와 반드시 구분한다(그래서 기본값을 null로 줬다).
        if ($cards === null) {
            bp_json_response([
                'ok' => false,
                'code' => 'INVALID_CARDS_JSON',
                'message' => '카드 데이터 형식이 올바르지 않습니다.',
            ], 400);
        }
        if (!is_array($cards)) {
            $cards = [];
        }

        if ($post_id <= 0) {
            bp_json_die_error('유효하지 않은 글(post_id)입니다. 글이 정상적으로 저장되었는지 확인해 주세요.', 'POST_ID_REQUIRED');
        }

        if (!$cards) {
            bp_json_die_error('조립할 카드 데이터가 없습니다.', 'CARDS_EMPTY');
        }

        $tbl_posts = bp_table('posts');
        $post = sql_fetch(" select id, version, project_id from {$tbl_posts} where id = '{$post_id}' ");
        if (!$post) {
            bp_json_die_error('원고를 찾을 수 없습니다.', 'POST_NOT_FOUND');
        }

        if ($project_id_param > 0 && $post['project_id'] != $project_id_param) {
            bp_json_die_error('권한이 없거나 소유 관계가 일치하지 않는 원고입니다.', 'POST_ACCESS_DENIED');
        }
        
        $html = '';
        foreach ($cards as $c) {
            if (!is_array($c)) continue;
            if (isset($c['deleted']) && $c['deleted']) continue;
            // 카드 본문 필드명이 화면/저장 경로에 따라 다르게 저장된 경우까지 허용한다
            // (content가 정상 케이스지만, body/card_content/html로 들어온 과거 데이터 대비).
            $content = trim((string)(
                $c['content']
                ?? $c['body']
                ?? $c['card_content']
                ?? $c['html']
                ?? ''
            ));
            if ($content === '') continue;
            $type_str = isset($c['type']) ? $c['type'] : '';
            if ($type_str === 'title') {
                $html .= "<h2>{$content}</h2>\n";
            } else {
                $title_str = isset($c['title']) ? $c['title'] : (isset($c['card_title']) ? $c['card_title'] : '');
                if ($title_str) {
                    $html .= "<h3>{$title_str}</h3>\n";
                }
                $html .= "<p>" . nl2br($content) . "</p>\n";
            }
        }
        
        $new_version_no = $post['version'] + 1;
        $tbl_versions = $g5['table_prefix'] . 'blog_post_versions';
        
        $content_html_safe = sql_real_escape_string($html);
        $content_json_safe = sql_real_escape_string($cards_json);
        
        sql_query(" insert into {$tbl_versions} 
                    set post_id = '{$post_id}',
                        version_no = '{$new_version_no}',
                        version_type = 'final_assembly',
                        content_html = '{$content_html_safe}',
                        content_json = '{$content_json_safe}',
                        created_at = '" . G5_TIME_YMDHIS . "' ");
        $version_id = sql_insert_id();
        
        sql_query(" update {$tbl_posts} set version = '{$new_version_no}' where id = '{$post_id}' ");
        
        bp_json_response(['ok' => true, 'version_id' => $version_id]);

    // ─── 발행 관련 AJAX 액션 ─────────────────────────────────────────
    // publish: blog-studio/post_builder에서 즉시 or 예약 발행 요청
    // 보안: auth_check_menu 는 파일 상단에서 이미 통과함.
    case 'publish':
        include_once(G5_ADMIN_PATH . '/blog/lib/blog_publisher.lib.php');

        $version_id   = isset($request['version_id']) ? (int)$request['version_id'] : 0;
        $site_id      = isset($request['site_id']) ? (int)$request['site_id'] : 0;
        $sched_type   = isset($request['schedule_type']) ? $request['schedule_type'] : '';
        $sched_at_raw = isset($request['scheduled_at']) ? trim($request['scheduled_at']) : '';

        $errors = [];
        $warnings = [];

        if ($post_id <= 0 || $site_id <= 0 || $version_id <= 0) {
            $errors[] = 'post_id, site_id 또는 version_id가 없습니다.';
        }
        if (!in_array($sched_type, ['immediate', 'scheduled'], true)) {
            $errors[] = '발행 방식이 올바르지 않습니다.';
        }
        if ($sched_type === 'scheduled') {
            $now = date('Y-m-d H:i:s');
            if ($sched_at_raw === '' || $sched_at_raw < $now) {
                $errors[] = '예약 시각은 현재보다 미래여야 합니다.';
            }
        }

        $tbl_jobs_a    = bp_table('publish_jobs');
        $tbl_targets_a = bp_table('post_targets');
        $tbl_posts_a   = bp_table('posts');
        $tbl_proj_a    = bp_table('content_projects');
        $tbl_sites_a   = bp_table('sites');
        $tbl_versions_a = $g5['table_prefix'] . 'blog_post_versions';

        // 2. 버전 정보 확인 (스냅샷 강제 고정)
        $vrow = sql_fetch(" select * from {$tbl_versions_a} where id = '{$version_id}' and post_id = '{$post_id}' ");
        if (!$vrow) {
            $errors[] = '발행할 원고 버전(최종본)을 찾을 수 없습니다.';
        }

        $trow = sql_fetch(" select t.*, p.project_id, prj.status as project_status 
                             from {$tbl_targets_a} t
                             join {$tbl_posts_a} p on p.id = t.post_id
                             join {$tbl_proj_a} prj on prj.id = p.project_id
                             where t.post_id = '{$post_id}' and t.site_id = '{$site_id}' ");
        
        $srow = sql_fetch(" select * from {$tbl_sites_a} where id = '{$site_id}' ");
        if (!$srow) {
            $errors[] = '발행 대상 사이트를 찾을 수 없습니다.';
        }

        if (!$trow && empty($errors)) {
            $post = sql_fetch(" select project_id from {$tbl_posts_a} where id = '{$post_id}' ");
            if (!$post) {
                $errors[] = '원본 포스트를 찾을 수 없습니다.';
            } else {
                $prj = sql_fetch(" select status as project_status from {$tbl_proj_a} where id = '{$post['project_id']}' ");
                if (!$prj) {
                    $errors[] = '프로젝트를 찾을 수 없습니다.';
                } else {
                    sql_query(" insert into {$tbl_targets_a} set post_id = '{$post_id}', site_id = '{$site_id}', publish_status = 'ready', created_at = '" . G5_TIME_YMDHIS . "' ");
                    $pt_id = sql_insert_id();
                    // project_id를 반드시 포함해야 한다 - 아래 자동 승인 로직이
                    // $trow['project_id']로 전이 대상을 찾는데, 이 값이 없으면 project_id=0으로
                    // 전이가 조용히 실패한 채 상태만 approved로 덮어써져서, DB에는 승인되지
                    // 않은 프로젝트가 그대로 발행되는 상태 불일치가 발생한다.
                    $trow = [
                        'id' => $pt_id,
                        'project_id' => (int)$post['project_id'],
                        'project_status' => $prj['project_status'],
                        'title' => '',
                    ];
                }
            }
        } else {
            $pt_id = $trow['id'] ?? 0;
        }

        if ($trow && !bp_is_publish_allowed($trow['project_status'])) {
            // post_builder에서 작성자가 직접 발행을 시도하는 경우 자동 승인 처리
            if ($trow['project_status'] === 'draft' || $trow['project_status'] === 'pending_approval') {
                include_once(G5_ADMIN_PATH . '/blog/lib/blog_state_machine.lib.php');
                
                // 1. 자동 승인 권한 제한
                $is_admin_flag = $is_admin ?? '';
                if (!$is_admin_flag && !bp_user_can_approve_project($member, (int)$trow['project_id'])) {
                    bp_json_response([
                        'ok' => false,
                        'code' => 'APPROVAL_PERMISSION_DENIED',
                        'error' => '이 프로젝트를 승인하고 발행할 권한이 없습니다.'
                    ], 403);
                }
                
                $actor = isset($member['mb_id']) && trim($member['mb_id']) !== '' ? trim($member['mb_id']) : '';
                if ($actor === '') {
                    bp_json_response([
                        'ok' => false,
                        'code' => 'INVALID_ACTOR',
                        'error' => '유효한 로그인 사용자가 아닙니다.'
                    ], 403);
                }
                
                $p_id = (int)$trow['project_id'];
                
                $transition_ok = true;
                if ($trow['project_status'] === 'draft') {
                    $res1 = bp_transition_project($p_id, 'pending_approval', $actor, 'post_builder auto publish');
                    if (!$res1['ok']) {
                        $errors[] = '승인 대기 상태로의 자동 전이에 실패했습니다: ' . $res1['error'];
                        $transition_ok = false;
                    }
                }
                
                if ($transition_ok) {
                    $res2 = bp_transition_project($p_id, 'approved', $actor, 'post_builder auto publish');
                    if (!$res2['ok']) {
                        $errors[] = '자동 승인 처리에 실패했습니다: ' . $res2['error'];
                    } else {
                        // DB 전이가 완전히 성공했을 때만 메모리상의 상태를 덮어씀
                        $trow['project_status'] = 'approved';
                    }
                }
            } else {
                $errors[] = "'{$trow['project_status']}' 상태에서는 발행할 수 없습니다.";
            }
        }

        // ==========================================
        // 1단계 추가 검증
        // ==========================================
        if (empty($errors)) {
            $platform = $srow['platform'];
            
            // 1. 대표 이미지 필수 체크
            $req_thumb = isset($request['require_thumbnail']) ? $request['require_thumbnail'] : 'N';
            if ($req_thumb === 'Y') {
                $thumb = isset($request['thumbnail']) ? trim($request['thumbnail']) : '';
                if ($thumb === '') {
                    $errors[] = '대표 이미지를 선택해 주세요.';
                }
            }
            
            // 2. 카테고리 - 선택 입력이다(필수 아님).
            // 화면(발행 설정 모달)이 "카테고리 ID (선택)", "빈 칸이면 기본값"이라고 명시하고,
            // 실제 발행 시 카테고리는 이 값이 아니라 blog_category_mappings에서 조회해
            // 없으면 '미분류'로 처리한다(blog_publisher.lib.php). 즉 이 값은 발행 로직에서
            // 전혀 쓰이지 않는데도 비어 있으면 발행을 막고 있어서, UI 안내와 모순된 채
            // 정상적인 발행이 전부 차단됐다.
            $cat_id = isset($request['category_id']) ? trim($request['category_id']) : '';

            // 3. 글자수 제한 체크
            $snap_title_s = mb_substr($trow['title'] ?? '', 0, 255);
            $snap_body_s  = $vrow['content_html'];
            $title_len = mb_strlen($snap_title_s, 'UTF-8');
            $body_len = mb_strlen(strip_tags($snap_body_s), 'UTF-8');
            
            $limit_title = 100;
            $limit_body = 65000;
            $limit_tag = 30;
            if ($platform === 'naver' || $platform === 'naver_blog') {
                $limit_title = 100;
                $limit_body = 65000;
                $limit_tag = 30;
            } elseif ($platform === 'wordpress') {
                $limit_title = 200;
                $limit_body = 500000;
                $limit_tag = 200;
            } elseif ($platform === 'php') {
                // 자체 PHP 사이트(BlogPhpPublisher)는 플랫폼 자체 글자수 제약이 없지만,
                // 아무 제한도 없이 그냥 통과시키면 "제목/본문/태그 제한 체크"가 이
                // 어댑터에서만 항상 비활성인 것처럼 보인다 - DB 컬럼 크기를 기준으로
                // 넉넉한 상한을 둔다(posts.title varchar(255) 기준).
                $limit_title = 255;
                $limit_body = 1000000;
                $limit_tag = 50;
            }
            if ($title_len > $limit_title) {
                $errors[] = "제목이 제한 길이를 초과했습니다 (현재 {$title_len}자 / 최대 {$limit_title}자).";
            }
            if ($body_len > $limit_body) {
                $errors[] = "본문이 제한 길이를 초과했습니다 (현재 {$body_len}자 / 최대 {$limit_body}자).";
            }

            // 5. 태그 글자수 제한 (지시서에 명시됐으나 누락돼 있던 부분)
            $tags_raw = isset($request['tags']) ? trim($request['tags']) : '';
            if ($tags_raw !== '') {
                foreach (array_filter(array_map('trim', explode(',', $tags_raw))) as $one_tag) {
                    $tag_len = mb_strlen($one_tag, 'UTF-8');
                    if ($tag_len > $limit_tag) {
                        $errors[] = "태그 '{$one_tag}'가 제한 길이를 초과했습니다 (현재 {$tag_len}자 / 최대 {$limit_tag}자).";
                    }
                }
            }

            // 4. 본문 이미지 경로 체크 (접근성)
            preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $snap_body_s, $img_matches);
            if (!empty($img_matches[1])) {
                foreach ($img_matches[1] as $img_src) {
                    if (strpos($img_src, 'http') === 0) {
                        $ch = curl_init($img_src);
                        curl_setopt($ch, CURLOPT_NOBODY, true);
                        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
                        curl_exec($ch);
                        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        if ($http_code < 200 || $http_code >= 400) {
                            $warnings[] = "이미지 접근 불가: {$img_src} (HTTP {$http_code})";
                        }
                    } else if (strpos($img_src, 'data:image') === 0) {
                        // Data URI는 정상
                    } else {
                        // 로컬 상대경로일 수 있음
                        $warnings[] = "외부 서버에서 접근할 수 없는 로컬 이미지 경로가 포함되어 있습니다: {$img_src}";
                    }
                }
            }
        }

        if (!empty($errors)) {
            bp_json_response(['ok' => false, 'errors' => $errors, 'warnings' => $warnings]);
        }

        $snap_title_s = sql_real_escape_string(mb_substr($trow['title'] ?? '', 0, 255));
        $snap_body_s  = sql_real_escape_string($vrow['content_html']);
        $creator = isset($member['mb_id']) ? sql_real_escape_string($member['mb_id']) : 'admin';

        if ($sched_type === 'scheduled') {


            $job_row = bp_get_or_create_publish_job((int)$pt_id, [
                'snapshot_title' => $snap_title_s,
                'snapshot_body'  => $snap_body_s,
                'created_by'     => $creator,
                'schedule_type'  => 'scheduled',
                'scheduled_at'   => $sched_at_raw
            ]);

            if (!$job_row) {
                // bp_get_or_create_publish_job()이 null을 반환하는 경우는 (1) 대상 미발견,
                // (2) 예외(대부분 SQL 오류)뿐이다. 이미 진행 중인 작업이 있으면 null이 아니라
                // 기존 작업 행을 반환하므로, "이미 진행 중"이라는 안내는 사실과 다르고 진짜
                // 원인(예: publish_jobs 스키마 불일치)을 감춘다.
                $job_err = isset($GLOBALS['bp_last_job_error']) ? $GLOBALS['bp_last_job_error'] : '';
                die(json_encode([
                    'ok' => false,
                    'code' => 'PUBLISH_JOB_CREATE_FAILED',
                    'error' => '발행 작업을 생성하지 못했습니다.' . ($job_err !== '' ? ' (' . $job_err . ')' : ' (원인 미상 - 서버 로그의 publish_job_create_failed 항목 확인)')
                ], JSON_UNESCAPED_UNICODE));
            }

            sql_query(" update {$tbl_targets_a} set publish_status = 'scheduled', published_version_id = '{$version_id}' where id = '{$pt_id}' ");
            $response['job_id']     = (int)$job_row['id'];
            $response['job_status'] = 'scheduled';
            $response['message']    = '예약 발행이 등록되었습니다.';

        } else {
            // 즉시 발행
            $job_row = bp_get_or_create_publish_job((int)$pt_id, [
                'snapshot_title' => $snap_title_s,
                'snapshot_body'  => $snap_body_s,
                'created_by'     => $creator
            ]);
            
            if (!$job_row) {
                // 위 예약 발행 분기와 동일 - null은 "이미 진행 중"이 아니라 생성 실패를 뜻한다.
                $job_err = isset($GLOBALS['bp_last_job_error']) ? $GLOBALS['bp_last_job_error'] : '';
                die(json_encode([
                    'ok' => false,
                    'code' => 'PUBLISH_JOB_CREATE_FAILED',
                    'error' => '발행 작업을 생성하지 못했습니다.' . ($job_err !== '' ? ' (' . $job_err . ')' : ' (원인 미상 - 서버 로그의 publish_job_create_failed 항목 확인)')
                ], JSON_UNESCAPED_UNICODE));
            }

            $new_job_id = (int)$job_row['id'];
            
            // targets 상태 업데이트
            sql_query(" update {$tbl_targets_a} set publish_status = 'queued', published_version_id = '{$version_id}' where id = '{$pt_id}' ");

            // 외부 API 전송
            $actor = $creator;
            $result = bp_dispatch_publish_job($pt_id, $actor);
            if (!$result['ok']) {
                die(json_encode(['ok' => false, 'error' => $result['error']]));
            }
            
            // 완료된 job의 최신 상태를 반환
            $job_row = sql_fetch(" select id, status, completed_at, last_error_message
                                    from {$tbl_jobs_a}
                                    where post_target_id = '{$pt_id}'
                                    order by id desc limit 1 ");
            $target_row = sql_fetch(" select publish_status, published_url, external_post_id
                                       from {$tbl_targets_a} where id = '{$pt_id}' ");
                                       
            $response['job_id']       = $job_row ? (int)$job_row['id'] : 0;
            $response['job_status']   = $job_row ? $job_row['status'] : 'unknown';
            $response['published_url']= $target_row ? $target_row['published_url'] : '';
            // 네이버 수동 발행 흐름의 "수동 발행 완료 기록" 버튼이 이 값을 쓴다.
            // 프런트의 _pbTargets는 발행 '이전'에 로드되므로, 이번 발행에서 새로 만들어진
            // 타겟은 target_id가 0이라 그 버튼이 항상 "대상 정보를 찾을 수 없습니다"로 끝났다.
            $response['target_id']    = (int)$pt_id;
            $response['message']      = '발행이 완료되었습니다.';
        }
        break;


    // job_status: UI에서 발행 완료 여부를 폴링할 때 사용 (3초 간격, 최대 30초)
    case 'job_status':
        $job_id_q = isset($request['job_id']) ? (int)$request['job_id'] : 0;
        $pt_id_q  = isset($request['post_target_id']) ? (int)$request['post_target_id'] : 0;
        $tbl_jobs_b    = bp_table('publish_jobs');
        $tbl_targets_b = bp_table('post_targets');

        if ($job_id_q > 0) {
            $job_row = sql_fetch(" select * from {$tbl_jobs_b} where id = '{$job_id_q}' ");
            if (!$job_row) {
                die(json_encode(['ok' => false, 'error' => '작업을 찾을 수 없습니다.']));
            }
            $target_row = sql_fetch(" select publish_status, published_url, external_post_id
                                       from {$tbl_targets_b}
                                       where id = '" . (int)$job_row['post_target_id'] . "' ");
            $response['job_id']             = (int)$job_row['id'];
            $response['job_status']         = $job_row['status'];
            $response['attempt_count']      = (int)$job_row['attempt_count'];
            $response['last_error_code']    = $job_row['last_error_code'] ?? '';
            $response['last_error_message'] = $job_row['last_error_message'] ?? '';
            $response['completed_at']       = $job_row['completed_at'] ?? '';
            $response['published_url']      = $target_row ? $target_row['published_url']  : '';
            $response['publish_status']     = $target_row ? $target_row['publish_status'] : '';
        } elseif ($pt_id_q > 0) {
            // post_target_id로 최신 job 조회
            $job_row    = sql_fetch(" select * from {$tbl_jobs_b}
                                       where post_target_id = '{$pt_id_q}'
                                       order by id desc limit 1 ");
            $target_row = sql_fetch(" select publish_status, published_url, external_post_id
                                       from {$tbl_targets_b} where id = '{$pt_id_q}' ");
            if ($job_row) {
                $response['job_id']             = (int)$job_row['id'];
                $response['job_status']         = $job_row['status'];
                $response['attempt_count']      = (int)$job_row['attempt_count'];
                $response['last_error_message'] = $job_row['last_error_message'] ?? '';
                $response['completed_at']       = $job_row['completed_at'] ?? '';
            }
            $response['published_url']  = $target_row ? $target_row['published_url']  : '';
            $response['publish_status'] = $target_row ? $target_row['publish_status'] : '';
        } else {
            die(json_encode(['ok' => false, 'error' => 'job_id 또는 post_target_id가 필요합니다.']));
        }
        break;


    // reset_target_status: post_targets의 상태를 draft로 초기화 (재발행을 위해)
    case 'reset_target_status':
        $target_id = isset($request['target_id']) ? (int)$request['target_id'] : 0;
        if ($target_id === 0) {
            die(json_encode(['ok' => false, 'error' => '타겟 ID가 유효하지 않습니다.']));
        }
        $tbl_tar_r = bp_table('post_targets');
        $tbl_jobs_r = bp_table('publish_jobs');
        
        sql_query(" update {$tbl_tar_r} set publish_status = 'draft' where id = '{$target_id}' ");
        // 관련된 최신/활성 발행 작업들의 영구 잠금 해제
        sql_query(" update {$tbl_jobs_r} set active_lock_key = NULL where post_target_id = '{$target_id}' and active_lock_key IS NOT NULL ");
        
        $response['ok'] = true;
        break;

    // get_post_targets: 현재 포스트에 연결된 사이트(post_targets) 목록 조회
    // 발행 모달에서 어느 사이트에 발행할지 선택할 때 사용
    case 'get_post_targets':
        $project_id_param = isset($request['project_id']) ? (int)$request['project_id'] : 0;
        if ($post_id === 0 && $project_id_param === 0) {
            die(json_encode(['ok' => false, 'error' => '포스트 또는 프로젝트 정보가 없습니다.']));
        }
        $tbl_tar_c  = bp_table('post_targets');
        $tbl_site_c = bp_table('sites');
        $tbl_jobs_c = bp_table('publish_jobs');
        $tbl_post_c = bp_table('posts');
        $tbl_proj_c = bp_table('content_projects');

        if ($project_id_param > 0) {
            $project = sql_fetch(" select advertiser_id from {$tbl_proj_c} where id = '{$project_id_param}' ");
        } else {
            $post = sql_fetch(" select project_id from {$tbl_post_c} where id = '{$post_id}' ");
            if (!$post) {
                die(json_encode(['ok' => false, 'error' => '포스트를 찾을 수 없습니다.']));
            }
            $project = sql_fetch(" select advertiser_id from {$tbl_proj_c} where id = '{$post['project_id']}' ");
        }
        
        if (!$project) {
            die(json_encode(['ok' => false, 'error' => '프로젝트를 찾을 수 없습니다.']));
        }
        $adv_id = $project['advertiser_id'];

        $targets_res = sql_query(" select s.id as site_id, s.name as site_name, s.platform, s.base_url,
                                          t.id as target_id, t.publish_status, t.published_url, t.external_post_id,
                                          t.last_error, t.retry_count,
                                          (select count(*) from {$tbl_jobs_c} j
                                           where j.post_target_id = t.id
                                             and j.status in ('queued','publishing','scheduled')) as active_jobs,
                                          (select j2.id from {$tbl_jobs_c} j2
                                           where j2.post_target_id = t.id
                                           order by j2.id desc limit 1) as latest_job_id,
                                          (select j2.status from {$tbl_jobs_c} j2
                                           where j2.post_target_id = t.id
                                           order by j2.id desc limit 1) as latest_job_status
                                   from {$tbl_site_c} s
                                   left join {$tbl_tar_c} t on t.site_id = s.id and t.post_id = '{$post_id}'
                                   where s.advertiser_id = '{$adv_id}' and s.status = 'Y'
                                   order by s.id asc ");
        $targets_list = [];
        while ($row = sql_fetch_array($targets_res)) {
            $targets_list[] = [
                'site_id'         => (int)$row['site_id'],
                'target_id'       => (int)($row['target_id'] ?? 0),
                'site_name'       => $row['site_name'],
                'platform'        => $row['platform'],
                'base_url'        => $row['base_url'],
                'publish_status'  => $row['publish_status'],
                'published_url'   => $row['published_url'],
                'external_post_id'=> $row['external_post_id'],
                'last_error'      => bp_scrub_secrets($row['last_error'] ?? ''),
                'retry_count'     => (int)$row['retry_count'],
                'has_active_job'  => ((int)$row['active_jobs'] > 0),
                'latest_job_id'   => (int)($row['latest_job_id'] ?? 0),
                'latest_job_status'=> $row['latest_job_status'] ?? '',
            ];
        }
        $response['targets'] = $targets_list;
        $response['message'] = count($targets_list) . '개 대상 로드 완료';
        break;
    case 'save_prompt_state':
        if (!bp_post_prompts_table_ready()) {
            die(json_encode(['ok'=>false, 'error'=>'단계별 누적 프롬프트 기능이 아직 설치되지 않았습니다. 관리자 설치 화면에서 v30을 먼저 실행해 주세요.']));
        }
        $project_id = isset($request['project_id']) ? (int)$request['project_id'] : 0;
        $post_id    = isset($request['post_id']) ? (int)$request['post_id'] : 0;
        $step       = isset($request['step']) ? (int)$request['step'] : 1;
        
        $global_prompt = isset($request['global_prompt']) ? $request['global_prompt'] : '';
        $step_1_prompt = isset($request['step_1_prompt']) ? $request['step_1_prompt'] : '';
        $step_2_prompt = isset($request['step_2_prompt']) ? $request['step_2_prompt'] : '';
        $step_3_prompt = isset($request['step_3_prompt']) ? $request['step_3_prompt'] : '';
        $step_4_prompt = isset($request['step_4_prompt']) ? $request['step_4_prompt'] : '';
        $step_5_prompt = isset($request['step_5_prompt']) ? $request['step_5_prompt'] : '';
        $user_custom   = isset($request['user_custom_prompt']) ? $request['user_custom_prompt'] : '';
        $merged_prompt = isset($request['merged_prompt']) ? $request['merged_prompt'] : '';
        $prompt_json   = isset($request['prompt_json']) ? $request['prompt_json'] : '';
        
        if (!$post_id || !$project_id) {
            die(json_encode(['ok'=>false, 'error'=>'post_id, project_id is required.']));
        }
        
        $tbl_prompts = bp_table('post_prompts');
        $check = sql_fetch(" select id from {$tbl_prompts} where post_id = '{$post_id}' ");
        
        if ($check) {
            $sql = " update {$tbl_prompts}
                        set current_step = '{$step}',
                            global_prompt = '" . sql_real_escape_string($global_prompt) . "',
                            step_1_prompt = '" . sql_real_escape_string($step_1_prompt) . "',
                            step_2_prompt = '" . sql_real_escape_string($step_2_prompt) . "',
                            step_3_prompt = '" . sql_real_escape_string($step_3_prompt) . "',
                            step_4_prompt = '" . sql_real_escape_string($step_4_prompt) . "',
                            step_5_prompt = '" . sql_real_escape_string($step_5_prompt) . "',
                            user_custom_prompt = '" . sql_real_escape_string($user_custom) . "',
                            merged_prompt = '" . sql_real_escape_string($merged_prompt) . "',
                            prompt_json = '" . sql_real_escape_string($prompt_json) . "',
                            updated_at = '" . G5_TIME_YMDHIS . "'
                      where post_id = '{$post_id}' ";
        } else {
            $sql = " insert into {$tbl_prompts}
                        set post_id = '{$post_id}',
                            project_id = '{$project_id}',
                            current_step = '{$step}',
                            global_prompt = '" . sql_real_escape_string($global_prompt) . "',
                            step_1_prompt = '" . sql_real_escape_string($step_1_prompt) . "',
                            step_2_prompt = '" . sql_real_escape_string($step_2_prompt) . "',
                            step_3_prompt = '" . sql_real_escape_string($step_3_prompt) . "',
                            step_4_prompt = '" . sql_real_escape_string($step_4_prompt) . "',
                            step_5_prompt = '" . sql_real_escape_string($step_5_prompt) . "',
                            user_custom_prompt = '" . sql_real_escape_string($user_custom) . "',
                            merged_prompt = '" . sql_real_escape_string($merged_prompt) . "',
                            prompt_json = '" . sql_real_escape_string($prompt_json) . "',
                            created_at = '" . G5_TIME_YMDHIS . "',
                            updated_at = '" . G5_TIME_YMDHIS . "' ";
        }
        sql_query($sql);
        $response['message'] = '프롬프트 상태 저장 완료';
        break;

    case 'load_prompt_state':
        if (!bp_post_prompts_table_ready()) {
            $response['prompt_state'] = null;
            break;
        }
        $post_id = isset($request['post_id']) ? (int)$request['post_id'] : 0;
        if (!$post_id) {
            die(json_encode(['ok'=>false, 'error'=>'post_id is required.']));
        }
        $tbl_prompts = bp_table('post_prompts');
        $row = sql_fetch(" select * from {$tbl_prompts} where post_id = '{$post_id}' ");
        $response['prompt_state'] = $row ? $row : null;
        break;

    case 'generate_final_prompts':
        if (!bp_post_prompts_table_ready()) {
            die(json_encode(['ok'=>false, 'error'=>'단계별 누적 프롬프트 기능이 아직 설치되지 않았습니다. 관리자 설치 화면에서 v30을 먼저 실행해 주세요.']));
        }
        $post_id = isset($request['post_id']) ? (int)$request['post_id'] : 0;
        if (!$post_id) {
            die(json_encode(['ok'=>false, 'error'=>'post_id is required.']));
        }
        
        $tbl_prompts = bp_table('post_prompts');
        $row = sql_fetch(" select * from {$tbl_prompts} where post_id = '{$post_id}' ");
        if (!$row) {
            die(json_encode(['ok'=>false, 'error'=>'저장된 프롬프트 상태가 없습니다.']));
        }
        
        // 5단계 최종 프롬프트 조립 (기획서 8절)
        $content_prompt = "";
        $content_prompt .= trim($row['global_prompt']) . "\n\n";
        $content_prompt .= trim($row['step_1_prompt']) . "\n\n";
        $content_prompt .= trim($row['step_2_prompt']) . "\n\n";
        $content_prompt .= trim($row['step_3_prompt']) . "\n\n";
        $content_prompt .= trim($row['step_4_prompt']) . "\n\n";
        $content_prompt .= trim($row['step_5_prompt']) . "\n\n";
        $content_prompt .= trim($row['user_custom_prompt']);
        $content_prompt = trim(preg_replace('/\n{3,}/', "\n\n", $content_prompt)); // 중복 공백 제거

        // 작업지시문 조립 (기획서 9절)
        $agent_work_order = "# 작업명\n자동 생성된 포스트 빌더 작업지시\n\n# 작업 목적\n위 조건에 맞는 최종 콘텐츠 생성을 수행합니다.\n\n# 프롬프트 조건\n" . $content_prompt;
        
        // DB 저장
        sql_query(" update {$tbl_prompts} 
                       set agent_work_order = '" . sql_real_escape_string($agent_work_order) . "'
                     where id = '{$row['id']}' ");
                     
        $response['content_prompt'] = $content_prompt;
        $response['agent_work_order'] = $agent_work_order;
        break;
    // ==========================================
    // CTA 프로필 관리 기능 (방안 B)
    // ==========================================
    case 'load_cta_profiles':
        $advertiser_id = isset($request['advertiser_id']) ? (int)$request['advertiser_id'] : 0;
        if (!$advertiser_id) die(json_encode(['ok'=>false, 'error'=>'advertiser_id is required.']));
        
        $tbl_profiles = $g5['table_prefix'] . 'blog_cta_profiles';
        $tbl_adv = $g5['table_prefix'] . 'blog_advertisers';
        
        // 1. 해당 광고주의 활성 프로필 모두 불러오기
        $profiles = [];
        $res = sql_query("SELECT * FROM {$tbl_profiles} WHERE advertiser_id='{$advertiser_id}' AND is_active=1 AND deleted_at IS NULL ORDER BY sort_order ASC, is_default DESC, id DESC");
        while ($row = sql_fetch_array($res)) {
            $row['custom_fields'] = $row['custom_fields_json'] ? json_decode($row['custom_fields_json'], true) : [];
            $profiles[] = $row;
        }
        
        // 2. 만약 프로필이 단 하나도 없다면 마스터(광고주) 정보에서 기본 프로필 임시 생성 반환
        if (empty($profiles)) {
            $adv = sql_fetch("SELECT * FROM {$tbl_adv} WHERE id='{$advertiser_id}'");
            if ($adv) {
                $default_profile = [
                    'id' => 0, // 아직 저장되지 않음
                    'advertiser_id' => $advertiser_id,
                    'profile_name' => '기본 프로필',
                    'business_name' => $adv['name'],
                    'primary_phone' => $adv['contact_phone'],
                    'address' => $adv['address'],
                    'service_area' => $adv['service_region'],
                    'homepage' => $adv['website_url'],
                    'is_default' => 1,
                    'custom_fields' => []
                ];
                $profiles[] = $default_profile;
            }
        }
        $response['profiles'] = $profiles;
        break;

    case 'save_cta_profile':
        $profile = isset($request['profile']) ? $request['profile'] : [];
        if (empty($profile['advertiser_id'])) die(json_encode(['ok'=>false, 'error'=>'advertiser_id is required.']));
        
        $tbl_profiles = $g5['table_prefix'] . 'blog_cta_profiles';
        $id = (int)($profile['id'] ?? 0);
        $adv_id = (int)$profile['advertiser_id'];
        
        $profile_name = sql_real_escape_string($profile['profile_name'] ?? '새 프로필');
        $business_name = sql_real_escape_string($profile['business_name'] ?? '');
        $primary_phone = sql_real_escape_string($profile['primary_phone'] ?? '');
        $secondary_phone = sql_real_escape_string($profile['secondary_phone'] ?? '');
        $fax = sql_real_escape_string($profile['fax'] ?? '');
        $address = sql_real_escape_string($profile['address'] ?? '');
        $homepage = sql_real_escape_string($profile['homepage'] ?? '');
        $email = sql_real_escape_string($profile['email'] ?? '');
        $service_area = sql_real_escape_string($profile['service_area'] ?? '');
        $is_default = (int)($profile['is_default'] ?? 0);
        $custom_json = sql_real_escape_string(json_encode($profile['custom_fields'] ?? [], JSON_UNESCAPED_UNICODE));
        $now = G5_TIME_YMDHIS;
        
        if ($is_default) {
            // 다른 기본 프로필 해제
            sql_query("UPDATE {$tbl_profiles} SET is_default=0 WHERE advertiser_id='{$adv_id}'");
        }
        
        if ($id > 0) {
            $sql = "UPDATE {$tbl_profiles} SET 
                        profile_name='{$profile_name}', business_name='{$business_name}', primary_phone='{$primary_phone}',
                        secondary_phone='{$secondary_phone}', fax='{$fax}', address='{$address}', homepage='{$homepage}',
                        email='{$email}', service_area='{$service_area}', custom_fields_json='{$custom_json}', 
                        is_default='{$is_default}', updated_at='{$now}'
                    WHERE id='{$id}' AND advertiser_id='{$adv_id}'";
            sql_query($sql);
            $response['profile_id'] = $id;
        } else {
            $sql = "INSERT INTO {$tbl_profiles} SET 
                        advertiser_id='{$adv_id}', profile_name='{$profile_name}', business_name='{$business_name}', 
                        primary_phone='{$primary_phone}', secondary_phone='{$secondary_phone}', fax='{$fax}', 
                        address='{$address}', homepage='{$homepage}', email='{$email}', service_area='{$service_area}', 
                        custom_fields_json='{$custom_json}', is_default='{$is_default}', created_at='{$now}', updated_at='{$now}'";
            sql_query($sql);
            $response['profile_id'] = sql_insert_id();
        }
        break;

    case 'delete_cta_profile':
        $id = isset($request['id']) ? (int)$request['id'] : 0;
        if (!$id) die(json_encode(['ok'=>false, 'error'=>'id is required.']));
        $tbl_profiles = $g5['table_prefix'] . 'blog_cta_profiles';
        sql_query("UPDATE {$tbl_profiles} SET is_active=0, deleted_at='".G5_TIME_YMDHIS."' WHERE id='{$id}'");
        break;

    case 'save_cta_snapshot':
        $post_id = isset($request['post_id']) ? (int)$request['post_id'] : 0;
        $profile_id = isset($request['cta_profile_id']) ? (int)$request['cta_profile_id'] : 0;
        $snapshot = isset($request['snapshot_json']) ? $request['snapshot_json'] : '{}';
        if (!$post_id) die(json_encode(['ok'=>false, 'error'=>'post_id is required.']));
        
        $tbl_snap = $g5['table_prefix'] . 'blog_post_cta_snapshots';
        $snap_esc = sql_real_escape_string($snapshot);
        $now = G5_TIME_YMDHIS;
        
        $chk = sql_fetch("SELECT post_id FROM {$tbl_snap} WHERE post_id='{$post_id}'");
        if ($chk) {
            sql_query("UPDATE {$tbl_snap} SET cta_profile_id='{$profile_id}', snapshot_json='{$snap_esc}', updated_at='{$now}' WHERE post_id='{$post_id}'");
        } else {
            sql_query("INSERT INTO {$tbl_snap} SET post_id='{$post_id}', cta_profile_id='{$profile_id}', snapshot_json='{$snap_esc}', created_at='{$now}', updated_at='{$now}'");
        }
        break;

    case 'mark_manual_published':
        $pt_id = isset($request['post_target_id']) ? (int)$request['post_target_id'] : 0;
        $published_url = isset($request['published_url']) ? trim($request['published_url']) : '';
        
        if (!$pt_id || !$published_url) {
            die(json_encode(['ok' => false, 'error' => 'post_target_id and published_url are required.']));
        }
        
        $tbl_targets = bp_table('post_targets');
        $tbl_jobs = bp_table('publish_jobs');
        
        // 대상 조회
        $target = sql_fetch(" select id, publish_status from {$tbl_targets} where id = '{$pt_id}' ");
        if (!$target) {
            die(json_encode(['ok' => false, 'error' => '대상을 찾을 수 없습니다.']));
        }
        
        // 강제로 완료 마킹
        $now = G5_TIME_YMDHIS;
        $url_esc = sql_real_escape_string($published_url);
        
        sql_query(" update {$tbl_targets} set publish_status = 'published', published_url = '{$url_esc}' where id = '{$pt_id}' ");
        
        // 관련된 job 중 완료되지 않은 최신 작업이 있으면 수동 완료로 상태 업데이트.
        // 'published'는 bp_is_terminal_publish_status()의 종료 상태 목록에 없어서
        // (완료 판정은 'completed'/'success' 기준) 실제 자동 발행 성공 경로
        // (blog_scheduler.lib.php:137-144)도 이 함수에 기대지 않고 active_lock_key/
        // lock_token/locked_at을 직접 같이 지운다 - 여기서도 그 세 필드를 반드시 같이
        // 지워야 한다. 빠뜨리면 락이 영구히 안 풀려서 같은 글 재발행 시도가 계속
        // "발행 작업이 이미 진행 중입니다"로 막힌다(v27~v29에서 고친 것과 같은 버그).
        $active_job = sql_fetch(" select id from {$tbl_jobs} where post_target_id = '{$pt_id}' order by id desc limit 1 ");
        if ($active_job) {
            sql_query(" update {$tbl_jobs}
                           set status = 'published',
                               active_lock_key = NULL,
                               lock_token = NULL,
                               locked_at = NULL,
                               completed_at = '{$now}',
                               last_error_message = '수동 등록됨'
                         where id = '{$active_job['id']}' ");
        }
        
        $response['message'] = '수동 발행 처리가 완료되었습니다.';
        break;

    default:
        $response['error'] = 'Unknown action';
        $response['ok'] = false;
        break;
}

bp_json_response($response);
