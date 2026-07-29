<?php
if (!defined('_GNUBOARD_')) exit;

require_once G5_LIB_PATH . '/showform_ai.lib.php';

// AI 생성 서비스 인터페이스 (BLOG_AUTOMATION_API_SPEC.md generate-titles/generate-content)
// Phase 2: 실제 외부 AI 호출(BlogOpenAiProvider)과 결정적 템플릿 폴백(BlogAiTemplateProvider)을
// 이 인터페이스 뒤로 분리한다. 두 구현 모두 실패 시에도 예외를 던지지 않고 ok=false로
// 보고한다 — 호출부(project_action.php)가 실패 시 기존 초안을 그대로 보존할 수 있어야 하기 때문이다.
interface BlogAiProvider
{
    // 반환: array('ok'=>bool, 'titles'=>string[], 'error'=>string)
    public function generateTitles(array $params, int $count): array;
    // 반환: array('ok'=>bool, 'body'=>string, 'error'=>string)
    public function generateBody(array $params): array;
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
        return array('ok' => true, 'titles' => $titles, 'error' => '');
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

        return array('ok' => true, 'body' => implode("\n", $sections), 'error' => '');
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
            return array('ok' => false, 'titles' => array(), 'error' => $result['error']);
        }

        $parsed = json_decode($result['content'], true);
        if (!is_array($parsed) || !isset($parsed['titles']) || !is_array($parsed['titles']) || empty($parsed['titles'])) {
            return array('ok' => false, 'titles' => array(), 'error' => 'AI가 올바른 제목 JSON 형식을 반환하지 않았습니다.');
        }

        $titles = array();
        foreach ($parsed['titles'] as $t) {
            if (is_string($t) && trim($t) !== '') {
                $titles[] = mb_substr(trim($t), 0, 60);
            }
        }
        if (empty($titles)) {
            return array('ok' => false, 'titles' => array(), 'error' => 'AI 응답에 유효한 제목이 없습니다.');
        }

        return array('ok' => true, 'titles' => array_slice($titles, 0, $count), 'error' => '');
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
            . "{\"subtitle\": \"소제목\", \"body\": \"도입부와 본문\", \"faq\": \"FAQ 섹션\", \"cta\": \"CTA 문구\"}";

        $result = $this->callChatCompletion($system_prompt, '본문 JSON을 생성해줘.');
        if (!$result['ok']) {
            return array('ok' => false, 'body' => '', 'error' => $result['error']);
        }

        $parsed = json_decode($result['content'], true);
        if (!is_array($parsed) || !isset($parsed['body']) || trim((string) $parsed['body']) === '') {
            return array('ok' => false, 'body' => '', 'error' => 'AI가 올바른 본문 JSON 형식을 반환하지 않았습니다.');
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

        return array('ok' => true, 'body' => implode("\n", $sections), 'error' => '');
    }

    // 반환: array('ok'=>bool, 'content'=>string, 'error'=>string) — content는 모델이 반환한 JSON 문자열 그대로.
    // 이 함수 밖으로는 $this->apiKeyPlain 값이 절대 전달되지 않는다(오류 메시지에도 포함 금지).
    private function callChatCompletion(string $systemPrompt, string $userPrompt): array
    {
        if ($this->apiKeyPlain === '') {
            return array('ok' => false, 'content' => '', 'error' => 'AI API 키가 설정되지 않았습니다.');
        }
        if (!function_exists('curl_init')) {
            return array('ok' => false, 'content' => '', 'error' => '서버에 curl 확장이 설치되어 있지 않습니다.');
        }

        $payload = array(
            'model' => $this->model,
            'response_format' => array('type' => 'json_object'),
            'messages' => array(
                array('role' => 'system', 'content' => $systemPrompt),
                array('role' => 'user', 'content' => $userPrompt),
            ),
            'temperature' => $this->temperature,
            'max_tokens' => $this->maxTokens,
        );

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
        curl_close($ch);

        if ($curl_err) {
            return array('ok' => false, 'content' => '', 'error' => 'AI 호출 오류: ' . $curl_err);
        }

        $res_data = json_decode((string) $response, true);
        if (!isset($res_data['choices'][0]['message']['content'])) {
            $msg = isset($res_data['error']['message']) ? $res_data['error']['message'] : 'AI 응답 파싱 실패';
            return array('ok' => false, 'content' => '', 'error' => $msg);
        }

        return array('ok' => true, 'content' => $res_data['choices'][0]['message']['content'], 'error' => '');
    }
}

function bp_ai_get_active_provider(): ?array
{
    $table = bp_table('ai_providers');
    $row = sql_fetch(" select * from {$table} where is_active = 'Y' limit 1 ");
    return $row ? $row : null;
}

// 활성 공급자(ai_providers.is_active='Y')가 있고 키가 정상 복호화되면 실제 OpenAI 공급자를,
// 그 외의 모든 경우(비활성·키 없음·복호화 실패)에는 안전한 템플릿 폴백을 반환한다.
function bp_ai_get_provider(): BlogAiProvider
{
    $active = bp_ai_get_active_provider();
    if (!$active || empty($active['api_key_enc'])) {
        return new BlogAiTemplateProvider();
    }

    $api_key = bp_decrypt_secret($active['api_key_enc']);
    if ($api_key === '') {
        return new BlogAiTemplateProvider();
    }

    return new BlogOpenAiProvider(
        $api_key,
        'https://api.openai.com/v1',
        isset($active['default_model']) ? $active['default_model'] : '',
        isset($active['max_tokens']) ? (int) $active['max_tokens'] : 2000,
        isset($active['temperature']) ? (float) $active['temperature'] : 0.7
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
