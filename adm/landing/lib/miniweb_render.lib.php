<?php
// 미니웹 렌더러.
//
// 빌더 미리보기와 실제 발행이 반드시 이 파일 하나를 거치게 한다. 렌더링 코드가 둘로
// 갈리면 "미리보기에는 나오는데 실제로는 다르다"가 생기고, 그건 화면만 봐서는
// 원인을 알 수 없는 종류의 버그가 된다.
//
// 자리표시자 문법(의도적으로 최소):
//   {{key}}            content_json[key] 값을 HTML 이스케이프해서 넣는다
//   {{#key}}…{{/key}}  값이 비어 있지 않을 때만 그 구간을 출력한다
//
// 고객이 입력한 값은 전부 이스케이프해서 넣는다. html_template은 관리자가 등록한
// 디자인이므로 그대로 두지만, content_json은 외부 입력이므로 그대로 두면 XSS가 된다.
if (!defined('_GNUBOARD_')) exit;

// 템플릿에 넣기 전에 파생 값을 만들어 둔다.
// 예: phone 이 "043-537-5949" 면 tel: 링크에 쓸 phone_raw 는 "0435375949".
function mw_render_derive(array $content)
{
    if (isset($content['phone'])) {
        $content['phone_raw'] = preg_replace('/[^0-9+]/', '', (string) $content['phone']);
    }
    // 링크 자리에 javascript: 같은 스킴이 들어가지 못하게 막는다.
    if (isset($content['cta_url'])) {
        $content['cta_url'] = mw_render_safe_url($content['cta_url']);
    }
    return $content;
}

// 앵커(#contact), 사이트 내부 경로(/...), http(s), tel, mailto 만 허용한다.
function mw_render_safe_url($url)
{
    $url = trim((string) $url);
    if ($url === '') {
        return '#';
    }
    if ($url[0] === '#' || $url[0] === '/') {
        return $url;
    }
    if (preg_match('#^(https?://|tel:|mailto:)#i', $url)) {
        return $url;
    }
    // 스킴을 알 수 없으면 이동시키지 않는다.
    return '#';
}

// 블록 하나를 콘텐츠로 채워 HTML을 만든다.
function mw_render_block(array $block, array $content)
{
    $html = isset($block['html_template']) ? (string) $block['html_template'] : '';
    if ($html === '') {
        return '';
    }

    $content = mw_render_derive($content);

    // 1) 조건 구간을 먼저 처리한다. 값이 비어 있으면 통째로 지운다.
    //    (먼저 {{key}}를 치환해버리면 여는/닫는 표시를 찾을 수 없다.)
    $html = preg_replace_callback(
        '/\{\{#([a-z0-9_]+)\}\}(.*?)\{\{\/\1\}\}/su',
        function ($m) use ($content) {
            $value = isset($content[$m[1]]) ? trim((string) $content[$m[1]]) : '';
            return $value === '' ? '' : $m[2];
        },
        $html
    );

    // 2) 남은 자리표시자를 값으로 바꾼다. 정의되지 않은 키는 빈 문자열로 지운다
    //    ({{title}} 같은 글자가 화면에 그대로 보이는 것을 막는다).
    $html = preg_replace_callback(
        '/\{\{([a-z0-9_]+)\}\}/',
        function ($m) use ($content) {
            $value = isset($content[$m[1]]) ? (string) $content[$m[1]] : '';
            return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        },
        $html
    );

    return $html;
}

// 프로젝트의 섹션을 순서대로 읽어 화면 조각들을 돌려준다.
// 반환: array('html' => '...', 'css' => '...', 'js' => '...')
function mw_render_project($project_id, $prefix = null)
{
    if ($prefix === null) {
        $prefix = G5_TABLE_PREFIX;
    }
    $project_id = (int) $project_id;
    $out = array('html' => '', 'css' => '', 'js' => '');
    if ($project_id < 1) {
        return $out;
    }

    $sec_table = $prefix . 'miniweb_section';
    $blk_table = $prefix . 'miniweb_block';

    $sql = " select s.*, b.block_code, b.html_template, b.css_code, b.js_code
             from {$sec_table} s
             left join {$blk_table} b on b.id = s.block_id
             where s.project_id = '{$project_id}' and s.is_enabled = 1
             order by s.sort_order asc, s.id asc ";
    $res = sql_query($sql, false);
    if (!$res) {
        return $out;
    }

    $seen_css = array();
    while ($row = sql_fetch_array($res)) {
        if (!$row['block_id'] || $row['html_template'] === null) {
            continue; // 아직 디자인을 고르지 않은 섹션은 건너뛴다.
        }
        $content = array();
        if (!empty($row['content_json'])) {
            $decoded = json_decode($row['content_json'], true);
            if (is_array($decoded)) {
                $content = $decoded;
            }
        }
        $out['html'] .= mw_render_block($row, $content) . "\n";

        // 같은 블록이 여러 번 쓰여도 CSS/JS는 한 번만 넣는다.
        $code = $row['block_code'];
        if (!isset($seen_css[$code])) {
            $seen_css[$code] = true;
            if (!empty($row['css_code'])) $out['css'] .= $row['css_code'] . "\n";
            if (!empty($row['js_code']))  $out['js']  .= $row['js_code'] . "\n";
        }
    }

    return $out;
}

// 블록 공통 CSS. 개별 블록 css_code보다 먼저 깔린다.
// 모바일 기본값으로 쓰고 넓은 화면에서 확장한다(PC를 먼저 만들고 줄이지 않는다).
function mw_render_base_css()
{
    return <<<CSS
.mw-container { width:calc(100% - 32px); max-width:1120px; margin:0 auto; }
.mw-hero { padding:44px 0 32px; background:#0f172a; color:#fff; }
.mw-hero__title { margin:0; font-size:26px; line-height:1.25; letter-spacing:-.02em; }
.mw-hero__desc { margin:12px 0 0; font-size:15px; line-height:1.7; color:rgba(255,255,255,.86); }
.mw-hero__actions { display:flex; flex-wrap:wrap; gap:10px; margin-top:20px; }
.mw-btn { display:inline-flex; align-items:center; justify-content:center;
          width:100%; min-height:48px; padding:0 18px; border-radius:12px;
          font-weight:800; text-decoration:none; }
.mw-btn--solid { background:#14b8a6; color:#04211d; }
.mw-btn--ghost { background:rgba(255,255,255,.12); color:#fff; border:1px solid rgba(255,255,255,.24); }

@media (min-width: 390px) {
    .mw-container { width:calc(100% - 40px); }
    .mw-btn { width:auto; }
}
@media (min-width: 768px) {
    .mw-hero { padding:64px 0 48px; }
    .mw-hero__title { font-size:38px; }
    .mw-hero__desc { font-size:17px; }
}
@media (min-width: 1200px) {
    .mw-container { width:min(1120px, calc(100% - 48px)); }
    .mw-hero__title { font-size:46px; }
}
CSS;
}
