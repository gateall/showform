<?php
if (!defined('_GNUBOARD_')) exit;

require_once G5_LIB_PATH . '/showform_ai.lib.php';

// AI 생성 서비스 인터페이스 (BLOG_AUTOMATION_API_SPEC.md generate-titles/generate-content)
// Phase 2: 실제 외부 AI 호출(BlogOpenAiProvider)과 결정적 템플릿 폴백(BlogAiTemplateProvider)을
// 이 인터페이스 뒤로 분리한다. 두 구현 모두 실패 시에도 예외를 던지지 않고 ok=false로
// 보고한다 — 호출부(project_action.php)가 실패 시 기존 초안을 그대로 보존할 수 있어야 하기 때문이다.
interface BlogAiProvider
{
    // 반환: array('ok'=>bool, 'titles'=>string[], 'error'=>string, 'tokens_prompt'=>int, 'tokens_completion'=>int)
    public function generateTitles(array $params, int $count): array;
    // 반환: array('ok'=>bool, 'body'=>string, 'hashtags'=>string, 'error'=>string, 'tokens_prompt'=>int, 'tokens_completion'=>int)
    public function generateBody(array $params): array;
    // 자유 형식 단일 프롬프트 채팅(포스팅 제작 화면의 "방향/제목/도입부/본문블록/글감분석" 등
    // 정형화되지 않은 짧은 생성에 공용으로 쓴다). 반환: array('ok'=>bool, 'message'=>string, 'error'=>string)
    public function chatRequest(string $prompt, string $systemPrompt): array;
    
    // 대화 내역(메시지 배열)을 통째로 전달하여 컨텍스트를 유지하는 챗봇용 API
    public function chatWithHistory(array $messages, bool $jsonMode = true): array;
}

// 템플릿 기반 폴백 공급자 — 키 없이 항상 동작하며 절대 실패하지 않는다(ok는 항상 true).
// lib/showform_ai.lib.php 의 업종 분기 로직을 재사용한다.
class BlogAiTemplateProvider implements BlogAiProvider
{
    public function generateTitles(array $params, int $count): array
    {
        $topic = isset($params['topic']) ? trim($params['topic']) : '서비스';
        $keyword = isset($params['primary_keyword']) ? trim($params['primary_keyword']) : $topic;
        $region = isset($params['service_region']) ? trim($params['service_region']) : '';
        $prefix = $region !== '' ? $region . ' ' : '';

        $patterns = array(
            $prefix . $keyword . ' 전 꼭 확인할 체크리스트',
            $prefix . $keyword . '을(를) 선택하는 기준',
            $keyword . '과(와) 일반 상품의 차이',
            $prefix . $topic . ' 준비 전 확인해야 할 비용',
            $keyword . ' 속도·비용·절차 비교 안내',
            $prefix . $topic . '에 대해 가장 많이 묻는 질문',
            $keyword . '으로 실제 문제를 해결한 사례',
        );

        $count = max(3, min(10, (int) $count));
        $titles = array();
        for ($i = 0; $i < $count && $i < count($patterns); $i++) {
            $titles[] = mb_substr($patterns[$i], 0, 60);
        }
        return array('ok' => true, 'titles' => $titles, 'error' => '', 'tokens_prompt' => 0, 'tokens_completion' => 0);
    }

    public function generateBody(array $params): array
    {
        $industry = isset($params['content_type']) ? $params['content_type'] : '';
        $company_name = isset($params['company_name']) ? $params['company_name'] : '{{business_name}}';
        $region = isset($params['service_region']) ? $params['service_region'] : '';
        $intro = isset($params['topic']) ? $params['topic'] : '';

        $sections = array();
        $sections[] = generate_main_copy($industry, $company_name, $region, $intro);
        $sections[] = generate_sub_copy($industry, $company_name, $region, $intro);
        $sections[] = "\n[문제]\n" . generate_problem_text($industry, $company_name, $region, $intro);
        $sections[] = "\n[강점]\n" . generate_strength_text($industry, $company_name, $region, $intro);
        $sections[] = "\n[FAQ]\n" . generate_faq_text($industry, $company_name, $region, $intro);
        $sections[] = "\n[CTA]\n" . generate_cta_text($industry, $company_name, $region, $intro) . " {{phone}} / {{consult_url}}";

        return array(
            'ok' => true,
            'body' => implode("\n", $sections),
            'hashtags' => $this->buildHashtags($params),
            'error' => '',
            'tokens_prompt' => 0,
            'tokens_completion' => 0,
        );
    }

    // 템플릿 모드는 정해진 틀(제목/본문) 밖의 자유형 프롬프트(방향 추천, 글감 분석 등)를
    // 의미 있게 흉내낼 방법이 없다 — 아무 문장이나 만들어 "생성 성공"으로 위장하는 대신
    // 명확한 실패로 보고한다(호출부가 이미 ok=false 처리를 하고 있어 안전하게 전파된다).
    public function chatRequest(string $prompt, string $systemPrompt): array
    {
        return array('ok' => false, 'message' => '', 'error' => 'AI 공급자가 설정되지 않아 이 기능은 템플릿 모드에서 지원되지 않습니다. AI 공급자 설정에서 API 키를 등록해 주세요.');
    }
    
    public function chatWithHistory(array $messages, bool $jsonMode = true): array
    {
        return array('ok' => false, 'content' => '', 'error' => 'AI 공급자가 설정되지 않아 이 기능은 템플릿 모드에서 지원되지 않습니다.');
    }

    // 키워드·지역 기반 결정적 해시태그 생성 — 외부 호출 없이 항상 동작한다.
    private function buildHashtags(array $params): string
    {
        $keyword = isset($params['primary_keyword']) ? trim($params['primary_keyword']) : '';
        $region = isset($params['service_region']) ? trim($params['service_region']) : '';

        $tags = array();
        if ($keyword !== '') {
            $tags[] = '#' . str_replace(' ', '', $keyword);
        }
        if ($region !== '') {
            $tags[] = '#' . str_replace(' ', '', $region);
        }
        $tags[] = '#정보';
        $tags[] = '#후기';

        return implode(' ', $tags);
    }
}

// 실제 OpenAI 호출 공급자 — adm/landing/ai_generate_action.php 의 curl 패턴을 재사용한다.
// API 키는 생성자에만 평문으로 존재하고(요청 직전 즉시 사용), 어떤 로그·오류 메시지에도 기록하지 않는다.
class BlogOpenAiProvider implements BlogAiProvider
{
    private string $apiKeyPlain;
    private string $endpoint;
    private string $model;
    private int $maxTokens;
    private float $temperature;

    public function __construct(string $apiKeyPlain, string $endpoint, string $model, int $maxTokens, float $temperature)
    {
        $this->apiKeyPlain = $apiKeyPlain;
        $this->endpoint = rtrim($endpoint, '/');
        $this->model = $model !== '' ? $model : 'gpt-4o';
        $this->maxTokens = $maxTokens > 0 ? $maxTokens : 2000;
        $this->temperature = $temperature;
    }

    public function generateTitles(array $params, int $count): array
    {
        $count = max(3, min(10, $count));
        $topic = isset($params['topic']) ? trim($params['topic']) : '';
        $keyword = isset($params['primary_keyword']) ? trim($params['primary_keyword']) : '';
        $region = isset($params['service_region']) ? trim($params['service_region']) : '';

        $system_prompt = "너는 블로그 SEO 제목을 만드는 카피라이터야. 아래 정보로 한국어 블로그 제목 후보를 "
            . "{$count}개 만들어라. 각 제목은 60자 이내, 과장광고·허위 정보 없이, 실제 검색 의도에 맞게 작성해라.\n"
            . "[정보]\n주제: {$topic}\n대표 키워드: {$keyword}\n지역: " . ($region !== '' ? $region : '전국') . "\n\n"
            . "반드시 아래 JSON 형식만 출력해라: {\"titles\": [\"제목1\", \"제목2\", ...]}";

        $result = $this->callChatCompletion($system_prompt, '제목 후보 JSON을 생성해줘.');
        if (!$result['ok']) {
            return array('ok' => false, 'titles' => array(), 'error' => $result['error'], 'tokens_prompt' => $result['tokens_prompt'], 'tokens_completion' => $result['tokens_completion']);
        }

        $parsed = json_decode($result['content'], true);
        if (!is_array($parsed) || !isset($parsed['titles']) || !is_array($parsed['titles']) || empty($parsed['titles'])) {
            return array('ok' => false, 'titles' => array(), 'error' => 'AI가 올바른 제목 JSON 형식을 반환하지 않았습니다.', 'tokens_prompt' => $result['tokens_prompt'], 'tokens_completion' => $result['tokens_completion']);
        }

        $titles = array();
        foreach ($parsed['titles'] as $t) {
            if (is_string($t) && trim($t) !== '') {
                $titles[] = mb_substr(trim($t), 0, 60);
            }
        }
        if (empty($titles)) {
            return array('ok' => false, 'titles' => array(), 'error' => 'AI 응답에 유효한 제목이 없습니다.', 'tokens_prompt' => $result['tokens_prompt'], 'tokens_completion' => $result['tokens_completion']);
        }

        return array('ok' => true, 'titles' => array_slice($titles, 0, $count), 'error' => '', 'tokens_prompt' => $result['tokens_prompt'], 'tokens_completion' => $result['tokens_completion']);
    }

    public function generateBody(array $params): array
    {
        $title = isset($params['title']) ? trim($params['title']) : '';
        $topic = isset($params['topic']) ? trim($params['topic']) : '';
        $keyword = isset($params['primary_keyword']) ? trim($params['primary_keyword']) : '';
        $region = isset($params['service_region']) ? trim($params['service_region']) : '';
        $content_type = isset($params['content_type']) ? trim($params['content_type']) : 'info';

        $system_prompt = "너는 지역 서비스 업체를 위한 블로그 글을 쓰는 작가야. 아래 정보로 한국어 블로그 본문을 "
            . "작성해라. 존재하지 않는 요금·통계·고객 사례를 만들어내지 말고, 과장광고 표현을 쓰지 마라. "
            . "업체명·전화번호·주소는 실제 값을 모르니 {{business_name}}, {{phone}}, {{address}}, "
            . "{{service_region}}, {{consult_url}} 자리표시자를 그대로 사용해라(나중에 서버가 치환한다).\n"
            . "[정보]\n제목: {$title}\n주제: {$topic}\n대표 키워드: {$keyword}\n지역: " . ($region !== '' ? $region : '전국')
            . "\n글 유형: {$content_type}\n\n"
            . "반드시 아래 JSON 형식만 출력해라: "
            . "{\"subtitle\": \"소제목\", \"body\": \"도입부와 본문\", \"faq\": \"FAQ 섹션\", \"cta\": \"CTA 문구\", \"hashtags\": [\"#태그1\", \"#태그2\"]}";

        $result = $this->callChatCompletion($system_prompt, '본문 JSON을 생성해줘.');
        if (!$result['ok']) {
            return array('ok' => false, 'body' => '', 'hashtags' => '', 'error' => $result['error'], 'tokens_prompt' => $result['tokens_prompt'], 'tokens_completion' => $result['tokens_completion']);
        }

        $parsed = json_decode($result['content'], true);
        if (!is_array($parsed) || !isset($parsed['body']) || trim((string) $parsed['body']) === '') {
            return array('ok' => false, 'body' => '', 'hashtags' => '', 'error' => 'AI가 올바른 본문 JSON 형식을 반환하지 않았습니다.', 'tokens_prompt' => $result['tokens_prompt'], 'tokens_completion' => $result['tokens_completion']);
        }

        $sections = array();
        if (!empty($parsed['subtitle'])) {
            $sections[] = (string) $parsed['subtitle'];
        }
        $sections[] = (string) $parsed['body'];
        if (!empty($parsed['faq'])) {
            $sections[] = "\n[FAQ]\n" . $parsed['faq'];
        }
        if (!empty($parsed['cta'])) {
            $sections[] = "\n[CTA]\n" . $parsed['cta'];
        }

        $hashtags = '';
        if (!empty($parsed['hashtags']) && is_array($parsed['hashtags'])) {
            $tags = array();
            foreach ($parsed['hashtags'] as $tag) {
                if (is_string($tag) && trim($tag) !== '') {
                    $tags[] = trim($tag);
                }
            }
            $hashtags = implode(' ', array_slice($tags, 0, 10));
        }

        return array(
            'ok' => true,
            'body' => implode("\n", $sections),
            'hashtags' => $hashtags,
            'error' => '',
            'tokens_prompt' => $result['tokens_prompt'],
            'tokens_completion' => $result['tokens_completion'],
        );
    }

    // generateTitles/generateBody처럼 고정 JSON 스키마를 강제하지 않는 자유형 프롬프트용 —
    // 포스팅 제작 화면의 방향추천/제목/도입부/본문블록/글감분석이 전부 이 메서드를 공유한다.
    public function chatRequest(string $prompt, string $systemPrompt): array
    {
        // analyze_material(글감분석)만 JSON 스키마를 요구하고, 방향/제목/도입부/본문블록은
        // 일반 문장을 기대한다 - system prompt에 "JSON"이 언급된 경우에만 json_object 모드를
        // 켠다(OpenAI는 프롬프트에 "json" 문구가 없으면 이 모드에서 오류를 반환하므로, 반대로
        // 일반 문장 요청에 강제로 켜면 기대와 다른 응답이 나온다).
        $jsonMode = (stripos($systemPrompt, 'json') !== false || stripos($prompt, 'json') !== false);
        $result = $this->callChatCompletion($systemPrompt, $prompt, $jsonMode);
        if (!$result['ok']) {
            return array('ok' => false, 'message' => '', 'error' => $result['error']);
        }
        return array('ok' => true, 'message' => $result['content'], 'error' => '');
    }
    
    public function chatWithHistory(array $messages, bool $jsonMode = true): array
    {
        return $this->callChatCompletionRaw($messages, $jsonMode);
    }

    // 반환: array('ok'=>bool, 'content'=>string, 'error'=>string, 'tokens_prompt'=>int, 'tokens_completion'=>int)
    // $jsonMode=true면 content는 모델이 반환한 JSON 문자열, false면 일반 텍스트 그대로.
    // 이 함수 밖으로는 $this->apiKeyPlain 값이 절대 전달되지 않는다(오류 메시지에도 포함 금지).
    private function callChatCompletion(string $systemPrompt, string $userPrompt, bool $jsonMode = true): array
    {
        $messages = array(
            array('role' => 'system', 'content' => $systemPrompt),
            array('role' => 'user', 'content' => $userPrompt),
        );
        return $this->callChatCompletionRaw($messages, $jsonMode);
    }
    
    private function callChatCompletionRaw(array $messages, bool $jsonMode = true): array
    {
        if ($this->apiKeyPlain === '') {
            return array('ok' => false, 'content' => '', 'error' => 'AI API 키가 설정되지 않았습니다.', 'tokens_prompt' => 0, 'tokens_completion' => 0);
        }
        if (!function_exists('curl_init')) {
            return array('ok' => false, 'content' => '', 'error' => '서버에 curl 확장이 설치되어 있지 않습니다.', 'tokens_prompt' => 0, 'tokens_completion' => 0);
        }

        $payload = array(
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        );
        if ($jsonMode) {
            $payload['response_format'] = array('type' => 'json_object');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->endpoint . '/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKeyPlain,
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

        $response = curl_exec($ch);
        $curl_err = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($curl_err) {
            return array('ok' => false, 'content' => '', 'error' => 'AI 호출 오류: ' . $curl_err, 'tokens_prompt' => 0, 'tokens_completion' => 0);
        }

        $res_data = json_decode((string) $response, true);
        $tokens_prompt = isset($res_data['usage']['prompt_tokens']) ? (int) $res_data['usage']['prompt_tokens'] : 0;
        $tokens_completion = isset($res_data['usage']['completion_tokens']) ? (int) $res_data['usage']['completion_tokens'] : 0;

        if (!isset($res_data['choices'][0]['message']['content'])) {
            $msg = isset($res_data['error']['message']) ? $res_data['error']['message'] : 'AI 응답 파싱 실패';
            // Rate Limit(429)·서버 오류(5xx)는 재시도로 해결될 수 있어 원인을 구분해 안내한다.
            if ($http_code === 429) {
                $msg = 'API 요청 한도(Rate Limit)를 초과했습니다. 잠시 후 다시 시도해 주세요. (' . $msg . ')';
            } else if ($http_code >= 500) {
                $msg = 'AI 서비스가 일시적으로 불안정합니다(HTTP ' . $http_code . '). 잠시 후 다시 시도해 주세요. (' . $msg . ')';
            } else if ($http_code !== 200) {
                $msg = 'HTTP ' . $http_code . ': ' . $msg;
            }
            return array('ok' => false, 'content' => '', 'error' => $msg, 'tokens_prompt' => $tokens_prompt, 'tokens_completion' => $tokens_completion);
        }

        return array('ok' => true, 'content' => $res_data['choices'][0]['message']['content'], 'error' => '', 'tokens_prompt' => $tokens_prompt, 'tokens_completion' => $tokens_completion);
    }
}

// 모델별 1K 토큰당 USD 단가 추정 — 실제 청구서와 다를 수 있는 참고용 수치이며, 정확한
// 비용은 OpenAI 대시보드에서 확인해야 한다. 등록되지 않은 모델은 보수적인 기본 단가를 쓴다.
function bp_estimate_openai_cost(string $model, int $tokensPrompt, int $tokensCompletion): float
{
    $rates = array(
        'gpt-4o'      => array('prompt' => 0.0025,  'completion' => 0.0100),
        'gpt-4o-mini' => array('prompt' => 0.00015, 'completion' => 0.0006),
        'gpt-4-turbo' => array('prompt' => 0.0100,  'completion' => 0.0300),
    );
    $rate = isset($rates[$model]) ? $rates[$model] : array('prompt' => 0.0050, 'completion' => 0.0150);

    $cost = ($tokensPrompt / 1000 * $rate['prompt']) + ($tokensCompletion / 1000 * $rate['completion']);
    return round($cost, 4);
}

// $projectId가 주어지고 그 프로젝트에 ai_provider_id가 지정돼 있으면 그 공급자를 그대로 쓴다
// (is_active 여부와 무관하게 - 사용자가 명시적으로 골랐으므로). 지정이 없으면 기존과 동일하게
// 전역 활성 공급자(is_active='Y')로 폴백한다.
function bp_ai_get_active_provider(int $projectId = 0): ?array
{
    $table = bp_table('ai_providers');

    if ($projectId > 0) {
        $projects_table = bp_table('content_projects');
        $project = sql_fetch(" select ai_provider_id from {$projects_table} where id = '{$projectId}' ");
        if ($project && !empty($project['ai_provider_id'])) {
            $picked = sql_fetch(" select * from {$table} where id = '" . (int) $project['ai_provider_id'] . "' ");
            if ($picked) {
                return $picked;
            }
        }
    }

    $row = sql_fetch(" select * from {$table} where is_active = 'Y' limit 1 ");
    return $row ? $row : null;
}

// 프로젝트별 설정과 별개로, 블로그 자동화 전체에서 AI 호출을 껐는지 확인한다
// (설정 > AI 설정의 "AI 기능 전체 사용 여부"). 행이 아직 없으면(설치 직후 등) 기본값은
// 켜짐으로 취급한다 - 이 스위치를 아직 한 번도 안 만졌다고 해서 기존에 잘 쓰던 AI 기능이
// 갑자기 막히면 안 된다.
function bp_ai_global_enabled(): bool
{
    $table = bp_table('ai_global_settings');
    $row = sql_fetch(" select is_enabled from {$table} where id = 1 ", false);
    return !$row || $row['is_enabled'] !== 'N';
}

// 프로젝트의 ai_disabled='Y'면 AI 호출 자체를 하지 않는다(수동 작성 전용) - 호출부가
// bp_ai_get_provider() 등을 부르기 전에 먼저 이 함수로 확인해서 명확한 에러를 돌려줘야 한다.
// (공급자 미설정 시의 "템플릿 폴백"과는 의도적으로 다른 상태 - 여기서는 아예 시도하지 않는다.)
function bp_ai_is_disabled_for_project(int $projectId): bool
{
    if ($projectId <= 0) {
        return false;
    }
    $table = bp_table('content_projects');
    $row = sql_fetch(" select ai_disabled from {$table} where id = '{$projectId}' ");
    return $row && $row['ai_disabled'] === 'Y';
}

// "실제 라이브 API 호출이 가능한 공급자 코드"의 단일 진실 공급원 - 현재는 openai만.
// 여기에 새 공급자를 추가하려면 bp_ai_get_provider()에도 그 공급자의 실제 연동 구현을
// 함께 추가해야 한다(이 함수만 고치면 "라이브"라고 표시만 되고 실제로는 여전히
// 템플릿으로 대체되는 불일치가 생긴다). manager/blog/post_builder.php의 공급자
// 드롭다운 상태 표시도 이 함수를 그대로 쓴다.
function bp_ai_provider_is_live(string $providerCode): bool
{
    return $providerCode === 'openai';
}

// 활성 공급자(ai_providers.is_active='Y')가 있고 키가 정상 복호화되면 실제 OpenAI 공급자를,
// 그 외의 모든 경우(비활성·키 없음·복호화 실패)에는 안전한 템플릿 폴백을 반환한다.
// 실제 외부 API 호출은 provider_code가 'openai'인 레코드에서만 지원한다. 이 체크가
// 빠져있던 이전 버전은 gemini/anthropic/deepseek/xai 등으로 등록한 키를 그대로
// OpenAI 엔드포인트로 보내버리는 버그가 있었다(다른 서비스 키가 OpenAI 인증에서
// 그냥 실패하거나, 최악의 경우 우연히 형식이 맞아 엉뚱한 곳에 전송될 수 있었다).
function bp_ai_get_provider(int $projectId = 0): BlogAiProvider
{
    $active = bp_ai_get_active_provider($projectId);
    if (!$active || empty($active['api_key_enc'])) {
        return new BlogAiTemplateProvider();
    }
    if (!bp_ai_provider_is_live($active['provider_code'])) {
        return new BlogAiTemplateProvider();
    }

    $api_key = bp_decrypt_secret($active['api_key_enc']);
    if ($api_key === '') {
        return new BlogAiTemplateProvider();
    }

    $endpoint = !empty($active['api_endpoint']) ? $active['api_endpoint'] : 'https://api.openai.com/v1';

    return new BlogOpenAiProvider(
        $api_key,
        $endpoint,
        isset($active['default_model']) ? $active['default_model'] : '',
        isset($active['max_tokens']) ? (int) $active['max_tokens'] : 2000,
        isset($active['temperature']) ? (float) $active['temperature'] : 0.7
    );
}

// bp_ai_get_provider()와 동일한 판단 로직으로, 어떤 공급자/모델이 실제로 쓰였는지만
// 반환한다(생성 로그 기록용 — project_action.php가 bp_log_generation_attempt()에 넘긴다).
function bp_ai_get_provider_meta(int $projectId = 0): array
{
    $active = bp_ai_get_active_provider($projectId);
    if (!$active || empty($active['api_key_enc']) || !bp_ai_provider_is_live($active['provider_code'])) {
        return array('provider' => 'template', 'model' => '');
    }
    $api_key = bp_decrypt_secret($active['api_key_enc']);
    if ($api_key === '') {
        return array('provider' => 'template', 'model' => '');
    }
    return array('provider' => 'openai', 'model' => isset($active['default_model']) && $active['default_model'] !== '' ? $active['default_model'] : 'gpt-4o');
}

// 프로젝트에 지정된 공급자가 준비 중(템플릿 대체 대상)일 때, 조용히 템플릿으로 넘어가지
// 않고 프론트가 "ChatGPT로 변경/템플릿으로 생성/취소" 선택창을 띄우도록 신호를 주는
// 공용 응답 payload. post_builder_ajax.php의 generate_all_cards/regenerate_card 둘 다 사용한다.
function bp_build_template_fallback_confirm(int $projectId): array
{
    $active = bp_ai_get_active_provider($projectId);
    $provider_label = ($active && !empty($active['display_name'])) ? $active['display_name'] : '선택한 공급자';

    return array(
        'ok' => false,
        'needs_provider_confirm' => true,
        'provider_label' => $provider_label,
        'error' => "현재 {$provider_label} API 실제 호출은 아직 연결되지 않았습니다. ChatGPT로 변경하거나 AI 없이 안전 템플릿으로 생성할 수 있습니다.",
    );
}

function bp_ai_generate_titles(array $params, int $count = 5): array
{
    $provider = bp_ai_get_provider();
    return $provider->generateTitles($params, $count);
}

function bp_ai_generate_body(array $params): array
{
    $provider = bp_ai_get_provider();
    return $provider->generateBody($params);
}

// 반환: array('ok'=>bool, 'message'=>string, 'error'=>string)
// $projectId를 넘기면 그 프로젝트에 지정된 AI 공급자를 쓴다(없으면 전역 활성 공급자로 폴백).
function bp_ai_chat_request(string $prompt, string $systemPrompt = '', int $projectId = 0): array
{
    $provider = bp_ai_get_provider($projectId);
    return $provider->chatRequest($prompt, $systemPrompt);
}

// 히스토리가 포함된 챗봇 프롬프트 통신 (jsonMode 기본 활성화)
function bp_ai_chat_with_history(array $messages, bool $jsonMode = true, int $projectId = 0): array
{
    $provider = bp_ai_get_provider($projectId);
    return $provider->chatWithHistory($messages, $jsonMode);
}
