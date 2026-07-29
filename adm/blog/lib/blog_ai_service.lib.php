<?php
if (!defined('_GNUBOARD_')) exit;

require_once G5_LIB_PATH . '/showform_ai.lib.php';

// AI 생성 서비스 인터페이스 (BLOG_AUTOMATION_API_SPEC.md generate-titles/generate-content)
// Phase 1 범위: 실제 외부 AI API 호출은 하지 않는다 (BLOG_AUTOMATION_MVP_PHASE1 작업지시서).
// 활성 공급자가 없거나 키가 없으면 결정적 템플릿 생성기로 폴백한다 — 이 폴백 경로가
// Phase 1의 기본/유일 동작 경로이며, 실 API 연동은 Phase 2에서 bp_ai_call_openai() 내부만 채우면 된다.
interface BlogAiProvider
{
    public function generateTitles(array $params, int $count): array;
    public function generateBody(array $params): string;
}

// 템플릿 기반 폴백 공급자 — 키 없이 항상 동작, lib/showform_ai.lib.php 의 업종 분기 로직을 재사용한다.
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
        return $titles;
    }

    public function generateBody(array $params): string
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

        return implode("\n", $sections);
    }
}

// 실제 외부 호출 지점 (Phase 2 예약 — 이번 단계에서는 절대 호출하지 않는다)
function bp_ai_call_openai_stub(string $api_key_plain, string $prompt): array
{
    // Phase 1에서는 의도적으로 미구현 상태로 남긴다. 절대 로그에 $api_key_plain을 기록하지 말 것.
    return array('ok' => false, 'error' => 'Phase 2에서 구현 예정 — Phase 1은 템플릿 폴백만 사용합니다.');
}

function bp_ai_get_active_provider(): ?array
{
    $table = bp_table('ai_providers');
    $row = sql_fetch(" select * from {$table} where is_active = 'Y' limit 1 ");
    return $row ? $row : null;
}

// 현재 이 함수는 항상 템플릿 공급자를 반환한다 — 활성 공급자가 있어도 Phase 1은 실제 호출을 하지 않기로
// 작업지시서에서 명시했으므로, 실 API 분기는 Phase 2에서 추가한다.
function bp_ai_get_provider(): BlogAiProvider
{
    return new BlogAiTemplateProvider();
}

function bp_ai_generate_titles(array $params, int $count = 5): array
{
    $provider = bp_ai_get_provider();
    return $provider->generateTitles($params, $count);
}

function bp_ai_generate_body(array $params): string
{
    $provider = bp_ai_get_provider();
    return $provider->generateBody($params);
}
