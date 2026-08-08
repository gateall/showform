-- MINIWEB-SEED-ID: benefits
-- MINIWEB-SEED-LABEL: Benefits 기본 블록
-- MINIWEB-SECTION-TYPE: benefits
-- MINIWEB-SEED-VERSION: 1
-- MINIWEB-SEED-BLOCKS: 3
--
-- 테이블 접두어는 {prefix} 자리표시자로 둔다. 'g5_' 를 그대로 박아 두면 접두어가 다른
-- 설치본에서 조용히 다른 테이블을 만들거나 실패한다.
--
-- 재실행 대비: block_code 가 UNIQUE 라 두 번째 설치는 중복 행을 만드는 대신 오류로 끝난다.
-- 관리자가 버튼을 다시 눌러도 최신 디자인으로 덮어쓰기만 하도록 ON DUPLICATE KEY UPDATE 를 쓴다.
-- 관리자가 손댔을 수 있는 항목(is_active, sort_order)은 덮어쓰지 않는다.

INSERT INTO `{prefix}miniweb_block` (`block_code`, `section_type`, `block_name`, `thumbnail_url`, `html_template`, `schema_json`, `is_active`, `sort_order`, `created_at`) VALUES
('benefits_a', 'benefits', 'Benefits Grid (3열)', '', '<div class="mw-benefits-a" style="padding: 40px 20px; background: #f8fafc; text-align: center;">\n  <h2 style="font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 30px;">{{title}}</h2>\n  <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center;">\n    {{#items}}\n    <div style="flex: 1 1 250px; background: #fff; padding: 24px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">\n      <div style="font-size: 32px; margin-bottom: 16px;">{{icon}}</div>\n      <h3 style="font-size: 18px; font-weight: 700; color: #334155; margin-bottom: 8px;">{{item_title}}</h3>\n      <p style="font-size: 14px; color: #64748b; line-height: 1.5;">{{desc}}</p>\n    </div>\n    {{/items}}\n  </div>\n</div>', '[{"key":"title","label":"섹션 제목","type":"text","required":true,"default":"우리의 특별한 장점"},{"key":"items","label":"장점 항목","type":"repeater","fields":[{"key":"icon","label":"아이콘(이모지 등)","type":"text"},{"key":"item_title","label":"항목 제목","type":"text"},{"key":"desc","label":"항목 설명","type":"textarea"}]}]', 1, 1, NOW()),

('benefits_b', 'benefits', 'Benefits List (좌측형)', '', '<div class="mw-benefits-b" style="padding: 40px 20px; background: #ffffff;">\n  <h2 style="font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 30px; text-align: center;">{{title}}</h2>\n  <div style="max-width: 600px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px;">\n    {{#items}}\n    <div style="display: flex; align-items: flex-start; gap: 16px;">\n      <div style="font-size: 24px; color: #2563eb; flex-shrink: 0; margin-top: 2px;">{{icon}}</div>\n      <div>\n        <h3 style="font-size: 18px; font-weight: 700; color: #334155; margin-bottom: 6px;">{{item_title}}</h3>\n        <p style="font-size: 15px; color: #64748b; line-height: 1.5; margin: 0;">{{desc}}</p>\n      </div>\n    </div>\n    {{/items}}\n  </div>\n</div>', '[{"key":"title","label":"섹션 제목","type":"text","required":true,"default":"왜 우리를 선택해야 할까요?"},{"key":"items","label":"장점 항목","type":"repeater","fields":[{"key":"icon","label":"아이콘(이모지)","type":"text"},{"key":"item_title","label":"항목 제목","type":"text"},{"key":"desc","label":"항목 설명","type":"textarea"}]}]', 1, 2, NOW()),

('benefits_c', 'benefits', 'Benefits Cards (다크형)', '', '<div class="mw-benefits-c" style="padding: 60px 20px; background: #1e293b; color: #fff; text-align: center;">\n  <h2 style="font-size: 28px; font-weight: 700; margin-bottom: 12px;">{{title}}</h2>\n  <p style="font-size: 16px; color: #94a3b8; margin-bottom: 40px;">{{subtitle}}</p>\n  <div style="display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; max-width: 900px; margin: 0 auto;">\n    {{#items}}\n    <div style="flex: 1 1 200px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); padding: 32px 24px; border-radius: 12px; transition: transform 0.2s;">\n      <div style="font-size: 40px; margin-bottom: 20px;">{{icon}}</div>\n      <h3 style="font-size: 20px; font-weight: 700; margin-bottom: 12px; color: #f8fafc;">{{item_title}}</h3>\n      <p style="font-size: 15px; color: #cbd5e1; line-height: 1.6;">{{desc}}</p>\n    </div>\n    {{/items}}\n  </div>\n</div>', '[{"key":"title","label":"섹션 제목","type":"text","required":true,"default":"핵심 가치"},{"key":"subtitle","label":"보조 설명","type":"text","required":false},{"key":"items","label":"장점 항목","type":"repeater","fields":[{"key":"icon","label":"아이콘","type":"text"},{"key":"item_title","label":"항목 제목","type":"text"},{"key":"desc","label":"항목 설명","type":"textarea"}]}]', 1, 3, NOW())
ON DUPLICATE KEY UPDATE
  `block_name` = VALUES(`block_name`),
  `section_type` = VALUES(`section_type`),
  `html_template` = VALUES(`html_template`),
  `schema_json` = VALUES(`schema_json`),
  `updated_at` = NOW();
