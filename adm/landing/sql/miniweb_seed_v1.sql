-- MINIWEB: Hero 기본 샘플 3종 (PoC용)
--
-- 세 블록 모두 schema_json의 key 집합이 동일하다(title / description / phone / cta_text / cta_url).
-- 이게 "디자인만 교체되고 내용은 유지된다"의 실제 조건이다. 블록마다 key 이름이 다르면
-- Hero A -> Hero C로 바꾸는 순간 content_json의 값을 찾지 못해 빈 화면이 된다.
--
-- html_template 안에는 고객 데이터를 넣지 않는다. {{key}} 자리표시자만 두고
-- 렌더링 시점에 section.content_json 값으로 치환한다.
-- {{#phone}}...{{/phone}} 는 값이 있을 때만 출력하는 블록이다.
--
-- 재실행해도 중복되지 않도록 block_code UNIQUE + ON DUPLICATE KEY UPDATE 를 쓴다.
-- 단, 관리자가 손댔을 수 있는 항목은 덮어쓰지 않는다(is_active, sort_order).

INSERT INTO `{prefix}miniweb_block`
  (`section_type`, `block_code`, `block_name`, `thumbnail_url`, `html_template`, `css_code`, `js_code`, `schema_json`, `is_active`, `sort_order`, `created_at`)
VALUES
(
  'hero', 'hero_left', 'Hero A · 왼쪽 정렬 기본형', '',
  '<section class="mw-hero mw-hero--left">\n  <div class="mw-container">\n    <h1 class="mw-hero__title">{{title}}</h1>\n    {{#description}}<p class="mw-hero__desc">{{description}}</p>{{/description}}\n    <div class="mw-hero__actions">\n      {{#phone}}<a class="mw-btn mw-btn--solid" href="tel:{{phone_raw}}">전화 상담 {{phone}}</a>{{/phone}}\n      {{#cta_text}}<a class="mw-btn mw-btn--ghost" href="{{cta_url}}">{{cta_text}}</a>{{/cta_text}}\n    </div>\n  </div>\n</section>',
  '.mw-hero--left { text-align:left; }',
  '',
  '[{"key":"title","label":"메인 제목","type":"text","required":true,"max":60},{"key":"description","label":"보조 설명","type":"textarea","required":false,"max":200},{"key":"phone","label":"대표 전화번호","type":"tel","required":false},{"key":"cta_text","label":"CTA 문구","type":"text","required":false,"max":20},{"key":"cta_url","label":"CTA 링크","type":"text","required":false,"default":"#contact"}]',
  1, 10, NOW()
),
(
  'hero', 'hero_center', 'Hero B · 가운데 정렬형', '',
  '<section class="mw-hero mw-hero--center">\n  <div class="mw-container">\n    <h1 class="mw-hero__title">{{title}}</h1>\n    {{#description}}<p class="mw-hero__desc">{{description}}</p>{{/description}}\n    <div class="mw-hero__actions">\n      {{#phone}}<a class="mw-btn mw-btn--solid" href="tel:{{phone_raw}}">전화 상담 {{phone}}</a>{{/phone}}\n      {{#cta_text}}<a class="mw-btn mw-btn--ghost" href="{{cta_url}}">{{cta_text}}</a>{{/cta_text}}\n    </div>\n  </div>\n</section>',
  '.mw-hero--center { text-align:center; }\n.mw-hero--center .mw-hero__actions { justify-content:center; }',
  '',
  '[{"key":"title","label":"메인 제목","type":"text","required":true,"max":60},{"key":"description","label":"보조 설명","type":"textarea","required":false,"max":200},{"key":"phone","label":"대표 전화번호","type":"tel","required":false},{"key":"cta_text","label":"CTA 문구","type":"text","required":false,"max":20},{"key":"cta_url","label":"CTA 링크","type":"text","required":false,"default":"#contact"}]',
  1, 20, NOW()
),
(
  'hero', 'hero_boxed', 'Hero C · 카드 강조형', '',
  '<section class="mw-hero mw-hero--boxed">\n  <div class="mw-container">\n    <div class="mw-hero__card">\n      <h1 class="mw-hero__title">{{title}}</h1>\n      {{#description}}<p class="mw-hero__desc">{{description}}</p>{{/description}}\n      <div class="mw-hero__actions">\n        {{#phone}}<a class="mw-btn mw-btn--solid" href="tel:{{phone_raw}}">전화 상담 {{phone}}</a>{{/phone}}\n        {{#cta_text}}<a class="mw-btn mw-btn--ghost" href="{{cta_url}}">{{cta_text}}</a>{{/cta_text}}\n      </div>\n    </div>\n  </div>\n</section>',
  '.mw-hero--boxed { background:#0f172a; }\n.mw-hero--boxed .mw-hero__card { background:#fff; color:#0f172a; border-radius:18px; padding:24px; }\n.mw-hero--boxed .mw-hero__desc { color:#475569; }',
  '',
  '[{"key":"title","label":"메인 제목","type":"text","required":true,"max":60},{"key":"description","label":"보조 설명","type":"textarea","required":false,"max":200},{"key":"phone","label":"대표 전화번호","type":"tel","required":false},{"key":"cta_text","label":"CTA 문구","type":"text","required":false,"max":20},{"key":"cta_url","label":"CTA 링크","type":"text","required":false,"default":"#contact"}]',
  1, 30, NOW()
)
ON DUPLICATE KEY UPDATE
  `block_name` = VALUES(`block_name`),
  `html_template` = VALUES(`html_template`),
  `css_code` = VALUES(`css_code`),
  `js_code` = VALUES(`js_code`),
  `schema_json` = VALUES(`schema_json`),
  `updated_at` = NOW();
