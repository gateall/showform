/**
 * 블로그 자동화 - 통합 포스팅 제작 폼 (Unified Post Builder)
 * Card-Based Studio UI Version (OpenAI 연동)
 */
// post_builder_ajax.php는 /adm/blog/에만 존재한다. 이 스크립트는 /manager/blog/post_builder.php에서도
// 로드되는데, fetch()의 상대경로는 현재 페이지 URL 기준으로 풀리므로 상대경로로 부르면 404가 난다.
// post_builder.php가 심어둔 POST_BUILDER_CONFIG?.ajaxUrl을 우선 쓰고, 없으면 상대경로로 폴백한다.
const PB_AJAX_URL = (typeof window !== 'undefined' && window.POST_BUILDER_CONFIG && window.POST_BUILDER_CONFIG.ajaxUrl) ? window.POST_BUILDER_CONFIG.ajaxUrl : 'post_builder_ajax.php';

const Builder = {
    currentStep: 1,
    maxStep: 4,
    projectId: 0,
    postId: 0,
    cards: [], // { id, type, title, content, state: 'draft|primary|selected|hidden|deleted', locked: boolean }
    activeCardId: null,

    init: function() {
        // load library items first
        this.loadLibraryItems();
        
        // toggleStep(1)이 renderRightPanel()을 통해 키워드/해시태그 패널까지 그려준다
        // (우측 패널로 옮기면서 여기서 따로 부를 필요가 없어졌다).
        this.toggleStep(1);
        this.updateProviderStatusUI();

        const loadProjectId = localStorage.getItem('pb_load_project_id');
        if (loadProjectId) {
            localStorage.removeItem('pb_load_project_id');
            this.onTopProjectChange(loadProjectId);
        }
    },

    // 키워드/해시태그 입력창 - 개수 선택 + 입력창 N개 + AI로 채우기.
    // posts.tags(콤마구분)/posts.hashtags("#태그" 공백구분)에 그대로 저장한다(project_view.php의
    // SEO 편집 폼이 쓰는 기존 저장 형식과 동일 - 새 컬럼/테이블을 만들지 않았다).
    tagState: {
        keywords: { count: 5, values: [], autoGenerate: false, collapsed: false },
        // enabled: values와 같은 길이로 유지되는 boolean 배열 - 검수완성(4단계) 패널에서
        // 체크 해제("미적용")한 항목은 여기서 false가 되고, 최종 조립(finishPost)/검수
        // 요약에서 제외된다. posts.hashtags 컬럼 자체는 값 목록만 담으므로 이 마스크는
        // builder_state JSON(gatherData/loadState)에 별도로 실어 보존한다.
        hashtags: { count: 5, values: [], enabled: [], autoGenerate: false, collapsed: false }
    },
    _autoTagTimers: {},
    _editingHashtagIdx: null,

    // "키워드 · 해시태그" 두 패널을 한 번에 접고 펼치는 상위 토글 - 패널마다 있는
    // 개별 접기/펼치기와는 별개로, 그룹 전체를 한 번에 숨길 때 쓴다.
    // 최종 본문 목표 글자수(선택된 구조의 전체 섹션 합산) - 1단계/4단계 컨트롤 패널 양쪽에서
    // 같은 값을 보고 조정한다. generateAllCards()가 서버로 넘겨서 프롬프트의 "약 N자 목표"에
    // 그대로 쓰인다.
    targetLength: 2000,
    onTargetLengthChange: function(value) {
        const n = parseInt(value, 10);
        this.targetLength = (!isNaN(n) && n > 0) ? n : 2000;
        if (this.currentStep === 5) this.inspectAll();
        this._syncTargetLengthPromptValue();
    },
    // 실제 입력창은 우측 컨트롤 패널(#pb_right_panel) 안에서만 존재하고 그 컨테이너는
    // 단계가 바뀔 때마다 통째로 다시 그려진다. 값 자체는 1단계 화면(#step-1, 단계
    // 이동에 사라지지 않는 정적 영역)의 숨은 필드(pb_target_length_prompt_value)에
    // 복제해 두어야 다른 단계로 이동해도 지시문 미리보기에서 값이 유지된다.
    _syncTargetLengthPromptValue: function() {
        const el = document.getElementById('pb_target_length_prompt_value');
        if (el) el.value = this.targetLength;
        if (typeof PromptManager !== 'undefined') PromptManager.triggerUpdate();
    },
    _renderTargetLengthControl: function() {
        this._syncTargetLengthPromptValue();
        return `
        <div style="margin-bottom:12px; padding:10px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">
            <label style="display:block; font-size:0.8rem; color:#666; margin-bottom:4px;">본문 목표 글자수(전체 섹션 합산)</label>
            <input type="number" class="frm_input" style="width:100px;" min="200" step="100" value="${this.targetLength}"
                onchange="Builder.onTargetLengthChange(this.value);"> 자
        </div>`;
    },

    charPerLine: 60,
    onCharPerLineChange: function(value) {
        const n = parseInt(value, 10);
        this.charPerLine = (!isNaN(n) && n > 0) ? n : 60;
        this._syncCharPerLinePromptValue();
        const input = document.getElementById('pb_char_per_line_input');
        const slider = document.getElementById('pb_char_per_line_slider');
        const display = document.getElementById('pb_cpl_display');
        if (input && input.value != this.charPerLine) input.value = this.charPerLine;
        if (slider && slider.value != this.charPerLine) slider.value = this.charPerLine;
        if (display) display.innerText = this.charPerLine;
        if (this.currentStep === 5) this.inspectAll();
    },
    _syncCharPerLinePromptValue: function() {
        const el = document.getElementById('pb_char_per_line_prompt_value');
        if (el) el.value = this.charPerLine;
        if (typeof PromptManager !== 'undefined') PromptManager.triggerUpdate();
    },
    _renderCharPerLineControl: function() {
        this._syncCharPerLinePromptValue();
        return `
        <div style="margin-bottom:12px; padding:10px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">
            <label style="display:flex; justify-content:space-between; font-size:0.8rem; color:#666; margin-bottom:8px;">
                <span>가로 글자 수</span>
                <span style="font-weight:bold; color:#4f46e5;"><span id="pb_cpl_display">${this.charPerLine}</span>자</span>
            </label>
            <div style="display:flex; gap:6px; margin-bottom:8px;">
                <button type="button" class="btn btn_02" style="flex:1; padding:4px;" onclick="Builder.onCharPerLineChange(40);">40자</button>
                <button type="button" class="btn btn_02" style="flex:1; padding:4px;" onclick="Builder.onCharPerLineChange(60);">60자</button>
                <button type="button" class="btn btn_02" style="flex:1; padding:4px;" onclick="Builder.onCharPerLineChange(80);">80자</button>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                <input type="range" id="pb_char_per_line_slider" min="20" max="100" step="5" value="${this.charPerLine}"
                    style="flex:1;" oninput="Builder.onCharPerLineChange(this.value);">
                <input type="number" id="pb_char_per_line_input" class="frm_input" style="width:60px;" min="20" max="100" step="5" value="${this.charPerLine}"
                    onchange="Builder.onCharPerLineChange(this.value);">
            </div>
        </div>`;
    },

    // 키워드/해시태그도 같은 이유로, 실제 입력창(우측 패널)이 아니라 1단계 정적
    // 영역의 숨은 필드(pb_keywords_prompt_value / pb_hashtags_prompt_value)에
    // 요약값을 채워 넣는다(구조 선택과 같은 패턴).
    _syncTagPromptValue: function(type) {
        const el = document.getElementById(type === 'hashtags' ? 'pb_hashtags_prompt_value' : 'pb_keywords_prompt_value');
        if (!el) return;
        const values = (this.tagState[type].values || []).filter(v => v && v.trim() !== '');
        el.value = values.join(', ');
        if (typeof PromptManager !== 'undefined') PromptManager.triggerUpdate();
    },

    // AI Instruction Library Items (Fetched from Server)
    libraryItems: {
        structure_template: [],
        generation_condition: [],
        additional_instruction: []
    },

    loadLibraryItems: function() {
        const payload = new URLSearchParams();
        payload.append('action', 'load_library_items');
        
        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success && res.items) {
                this.libraryItems = { structure_template: [], generation_condition: [], additional_instruction: [] };
                res.items.forEach(item => {
                    if (this.libraryItems[item.instruction_type]) {
                        this.libraryItems[item.instruction_type].push(item);
                    }
                });
                // 페이지 초기 로드 시 renderStructureList()는 이 fetch가 끝나기 전에
                // 이미 한 번 호출돼서(빈 배열 상태로) "등록된 글 전개 구조가 없습니다"를
                // 그려놓은 채로 끝나 있었다 - 데이터가 실제로 도착한 지금 다시 그려야 한다.
                this.renderStructureList();
                this.updateSelectedStructureSummary();
                this.renderGenerationConditions();
                this.renderAdditionalInstructions();
            }
        });
    },

    migrateLibrary: function() {
        if (!confirm('기본 라이브러리 데이터(약 160개)를 설치하시겠습니까? (최고관리자 전용)')) return;
        const payload = new URLSearchParams();
        payload.append('action', 'migrate_v2_library');
        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                if (res.results) {
                    const r = res.results;
                    const msg = [
                        `- 글 전개 구조: 신규 ${r.structure.new}건 / 기존 ${r.structure.existing}건 / 실패 ${r.structure.failed}건`,
                        `- AI 생성 조건: 신규 ${r.condition.new}건 / 기존 ${r.condition.existing}건 / 실패 ${r.condition.failed}건`,
                        `- 추가 지시 라이브러리: 신규 ${r.instruction.new}건 / 기존 ${r.instruction.existing}건 / 실패 ${r.instruction.failed}건`,
                        `- 전체 DB 반영 건수: ${res.total_new}`
                    ].join('\\n');
                    alert('데이터 설치 완료\\n\\n' + msg);
                } else {
                    alert(res.message || '설치가 완료되었습니다.');
                }
                this.loadLibraryItems();
            } else {
                alert('오류: ' + res.error);
            }
        }).catch(err => {
            alert('통신 오류가 발생했습니다.');
        });
    },

    selectedStructures: [], // 다중 선택된 구조 ID 배열

    structureTemplate: 0,
    stagedStructures: [],

    onStructureTemplateChange: function(id) {
        const idStr = id.toString();
        if (this.stagedStructures.includes(idStr)) {
            this.stagedStructures = this.stagedStructures.filter(x => x !== idStr);
        } else {
            this.stagedStructures.push(idStr);
        }

        this.updateStagedActionsBar();
        this.renderStructureList();
    },

    updateStagedActionsBar: function() {
        const bar = document.getElementById('pb_staged_actions_bar');
        const count = document.getElementById('pb_staged_count');
        if (!bar || !count) return;

        count.textContent = this.stagedStructures.length;
        if (this.stagedStructures.length > 0) {
            bar.style.display = 'flex';
        } else {
            bar.style.display = 'none';
        }
    },

    applyStagedStructures: function(mode) {
        if (this.stagedStructures.length === 0) {
            alert('적용할 구조를 하나 이상 선택해주세요.');
            return;
        }

        const currentIds = document.getElementById('pb_selected_structure_ids').value.split(',').filter(Boolean);

        if (mode === 'replace') {
            document.getElementById('pb_selected_structure_ids').value = this.stagedStructures.join(',');
        } else if (mode === 'append') {
            const newIds = [...new Set([...currentIds, ...this.stagedStructures])];
            document.getElementById('pb_selected_structure_ids').value = newIds.join(',');
        }
        
        this.stagedStructures = [];
        this.updateStagedActionsBar();
        this.updateSelectedStructureSummary();
        this.renderStructureList();
    },

    clearStagedStructures: function() {
        this.stagedStructures = [];
        this.updateStagedActionsBar();
        this.renderStructureList();
    },

    applySingleStructure: function(event, id) {
        event.stopPropagation();
        document.getElementById('pb_selected_structure_ids').value = id.toString();
        this.stagedStructures = [];
        this.updateStagedActionsBar();
        this.updateSelectedStructureSummary();
        this.renderStructureList();
    },

    clearAppliedStructures: function() {
        document.getElementById('pb_selected_structure_ids').value = '';
        this.updateSelectedStructureSummary();
    },

    renderStructureList: function() {
        const filterEl = document.getElementById('pb_filter_category');
        const listEl = document.getElementById('pb_structure_list');
        if (!listEl) return;

        const structures = this.libraryItems.structure_template;
        const currentFilter = filterEl ? filterEl.value : '';
        const filtered = currentFilter ? structures.filter(s => s.category === currentFilter) : structures;

        let html = '';
        if (filtered.length === 0) {
            html = '<div style="grid-column:1/-1; padding:20px; text-align:center; color:#94a3b8;">등록된 글 전개 구조가 없습니다.</div>';
        } else {
            filtered.forEach(s => {
                const isSelected = this.stagedStructures.includes(s.id.toString());
                const selIndex = isSelected ? this.stagedStructures.indexOf(s.id.toString()) + 1 : 0;
                const selClass = isSelected ? 'selected' : '';
                html += `
                <div class="pb-card ${selClass}" onclick="Builder.onStructureTemplateChange(${s.id})" style="position:relative; border: 1px solid ${isSelected ? '#3b82f6' : '#e2e8f0'}; border-radius: 6px; padding: 15px; cursor: pointer; box-shadow: ${isSelected ? '0 0 0 2px #3b82f6' : '0 1px 3px rgba(0,0,0,0.1)'}; background: ${isSelected ? '#eff6ff' : '#fff'}; transition: all 0.2s;">
                    <div style="display:flex; align-items:flex-start; margin-bottom:5px;">
                        <input type="checkbox" ${isSelected ? 'checked' : ''} style="margin-right:8px; margin-top:4px; pointer-events:none;">
                        <div style="flex:1;">
                            <div style="font-weight: bold; font-size: 1.1rem; color: ${isSelected ? '#1e3a8a' : '#1e293b'};">
                                ${this._escapeAttr(s.title)}
                                ${isSelected ? `<span style="display:inline-block; margin-left:5px; background:#3b82f6; color:#fff; border-radius:12px; font-size:0.75rem; padding:2px 6px;">선택됨 ${selIndex}</span>` : ''}
                            </div>
                        </div>
                    </div>
                    
                    <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 15px; margin-left:22px;">${this._escapeAttr(s.category || '기타')}</div>
                    
                    <div style="display: flex; gap: 8px; font-size: 0.8rem; justify-content: flex-end; flex-wrap:wrap;">
                        <a href="#" onclick="Builder.showLibrarySample(event, ${s.id})" style="color: #4f46e5; text-decoration: none; padding:4px 8px; background:#e0e7ff; border-radius:4px;">샘플 보기</a>
                        <a href="#" onclick="Builder.showLibraryForm(${s.id}, 'structure_template'); event.stopPropagation();" style="color: #64748b; text-decoration: none; padding:4px 8px; background:#f1f5f9; border-radius:4px;">수정</a>
                        <a href="#" onclick="Builder.duplicateLibraryItem(${s.id}, 'structure_template'); event.stopPropagation();" style="color: #64748b; text-decoration: none; padding:4px 8px; background:#f1f5f9; border-radius:4px;">복제</a>
                        <a href="#" onclick="Builder.deleteLibraryItem(${s.id}); event.stopPropagation();" style="color: #ef4444; text-decoration: none; padding:4px 8px; background:#fee2e2; border-radius:4px;">삭제</a>
                    </div>
                    ${isSelected ? `
                    <div style="margin-top:10px; padding-top:10px; border-top:1px dashed #bfdbfe; text-align:right;">
                        <button type="button" class="btn btn_01" style="font-size:0.8rem; background:#3b82f6; border:none; padding:4px 10px;" onclick="Builder.applySingleStructure(event, ${s.id})">이 구조 바로 사용</button>
                    </div>
                    ` : ''}
                </div>
                `;
            });
        }
        listEl.innerHTML = html;
    },

    // ----------------------------------------------------
    // 모달 탭 전환 로직
    // ----------------------------------------------------
    switchLibraryTab: function(tabName) {
        const tabs = ['select', 'manage', 'category'];
        tabs.forEach(t => {
            const btn = document.getElementById('pb_lib_tab_btn_' + t);
            const panel = document.getElementById('pb_lib_tab_' + t);
            if (btn) {
                if (t === tabName) {
                    btn.style.borderBottomColor = '#3182ce';
                    btn.style.color = '#3182ce';
                } else {
                    btn.style.borderBottomColor = 'transparent';
                    btn.style.color = '#718096';
                }
            }
            if (panel) {
                panel.style.display = (t === tabName) ? (t === 'select' ? 'flex' : 'block') : 'none';
            }
        });

        if (tabName === 'select') {
            this.renderStructureAccordion();
        } else if (tabName === 'manage') {
            this.renderLibraryModalList();
        } else if (tabName === 'category') {
            this.renderCategoryList();
        }
    },

    // ----------------------------------------------------
    // 구조 아코디언 관련 로직
    // ----------------------------------------------------
    renderStructureAccordion: function() {
        const container = document.getElementById('pb_accordion_container');
        const searchEl = document.getElementById('pb_accordion_search');
        if (!container) return;

        const structures = this.libraryItems.structure_template;
        const categories = [...new Set(structures.map(s => s.category).filter(Boolean))];
        const searchVal = searchEl ? searchEl.value.trim().toLowerCase() : '';

        let html = '';
        if (structures.length === 0) {
            html = `<div style="color:#94a3b8; font-size:0.85rem; padding:10px 0; text-align:center;">등록된 글 전개 구조가 없습니다.</div>`;
        } else {
            categories.forEach(cat => {
                const catStructures = structures.filter(s => s.category === cat);
                
                // 검색어 필터링
                const filtered = catStructures.filter(s => {
                    if (!searchVal) return true;
                    const textTarget = (cat + ' ' + s.title + ' ' + (s.description||'') + ' ' + (s.content||'')).toLowerCase();
                    return textTarget.includes(searchVal);
                });

                if (filtered.length === 0) return; // 검색 결과 없으면 카테고리 렌더링 안함

                // 카테고리 정보
                const catInfo = this.libraryState.categories.find(c => c.category_name === cat && c.library_type === 'structure');
                const catDesc = catInfo && catInfo.description ? this._escapeAttr(catInfo.description) : '';

                // 아코디언 오픈 상태 유지용 속성
                // 검색 중이거나 전체 펼치기 시 open 속성 활용을 위해 (기본은 닫힘)
                const isExpanded = searchVal ? 'open' : '';

                html += `
                <details class="pb-accordion-cat" style="margin-bottom:10px; border:1px solid #e2e8f0; border-radius:6px; background:#f8fafc; overflow:hidden;" ${isExpanded}>
                    <summary style="padding:12px 16px; cursor:pointer; font-weight:bold; color:#1e293b; display:flex; justify-content:space-between; align-items:center; list-style:none; outline:none; user-select:none;">
                        <div style="display:flex; align-items:center; gap:8px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="pb-chevron" style="transition:transform 0.2s;"><polyline points="9 18 15 12 9 6"></polyline></svg>
                            ${this._escapeAttr(cat)}
                            <span style="font-weight:normal; font-size:0.8rem; color:#64748b; background:#e2e8f0; padding:2px 6px; border-radius:10px;">${catStructures.length}</span>
                        </div>
                        <div style="font-size:0.8rem; color:#94a3b8; font-weight:normal;">${catDesc}</div>
                    </summary>
                    <div style="padding:10px; background:#fff; border-top:1px solid #e2e8f0; display:flex; flex-direction:column; gap:8px;">
                `;

                filtered.forEach(s => {
                    // structure_steps라는 별도 컬럼을 읽고 있었는데 실제 전개 단계 데이터는
                    // 거기 없다 - 구조 데이터는 content_json.sections(또는 content의 JSON
                    // 문자열)에 들어있다. 그래서 지금까지 전개 순서가 항상 비어 보였다.
                    let stepTitles = [];
                    if (s.content_json && Array.isArray(s.content_json.sections)) {
                        stepTitles = s.content_json.sections.map(sec => sec.title);
                    } else if (s.content) {
                        try {
                            const parsed = JSON.parse(s.content);
                            if (parsed && Array.isArray(parsed.sections)) {
                                stepTitles = parsed.sections.map(sec => sec.title);
                            }
                        } catch (e) { /* JSON이 아니면 무시 */ }
                    }
                    const stepCount = stepTitles.length;
                    const stepsPreview = stepTitles.join(' → ');

                    const isChecked = this.selectedStructures.includes(s.id.toString());
                    // 즐겨찾기 여부와 무관하게 별 아이콘 자체는 항상 보여주고 클릭해서
                    // 바로 토글할 수 있게 한다(체크박스 클릭과 겹치지 않도록 stopPropagation).
                    const favColor = s.is_favorite == 1 ? '#fbbf24' : '#cbd5e1';

                    html += `
                        <label style="display:flex; align-items:flex-start; gap:10px; padding:12px; border:1px solid ${isChecked ? '#4f46e5' : '#cbd5e1'}; border-radius:6px; cursor:pointer; background:${isChecked ? '#eef2ff' : '#fff'}; transition:all 0.2s;">
                            <input type="checkbox" value="${s.id}" ${isChecked ? 'checked' : ''} onchange="Builder.toggleStructureSelection('${s.id}', this.checked)" style="margin-top:4px;">
                            <div style="flex:1;">
                                <div style="display:flex; align-items:center; gap:6px; margin-bottom:4px;">
                                    <span onclick="event.preventDefault(); event.stopPropagation(); Builder.toggleLibraryFavorite(${s.id}, ${s.is_favorite || 0});" style="color:${favColor}; cursor:pointer; font-size:1rem; line-height:1;">★</span>
                                    <span style="font-weight:bold; color:${isChecked ? '#4f46e5' : '#334155'};">${this._escapeAttr(s.title)}</span>
                                </div>
                                <div style="font-size:0.8rem; color:#64748b; margin-bottom:6px; line-height:1.4;">
                                    ${stepCount > 0
                                        ? `<span style="background:#e2e8f0; color:#475569; padding:2px 6px; border-radius:4px; font-size:0.7rem; margin-right:6px;">${stepCount}단계</span>${this._escapeHtml(stepsPreview)}`
                                        : '<span style="color:#cbd5e1;">등록된 전개 단계가 없습니다</span>'}
                                </div>
                                <div style="display:flex; gap:8px; font-size:0.75rem;">
                                    <a href="javascript:;" onclick="event.preventDefault(); event.stopPropagation(); Builder.openSampleModal(${s.id});" style="color:#4f46e5; text-decoration:none;">샘플 보기</a>
                                </div>
                            </div>
                        </label>
                    `;
                });

                html += `
                    </div>
                </details>
                `;
            });
        }
        
        container.innerHTML = html;
        
        // CSS for custom chevron
        if (!document.getElementById('pb-accordion-style')) {
            const style = document.createElement('style');
            style.id = 'pb-accordion-style';
            style.innerHTML = `
                details.pb-accordion-cat > summary::-webkit-details-marker { display:none; }
                details.pb-accordion-cat[open] > summary .pb-chevron { transform: rotate(90deg); }
            `;
            document.head.appendChild(style);
        }

        document.getElementById('pb_accordion_selected_count').innerText = this.selectedStructures.length;
    },

    toggleAllAccordions: function(expand) {
        const details = document.querySelectorAll('.pb-accordion-cat');
        details.forEach(d => {
            if (expand) d.setAttribute('open', '');
            else d.removeAttribute('open');
        });
    },

    updateSelectedStructureSummary: function() {
        const summaryEl = document.getElementById('pb_applied_summary');
        if (!summaryEl) return;

        const idsStr = document.getElementById('pb_selected_structure_ids').value;
        if (!idsStr) {
            summaryEl.innerHTML = `
                <div style="color:#64748b; font-size:0.85rem; text-align:center;">
                    현재 적용된 글 전개 구조가 없습니다.
                </div>
            `;
            this.structureTemplate = 0;
            this._syncStructurePromptValue([]);
            return;
        }

        const ids = idsStr.split(',').filter(Boolean);
        const applied = ids.map(id => this.libraryItems.structure_template.find(item => item.id == id)).filter(Boolean);

        if (applied.length === 0) {
            summaryEl.innerHTML = `
                <div style="color:#64748b; font-size:0.85rem; text-align:center;">
                    현재 적용된 글 전개 구조가 없습니다.
                </div>
            `;
            this._syncStructurePromptValue([]);
            return;
        }

        // 여러 구조를 조합할 때 합쳐지는 순서가 곧 실제 글 전개 순서이므로, 번호와
        // 위/아래 이동 버튼을 제공해 순서를 직접 조정할 수 있게 한다.
        const rowsHtml = applied.map((s, idx) => `
            <div style="display:flex; align-items:center; gap:8px; padding:6px 0; ${idx > 0 ? 'border-top:1px dashed #e2e8f0;' : ''}">
                <span style="font-size:0.8rem; color:#64748b; min-width:18px;">${idx + 1}.</span>
                <span style="flex:1; font-weight:bold; color:#0f172a; font-size:0.92rem;">${this._escapeAttr(s.title)}</span>
                <button type="button" onclick="Builder.moveAppliedStructure(${s.id}, -1)" ${idx === 0 ? 'disabled style="opacity:0.3;"' : ''} style="background:#f1f5f9; border:1px solid #e2e8f0; border-radius:4px; cursor:pointer; padding:2px 8px; font-size:0.75rem;">▲</button>
                <button type="button" onclick="Builder.moveAppliedStructure(${s.id}, 1)" ${idx === applied.length - 1 ? 'disabled style="opacity:0.3;"' : ''} style="background:#f1f5f9; border:1px solid #e2e8f0; border-radius:4px; cursor:pointer; padding:2px 8px; font-size:0.75rem;">▼</button>
                <button type="button" onclick="Builder.removeAppliedStructure(${s.id})" style="background:none; border:none; color:#ef4444; cursor:pointer; font-size:0.85rem;" title="이 구조만 빼기">✕</button>
            </div>
        `).join('');

        summaryEl.innerHTML = `
            <div style="font-size:0.85rem; font-weight:bold; color:#1e293b; margin-bottom:5px;">적용 중인 구조 (${applied.length}개, 이 순서대로 이어져 글이 생성됩니다):</div>
            ${rowsHtml}
            <div style="display:flex; gap:8px; margin-top:10px;">
                <button type="button" class="btn btn_02" style="font-size:0.8rem; background:#fff; color:#ef4444; border-color:#fca5a5;" onclick="Builder.clearAppliedStructures()">전체 선택 해제</button>
            </div>
        `;

        this.renderStructurePreview();
        this._syncStructurePromptValue(applied.map(s => s.title));
    },

    // 구조 선택은 카드 클릭 UI라 일반 체크박스처럼 [data-prompt-key]가 없다. 적용된
    // 구조 제목을 숨은 필드(#pb_structure_prompt_value)에 채워 넣어야 지시문
    // 미리보기가 "선택한 글 전개 구조"를 반영할 수 있다.
    _syncStructurePromptValue: function(titles) {
        const el = document.getElementById('pb_structure_prompt_value');
        if (!el) return;
        el.value = titles.join(', ');
        if (typeof PromptManager !== 'undefined') PromptManager.triggerUpdate();
    },

    moveAppliedStructure: function(id, direction) {
        const idsEl = document.getElementById('pb_selected_structure_ids');
        if (!idsEl) return;
        const ids = idsEl.value.split(',').filter(Boolean);
        const idx = ids.indexOf(id.toString());
        const targetIdx = idx + direction;
        if (idx === -1 || targetIdx < 0 || targetIdx >= ids.length) return;
        [ids[idx], ids[targetIdx]] = [ids[targetIdx], ids[idx]];
        idsEl.value = ids.join(',');
        this.updateSelectedStructureSummary();
    },

    removeAppliedStructure: function(id) {
        const idsEl = document.getElementById('pb_selected_structure_ids');
        if (!idsEl) return;
        idsEl.value = idsEl.value.split(',').filter(Boolean).filter(x => x !== id.toString()).join(',');
        this.updateSelectedStructureSummary();
        this.renderStructureList();
    },

    toggleStructureSelection: function(id, checked) {
        if (checked) {
            if (!this.selectedStructures.includes(id)) this.selectedStructures.push(id);
        } else {
            this.selectedStructures = this.selectedStructures.filter(x => x !== id);
        }
        this.renderStructureAccordion();
    },

    clearAccordionSelection: function() {
        this.selectedStructures = [];
        this.renderStructureAccordion();
    },

    applySelectedStructures: function(mode) {
        if (this.selectedStructures.length === 0) {
            alert('적용할 구조를 하나 이상 선택해주세요.');
            return;
        }

        const currentIds = document.getElementById('pb_selected_structure_ids').value.split(',').filter(Boolean);

        if (mode === 'replace') {
            if (!confirm('현재 적용된 글 전개 구조를 삭제하고 선택한 구조로 교체하시겠습니까?')) return;
            document.getElementById('pb_selected_structure_ids').value = this.selectedStructures.join(',');
        } else if (mode === 'append') {
            // 중복 방지하며 추가
            const newIds = [...new Set([...currentIds, ...this.selectedStructures])];
            document.getElementById('pb_selected_structure_ids').value = newIds.join(',');
        }

        this.updateSelectedStructureSummary();
        this.closeLibraryModal();
        alert('구조가 성공적으로 적용되었습니다.');
    },

    renderStructurePreview: function() {
        const previewEl = document.getElementById('pb_structure_preview');
        if (!previewEl) return;

        const idsStr = document.getElementById('pb_selected_structure_ids');
        if (!idsStr || !idsStr.value) {
            previewEl.style.display = 'none';
            return;
        }

        const ids = idsStr.value.split(',');
        let html = '<div style="font-weight:bold; color:#475569; margin-bottom:8px; font-size:0.85rem;">예상 단계 (결합됨)</div><ul style="margin:0; padding-left:20px; font-size:0.8rem; color:#64748b; line-height:1.6;">';
        
        let foundAny = false;
        ids.forEach(id => {
            const s = this.libraryItems.structure_template.find(item => item.id == id);
            if (s && s.content_json && Array.isArray(s.content_json.sections)) {
                foundAny = true;
                s.content_json.sections.forEach(sec => {
                    html += `<li><strong>${this._escapeAttr(sec.title)}</strong>: ${this._escapeAttr(sec.desc)}</li>`;
                });
            }
        });

        if (!foundAny) {
            previewEl.style.display = 'none';
            return;
        }

        html += '</ul>';
        previewEl.innerHTML = html;
        previewEl.style.display = 'block';
    },

    renderGenerationConditions: function() {
        const container = document.getElementById('pb_generation_condition_list');
        if (!container) return;

        let html = '';
        // 시드 데이터 이슈로 같은 제목의 조건이 중복 저장돼 있는 경우가 있다(예:
        // "과장 광고 금지"가 서로 다른 id로 두 번 들어있는 등). 중복 항목이 그대로
        // 렌더링되면 화면에 같은 체크박스가 두 번 보이고, 사용자가 하나를 체크/해제해도
        // 나머지 복사본이 그대로 남아 지시문 미리보기에 항상 섞여 나온다. 제목 기준으로
        // 한 번만 남기고(기본 체크된 쪽을 우선 채택), 실제 DB 중복은 별도로 정리가 필요하다.
        const seenTitles = new Map();
        this.libraryItems.generation_condition.forEach(c => {
            const key = (c.category || '') + '::' + c.title;
            const existing = seenTitles.get(key);
            if (!existing || (c.is_default_checked == 1 && existing.is_default_checked != 1)) {
                seenTitles.set(key, c);
            }
        });
        const conditions = Array.from(seenTitles.values());
        if (conditions.length === 0) {
            html = `<div style="color:#94a3b8; font-size:0.85rem; padding:10px 0;">
                        등록된 생성 조건이 없습니다.
                        <div style="margin-top:10px;">
                            <button type="button" onclick="Builder.migrateLibrary()" style="padding:6px 12px; background:#4f46e5; color:#fff; border:none; border-radius:4px; cursor:pointer;">기본 데이터 자동 생성 (최고관리자)</button>
                        </div>
                    </div>`;
        } else {
            const byCategory = {};
            conditions.forEach(c => {
                const cat = c.category || '기타 조건';
                if (!byCategory[cat]) byCategory[cat] = [];
                byCategory[cat].push(c);
            });

            for (const cat in byCategory) {
                html += `<div style="font-size:0.8rem; font-weight:600; color:#64748b; margin-top:8px; margin-bottom:4px;">${this._escapeAttr(cat)}</div>`;
                html += `<div class="ai-condition-list">`;
                byCategory[cat].forEach(c => {
                    let isChecked = c.is_default_checked == 1;
                    if (this.pendingRuleIds) {
                        isChecked = this.pendingRuleIds.includes(c.id.toString());
                    }
                    const checkedStr = isChecked ? 'checked' : '';
                    // 화면에 보이는 건 짧은 이름(c.title)이고 AI에 실제로 가는 건
                    // instruction_content다. 둘이 달라서 "이 조건을 켜면 뭐가 달라지는지"를
                    // 화면에서 알 수 없었다 - 항목마다 실제 지시문을 펼쳐볼 수 있게 한다.
                    // 버튼이 label 안에 있으므로 preventDefault로 체크 토글을 막아야 한다.
                    const instruction = (c.content || '').trim();
                    const hintId = 'genrule_hint_' + c.id;
                    html += `
                    <label class="ai-condition-item">
                        <input type="checkbox" class="pb-chk-generation-rule" value="${c.id}" ${checkedStr}
                            data-prompt-key="genrule_${c.id}" data-prompt-template="${this._escapeAttr('글 작성 조건: ' + c.title)}"
                            onchange="if (typeof PromptManager !== 'undefined') PromptManager.triggerUpdate();">
                        <span class="ai-condition-body">
                            <span class="ai-condition-title">${this._escapeAttr(c.title)}</span>
                            ${instruction ? `<button type="button" class="ai-condition-hint-btn"
                                    aria-expanded="false" aria-controls="${hintId}"
                                    title="AI에 실제로 전달되는 지시문"
                                    onclick="Builder.toggleConditionHint(event, '${hintId}')">지시문</button>
                                <span class="ai-condition-hint" id="${hintId}" hidden>${this._escapeAttr(instruction)}</span>` : ''}
                        </span>
                    </label>`;
                });
                html += `</div>`;
            }
        }
        container.innerHTML = html;
        if (this.pendingRuleIds) this.pendingRuleIds = null;
    },

    // 조건 항목은 label이라 안쪽을 누르면 체크가 토글된다. 지시문을 펼치려고 누른
    // 것뿐인데 조건이 켜지거나 꺼지면 안 되므로 기본 동작을 막는다.
    toggleConditionHint: function(event, hintId) {
        event.preventDefault();
        event.stopPropagation();
        const hint = document.getElementById(hintId);
        if (!hint) return;
        const willShow = hint.hasAttribute('hidden');
        if (willShow) {
            hint.removeAttribute('hidden');
        } else {
            hint.setAttribute('hidden', '');
        }
        if (event.currentTarget) {
            event.currentTarget.setAttribute('aria-expanded', willShow ? 'true' : 'false');
        }
    },

    // 조건이 수십 개가 되면 하나씩 누르는 게 일이라 일괄 토글을 둔다.
    // 프롬프트 미리보기는 change 이벤트를 듣고 있으므로, 여기서 직접 갱신을 부른다.
    toggleAllGenerationConditions: function(checked) {
        const boxes = document.querySelectorAll('#pb_generation_condition_list .pb-chk-generation-rule');
        boxes.forEach(box => { box.checked = !!checked; });
        if (typeof PromptManager !== 'undefined') PromptManager.triggerUpdate();
    },

    // 검수 결과에 나오는 check_key를 사람이 읽는 이름으로. 예전에는 duplicate_sentences
    // 같은 내부 키가 그대로 화면에 찍혔다.
    QUALITY_LABELS: {
        title_length: '제목 길이',
        body_length: '본문 길이',
        forbidden_words: '금지어',
        contact_missing: '연락처 누락',
        business_mismatch: '업체정보 일치',
        duplicate_sentences: '중복 문장',
        keyword_stuffing: '키워드 과다 반복'
    },

    // 문장 경계를 찾아 {text, start, end}로 돌려준다.
    // 서버(blog_quality.lib.php)는 /[\.\!\?\n]+/로 무조건 쪼개서 https://showform.kr,
    // 3.14, "1. 소개" 같은 것이 문장으로 조각났다. 여기서는 마침표 뒤가 공백이나 문장
    // 끝일 때만 경계로 보고, 앞이 숫자뿐이면(번호 표기) 경계로 치지 않는다.
    _splitSentencesWithOffsets: function(text) {
        const out = [];
        let start = 0;
        for (let i = 0; i < text.length; i++) {
            const ch = text[i];
            let boundary = false;
            if (ch === '\n') {
                boundary = true;
            } else if (ch === '.' || ch === '!' || ch === '?') {
                const next = text[i + 1];
                if (next === undefined || /\s/.test(next)) {
                    // 바로 앞 토큰이 숫자뿐이면 "1." 같은 목록 번호다.
                    if (!/(^|\s)\d+$/.test(text.slice(start, i))) boundary = true;
                }
            }
            if (boundary) {
                out.push({ text: text.slice(start, i), start: start, end: i });
                start = i + 1;
            }
        }
        if (start < text.length) {
            out.push({ text: text.slice(start), start: start, end: text.length });
        }
        return out;
    },

    // 편집 중인 카드는 this.cards에 아직 반영 안 된 값을 갖고 있을 수 있으므로
    // textarea의 현재 값을 우선한다.
    _currentCardText: function(card) {
        const el = document.getElementById('textarea_' + card.id);
        if (el && typeof el.value === 'string') return el.value;
        return card.content || '';
    },

    // 카드 본문만 대상으로 중복 문장을 찾는다. 카드 제목(card.title)은 제외한다 —
    // 같은 소제목을 두 카드가 쓴다고 "본문이 반복된다"고 볼 수는 없다.
    findDuplicateSentences: function() {
        const cards = (this.cards || []).filter(c =>
            c.type !== 'title' && (c.state === 'selected' || c.state === 'primary'));

        const map = new Map(); // 문장 -> [{cardId, start, end}]
        cards.forEach(card => {
            const text = this._currentCardText(card);
            this._splitSentencesWithOffsets(text).forEach(seg => {
                const lead = seg.text.length - seg.text.replace(/^\s+/, '').length;
                const trimmed = seg.text.trim();
                // 공백을 뺀 실질 길이로 판단한다(공백만 늘어난 조각이 걸리지 않도록).
                if (trimmed.replace(/\s/g, '').length <= 5) return;
                const start = seg.start + lead;
                if (!map.has(trimmed)) map.set(trimmed, []);
                map.get(trimmed).push({ cardId: card.id, start: start, end: start + trimmed.length });
            });
        });

        const dupes = [];
        map.forEach((occurrences, sentence) => {
            // 같은 카드 안에서 두 번 나오는 경우도 있으므로 카드 수가 아니라 발생 수로 센다.
            if (occurrences.length >= 2) {
                dupes.push({ sentence: sentence, count: occurrences.length, occurrences: occurrences });
            }
        });
        dupes.sort((a, b) => b.count - a.count);
        return dupes;
    },

    // 해당 카드로 스크롤하고 문제 구간만 선택해준다.
    jumpToSentence: function(cardId, start, end) {
        const ta = document.getElementById('textarea_' + cardId);
        if (!ta) {
            alert('해당 카드를 화면에서 찾지 못했습니다. 카드가 접혀 있거나 삭제되었을 수 있습니다.');
            return;
        }
        ta.focus();
        try { ta.setSelectionRange(start, end); } catch (e) { /* input 타입 등 선택 불가 */ }
        ta.scrollIntoView({ behavior: 'smooth', block: 'center' });
    },

    // 검수 결과의 "중복 문장" 줄을 눌렀을 때 상세를 펼친다.
    toggleDuplicateDetail: function(btn) {
        const panel = document.getElementById('pb_dupe_detail');
        if (!panel) return;
        const show = panel.hasAttribute('hidden');
        if (!show) {
            panel.setAttribute('hidden', '');
            if (btn) btn.setAttribute('aria-expanded', 'false');
            return;
        }

        const dupes = this.findDuplicateSentences();
        let html = '<div class="pb-dupe-head">현재 편집본에서 찾은 중복 문장 '
                 + '<span class="pb-dupe-note">(카드 제목 제외 · 본문 기준)</span></div>';
        if (dupes.length === 0) {
            html += '<div class="pb-dupe-empty">지금 편집 중인 본문에서는 중복 문장이 발견되지 않았습니다. '
                  + '위 서버 검수 결과는 검수를 실행한 시점의 본문 기준입니다.</div>';
        } else {
            dupes.forEach(d => {
                html += '<div class="pb-dupe-item"><div class="pb-dupe-sentence">'
                      + this._escapeAttr(d.sentence) + '</div>'
                      + '<div class="pb-dupe-meta">' + d.count + '회 반복</div>'
                      + '<div class="pb-dupe-jumps">';
                d.occurrences.forEach((o, i) => {
                    html += '<button type="button" class="pb-dupe-jump" onclick="Builder.jumpToSentence('
                          + JSON.stringify(o.cardId) + ',' + o.start + ',' + o.end + ')">'
                          + (i + 1) + '번째 위치로 이동</button>';
                });
                html += '</div></div>';
            });
        }
        panel.innerHTML = html;
        panel.removeAttribute('hidden');
        if (btn) btn.setAttribute('aria-expanded', 'true');
    },

    renderAdditionalInstructions: function() {
        // UI for inserting additional instructions is a button that opens modal
    },

    // Library Modal Methods
    libraryState: {
        selectedIds: [],
        categories: []
    },

    openLibraryModal: function(type, defaultTab) {
        document.getElementById('lib_type').value = type;
        const titleMap = {
            'generation_condition': 'AI 글 생성 조건 관리',
            'additional_instruction': '이번 글 추가 지시 라이브러리',
            'structure_template': '글 전개 구조 관리'
        };
        const addBtnTextMap = {
            'generation_condition': '+ 새 조건 등록',
            'additional_instruction': '+ 새 지시 등록',
            'structure_template': '+ 새 글 구조 등록'
        };
        document.getElementById('pb_library_modal_title').innerText = titleMap[type] || '라이브러리 관리';
        const addBtn = document.getElementById('pb_library_add_btn');
        if (addBtn) addBtn.innerText = addBtnTextMap[type] || '+ 새 항목 등록';
        
        // 탭 UI 표시 여부 제어
        const tabsEl = document.getElementById('pb_library_tabs');
        const manageTab = document.getElementById('pb_lib_tab_manage');
        const selectTab = document.getElementById('pb_lib_tab_select');
        const catTab = document.getElementById('pb_lib_tab_category');
        
        if (type === 'structure_template') {
            if(tabsEl) tabsEl.style.display = 'flex';
        } else {
            if(tabsEl) tabsEl.style.display = 'none';
            if(selectTab) selectTab.style.display = 'none';
            if(catTab) catTab.style.display = 'none';
            if(manageTab) manageTab.style.display = 'block';
        }

        this.libraryState.selectedIds = [];
        this.selectedStructures = []; // 체크 해제
        
        document.getElementById('lib_filter_search').value = '';
        document.getElementById('lib_filter_category').value = '';
        document.getElementById('lib_filter_status').value = '1';
        document.getElementById('lib_filter_fav').checked = false;
        document.getElementById('lib_filter_sort').value = 'sort_asc';
        if (document.getElementById('lib_check_all')) document.getElementById('lib_check_all').checked = false;
        
        this.loadLibraryCategories(() => {
            document.getElementById('pb_library_form_wrap').style.display = 'none';
            this.renderLibraryModalList();
            
            if (type === 'structure_template') {
                this.switchLibraryTab(defaultTab || 'select');
                
                // 모달 띄울 때 현재 확정된 구조들을 기본으로 선택 상태에 두기
                const idsStr = document.getElementById('pb_selected_structure_ids');
                if (idsStr && idsStr.value) {
                    this.selectedStructures = idsStr.value.split(',').filter(Boolean);
                } else {
                    this.selectedStructures = [];
                }
                this.renderStructureAccordion();
            } else {
                this.switchLibraryTab('manage');
            }
        });
        
        document.getElementById('pb-library-overlay').style.display = 'block';
        document.getElementById('pb-library-modal').style.display = 'flex';
    },

    loadLibraryCategories: function(callback) {
        const payload = new URLSearchParams();
        payload.append('action', 'load_library_categories');
        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.libraryState.categories = res.categories || [];
                this._updateCategoryDropdowns();
                if (callback) callback();
            }
        });
    },

    _updateCategoryDropdowns: function() {
        const type = document.getElementById('lib_type').value;
        const filtered = this.libraryState.categories.filter(c => c.library_type === type || c.library_type === (type === 'structure_template' ? 'structure' : type));
        
        // Filter Dropdown
        const filterSel = document.getElementById('lib_filter_category');
        if (filterSel) {
            filterSel.innerHTML = '<option value="">전체 카테고리 ▼</option>' + 
                filtered.map(c => `<option value="${c.id}">${this._escapeAttr(c.category_name)}</option>`).join('');
        }
        
        // Form Dropdown
        const formSel = document.getElementById('lib_category');
        if (formSel) {
            formSel.innerHTML = '<option value="">카테고리 선택 (선택)</option>' + 
                filtered.map(c => `<option value="${c.id}">${this._escapeAttr(c.category_name)}</option>`).join('') +
                '<option value="__NEW__">+ 새 카테고리 추가</option>';
        }
    },

    closeLibraryModal: function() {
        document.getElementById('pb-library-overlay').style.display = 'none';
        document.getElementById('pb-library-modal').style.display = 'none';
        this.loadLibraryItems(); // Refresh on close
    },

    showLibraryForm: function(id) {
        document.getElementById('pb_lib_tab_select').style.display = 'none';
        document.getElementById('pb_lib_tab_manage').style.display = 'none';
        document.getElementById('pb_lib_tab_category').style.display = 'none';
        const tabsEl = document.getElementById('pb_library_tabs');
        if (tabsEl) tabsEl.style.display = 'none';
        document.getElementById('pb_library_form_wrap').style.display = 'block';
        
        if (id > 0) {
            this.editLibraryItem(id);
        } else {
            this.resetLibraryForm();
        }
    },

    hideLibraryForm: function() {
        document.getElementById('pb_library_form_wrap').style.display = 'none';
        
        const tabsEl = document.getElementById('pb_library_tabs');
        if (tabsEl) tabsEl.style.display = 'flex';
        
        this.switchLibraryTab('manage');
    },
    
    showCategoryManager: function() {
        this.switchLibraryTab('category');
    },

    hideCategoryManager: function() {
        this.switchLibraryTab('manage');
    },

    resetLibraryForm: function() {
        document.getElementById('lib_id').value = '0';
        document.getElementById('lib_title').value = '';
        document.getElementById('lib_category').value = '';
        document.getElementById('lib_content').value = '';
        document.getElementById('lib_description').value = '';
        document.getElementById('lib_tags').value = '';
        document.getElementById('lib_recommended_purpose').value = '';
        document.getElementById('lib_recommended_industries').value = '';
        document.getElementById('lib_is_default_checked').checked = false;
        document.getElementById('lib_is_favorite_checked').checked = false;
        document.getElementById('lib_is_active').checked = false;
        document.getElementById('pb_library_form_title').innerText = '새 항목 등록';
        
        if(document.getElementById('lib_item_code')) document.getElementById('lib_item_code').value = '';
        if(document.getElementById('lib_expected_length')) document.getElementById('lib_expected_length').value = '';
        this.structureSteps = [];
        this.renderStructureSteps();
        
        const type = document.getElementById('lib_type').value;
        if(type === 'structure_template') {
            document.getElementById('lib_structure_fields').style.display = 'flex';
            document.getElementById('lib_steps_group').style.display = 'block';
            document.getElementById('lib_content_group').style.display = 'none';
        } else {
            document.getElementById('lib_structure_fields').style.display = 'none';
            document.getElementById('lib_steps_group').style.display = 'none';
            document.getElementById('lib_content_group').style.display = 'block';
        }
    },

    editLibraryItem: function(id) {
        const type = document.getElementById('lib_type').value;
        const item = this.libraryItems[type].find(i => i.id == id);
        if (!item) return;
        
        document.getElementById('lib_id').value = item.id;
        document.getElementById('lib_title').value = item.title;
        document.getElementById('lib_category').value = item.category_id || '';
        document.getElementById('lib_content').value = item.content;
        document.getElementById('lib_description').value = item.description || '';
        document.getElementById('lib_tags').value = item.tags || '';
        document.getElementById('lib_recommended_purpose').value = item.recommended_purpose || '';
        document.getElementById('lib_recommended_industries').value = item.recommended_industries || '';
        document.getElementById('lib_is_default_checked').checked = (item.is_default_checked == 1);
        document.getElementById('lib_is_favorite_checked').checked = (item.is_favorite == 1);
        document.getElementById('lib_is_active').checked = (item.is_active == 0); // is_active=0 means checkbox checked (사용 중지)
        document.getElementById('pb_library_form_title').innerText = '항목 수정';
        
        if(type === 'structure_template') {
            document.getElementById('lib_structure_fields').style.display = 'flex';
            document.getElementById('lib_steps_group').style.display = 'block';
            document.getElementById('lib_content_group').style.display = 'none';
            if(document.getElementById('lib_item_code')) document.getElementById('lib_item_code').value = item.item_code || '';
            if(document.getElementById('lib_expected_length')) document.getElementById('lib_expected_length').value = item.expected_length || '';
            
            this.structureSteps = [];
            if(item.structure_steps) {
                try {
                    this.structureSteps = JSON.parse(item.structure_steps);
                } catch(e) {}
            }
            this.renderStructureSteps();
        } else {
            document.getElementById('lib_structure_fields').style.display = 'none';
            document.getElementById('lib_steps_group').style.display = 'none';
            document.getElementById('lib_content_group').style.display = 'block';
        }
    },

    structureSteps: [],
    
    addStructureStep: function() {
        this.structureSteps.push({ name: '', desc: '', instruction: '' });
        this.renderStructureSteps();
    },
    
    removeStructureStep: function(idx) {
        this.structureSteps.splice(idx, 1);
        this.renderStructureSteps();
    },
    
    updateStructureStep: function(idx, field, value) {
        if(this.structureSteps[idx]) {
            this.structureSteps[idx][field] = value;
        }
    },
    
    renderStructureSteps: function() {
        const container = document.getElementById('lib_steps_container');
        if(!container) return;
        
        if(this.structureSteps.length === 0) {
            container.innerHTML = '<div style="text-align:center; padding:15px; color:#94a3b8; font-size:13px;">단계가 없습니다. [단계 추가] 버튼을 눌러 구조를 구성하세요.</div>';
            return;
        }
        
        let html = '';
        this.structureSteps.forEach((step, idx) => {
            html += `
            <div style="background:#fff; border:1px solid #cbd5e1; border-radius:6px; padding:10px;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <span style="font-weight:bold; font-size:13px; color:#475569;">Step ${idx+1}</span>
                    <button type="button" class="btn btn_02" style="font-size:11px; padding:2px 6px; color:#ef4444;" onclick="Builder.removeStructureStep(${idx})">삭제</button>
                </div>
                <div class="pb-form-grid" style="margin-bottom:8px;">
                    <div class="pb-form-group" style="margin-bottom:0;">
                        <input type="text" class="frm_input" placeholder="단계명 (예: 문제 제기)" value="${this._escapeAttr(step.name)}" onchange="Builder.updateStructureStep(${idx}, 'name', this.value)">
                    </div>
                    <div class="pb-form-group" style="margin-bottom:0;">
                        <input type="text" class="frm_input" placeholder="간단 설명 (옵션)" value="${this._escapeAttr(step.desc)}" onchange="Builder.updateStructureStep(${idx}, 'desc', this.value)">
                    </div>
                </div>
                <textarea class="frm_input" rows="2" placeholder="AI에게 내릴 상세 작성 지시" onchange="Builder.updateStructureStep(${idx}, 'instruction', this.value)">${this._escapeAttr(step.instruction)}</textarea>
            </div>
            `;
        });
        container.innerHTML = html;
    },

    saveLibraryItem: function() {
        const payload = new URLSearchParams();
        const type = document.getElementById('lib_type').value;
        payload.append('action', 'save_library_item');
        payload.append('id', document.getElementById('lib_id').value);
        payload.append('instruction_type', type);
        payload.append('title', document.getElementById('lib_title').value);
        
        const catVal = document.getElementById('lib_category').value;
        if (catVal && catVal !== '__NEW__') {
            const cat = this.libraryState.categories.find(c => c.id == catVal);
            if (cat) payload.append('category', cat.category_name);
        }
        
        if (type === 'structure_template') {
            if(document.getElementById('lib_item_code')) {
                payload.append('item_code', document.getElementById('lib_item_code').value);
            }
            if(document.getElementById('lib_expected_length')) {
                payload.append('expected_length', document.getElementById('lib_expected_length').value);
            }
            // steps -> json and text content
            payload.append('structure_steps', JSON.stringify(this.structureSteps));
            
            // Generate combined text instruction for fallback
            let combined = "";
            this.structureSteps.forEach((step, idx) => {
                combined += `${idx+1}단계: ${step.name}\n`;
                if(step.desc) combined += `- ${step.desc}\n`;
                combined += `- 지시: ${step.instruction}\n\n`;
            });
            payload.append('content', combined.trim());
        } else {
            payload.append('content', document.getElementById('lib_content').value);
        }
        
        payload.append('description', document.getElementById('lib_description').value);
        payload.append('tags', document.getElementById('lib_tags').value);
        payload.append('recommended_purpose', document.getElementById('lib_recommended_purpose').value);
        payload.append('recommended_industries', document.getElementById('lib_recommended_industries').value);
        payload.append('is_default_checked', document.getElementById('lib_is_default_checked').checked ? 'Y' : 'N');
        payload.append('is_favorite_checked', document.getElementById('lib_is_favorite_checked').checked ? 'Y' : 'N');
        payload.append('is_active', document.getElementById('lib_is_active').checked ? '0' : '1');

        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.loadLibraryItems();
                setTimeout(() => {
                    this.renderLibraryModalList();
                    this.hideLibraryForm();
                }, 300);
            } else {
                alert(res.error || '저장 실패');
            }
        });
    },

    deleteLibraryItem: function(id, type) {
        if (!confirm('이 항목을 삭제(사용 중지)하시겠습니까?\\n이후 목록에 표시되지 않습니다.')) return;
        const payload = new URLSearchParams();
        payload.append('action', 'delete_library_item');
        payload.append('id', id);
        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.loadLibraryItems();
                setTimeout(() => this.renderLibraryModalList(), 300);
            } else {
                alert(res.error || '삭제 실패');
            }
        });
    },

    duplicateLibraryItem: function(id, type) {
        const payload = new URLSearchParams();
        payload.append('action', 'duplicate_library_item');
        payload.append('id', id);
        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this._toast('복제되었습니다.', 'success');
                this.loadLibraryItems();
                setTimeout(() => this.renderLibraryModalList(), 300);
            } else {
                alert(res.error || '복제 실패');
            }
        });
    },

    toggleLibraryFavorite: function(id, current) {
        const payload = new URLSearchParams();
        payload.append('action', 'toggle_favorite_library_item');
        payload.append('id', id);
        payload.append('is_favorite', current == 1 ? 0 : 1);
        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.loadLibraryItems();
                // loadLibraryItems()의 fetch가 끝난 뒤에 다시 그려야 갱신된 별 상태가
                // 보인다 - "글 구조 선택" 탭(아코디언)에도 별 아이콘을 새로 추가했으므로
                // 같이 갱신한다.
                setTimeout(() => {
                    this.renderLibraryModalList();
                    this.renderStructureAccordion();
                }, 300);
            }
        });
    },

    changeLibraryStatus: function(id, current) {
        if (!confirm(current == 1 ? '보관(사용 중지) 처리하시겠습니까?' : '복구(사용) 처리하시겠습니까?')) return;
        const payload = new URLSearchParams();
        payload.append('action', 'update_library_status');
        payload.append('id', id);
        payload.append('is_active', current == 1 ? 0 : 1);
        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.loadLibraryItems();
                setTimeout(() => this.renderLibraryModalList(), 300);
            }
        });
    },

    appendAdditionalInstruction: function(content, id) {
        const el = document.getElementById('pb_extra_instruction');
        if (!el) return;

        // 이미 같은 지시문이 들어가 있으면 경고 후 확인받는다 - 여러 번 누르면
        // 같은 내용이 계속 쌓이는 것을 방지한다.
        if (el.value.indexOf(content) !== -1) {
            if (!confirm('이미 현재 글의 추가 지시에 포함된 항목입니다.\n그래도 추가하시겠습니까?')) return;
        }

        // 이전에는 여기서 실제 줄바꿈 대신 리터럴 문자열 "\n\n"(백슬래시+n 두 글자)이 그대로
        // 삽입되고 있었다 - 화면에는 물론 실제 AI 프롬프트에도 "\n\n" 텍스트가 그대로
        // 찍히는 버그였다. 자바스크립트 개행 이스케이프는 "\n"(백슬래시 한 개)여야 한다.
        el.value = el.value.trim() ? el.value.trim() + '\n\n' + content : content;
        this._toast('입력창에 삽입되었습니다.', 'success');
        if (id) {
            this.updateLibraryUsage([id]);
        }
        this.closeLibraryModal();
    },

    // "바꿔 넣기" - 기존 이어 붙이기와 달리 현재 입력값을 완전히 교체한다. 실수로 기존
    // 내용을 날리지 않도록 교체 전 확인창을 띄운다(PM 요구사항).
    replaceAdditionalInstruction: function(content, id) {
        const el = document.getElementById('pb_extra_instruction');
        if (!el) return;

        if (el.value.trim() !== '') {
            if (!confirm('현재 입력된 추가 지시문을 선택한 라이브러리 내용으로 교체하시겠습니까?')) return;
        }

        el.value = content;
        this._toast('입력창 내용을 교체했습니다.', 'success');
        if (id) {
            this.updateLibraryUsage([id]);
        }
        this.closeLibraryModal();
    },

    updateLibraryUsage: function(ids) {
        if (!ids || !ids.length) return;
        const payload = new URLSearchParams();
        payload.append('action', 'update_library_usage');
        ids.forEach(id => payload.append('ids[]', id));
        fetch('ajax.builder.php', { method: 'POST', body: payload });
    },
    
    toggleLibrarySelectAll: function(checked) {
        const checkboxes = document.querySelectorAll('.lib-item-check');
        this.libraryState.selectedIds = [];
        checkboxes.forEach(chk => {
            chk.checked = checked;
            if (checked) this.libraryState.selectedIds.push(chk.value);
        });
    },

    toggleLibrarySelect: function(id, checked) {
        if (checked) {
            if (!this.libraryState.selectedIds.includes(String(id))) this.libraryState.selectedIds.push(String(id));
        } else {
            this.libraryState.selectedIds = this.libraryState.selectedIds.filter(val => val !== String(id));
            if (document.getElementById('lib_check_all')) document.getElementById('lib_check_all').checked = false;
        }
    },

    insertSelectedLibraryItems: function() {
        if (!this.libraryState.selectedIds.length) {
            alert('삽입할 항목을 선택해 주세요.');
            return;
        }
        const type = document.getElementById('lib_type').value;
        const items = this.libraryItems[type] || [];
        const selectedItems = items.filter(i => this.libraryState.selectedIds.includes(String(i.id)));
        
        // 여기도 append와 같은 리터럴 "\n\n" 버그가 있었다 - 실제 개행 이스케이프는 "\n" 하나.
        const contents = selectedItems.map(i => i.content).join('\n\n');
        this.appendAdditionalInstruction(contents);
        this.updateLibraryUsage(this.libraryState.selectedIds);
    },

    // 관리 목록의 "이어 붙이기/바꿔 넣기"는 원래 추가지시(텍스트) 라이브러리 전용
    // 함수(appendAdditionalInstruction/replaceAdditionalInstruction - pb_extra_instruction
    // 텍스트창에 꽂는 함수)였다. 이걸 type 구분 없이 구조(structure_template)에도 그대로
    // 재사용하면, 구조를 "뒤에 추가"해도 실제로는 추가 지시 텍스트창에 JSON이 꽂히는
    // 완전히 엉뚱한 동작이 된다. 구조는 반드시 pb_selected_structure_ids(적용된 구조
    // 목록)에 반영해야 한다 - 아래 두 함수가 그 역할을 한다.
    appendStructureToSelection: function(id) {
        const idsEl = document.getElementById('pb_selected_structure_ids');
        if (!idsEl) return;
        const currentIds = idsEl.value.split(',').filter(Boolean);
        const idStr = id.toString();
        if (!currentIds.includes(idStr)) currentIds.push(idStr);
        idsEl.value = currentIds.join(',');
        this.updateSelectedStructureSummary();
        this.renderStructureList();
        this.renderLibraryModalList();
        const s = this.libraryItems.structure_template.find(item => item.id == id);
        this._toast(s ? `'${s.title}'을(를) 현재 구조 뒤에 추가했습니다.` : '추가되었습니다.', 'success');
    },

    replaceStructureSelectionSingle: function(id) {
        const s = this.libraryItems.structure_template.find(item => item.id == id);
        const idsEl = document.getElementById('pb_selected_structure_ids');
        if (!idsEl) return;
        if (idsEl.value) {
            if (!confirm(`현재 적용된 글 전개 구조를 삭제하고 '${s ? s.title : '선택한 구조'}'(으)로 교체하시겠습니까?`)) return;
        }
        idsEl.value = id.toString();
        this.updateSelectedStructureSummary();
        this.renderStructureList();
        this.renderLibraryModalList();
        this._toast(s ? `'${s.title}' 구조로 교체했습니다.` : '교체되었습니다.', 'success');
    },

    // 체크박스로 여러 구조를 골라 한 번에 뒤에 추가/교체 - libraryState.selectedIds를
    // 그대로 재사용한다(체크한 순서를 배열 순서로 유지).
    appendCheckedStructuresToSelection: function() {
        const checked = this.libraryState.selectedIds;
        if (!checked.length) {
            alert('추가할 구조를 하나 이상 체크해 주세요.');
            return;
        }
        const idsEl = document.getElementById('pb_selected_structure_ids');
        const currentIds = idsEl.value.split(',').filter(Boolean);
        const newIds = [...new Set([...currentIds, ...checked])];
        idsEl.value = newIds.join(',');
        this.updateSelectedStructureSummary();
        this.renderStructureList();
        this._toast(`${checked.length}개 구조를 뒤에 추가했습니다.`, 'success');
    },

    replaceSelectionWithCheckedStructures: function() {
        const checked = this.libraryState.selectedIds;
        if (!checked.length) {
            alert('교체할 구조를 하나 이상 체크해 주세요.');
            return;
        }
        if (!confirm('현재 적용된 글 전개 구조를 삭제하고 체크한 구조로 교체하시겠습니까?')) return;
        document.getElementById('pb_selected_structure_ids').value = checked.join(',');
        this.updateSelectedStructureSummary();
        this.renderStructureList();
        this._toast(`${checked.length}개 구조로 교체했습니다.`, 'success');
    },

    renderLibraryModalList: function() {
        const listEl = document.getElementById('pb_library_modal_list');
        if (!listEl) return;
        const type = document.getElementById('lib_type').value;
        const isStructure = (type === 'structure_template');

        let items = this.libraryItems[type] || [];
        
        // Filtering
        const filterCat = document.getElementById('lib_filter_category') ? document.getElementById('lib_filter_category').value : '';
        const filterStatus = document.getElementById('lib_filter_status') ? document.getElementById('lib_filter_status').value : '1';
        const filterSearch = document.getElementById('lib_filter_search') ? document.getElementById('lib_filter_search').value.toLowerCase() : '';
        const filterFav = document.getElementById('lib_filter_fav') ? document.getElementById('lib_filter_fav').checked : false;

        items = items.filter(i => {
            if (filterCat && String(i.category_id) !== filterCat) return false;
            if (filterStatus !== 'all' && String(i.is_active || 1) !== filterStatus) return false;
            if (filterFav && i.is_favorite != 1) return false;
            if (filterSearch) {
                const searchStr = (i.title + ' ' + i.content + ' ' + (i.tags||'') + ' ' + (i.description||'')).toLowerCase();
                if (searchStr.indexOf(filterSearch) === -1) return false;
            }
            return true;
        });

        // Sorting
        const sortBy = document.getElementById('lib_filter_sort') ? document.getElementById('lib_filter_sort').value : 'sort_asc';
        items.sort((a, b) => {
            if (sortBy === 'recent_used') {
                return (b.last_used_at || '0000-00-00').localeCompare(a.last_used_at || '0000-00-00');
            } else if (sortBy === 'most_used') {
                return (parseInt(b.usage_count) || 0) - (parseInt(a.usage_count) || 0);
            } else if (sortBy === 'recent_added') {
                return parseInt(b.id) - parseInt(a.id);
            }
            return 0; // sort_asc (default)
        });

        // 구조(structure_template)는 적용된 목록(pb_selected_structure_ids)에 순서가
        // 있으므로, 목록에 몇 번째로 적용됐는지 배지로 보여준다.
        const appliedIds = isStructure && document.getElementById('pb_selected_structure_ids')
            ? document.getElementById('pb_selected_structure_ids').value.split(',').filter(Boolean)
            : [];

        let html = '';

        // 구조 전용 일괄 처리 바 - 체크한 여러 구조를 한 번에 뒤에 추가/교체한다.
        // 구조 전용 일괄 처리 바
        if (isStructure && items.length > 0) {
            html += `
                <div style="display:flex; justify-content:space-between; align-items:center; padding:8px 10px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:8px; flex-wrap:wrap; gap:8px;">
                    <label style="font-size:13px; display:flex; align-items:center; gap:6px;">
                        <input type="checkbox" id="lib_check_all_structure" onchange="Builder.toggleLibrarySelectAll(this.checked)"> 전체선택
                    </label>
                    <div style="display:flex; gap:6px;">
                        <button type="button" class="btn btn_01" style="background:#4f46e5; border-color:#4f46e5; font-size:12px; padding:5px 15px;" onclick="Builder.appendCheckedStructuresToSelection()">선택 항목 이어 붙이기</button>
                    </div>
                </div>`;
        }

        if (items.length === 0) {
            html += `<div style="color:#94a3b8; font-size:13px; padding:20px 0; text-align:center;">조건에 맞는 항목이 없습니다.</div>`;
        } else {
            items.forEach(item => {
                const checkedStr = this.libraryState.selectedIds.includes(String(item.id)) ? 'checked' : '';
                const favColor = item.is_favorite == 1 ? '#eab308' : '#cbd5e0';
                const opacity = item.is_active == 0 ? '0.6' : '1';
                const statusBadge = item.is_active == 0 ? '<span style="background:#ef4444;color:#fff;padding:2px 6px;border-radius:4px;font-size:11px;">보관됨</span>' : '';

                const metaHtml = [];
                if (item.category) metaHtml.push(`[${this._escapeAttr(item.category)}]`);
                if (item.tags) metaHtml.push(`태그: ${this._escapeAttr(item.tags)}`);
                if (item.usage_count > 0) metaHtml.push(`사용: ${item.usage_count}회`);
                const metaStr = metaHtml.length ? `<div style="font-size:11px; color:#94a3b8; margin-top:6px;">${metaHtml.join(' | ')}</div>` : '';

                // 구조는 원본이 JSON(sections 배열)이라 그대로 보여주면 안 읽힌다 -
                // 단계 제목을 화살표로 이어서 요약 표시한다.
                let contentDisplay = this._escapeAttr(item.content);
                let appliedOrderBadge = '';
                if (isStructure) {
                    let stepsHtml = '';
                    if (item.content_json && Array.isArray(item.content_json.sections)) {
                        stepsHtml = item.content_json.sections.map((sec, idx) => `${idx + 1}. ${this._escapeHtml(sec.title)}${sec.desc ? ': ' + this._escapeHtml(sec.desc) : ''}`).join('<br>');
                    } else {
                        try {
                            const parsed = JSON.parse(item.content);
                            if (parsed && Array.isArray(parsed.sections)) {
                                stepsHtml = parsed.sections.map((sec, idx) => `${idx + 1}. ${this._escapeHtml(sec.title)}${sec.desc ? ': ' + this._escapeHtml(sec.desc) : ''}`).join('<br>');
                            }
                        } catch (e) { /* 원본 텍스트 그대로 표시 */ }
                    }
                    if (stepsHtml) {
                        contentDisplay = stepsHtml;
                    }
                    const appliedIdx = appliedIds.indexOf(String(item.id));
                    if (appliedIdx !== -1) {
                        appliedOrderBadge = `<span style="background:#3b82f6; color:#fff; font-size:11px; font-weight:600; border-radius:10px; padding:1px 7px;">적용됨 ${appliedIdx + 1}</span>`;
                    }
                }

                const actionButtonsHtml = isStructure ? `
                        <button type="button" class="btn btn_02" style="font-size:11px; padding:4px 8px; width:100%;" onclick="Builder.appendStructureToSelection(${item.id})">이어 붙이기</button>
                        <button type="button" class="btn btn_02" style="font-size:11px; padding:4px 8px; width:100%;" onclick="Builder.replaceStructureSelectionSingle(${item.id})">바꿔 넣기</button>
                        <button type="button" class="btn btn_02" style="font-size:11px; padding:4px 8px; width:100%;" onclick="Builder.editLibraryItem(${item.id})">수정</button>
                        <div style="display:flex; gap:4px;">
                            <button type="button" class="btn btn_02" style="font-size:11px; padding:4px; flex:1;" onclick="Builder.duplicateLibraryItem(${item.id}, '${type}')" title="복제">📋</button>
                            ${item.is_active == 1 ?
                                `<button type="button" class="btn btn_02" style="font-size:11px; padding:4px; flex:1; color:#ef4444;" onclick="Builder.changeLibraryStatus(${item.id}, 1)" title="보관">🗑</button>` :
                                `<button type="button" class="btn btn_02" style="font-size:11px; padding:4px; flex:1; color:#10b981;" onclick="Builder.changeLibraryStatus(${item.id}, 0)" title="복구">♻</button>`}
                        </div>` : `
                        <button type="button" class="btn btn_01" style="font-size:11px; padding:4px 8px; width:100%;" onclick="Builder.appendAdditionalInstruction('${this._escapeAttr(item.content).replace(/'/g, "\\'")}', ${item.id})">이어 붙이기</button>
                        <button type="button" class="btn btn_02" style="font-size:11px; padding:4px 8px; width:100%;" onclick="Builder.replaceAdditionalInstruction('${this._escapeAttr(item.content).replace(/'/g, "\\'")}', ${item.id})">바꿔 넣기</button>
                        <button type="button" class="btn btn_02" style="font-size:11px; padding:4px 8px; width:100%;" onclick="Builder.editLibraryItem(${item.id})">수정</button>
                        <div style="display:flex; gap:4px;">
                            <button type="button" class="btn btn_02" style="font-size:11px; padding:4px; flex:1;" onclick="Builder.duplicateLibraryItem(${item.id}, '${type}')" title="복제">📋</button>
                            ${item.is_active == 1 ?
                                `<button type="button" class="btn btn_02" style="font-size:11px; padding:4px; flex:1; color:#ef4444;" onclick="Builder.changeLibraryStatus(${item.id}, 1)" title="보관">🗑</button>` :
                                `<button type="button" class="btn btn_02" style="font-size:11px; padding:4px; flex:1; color:#10b981;" onclick="Builder.changeLibraryStatus(${item.id}, 0)" title="복구">♻</button>`}
                        </div>`;

                html += `
                <div style="background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:12px; display:flex; align-items:flex-start; opacity:${opacity}; transition:all 0.2s;">
                    <input type="checkbox" class="lib-item-check" value="${item.id}" onchange="Builder.toggleLibrarySelect(${item.id}, this.checked)" ${checkedStr} style="margin-top:4px; margin-right:12px;">

                    <div style="flex:1; min-width:0;">
                        <div style="display:flex; align-items:center; gap:8px; margin-bottom:4px;">
                            <button onclick="event.stopPropagation(); Builder.toggleLibraryFavorite(${item.id}, ${item.is_favorite})" style="background:none;border:none;cursor:pointer;color:${favColor};font-size:16px;padding:0;line-height:1;">★</button>
                            <span style="font-weight:700; font-size:14px; color:#334155;">${this._escapeAttr(item.title)}</span>
                            ${item.is_default_checked == 1 ? '<span style="color:#10b981; font-size:11px; font-weight:600; border:1px solid #10b981; border-radius:4px; padding:1px 4px;">기본</span>' : ''}
                            ${appliedOrderBadge}
                            ${statusBadge}
                        </div>
                        ${item.description ? `<div style="font-size:12px; color:#475569; margin-bottom:6px; font-weight:600;">${this._escapeAttr(item.description)}</div>` : ''}
                        <div style="font-size:12.5px; color:#64748b; white-space:pre-wrap; line-height:1.4; background:#f8fafc; padding:8px; border-radius:6px; border:1px solid #f1f5f9; overflow-wrap:anywhere;">${contentDisplay}</div>
                        ${metaStr}
                    </div>

                    <div style="display:flex; flex-direction:column; gap:6px; margin-left:15px; min-width:80px;">
                        ${actionButtonsHtml}
                    </div>
                </div>`;
            });
        }
        listEl.innerHTML = html;
    },

    saveLibraryCategory: function(id) {
        const payload = new URLSearchParams();
        payload.append('action', 'save_library_category');
        payload.append('id', id);
        payload.append('library_type', document.getElementById('lib_type').value);
        payload.append('category_name', document.getElementById('new_cat_name').value);
        
        if (!document.getElementById('new_cat_name').value.trim()) return;

        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                document.getElementById('new_cat_name').value = '';
                this.loadLibraryCategories(() => this.renderCategoryList());
            } else {
                alert(res.error || '저장 실패');
            }
        });
    },

    deleteLibraryCategory: function(id) {
        if (!confirm('이 카테고리를 삭제하시겠습니까?')) return;
        const payload = new URLSearchParams();
        payload.append('action', 'delete_library_category');
        payload.append('id', id);
        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this.loadLibraryCategories(() => this.renderCategoryList());
            } else {
                alert(res.error || '삭제 실패');
            }
        });
    },

    renderCategoryList: function() {
        const listEl = document.getElementById('pb_category_list');
        if (!listEl) return;
        const type = document.getElementById('lib_type').value;
        const filtered = this.libraryState.categories.filter(c => c.library_type === type || c.library_type === (type === 'structure_template' ? 'structure' : type));
        
        if (filtered.length === 0) {
            listEl.innerHTML = '<div style="color:#94a3b8; font-size:13px; text-align:center; padding:10px 0;">등록된 카테고리가 없습니다.</div>';
            return;
        }

        listEl.innerHTML = filtered.map(c => `
            <div style="display:flex; justify-content:space-between; align-items:center; background:#f8fafc; padding:8px 12px; border:1px solid #e2e8f0; border-radius:6px;">
                <span style="font-size:14px; font-weight:600; color:#334155;">${this._escapeAttr(c.category_name)}</span>
                <button type="button" class="btn btn_02" style="font-size:11px; padding:4px 8px; color:#ef4444;" onclick="Builder.deleteLibraryCategory(${c.id})">삭제</button>
            </div>
        `).join('');
    },

    tagGroupCollapsed: false,
    toggleTagGroup: function() {
        this.tagGroupCollapsed = !this.tagGroupCollapsed;
        this.renderRightPanel();
    },

    // "업체 정보 삽입" - 1단계 컨트롤 패널에서 상호/주소/전화/이메일/웹사이트/SNS주소 중
    // 무엇을 "방문팁" 마무리 섹션에 넣을지 체크박스로 고른다. 체크 상태는
    // generateAllCards()가 contact_fields로 서버에 넘겨서 post_builder_ajax.php가 그
    // 항목만 프롬프트에 포함시킨다. SNS주소는 advertisers 테이블에 아직 별도 컬럼이 없어서
    // consult_url(상담/SNS 주소로 같이 쓰는 기존 필드)을 그대로 쓴다.
    advertiserInfo: {},
    contactState: { collapsed: false, checked: {} },
    _contactFields: [
        { key: 'name', label: '상호' },
        { key: 'address', label: '주소' },
        { key: 'phone', label: '전화' },
        { key: 'email', label: '이메일' },
        { key: 'domain', label: '웹사이트' },
        { key: 'consult_url', label: 'SNS/상담 주소' }
    ],

    // advertiserInfo가 새로 채워질 때마다 호출 - 값이 있는 항목은 기본 체크, 없으면 체크 해제.
    _initContactState: function() {
        const info = this.advertiserInfo || {};
        this._contactFields.forEach(f => {
            this.contactState.checked[f.key] = !!(info[f.key] && String(info[f.key]).trim() !== '');
        });
    },

    toggleContactPanel: function() {
        this.contactState.collapsed = !this.contactState.collapsed;
        this.renderContactPanel();
    },

    onContactCheckToggle: function(key, checked) {
        this.contactState.checked[key] = checked;
    },

    renderContactPanel: function() {
        const container = document.getElementById('pb_contact_panel');
        if (!container) return;
        const info = this.advertiserInfo || {};

        if (this.contactState.collapsed) {
            const checkedCount = this._contactFields.filter(f => this.contactState.checked[f.key]).length;
            container.innerHTML = `
            <div style="margin-top:12px; padding:10px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:0.85rem; font-weight:bold; color:#475569;">업체 정보 삽입 (${checkedCount}개 선택)</span>
                <button type="button" class="btn btn_02" onclick="Builder.toggleContactPanel()">펼치기 ▾</button>
            </div>`;
            return;
        }

        let rows = '';
        this._contactFields.forEach(f => {
            const value = info[f.key] ? String(info[f.key]) : '';
            const hasValue = value.trim() !== '';
            const checked = hasValue && this.contactState.checked[f.key];
            rows += `
            <label style="display:flex; align-items:center; gap:8px; font-size:0.85rem; color:${hasValue ? '#334155' : '#94a3b8'}; padding:4px 0;">
                <input type="checkbox" ${checked ? 'checked' : ''} ${hasValue ? '' : 'disabled'} onchange="Builder.onContactCheckToggle('${f.key}', this.checked)">
                <span style="width:70px; flex-shrink:0;">${f.label}</span>
                <span style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${hasValue ? this._escapeAttr(value) : '미등록'}</span>
            </label>`;
        });

        container.innerHTML = `
        <div style="margin-top:12px; padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
                <span style="font-size:0.85rem; font-weight:bold; color:#475569;">업체 정보 삽입 (마무리 섹션에 포함)</span>
                <button type="button" class="btn btn_02" onclick="Builder.toggleContactPanel()">접기 ▴</button>
            </div>
            <p style="font-size:0.75rem; color:#94a3b8; margin:0 0 8px;">체크한 항목만 생성 시 마무리 섹션에 포함됩니다. 값이 없는 항목은 "광고주 수정"에서 먼저 등록하세요.</p>
            ${rows}
        </div>`;
    },

    toggleTagPanel: function(type) {
        this.tagState[type].collapsed = !this.tagState[type].collapsed;
        this.renderTagPanel(type);
    },

    renderTagPanel: function(type) {
        const containerId = type === 'hashtags' ? 'pb_tags_hashtags' : 'pb_tags_keywords';
        const container = document.getElementById(containerId);
        if (!container) return;

        const label = type === 'hashtags' ? '해시태그' : '키워드';
        const state = this.tagState[type];
        const values = state.values;

        // 우측 패널이 세로로 너무 길어진다는 피드백 - 접었을 때는 헤더 한 줄만 남긴다.
        if (state.collapsed) {
            const filledCount = values.filter(v => v && v.trim() !== '').length;
            container.innerHTML = `
            <div style="margin-top:12px; padding:10px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; display:flex; align-items:center; justify-content:space-between;">
                <span style="font-size:0.85rem; font-weight:bold; color:#475569;">${label} (${filledCount}개 입력됨)</span>
                <button type="button" class="btn btn_02" onclick="Builder.toggleTagPanel('${type}')">펼치기 ▾</button>
            </div>`;
            this._syncTagPromptValue(type);
            return;
        }

        let inputs = '';
        for (let i = 0; i < state.count; i++) {
            const v = values[i] || '';
            inputs += `<input type="text" class="frm_input pb-tag-input" style="width:120px;" placeholder="${label} ${i + 1}" value="${this._escapeAttr(v)}" onchange="Builder.onTagInputChange('${type}', ${i}, this.value)">`;
        }

        let countOptions = '';
        for (let n = 3; n <= 30; n++) {
            countOptions += `<option value="${n}" ${n === state.count ? 'selected' : ''}>${n}</option>`;
        }

        container.innerHTML = `
        <div style="margin-top:12px; padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                <span style="font-size:0.85rem; font-weight:bold; color:#475569;">${label} (최대 30개)</span>
                <button type="button" class="btn btn_02" onclick="Builder.toggleTagPanel('${type}')">접기 ▴</button>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:10px;">
                <span style="font-size:0.8rem; color:#666;">개수</span>
                <select class="frm_input" style="width:60px;" onchange="Builder.onTagCountChange('${type}', this.value)">
                    ${countOptions}
                </select>
                <button type="button" class="btn btn_02" onclick="Builder.generateTags('${type}', false)">AI로 채우기</button>
                <button type="button" class="btn btn_02" onclick="Builder.generateTags('${type}', true)">포스트 내용에 맞게</button>
            </div>
            <label style="display:flex; align-items:center; gap:6px; font-size:0.8rem; color:#666; margin-bottom:10px;">
                <input type="checkbox" ${state.autoGenerate ? 'checked' : ''} onchange="Builder.onAutoTagToggle('${type}', this.checked)">
                포스트 내용이 바뀔 때마다 AI가 자동으로 다시 생성
            </label>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">${inputs}</div>
        </div>`;
        this._syncTagPromptValue(type);
    },

    onTagCountChange: function(type, count) {
        this.tagState[type].count = parseInt(count, 10);
        this.renderTagPanel(type);
    },

    onTagInputChange: function(type, idx, value) {
        this.tagState[type].values[idx] = value;
        this.saveTags(type);
        this._syncTagPromptValue(type);
    },

    onAutoTagToggle: function(type, checked) {
        this.tagState[type].autoGenerate = checked;
    },

    // 카드가 바뀔 때마다(saveState 안에서 호출) 자동 생성이 켜진 항목만, 몇 초 안에 연속으로
    // 편집이 이어지면 계속 미뤄지는 디바운스로 트리거한다 - 매 글자 수정마다 AI를 부르면
    // 비용도 문제고 응답도 이상해지기 쉽다.
    scheduleAutoTagGenerate: function() {
        ['keywords', 'hashtags'].forEach(type => {
            if (!this.tagState[type].autoGenerate) return;
            if (this._autoTagTimers[type]) clearTimeout(this._autoTagTimers[type]);
            this._autoTagTimers[type] = setTimeout(() => {
                if (this.cards.length > 0) this.generateTags(type, true, true);
            }, 4000);
        });
    },

    // 카드(제목/본문)를 하나의 텍스트로 합친다 - "포스트 내용에 맞게" 생성이 참고할 실제
    // 작성된 내용(inspectAll()의 조립 로직과 동일한 기준: 대표 제목 + 사용 중인 본문).
    _assemblePostContentText: function() {
        const primaryTitle = this.cards.find(c => c.type === 'title' && c.state === 'primary');
        let text = primaryTitle ? primaryTitle.content : '';
        this.cards
            .filter(c => c.type !== 'title' && (c.state === 'selected' || c.state === 'primary'))
            .forEach(c => { text += '\n' + (c.title ? c.title + '\n' : '') + c.content; });
        return text;
    },

    saveTags: function(type) {
        if (!this.projectId) return;
        const payload = new URLSearchParams();
        payload.append('action', 'save_tags');
        payload.append('project_id', this.projectId);
        payload.append('post_id', this.postId);
        payload.append('tag_type', type);
        payload.append('values', JSON.stringify(this.tagState[type].values));

        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            if (res.ok && res.post_id > 0) this.postId = res.post_id;
        })
        .catch(() => {}); // 자동저장 성격 - 실패해도 카드 작업엔 영향 없고, 다음 입력 시 다시 시도된다.
    },

    // hashtags.enabled를 values와 같은 길이로 맞춘다 - 새로 추가된 항목은 기본 활성(true),
    // 삭제로 짧아진 경우엔 뒤쪽 여분을 잘라낸다. 렌더링/토글/추가/삭제 전에 항상 먼저 부른다.
    _ensureHashtagEnabledLength: function() {
        const st = this.tagState.hashtags;
        if (!Array.isArray(st.enabled)) st.enabled = [];
        while (st.enabled.length < st.values.length) st.enabled.push(true);
        st.enabled.length = st.values.length;
    },

    // 검수완성(4단계) 컨트롤 패널의 "최종 해시태그" - 1단계 생성 컨트롤과는 별개로, AI가
    // 최종 생성한 해시태그를 체크(미적용)/수정/삭제/직접추가로 직접 관리한다.
    _renderFinalHashtagPanelInto: function() {
        const container = document.getElementById('pb_final_hashtags');
        if (container) container.innerHTML = this._renderFinalHashtagPanel();
    },

    _renderFinalHashtagPanel: function() {
        this._ensureHashtagEnabledLength();
        const st = this.tagState.hashtags;
        const rows = st.values.map((v, i) => {
            if (!v || v.trim() === '') return '';
            const enabled = st.enabled[i] !== false;
            const display = v.charAt(0) === '#' ? v : '#' + v;
            const body = this._editingHashtagIdx === i
                ? `<input type="text" class="frm_input" style="flex:1; min-width:0;" value="${this._escapeAttr(v)}"
                       onkeydown="if(event.key==='Enter'){Builder.commitEditFinalHashtag(${i}, this.value);}"
                       onblur="Builder.commitEditFinalHashtag(${i}, this.value)">`
                : `<span style="flex:1; ${enabled ? '' : 'color:#94a3b8; text-decoration:line-through;'}">${this._escapeAttr(display)}</span>`;
            return `
            <div style="display:flex; align-items:center; gap:6px; padding:5px 0; border-bottom:1px solid #eef2f7;">
                <input type="checkbox" ${enabled ? 'checked' : ''} title="체크 해제하면 최종 글에서 제외(미적용)" onchange="Builder.toggleFinalHashtag(${i})">
                ${body}
                ${this._editingHashtagIdx === i ? '' : `<button type="button" class="btn btn_02" style="padding:2px 8px; font-size:0.75rem;" onclick="Builder.startEditFinalHashtag(${i})">수정</button>`}
                <button type="button" class="btn btn_02" style="padding:2px 8px; font-size:0.75rem; color:#ef4444;" onclick="Builder.deleteFinalHashtag(${i})">삭제</button>
            </div>`;
        }).join('');

        return `
        <div style="margin-bottom:12px; padding:10px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; font-size:0.85rem;">
            ${rows || '<p style="color:#94a3b8; margin:0 0 8px;">아직 생성된 해시태그가 없습니다. 1단계 컨트롤 패널에서 "AI로 채우기"를 먼저 눌러주세요.</p>'}
            <div style="display:flex; gap:6px; margin-top:8px;">
                <input type="text" id="pb_final_hashtag_new" class="frm_input" style="flex:1;" placeholder="새 해시태그 직접입력"
                       onkeydown="if(event.key==='Enter'){event.preventDefault(); Builder.addFinalHashtag();}">
                <button type="button" class="btn btn_02" onclick="Builder.addFinalHashtag()">추가</button>
            </div>
        </div>`;
    },

    toggleFinalHashtag: function(idx) {
        this._ensureHashtagEnabledLength();
        const st = this.tagState.hashtags;
        st.enabled[idx] = !(st.enabled[idx] !== false);
        this._renderFinalHashtagPanelInto();
        this.saveState();
    },

    startEditFinalHashtag: function(idx) {
        this._editingHashtagIdx = idx;
        this._renderFinalHashtagPanelInto();
    },

    // Enter로 커밋된 직후 input이 사라지면서 블러도 같이 발생할 수 있어(중복 저장 방지),
    // 편집 중인 idx가 이미 닫혔으면 두 번째 호출은 조용히 무시한다.
    commitEditFinalHashtag: function(idx, value) {
        if (this._editingHashtagIdx !== idx) return;
        this._editingHashtagIdx = null;
        const v = value.trim();
        if (v !== '') this.tagState.hashtags.values[idx] = v;
        this._renderFinalHashtagPanelInto();
        this.saveTags('hashtags');
        this.saveState();
    },

    deleteFinalHashtag: function(idx) {
        const st = this.tagState.hashtags;
        st.values.splice(idx, 1);
        this._ensureHashtagEnabledLength();
        if (this._editingHashtagIdx === idx) this._editingHashtagIdx = null;
        this._renderFinalHashtagPanelInto();
        this.saveTags('hashtags');
        this.saveState();
    },

    addFinalHashtag: function() {
        const input = document.getElementById('pb_final_hashtag_new');
        if (!input) return;
        const v = input.value.trim();
        if (v === '') return;
        const st = this.tagState.hashtags;
        st.values.push(v);
        this._ensureHashtagEnabledLength();
        if (st.count < st.values.length) st.count = st.values.length;
        input.value = '';
        this._renderFinalHashtagPanelInto();
        this.saveTags('hashtags');
        this.saveState();
    },

    // useContent=true면 글감(raw_material) 대신 실제 작성된 포스트 내용을 참고한다("포스트
    // 내용에 맞게" 버튼, 그리고 자동 생성도 항상 이 모드를 쓴다). silent=true면 자동 생성
    // 트리거라서 실패해도 alert 없이 조용히 넘어간다(디바운스로 다시 시도됨).
    generateTags: function(type, useContent, silent) {
        if (!this._requireProject()) return;
        const source = useContent ? this._assemblePostContentText() : document.getElementById('pb_raw_material').value.trim();
        const state = this.tagState[type];
        const seeds = state.values.filter(v => v && v.trim() !== '');

        const payload = new URLSearchParams();
        payload.append('action', 'generate_tags');
        payload.append('project_id', this.projectId);
        payload.append('tag_type', type);
        payload.append('count', state.count);
        payload.append('raw_material', source);
        payload.append('seed_values', JSON.stringify(seeds));

        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            if (res.ok && Array.isArray(res.values)) {
                state.values = res.values;
                this.renderTagPanel(type);
                this.saveTags(type);
            } else if (!silent) {
                alert('생성 실패: ' + (res.error || '알 수 없는 오류'));
            }
        })
        .catch(err => {
            if (silent) return;
            alert(err.message === 'LOGIN_REQUIRED' ? '로그인이 만료되었습니다. 새로고침 후 다시 로그인해주세요.' : '네트워크 오류');
        });
    },

    _toast: function(message, tone) {
        if (typeof window.mgrToast === 'function') window.mgrToast(message, tone);
        else alert(message);
    },

    // fetch 응답을 JSON으로 파싱한다. 세션이 끊긴 상태로 /adm/의 AJAX를 부르면 그누보드가
    // JSON이 아니라 로그인 페이지 전체(HTML)를 200으로 돌려주는데, 그러면 res.json()이
    // SyntaxError를 던지고 그게 그냥 catch()에서 "네트워크 오류"로 뭉개져서 실제로는 로그인이
    // 끊겼을 뿐인데도 원인을 알 수 없는 네트워크 문제처럼 보였다(이번 세션에 여러 번 이걸로
    // 헷갈렸다). 응답 텍스트에서 그누보드 로그인 리다이렉트 흔적을 찾아 구분해준다.
    _parseAjaxJson: function(res) {
        if (res.status === 401) {
            throw new Error('LOGIN_REQUIRED');
        }
        return res.text().then(text => {
            try {
                const parsed = JSON.parse(text);
                if (!res.ok) {
                    throw new Error(parsed.error || parsed.message || ('HTTP Error ' + res.status));
                }
                return parsed;
            } catch (e) {
                // 한글('로그인')만으로 판별하면 소스 파일 업로드/인코딩 문제로 한글 리터럴이
                // 깨졌을 때 이 비교가 조용히 실패해 실제로는 세션 만료인데 다른 오류 메시지로
                // 잘못 표시된 적이 있다. ASCII만으로 판별되는 HTML 신호를 우선 확인한다.
                const lower = text.toLowerCase();
                const looksLikeHtmlPage = lower.indexOf('<!doctype') !== -1 || lower.indexOf('<html') !== -1;
                if (looksLikeHtmlPage || text.indexOf('login.php') !== -1 || text.indexOf('로그인') !== -1) {
                    throw new Error('LOGIN_REQUIRED');
                }
                // If it's already an Error object from our throw above, rethrow it
                if (e instanceof Error && e.message !== 'Unexpected token') {
                    if (!e.message.startsWith('Unexpected token') && !e.message.includes('JSON')) {
                        throw e;
                    }
                }
                throw new Error('JSON parsing failed: HTTP ' + res.status + ' - ' + text.substring(0, 100));
            }
        });
    },

    // 준비 중인 공급자로 실제 생성을 시도했을 때, 조용히 템플릿으로 넘어가지 않고
    // 사용자에게 명시적으로 선택지를 준다. onChoice에는 'switch'/'template'/'cancel' 중 하나가 온다.
    _confirmProviderFallback: function(providerLabel, onChoice) {
        const overlay = document.createElement('div');
        overlay.style.cssText = 'position:fixed; inset:0; background:rgba(15,23,42,0.45); z-index:9999; display:flex; align-items:center; justify-content:center;';
        overlay.innerHTML =
            '<div style="background:#fff; border-radius:10px; padding:24px; max-width:420px; width:90%; box-shadow:0 10px 30px rgba(0,0,0,0.2);">' +
                '<h3 style="margin:0 0 12px; font-size:1.05rem;">실제 AI 호출이 연결되지 않은 공급자입니다</h3>' +
                '<p style="margin:0 0 18px; color:#475569; font-size:0.9rem; line-height:1.5;">현재 <strong>' + providerLabel + '</strong> API 실제 호출은 아직 연결되지 않았습니다.<br>ChatGPT로 변경하거나 AI 없이 안전 템플릿으로 생성할 수 있습니다.</p>' +
                '<div style="display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">' +
                    '<button type="button" class="btn btn_02" data-choice="cancel">취소</button>' +
                    '<button type="button" class="btn btn_02" data-choice="template">템플릿으로 생성</button>' +
                    '<button type="button" class="btn_submit btn" data-choice="switch">ChatGPT로 변경</button>' +
                '</div>' +
            '</div>';
        overlay.querySelectorAll('button[data-choice]').forEach(function(btn) {
            btn.addEventListener('click', function() {
                document.body.removeChild(overlay);
                onChoice(btn.getAttribute('data-choice'));
            });
        });
        document.body.appendChild(overlay);
    },

    // pb_ai_provider_id의 <option data-live="1">(코드 지원+키 있음+활성, 서버 판단과 동일 기준)
    // 중 첫 번째로 전환하고 저장까지 마친 뒤에만 resolve(true)한다 - 저장이 끝나기 전에
    // 바로 재시도하면 서버가 여전히 이전 공급자를 보고 다시 준비중 취급할 수 있어서다.
    _switchToChatGptProvider: function() {
        const select = document.getElementById('pb_ai_provider_id');
        if (!select) return Promise.resolve(false);
        const liveOption = Array.from(select.options).find(opt => opt.dataset.live === '1');
        if (!liveOption) {
            this._toast('사용 가능한 ChatGPT 공급자가 없습니다. AI API 설정 관리에서 먼저 등록해 주세요.', 'error');
            return Promise.resolve(false);
        }
        select.value = liveOption.value;
        return this.saveAiProviderPref(true).then(() => true).catch(() => false);
    },

    /* ==========================================
     * 1. Top Panel / Project Selection
     * ========================================== */
    onTopAdvertiserChange: function(advId, selectProjId = null) {
        const projSelect = document.getElementById('top_project_id');
        projSelect.innerHTML = '<option value="">프로젝트 선택 ▼</option>';
        projSelect.disabled = true;
        if (!advId) {
            document.getElementById('pb-status-display').innerText = '광고주 미지정';
            return;
        }

        const payload = new URLSearchParams();
        payload.append('action', 'load_projects_by_adv');
        payload.append('advertiser_id', advId);

        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success && res.projects) {
                res.projects.forEach(p => {
                    projSelect.innerHTML += `<option value="${p.id}">${p.topic}</option>`;
                });
                projSelect.disabled = false;
                if (selectProjId) projSelect.value = selectProjId;
            }
        });
    },

    onTopProjectChange: function(projId) {
        if (!projId) return;
        const payload = new URLSearchParams();
        payload.append('action', 'load_project');
        payload.append('project_id', projId);

        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                // 프로젝트 연결은 "이전 내용을 불러올지"와 무관하게 항상 확정한다.
                // (예전엔 confirm에서 취소를 누르면 projectId가 끝내 설정되지 않아
                //  방금 등록/선택한 프로젝트인데도 "새 프로젝트 등록을 완료해 주세요" 경고가 반복됐다.)
                this.projectId = res.project.id;
                this._persistCurrentProject();

                // 상단 광고주/프로젝트 선택창도 함께 맞춰준다 - 새로고침 후 자동 복원되는
                // 경우 이 함수가 유일한 진입점이라, 여기서 안 맞춰주면 내부 상태는 정상인데
                // 화면 선택창만 비어있는 상태가 된다.
                const advSelect = document.getElementById('top_advertiser_id');
                if (advSelect && res.project.advertiser_id && advSelect.value != res.project.advertiser_id) {
                    advSelect.value = res.project.advertiser_id;
                    this.onTopAdvertiserChange(res.project.advertiser_id, res.project.id);
                } else {
                    const projSelect = document.getElementById('top_project_id');
                    if (projSelect) projSelect.value = res.project.id;
                }

                if (res.latest_post) {
                    if (!confirm("이전에 작성 중이던 내용이 있습니다. 불러오시겠습니까?")) {
                        this.applyProjectData(res.project, res.advertiser, null);
                        return;
                    }
                    this.applyProjectData(res.project, res.advertiser, res.latest_post);
                    return;
                }

                this.applyProjectData(res.project, res.advertiser, null);
                // 서버에 저장된 초안이 없어도(자동저장이 세션 만료 등으로 실패했던 경우)
                // 이 브라우저에 남긴 로컬 백업이 있으면 그걸로 복구를 시도한다 - 실제로
                // 서버 저장이 조용히 실패해서 생성했던 제목 5개를 통째로 잃어버린 적이 있었다.
                this._tryRestoreFromLocalBackup(res.project.id);
            }
        });
    },

    // 저장된 프로젝트 id로 이 브라우저의 localStorage 백업을 읽어온다(없으면 null).
    _readLocalBackup: function(projectId) {
        try {
            const raw = localStorage.getItem('pb_backup_' + projectId);
            if (!raw) return null;
            const backup = JSON.parse(raw);
            return (backup && Array.isArray(backup.cards) && backup.cards.length > 0) ? backup : null;
        } catch (e) {
            return null; // 손상된 백업 - 무시
        }
    },

    _applyLocalBackup: function(backup) {
        if (backup.raw_material) document.getElementById('pb_raw_material').value = backup.raw_material;
        this.cards = backup.cards;
        this.toggleStep(2);
        this.saveState(); // 불러온 즉시 서버에도 반영해서 같은 손실이 반복되지 않게 한다.
    },

    // saveState()가 매번 남기는 localStorage 백업으로부터 복구한다. 서버 쪽에 아직
    // posts 행이 없을 때(자동저장이 한 번도 성공 못 한 경우)만 의미가 있다 - 프로젝트를
    // 선택했을 때 자동으로 확인한다.
    _tryRestoreFromLocalBackup: function(projectId) {
        const backup = this._readLocalBackup(projectId);
        if (!backup) return;

        if (!confirm("서버에 저장된 초안은 없지만, 이 브라우저에 임시로 남아있는 이전 작업 내용이 있습니다. 불러오시겠습니까?")) {
            return;
        }
        this._applyLocalBackup(backup);
        this._toast('로컬 백업에서 복구했습니다.', 'success');
    },

    // "임시저장 불러오기" 버튼 - 서버에 이미 내용이 있어도 언제든 이 브라우저의 임시저장을
    // 확인하고 불러올 수 있게 한다(자동 복구는 서버에 저장된 게 없을 때만 물어보므로,
    // 그 외의 경우를 위한 수동 진입점).
    loadLocalBackup: function() {
        if (!this._requireProject()) return;
        const backup = this._readLocalBackup(this.projectId);
        if (!backup) {
            alert('이 프로젝트에 저장된 임시저장 내용이 없습니다.');
            return;
        }
        const savedAt = backup.savedAt ? new Date(backup.savedAt).toLocaleString('ko-KR') : '알 수 없음';
        if (!confirm(`이 브라우저에 ${savedAt}에 임시저장된 내용이 있습니다. 지금 화면 내용을 덮어쓰고 불러오시겠습니까?`)) {
            return;
        }
        this._applyLocalBackup(backup);
        this._toast('임시저장 내용을 불러왔습니다.', 'success');
    },

    // 새로고침/재접속 후에도 마지막 작업 프로젝트를 자동으로 이어서 열 수 있도록 저장한다.
    _persistCurrentProject: function() {
        if (this.projectId) {
            localStorage.setItem('pb_load_project_id', this.projectId);
        } else {
            localStorage.removeItem('pb_load_project_id');
        }
    },

    // 카드 작업(전체 생성 등) 진입 전 프로젝트 연결 여부를 실제 상태 기준으로 확인한다.
    // top_project_id 선택창 값 하나만 보지 않고, 화면에 반영이 안 됐더라도 선택창에
    // 값이 남아있으면 그 값으로 스스로 복구한다.
    _requireProject: function() {
        const selectVal = document.getElementById('top_project_id') ? document.getElementById('top_project_id').value : '';
        if (!this.projectId && selectVal) {
            this.projectId = selectVal;
        }
        if (!this.projectId) {
            alert('먼저 새 프로젝트를 등록하거나 기존 프로젝트를 선택해 주세요.');
            this.toggleNewProjectForm();
            return false;
        }
        return true;
    },

    // 상단에서 선택된 광고주의 수정 화면을 새 탭으로 연다 - 작성 중인 초안을 잃지 않도록
    // 현재 탭을 이동시키지 않는다.
    editAdvertiser: function() {
        const advId = document.getElementById('top_advertiser_id').value;
        if (!advId) {
            alert('먼저 상단에서 광고주를 선택해주세요.');
            return;
        }
        window.open('./advertiser_form.php?id=' + advId, '_blank');
    },

    toggleNewProjectForm: function() {
        this.toggleStep(0);
        const advId = document.getElementById('top_advertiser_id').value;
        if (advId) {
            document.getElementById('inline_advertiser_id').value = advId;
            const advName = document.querySelector('#top_advertiser_id option[value="' + advId + '"]');
            if (advName) document.getElementById('inline_advertiser_search').value = advName.text;
            this.loadAdvertiserDefaults(advId);
        }
    },

    /* ==========================================
     * 1-1. 새 프로젝트 등록 - 선택 중심 UI 헬퍼
     * ========================================== */
    _collectChecked: function(selector) {
        return Array.from(document.querySelectorAll(selector + ':checked')).map(el => el.value);
    },

    _setChecked: function(selector, values) {
        const arr = Array.isArray(values) ? values : (typeof values === 'string' ? values.split(',').map(s => s.trim()).filter(Boolean) : []);
        document.querySelectorAll(selector).forEach(el => {
            el.checked = arr.includes(el.value);
        });
    },

    // 서버가 JSON이 아닌 응답(PHP 에러 페이지, 빈 응답 등)을 돌려줄 때 그냥 "요청 실패"로
    // 뭉개지 않고 실제 원인을 최대한 보여준다 - 화면 없이는 브라우저 콘솔/네트워크 탭을
    // 볼 수 없는 상황에서 원인을 추정하려면 이 정보가 꼭 필요하다.
    _postForm: function(url, payload) {
        return fetch(url, { method: 'POST', body: payload })
            .then(res => res.text().then(text => {
                let json;
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    const preview = text.replace(/\s+/g, ' ').trim().slice(0, 300);
                    throw new Error('서버가 올바른 응답을 반환하지 않았습니다(HTTP ' + res.status + '). ' + (preview || '(빈 응답)'));
                }
                return json;
            }));
    },

    toggleOther: function(wrapId, checkboxEl) {
        const wrap = document.getElementById(wrapId);
        if (wrap) wrap.classList.toggle('active', checkboxEl.checked);
    },

    onContentTypeCheck: function(checkboxEl) {
        const label = checkboxEl.closest('label');
        const radio = label ? label.querySelector('.pb-radio-content-type-primary') : null;
        if (!checkboxEl.checked) {
            if (radio) radio.checked = false;
            const anyPrimary = document.querySelector('.pb-radio-content-type-primary:checked');
            if (!anyPrimary) {
                const nextChecked = document.querySelector('.pb-chk-content-type:checked');
                if (nextChecked) {
                    const nextRadio = nextChecked.closest('label').querySelector('.pb-radio-content-type-primary');
                    if (nextRadio) nextRadio.checked = true;
                }
            }
            return;
        }
        const anyPrimary = document.querySelector('.pb-radio-content-type-primary:checked');
        if (!anyPrimary && radio) radio.checked = true;
    },

    autoGenProjectName: function() {
        const nameField = document.getElementById('inline_project_name');
        if (!nameField || nameField.dataset.manual === 'true') return;
        const advName = document.getElementById('inline_advertiser_search').value.trim();
        const topic = document.getElementById('inline_topic').value.trim();
        if (!advName && !topic) { nameField.value = ''; return; }
        const today = new Date();
        const dateStr = today.getFullYear() + String(today.getMonth() + 1).padStart(2, '0') + String(today.getDate()).padStart(2, '0');
        nameField.value = [advName, topic.replace(/\s+/g, ''), dateStr].filter(Boolean).join('_');
    },

    onAdvertiserSearchInput: function(inputEl) {
        const val = inputEl.value.trim();
        let matchedId = '';
        document.querySelectorAll('#advertiser_datalist option').forEach(o => {
            if (o.value === val) matchedId = o.getAttribute('data-id');
        });
        document.getElementById('inline_advertiser_id').value = matchedId;
        this.autoGenProjectName();

        const panel = document.getElementById('pb_adv_industry_panel');
        if (matchedId) {
            this.loadAdvertiserDefaults(matchedId);
        } else if (panel) {
            panel.style.display = 'none';
        }
    },

    loadAdvertiserDefaults: function(advertiserId) {
        const payload = new URLSearchParams();
        payload.append('action', 'load_advertiser_defaults');
        payload.append('advertiser_id', advertiserId);

        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (!res.success) return;

            const panel = document.getElementById('pb_adv_industry_panel');
            if (panel) {
                panel.style.display = 'block';
                this._setChecked('.pb-chk-adv-industry', res.advertiser.industry);
                document.getElementById('pb_adv_industry_detail').value = res.advertiser.industry_detail || '';
            }

            if (res.last_project) {
                const lp = res.last_project;
                this._setChecked('.pb-chk-purpose', lp.purpose_tags);
                const secondaryTypes = (lp.content_type ? lp.content_type + ',' : '') + (lp.content_type_secondary || '');
                this._setChecked('.pb-chk-content-type', secondaryTypes);
                if (lp.content_type) {
                    document.querySelectorAll('.pb-radio-content-type-primary').forEach(r => {
                        r.checked = (r.value === lp.content_type);
                    });
                }
                this._setChecked('.pb-chk-reader-type', lp.target_reader_type);
                this._setChecked('.pb-chk-age-group', lp.target_age_group);
                this._setChecked('.pb-chk-customer-stage', lp.target_customer_stage);
                this._setChecked('.pb-chk-region', lp.target_region);
            }
        });
    },

    toggleNewAdvertiserForm: function() {
        const form = document.getElementById('pb_new_advertiser_form');
        if (form) form.classList.toggle('active');
    },

    saveInlineAdvertiser: function() {
        const name = document.getElementById('new_adv_name').value.trim();
        if (!name) { alert('상호명을 입력해 주세요.'); return; }

        const industry = this._collectChecked('.pb-chk-new-adv-industry').join(',');
        const payload = new URLSearchParams();
        payload.append('action', 'save_advertiser');
        payload.append('name', name);
        payload.append('ceo_name', document.getElementById('new_adv_ceo_name').value);
        payload.append('industry', industry);
        payload.append('industry_detail', document.getElementById('new_adv_industry_detail').value);
        payload.append('domain', document.getElementById('new_adv_domain').value);
        payload.append('phone', document.getElementById('new_adv_phone').value);
        payload.append('address', document.getElementById('new_adv_address').value);
        payload.append('service_region', document.getElementById('new_adv_service_region').value);
        payload.append('intro_text', document.getElementById('new_adv_intro_text').value);
        payload.append('core_service', document.getElementById('new_adv_core_service').value);
        payload.append('memo', document.getElementById('new_adv_memo').value);
        payload.append('sub_phone', document.getElementById('new_adv_sub_phone').value);
        payload.append('email', document.getElementById('new_adv_email').value);

        this._postForm('ajax.builder.php', payload)
        .then(res => {
            if (!res.success) { alert('광고주 등록 실패: ' + res.error); return; }
            this._toast('광고주가 등록되었습니다. 현재 프로젝트에 자동 연결됩니다.', 'success');

            const dl = document.getElementById('advertiser_datalist');
            const opt = document.createElement('option');
            opt.value = res.advertiser.name;
            opt.setAttribute('data-id', res.advertiser_id);
            dl.appendChild(opt);

            const topSelect = document.getElementById('top_advertiser_id');
            const topOpt = document.createElement('option');
            topOpt.value = res.advertiser_id;
            topOpt.text = res.advertiser.name;
            topSelect.appendChild(topOpt);

            document.getElementById('inline_advertiser_search').value = res.advertiser.name;
            document.getElementById('inline_advertiser_id').value = res.advertiser_id;
            document.getElementById('pb_new_advertiser_form').classList.remove('active');
            this.autoGenProjectName();
            this.loadAdvertiserDefaults(res.advertiser_id);
        })
        .catch(err => alert('광고주 등록 요청에 실패했습니다.\n' + err.message));
    },

    saveAdvertiserIndustry: function() {
        const advId = document.getElementById('inline_advertiser_id').value;
        if (!advId) { alert('먼저 광고주를 선택해 주세요.'); return; }

        const industry = this._collectChecked('.pb-chk-adv-industry').join(',');
        const payload = new URLSearchParams();
        payload.append('action', 'save_advertiser');
        payload.append('id', advId);
        payload.append('industry', industry);
        payload.append('industry_detail', document.getElementById('pb_adv_industry_detail').value);

        this._postForm('ajax.builder.php', payload)
        .then(res => {
            if (res.success) this._toast('업종 정보가 저장되었습니다.', 'success');
            else alert('저장 실패: ' + res.error);
        })
        .catch(err => alert('업종 저장 요청에 실패했습니다.\n' + err.message));
    },

    saveInlineProject: function() {
        const advertiserId = document.getElementById('inline_advertiser_id').value;
        const topic = document.getElementById('inline_topic').value.trim();
        if (!advertiserId || !topic) {
            alert('광고주와 핵심 주제는 필수 항목입니다.');
            return;
        }

        const contentTypePrimary = document.querySelector('.pb-radio-content-type-primary:checked');
        const contentTypeAll = this._collectChecked('.pb-chk-content-type');
        const contentTypeOtherChk = document.getElementById('pb_content_type_other_chk');
        if (contentTypeOtherChk && contentTypeOtherChk.checked) {
            const other = document.getElementById('pb_content_type_other').value.trim();
            if (other) contentTypeAll.push(other);
        }
        const primaryType = contentTypePrimary ? contentTypePrimary.value : (contentTypeAll[0] || '');
        const secondaryTypes = contentTypeAll.filter(t => t !== primaryType);

        const purposeTags = this._collectChecked('.pb-chk-purpose');
        const purposeOtherChk = document.getElementById('pb_purpose_other_chk');
        if (purposeOtherChk && purposeOtherChk.checked) {
            const other = document.getElementById('pb_purpose_other').value.trim();
            if (other) purposeTags.push(other);
        }

        const payload = new URLSearchParams();
        payload.append('action', 'save_project');
        payload.append('advertiser_id', advertiserId);
        payload.append('topic', topic);
        payload.append('project_name', document.getElementById('inline_project_name').value.trim());
        payload.append('content_type', primaryType);
        payload.append('content_type_secondary', secondaryTypes.join(','));
        payload.append('purpose_tags', purposeTags.join(','));
        payload.append('target_reader_type', this._collectChecked('.pb-chk-reader-type').join(','));
        payload.append('target_age_group', this._collectChecked('.pb-chk-age-group').join(','));
        payload.append('target_customer_stage', this._collectChecked('.pb-chk-customer-stage').join(','));
        payload.append('target_region', this._collectChecked('.pb-chk-region').join(','));
        payload.append('target_audience_detail', document.getElementById('inline_target_audience_detail').value.trim());
        payload.append('project_notes', document.getElementById('inline_project_notes').value.trim());

        this._postForm('ajax.builder.php', payload)
        .then(res => {
            if (res.success) {
                this.projectId = res.project_id;
                this._persistCurrentProject();
                this._toast('새 프로젝트가 등록되었습니다. 이제 글감을 입력해 주세요.', 'success');
                this.toggleStep(1);

                document.getElementById('top_advertiser_id').value = res.project.advertiser_id;
                this.onTopAdvertiserChange(res.project.advertiser_id);
                setTimeout(() => {
                    document.getElementById('top_project_id').value = res.project_id;
                }, 500);

                this.applyProjectData(res.project, null, null);
            } else {
                alert('등록 실패: ' + res.error);
            }
        })
        .catch(err => alert('프로젝트 등록 요청에 실패했습니다.\n' + err.message));
    },

    applyProjectData: function(project, advertiser, latestPost) {
        if(latestPost) {
            this.postId = latestPost.id;
        } else {
            this.postId = 0;
        }
        document.getElementById('pb-status-display').innerText = '진행 중';

        const providerSelect = document.getElementById('pb_ai_provider_id');
        if (providerSelect) providerSelect.value = project.ai_provider_id || '';
        this._renderAiToggle(project.ai_disabled === 'Y');

        // "업체 정보 삽입" 체크박스가 실제 값 유무로 기본 체크 여부를 정하므로 광고주
        // 정보를 저장해둔다 - 값이 있는 항목만 기본 체크, 없는 항목은 비활성으로 둔다.
        this.advertiserInfo = advertiser || {};
        this._initContactState();
        if (this.currentStep === 1) this.renderRightPanel();

        // CTA 폼 데이터 적용
        const ctaEnabledEl = document.getElementById('pb_cta_enabled');
        if (ctaEnabledEl) {
            ctaEnabledEl.checked = project.cta_enabled == 1;
            const ctaContentEl = document.getElementById('pb_cta_content');
            if (ctaContentEl) ctaContentEl.value = project.cta_content || '';
            
            const fields = (project.cta_company_fields || '').split(',').filter(Boolean);
            document.querySelectorAll('.pb-chk-cta-field').forEach(el => {
                el.checked = fields.includes(el.value);
            });
            this.toggleCtaEnabled();
        }

        // CTA 프로필 목록 로드 (4단계 드롭다운 채우기)
        this.loadCtaProfiles();

        this.loadState();
    },

    _renderAiToggle: function(disabled) {
        const hidden = document.getElementById('pb_ai_disabled');
        const onBtn = document.getElementById('pb_ai_toggle_on');
        const offBtn = document.getElementById('pb_ai_toggle_off');
        if (hidden) hidden.value = disabled ? 'Y' : 'N';
        if (onBtn) {
            onBtn.style.borderColor = disabled ? '#cbd5e1' : '#10b981';
            onBtn.style.background = disabled ? '#fff' : '#ecfdf5';
            onBtn.style.color = disabled ? '#334155' : '#047857';
        }
        if (offBtn) {
            offBtn.style.borderColor = disabled ? '#dc2626' : '#cbd5e1';
            offBtn.style.background = disabled ? '#fef2f2' : '#fff';
            offBtn.style.color = disabled ? '#b91c1c' : '#334155';
        }
    },

    setAiDisabled: function(disabled) {
        this._renderAiToggle(disabled);
        this.saveAiProviderPref();
    },

    toggleCtaEnabled: function() {
        const ctaEnabledEl = document.getElementById('pb_cta_enabled');
        const area = document.getElementById('pb_cta_editor_area');
        if (ctaEnabledEl && area) {
            area.style.display = ctaEnabledEl.checked ? 'block' : 'none';
        }
        // 우측 패널의 "CTA 상태" 요약이 체크박스를 바꿔도 다시 그려지지 않아서,
        // 체크는 활성인데 요약은 계속 "비활성"으로 남아있던 문제 - 여기서 갱신한다.
        if (this.currentStep === 4) this.renderRightPanel();
    },

    // ==========================================
    // CTA 프로필 관리 기능 (방안 B)
    // ==========================================
    ctaProfiles: [],
    currentCtaProfileId: 0,
    
    loadCtaProfiles: function() {
        // 광고주 ID: 반드시 "현재 열려 있는 프로젝트"의 광고주(advertiserInfo, loadProject에서
        // 채워짐)를 우선한다. #top_advertiser_id는 페이지 상단의 광고주 전환/필터용 셀렉트라
        // 지금 프로젝트의 광고주와 다를 수 있다 - 이 셀렉트가 DOM에 존재한다는 이유만으로
        // 그 값을 먼저 쓰면(과거 로직), 값이 비어있거나 다른 광고주를 가리킬 때 CTA 프로필이
        // 하나도 안 불러와져 "프로필 없음 / 필드 비활성"으로 보이는 문제가 있었다.
        const advId = (this.advertiserInfo && this.advertiserInfo.id)
            ? this.advertiserInfo.id
            : (document.getElementById('pb_advertiser_id') || document.getElementById('top_advertiser_id') || {}).value;
        if (!advId) {
            this.ctaProfiles = [];
            this._renderCtaProfileSelect();
            return;
        }

        const payload = new URLSearchParams();
        payload.append('action', 'load_cta_profiles');
        payload.append('advertiser_id', advId);

        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.ok) {
                this.ctaProfiles = res.profiles || [];
                this._renderCtaProfileSelect();

                // 이미 스냅샷 등으로 프로필이 선택된 경우 렌더링 건너뜀
                if (this.currentCtaProfileId > 0) {
                    const sel = document.getElementById('pb_cta_profile_select');
                    if (sel) sel.value = this.currentCtaProfileId;
                } else if (this.ctaProfiles.length > 0) {
                    let defaultProf = this.ctaProfiles.find(p => p.is_default == 1) || this.ctaProfiles[0];
                    this.currentCtaProfileId = defaultProf.id;
                    const sel = document.getElementById('pb_cta_profile_select');
                    if (sel) sel.value = defaultProf.id;
                    this._renderCtaForm(defaultProf);
                }
            } else {
                console.error('CTA 프로필 로드 실패:', res.error);
                this.ctaProfiles = [];
                this._renderCtaProfileSelect();
            }
        })
        .catch(err => {
            console.error('CTA 프로필 AJAX 오류:', err);
            this.ctaProfiles = [];
            this._renderCtaProfileSelect();
        });
    },

    _renderCtaProfileSelect: function() {
        const sel = document.getElementById('pb_cta_profile_select');
        if (!sel) return;
        
        let html = '';
        if (this.ctaProfiles.length === 0) {
            html = '<option value="">프로필 없음 - 새 프로필을 추가하세요</option>';
        } else {
            this.ctaProfiles.forEach(p => {
                const isDef = p.is_default == 1 ? ' (기본)' : '';
                const escapedName = typeof this._escapeHtml === 'function' ? this._escapeHtml(p.profile_name) : this._escapeAttr(p.profile_name);
                html += `<option value="${p.id}">${escapedName}${isDef}</option>`;
            });
        }
        sel.innerHTML = html;
    },
    
    onCtaProfileChange: function() {
        const sel = document.getElementById('pb_cta_profile_select');
        if (!sel) return;
        const id = parseInt(sel.value, 10);
        this.currentCtaProfileId = id;
        const prof = this.ctaProfiles.find(p => p.id === id);
        if (prof) {
            this._renderCtaForm(prof);
        }
    },
    
    _renderCtaForm: function(prof) {
        const stdFields = document.getElementById('pb_cta_standard_fields');
        const custFields = document.getElementById('pb_cta_custom_fields');
        
        // 체크박스=표시 여부만 결정, 입력창=항상 편집 가능(원래 설계 원칙). 체크
        // 해제된 항목도 값을 수정할 수 있어야 하므로 input을 disabled 처리하지 않는다
        // (한 번 disabled로 막았다가 "수정이 안 된다"는 문제가 생겨 되돌림). 대신
        // data-prompt-gate로 체크박스 id를 참조시켜, 지시문 수집 시 체크된 항목의
        // 값만 포함하도록 한다.
        const makeStdRow = (key, label, val) => `
            <div style="display:flex; align-items:center; gap:10px;">
                <label style="width:120px;"><input type="checkbox" id="cta_chk_${key}" class="pb-chk-cta-field" value="${key}" checked onchange="Builder._onCtaFieldCheck(this)"> ${label}</label>
                <input type="text" id="cta_val_${key}" class="frm_input" style="flex:1;" value="${this._escapeAttr(val)}" oninput="Builder._onCtaFieldInput('${key}')" data-prompt-key="cta_val_${key}" data-prompt-template="${label}: {value}" data-prompt-gate="cta_chk_${key}">
            </div>`;
            
        stdFields.innerHTML = 
            makeStdRow('business_name', '상호명/업체명', prof.business_name) +
            makeStdRow('primary_phone', '대표 전화', prof.primary_phone) +
            makeStdRow('secondary_phone', '보조 전화', prof.secondary_phone) +
            makeStdRow('address', '주소', prof.address) +
            makeStdRow('homepage', '홈페이지', prof.homepage) +
            makeStdRow('email', '이메일', prof.email) +
            makeStdRow('service_area', '영업지역', prof.service_area);
            
        let cHtml = '';
        if (prof.custom_fields && prof.custom_fields.length > 0) {
            prof.custom_fields.forEach((cf, idx) => {
                cHtml += this._makeCustomFieldRow(idx, cf.key, cf.value);
            });
        }
        custFields.innerHTML = cHtml;
        
        // 맺음말
        const ctaBody = document.getElementById('pb_cta_content');
        if (ctaBody) ctaBody.value = prof.cta_body || '';
        
        if (typeof PromptManager !== 'undefined') PromptManager.triggerUpdate();
    },
    
    _onCtaFieldCheck: function(chk) {
        // 입력창은 건드리지 않는다 - 체크박스는 "본문/지시문에 포함할지"만 결정하고,
        // 값 편집은 체크 여부와 무관하게 항상 가능해야 한다.
        if (typeof PromptManager !== 'undefined') PromptManager.triggerUpdate();
    },
    
    _onCtaFieldInput: function(key) {
        const chk = document.querySelector(`.pb-chk-cta-field[value="${key}"]`);
        if (chk && !chk.checked) {
            chk.checked = true;
            chk.dispatchEvent(new Event('change'));
        }
        if (typeof PromptManager !== 'undefined') PromptManager.triggerUpdate();
    },
    
    _makeCustomFieldRow: function(idx, keyName, value) {
        return `
            <div class="pb-custom-cta-row" data-idx="${idx}" style="display:flex; align-items:center; gap:10px;">
                <input type="text" class="frm_input cta-custom-key" style="width:120px;" placeholder="항목명" value="${this._escapeAttr(keyName)}">
                <input type="text" class="frm_input cta-custom-val" style="flex:1;" placeholder="내용" value="${this._escapeAttr(value)}">
                <button type="button" class="btn btn_01" onclick="this.parentElement.remove()">✕</button>
            </div>`;
    },
    
    addCustomCtaField: function() {
        const custFields = document.getElementById('pb_cta_custom_fields');
        const idx = Date.now();
        custFields.insertAdjacentHTML('beforeend', this._makeCustomFieldRow(idx, '', ''));
    },
    
    createNewCtaProfile: function() {
        const name = prompt("새 프로필의 이름을 입력하세요.");
        if (!name) return;
        
        const advId = document.getElementById('pb_advertiser_id');
        if (!advId || !advId.value) return;
        
        const newProf = {
            id: 0,
            advertiser_id: advId.value,
            profile_name: name,
            is_default: 0,
            custom_fields: []
        };
        this.ctaProfiles.push(newProf);
        this.currentCtaProfileId = 0;
        this._renderCtaProfileSelect();
        document.getElementById('pb_cta_profile_select').value = '0';
        this._renderCtaForm(newProf);
    },
    
    saveCtaProfile: function(isSaveAs) {
        const advId = document.getElementById('pb_advertiser_id');
        if (!advId || !advId.value) return;
        
        let profName = "";
        if (isSaveAs) {
            profName = prompt("저장할 프로필 이름을 입력하세요.");
            if (!profName) return;
        } else {
            const prof = this.ctaProfiles.find(p => p.id === this.currentCtaProfileId);
            profName = prof ? prof.profile_name : '새 프로필';
        }
        
        const getVal = (id) => {
            const el = document.getElementById(id);
            return el ? el.value : '';
        };
        
        const customFields = [];
        document.querySelectorAll('.pb-custom-cta-row').forEach(row => {
            const k = row.querySelector('.cta-custom-key').value;
            const v = row.querySelector('.cta-custom-val').value;
            if (k && v) customFields.push({key: k, value: v});
        });
        
        const payload = {
            id: isSaveAs ? 0 : this.currentCtaProfileId,
            advertiser_id: advId.value,
            profile_name: profName,
            business_name: getVal('cta_val_business_name'),
            primary_phone: getVal('cta_val_primary_phone'),
            secondary_phone: getVal('cta_val_secondary_phone'),
            address: getVal('cta_val_address'),
            homepage: getVal('cta_val_homepage'),
            email: getVal('cta_val_email'),
            service_area: getVal('cta_val_service_area'),
            cta_body: getVal('pb_cta_content'),
            custom_fields: customFields,
            is_default: (this.ctaProfiles.length === 0 || this.currentCtaProfileId === 0) ? 1 : 0
        };
        
        const formData = new URLSearchParams();
        formData.append('action', 'save_cta_profile');
        // payload 객체의 각 키를 profile[key] 형태로 전달
        Object.keys(payload).forEach(k => {
            if (k === 'custom_fields') {
                formData.append('profile[custom_fields]', JSON.stringify(payload[k]));
            } else {
                formData.append('profile[' + k + ']', payload[k]);
            }
        });

        fetch(PB_AJAX_URL, { method: 'POST', body: formData })
        .then(res => res.json())
        .then(res => {
            if (res.ok) {
                this._toast('저장되었습니다.', 'success');
                this.loadCtaProfiles(); // 목록 새로고침
            } else {
                alert('저장 실패: ' + (res.error || '알 수 없는 오류'));
            }
        })
        .catch(err => alert('CTA 프로필 저장 요청에 실패했습니다.'));
    },
    
    deleteCtaProfile: function() {
        if (!this.currentCtaProfileId) {
            alert('삭제할 프로필을 먼저 저장해주세요.');
            return;
        }
        if (!confirm('현재 프로필을 삭제하시겠습니까?')) return;
        
        const delPayload = new URLSearchParams();
        delPayload.append('action', 'delete_cta_profile');
        delPayload.append('id', this.currentCtaProfileId);

        fetch(PB_AJAX_URL, { method: 'POST', body: delPayload })
        .then(res => res.json())
        .then(res => {
            if (res.ok) {
                this._toast('프로필이 삭제되었습니다.', 'success');
                this.currentCtaProfileId = 0;
                this.loadCtaProfiles();
            } else {
                alert('삭제 실패: ' + (res.error || '알 수 없는 오류'));
            }
        })
        .catch(err => alert('CTA 프로필 삭제 요청에 실패했습니다.'));
    },
    
    _saveCtaSnapshot: function() {
        if (!this.postId || !document.getElementById('pb_cta_enabled')) return;
        
        const enabled = document.getElementById('pb_cta_enabled').checked;
        const snapshot = {
            enabled: enabled,
            profile_id: this.currentCtaProfileId,
            content: document.getElementById('pb_cta_content') ? document.getElementById('pb_cta_content').value : ''
        };
        
        // 체크된 항목들의 값만 스냅샷에 저장 (실제 렌더링 시 필요)
        const fields = {};
        document.querySelectorAll('.pb-chk-cta-field').forEach(chk => {
            if (chk.checked) {
                const input = document.getElementById('cta_val_' + chk.value);
                fields[chk.value] = input ? input.value : '';
            }
        });
        snapshot.fields = fields;
        
        const customFields = [];
        document.querySelectorAll('.pb-custom-cta-row').forEach(row => {
            const k = row.querySelector('.cta-custom-key').value;
            const v = row.querySelector('.cta-custom-val').value;
            if (k && v) customFields.push({key: k, value: v});
        });
        snapshot.custom_fields = customFields;
        
        const snapPayload = new URLSearchParams();
        snapPayload.append('action', 'save_cta_snapshot');
        snapPayload.append('post_id', this.postId);
        snapPayload.append('cta_profile_id', this.currentCtaProfileId);
        snapPayload.append('snapshot_json', JSON.stringify(snapshot));

        fetch(PB_AJAX_URL, { method: 'POST', body: snapPayload })
        .then(res => res.json())
        .catch(() => {}); // 자동저장 - 실패해도 영향 없음
    },
    // 1단계의 "AI 공급자 / AI 사용 안 함"은 프로젝트당 하나씩만 있으면 되므로
    // 별도 저장 버튼 없이 바꾸는 즉시 저장한다(토글 버튼/셀렉트에서 호출).
    // silent=true면 성공 토스트를 생략한다(_switchToChatGptProvider가 저장 직후 바로
    // 생성을 재시도할 때, 저장 완료를 먼저 기다려야 하므로 Promise를 그대로 반환한다).
    saveAiProviderPref: function(silent) {
        if (!this.projectId) return Promise.resolve();
        const providerSelect = document.getElementById('pb_ai_provider_id');
        const disabledHidden = document.getElementById('pb_ai_disabled');

        const payload = new URLSearchParams();
        payload.append('action', 'update_ai_pref');
        payload.append('project_id', this.projectId);
        payload.append('ai_provider_id', providerSelect ? providerSelect.value : '');
        payload.append('ai_disabled', (disabledHidden && disabledHidden.value === 'Y') ? 'Y' : 'N');

        return this._postForm('ajax.builder.php', payload)
        .then(res => {
            if (res.success) { if (!silent) this._toast('AI 설정이 저장되었습니다.', 'success'); }
            else alert('저장 실패: ' + res.error);
            return res;
        })
        .catch(err => { alert('AI 설정 저장 요청에 실패했습니다.\n' + err.message); throw err; });
    },

    // 선택된 공급자의 상태(data-status, post_builder.php가 서버에서 렌더링)를 보고
    // "전체 자동 작성 시작" 버튼을 켜고 끈다. 연결 오류·API 키 필요일 때만 막는다(테스트
    // 필요·사용 안 함은 그대로 시도 가능 - 서버 쪽 판단과 일치시키기 위함).
    updateProviderStatusUI: function() {
        const select = document.getElementById('pb_ai_provider_id');
        const btn = document.getElementById('pb_btn_generate_all');
        const msgBox = document.getElementById('pb_generate_blocked_msg');
        if (!select || !btn || !msgBox) return;

        const selected = select.options[select.selectedIndex];
        const status = selected ? selected.dataset.status : '';

        if (status === 'error' || status === 'no_key') {
            const label = status === 'error' ? '연결 오류' : 'API 키 필요';
            btn.disabled = true;
            msgBox.style.display = '';
            msgBox.innerHTML = '선택한 AI 공급자 상태: <strong>' + label + '</strong> - 실행할 수 없습니다. ' +
                '<a href="./settings.php?tab=ai" target="_blank" class="btn btn_02" style="margin-left:8px;">AI API 설정 관리로 이동</a>';
        } else {
            btn.disabled = false;
            msgBox.style.display = 'none';
            msgBox.innerHTML = '';
        }
    },

    /* ==========================================
     * 2. Step Navigation
     * ========================================== */
    toggleStep: function(step) {
        if (!this._canAdvanceFromStep(this.currentStep, step)) return;

        document.querySelectorAll('.pb-nav-item').forEach(el => el.classList.remove('active'));
        if (step > 0 && step <= 5) {
            const navItems = document.querySelectorAll('.pb-nav-item');
            if(navItems[step - 1]) navItems[step - 1].classList.add('active');
        }

        document.querySelectorAll('.pb-step-view').forEach(el => el.classList.remove('active'));
        
        // step 2 and 3 share the same canvas
        const targetViewId = (step === 2 || step === 3) ? 'step-23' : 'step-' + step;
        const stepEl = document.getElementById(targetViewId);
        if (stepEl) stepEl.classList.add('active');

        if (step === 2 || step === 3) {
            document.getElementById('pb_step23_title').innerText = step === 2 ? '🧩 2단계 · 구성·초안 검토' : '✂️ 3단계 · 편집·최적화';
            if (this.cards.length === 0) {
                document.getElementById('pb_card_canvas').innerHTML = '<div style="text-align:center; padding:50px; color:#94a3b8;">아직 생성된 초안이 없습니다.<br>1단계에서 [전체 자동 작성 시작]을 눌러주세요.</div>';
            } else {
                this.renderCards();
            }
            // step-23은 2/3단계가 같은 화면을 쓰므로, 하단 이전/다음 버튼이 가리키는
            // 단계도 지금이 2단계인지 3단계인지에 따라 매번 다시 맞춰준다.
            const prevBtn = document.getElementById('pb_step23_prev_btn');
            const nextBtn = document.getElementById('pb_step23_next_btn');
            if (prevBtn) prevBtn.onclick = () => this.toggleStep(step - 1);
            if (nextBtn) nextBtn.onclick = () => this.toggleStep(step + 1);
        }
        
        if (step === 5) {
            this.inspectAll();
        }

        this.currentStep = step;
        this.activeCardId = null;
        this.renderRightPanel();
        if (typeof PromptManager !== 'undefined') PromptManager.triggerUpdate();
    },

    _scrollToActiveStepTab: function() {
        const activeNavItem = document.querySelector('.pb-nav-item.active');
        if (activeNavItem) {
            activeNavItem.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest',
                inline: 'center'
            });
        }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },

    _renderStepNavigation: function() {
        const totalSteps = 5;
        const currentStep = this.currentStep;
        const prevDisabled = currentStep <= 1 ? 'disabled' : '';
        const isLastStep = currentStep >= totalSteps;

        // 마지막 단계(4. 검수·완성)에서는 더 이동할 "다음 단계"가 없으므로, 비활성화된
        // 버튼을 그대로 두지 않고 그 자리에 실제로 눌러야 하는 "최종 조립 및 완성본
        // 보기"(finishPost) 버튼을 보여준다. 헤더의 동일 버튼과 별개로, 컨트롤 패널
        // 하단에서도 바로 누를 수 있게 한다.
        const nextButtonHtml = isLastStep ? `
            <button
                type="button"
                id="pb_btn_next_step"
                class="pb-step-nav-button btn_submit btn"
                style="padding:8px 16px; font-size:0.9rem; background:#4f46e5; border:1px solid #4338ca; border-radius:6px; color:#fff; cursor:pointer;"
                aria-label="최종 조립 및 완성본 보기"
                onclick="Builder.finishPost()"
            >
                ✅ 최종 조립 및 완성본 보기
            </button>
        ` : `
            <button
                type="button"
                id="pb_btn_next_step"
                class="pb-step-nav-button pb-step-nav-next btn btn_02"
                aria-label="다음 작성 단계로 이동"
                onclick="Builder.nextStep()"
                style="padding:8px 16px; font-size:0.9rem; background:#334155; border:1px solid #1e293b; border-radius:6px; color:#fff; cursor:pointer;"
            >
                다음 단계 →
            </button>
        `;

        return `
        <div class="pb-step-navigation" aria-label="작성 단계 이동" style="display:flex; justify-content:space-between; align-items:center; margin-top:20px; padding-top:15px; border-top:1px solid #e2e8f0; position:sticky; bottom:0; background:#fff; padding-bottom:10px;">
            <button
                type="button"
                id="pb_btn_prev_step"
                class="pb-step-nav-button pb-step-nav-prev btn"
                aria-label="이전 작성 단계로 이동"
                onclick="Builder.prevStep()"
                style="padding:8px 16px; font-size:0.9rem; background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; color:#475569; cursor:pointer;"
                ${prevDisabled}
            >
                ← 이전 단계
            </button>

            <span class="pb-step-navigation-status" aria-live="polite" style="font-size:0.85rem; font-weight:bold; color:#64748b;">
                ${currentStep} / ${totalSteps}
            </span>

            ${nextButtonHtml}
        </div>
        `;
    },

    prevStep: function() {
        if (this.currentStep > 1) {
            this.saveState();
            this.toggleStep(this.currentStep - 1);
            this._scrollToActiveStepTab();
        }
    },

    nextStep: function() {
        if (this.currentStep < 5) {
            this.saveState();
            this.toggleStep(this.currentStep + 1);
            this._scrollToActiveStepTab();
        }
    },

    /* ==========================================
     * 3. State Management (Save/Load)
     * ========================================== */
    loadState: function() {
        if (!this.projectId) return;
        const payload = new URLSearchParams();
        payload.append('action', 'load_state');
        payload.append('project_id', this.projectId);
        
        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            if (res.ok && res.builder_state) {
                const s = res.builder_state;
                if(s.raw_material) document.getElementById('pb_raw_material').value = s.raw_material;
                if(s.cards && Array.isArray(s.cards) && s.cards.length > 0) {
                    this.cards = s.cards;
                    // 페이지를 막 열었을 때는 currentStep이 아직 1이라 이 조건이 항상 거짓이었고,
                    // 카드는 메모리에 들어왔지만 화면엔 안 보인 채로 남아 "불러오시겠습니까?"에
                    // 확인을 눌러도 빈 화면처럼 보였다 - 복원된 카드가 있으면 검토 단계로 바로 넘긴다.
                    this.toggleStep(2);
                    // 마지막으로 선택했던 카드/집중 편집 모드를 복원한다(화면 상태만, 본문 데이터 아님).
                    this._restoreUiState();
                }
                if (Array.isArray(s.hashtag_enabled)) this.tagState.hashtags.enabled = s.hashtag_enabled;
                if (Array.isArray(s.selected_rule_ids)) {
                    this.pendingRuleIds = s.selected_rule_ids;
                    this._setChecked('.pb-chk-generation-rule', s.selected_rule_ids);
                }
                if (s.structure_template_id) {
                    this.pendingStructureTemplate = s.structure_template_id;
                    this.structureTemplate = s.structure_template_id;
                    this.selectedStructures = s.structure_template_id.toString().split(',').filter(Boolean);
                    if (document.getElementById('pb_selected_structure_ids')) {
                        document.getElementById('pb_selected_structure_ids').value = s.structure_template_id;
                    }
                    this.updateSelectedStructureSummary();
                }
                if (s.extra_instruction && document.getElementById('pb_extra_instruction')) {
                    document.getElementById('pb_extra_instruction').value = s.extra_instruction;
                }
                // 과거 저장 데이터에는 없을 수 있으므로 존재할 때만 복원한다.
                if (s.length_control_state && typeof s.length_control_state === 'object') {
                    this.lengthControlState = s.length_control_state;
                }
                if (s.char_per_line) {
                    this.charPerLine = parseInt(s.char_per_line, 10);
                    this._syncCharPerLinePromptValue();
                }
            }
            if (res.ok && Array.isArray(res.keywords) && res.keywords.length > 0) {
                this.tagState.keywords.values = res.keywords;
                this.tagState.keywords.count = Math.max(this.tagState.keywords.count, res.keywords.length);
                this.renderTagPanel('keywords');
            }
            if (res.ok && Array.isArray(res.hashtags) && res.hashtags.length > 0) {
                this.tagState.hashtags.values = res.hashtags;
                this.tagState.hashtags.count = Math.max(this.tagState.hashtags.count, res.hashtags.length);
                this.renderTagPanel('hashtags');
            }
            if (res.ok && res.cta_snapshot) {
                const snap = res.cta_snapshot;
                const ctaEnabledEl = document.getElementById('pb_cta_enabled');
                if (ctaEnabledEl) ctaEnabledEl.checked = snap.enabled;
                this.currentCtaProfileId = snap.profile_id;
                
                const sel = document.getElementById('pb_cta_profile_select');
                if (sel) sel.value = snap.profile_id;
                
                const prof = {
                    business_name: snap.fields.business_name || '',
                    primary_phone: snap.fields.primary_phone || '',
                    secondary_phone: snap.fields.secondary_phone || '',
                    address: snap.fields.address || '',
                    homepage: snap.fields.homepage || '',
                    email: snap.fields.email || '',
                    service_area: snap.fields.service_area || '',
                    custom_fields: snap.custom_fields || [],
                    cta_body: snap.content || ''
                };
                this._renderCtaForm(prof);
                
                document.querySelectorAll('.pb-chk-cta-field').forEach(chk => {
                    if (snap.fields[chk.value] === undefined) {
                        chk.checked = false;
                        const input = document.getElementById('cta_val_' + chk.value);
                        if (input) input.disabled = true;
                    }
                });
                
                this.toggleCtaEnabled();
            }
        })
        .catch(err => {
            this._toast(err.message === 'LOGIN_REQUIRED' ? '로그인이 만료되어 이전 내용을 불러오지 못했습니다. 새로고침 후 다시 로그인해주세요.' : '이전 내용을 불러오는 중 오류가 발생했습니다.', 'error');
        });
    },

    gatherData: function() {
        return {
            raw_material: document.getElementById('pb_raw_material') ? document.getElementById('pb_raw_material').value : '',
            cards: this.cards,
            // posts.hashtags 컬럼은 값 목록만 담으므로, "미적용" 체크 마스크는 새 컬럼을
            // 만들지 않고 이 builder_state JSON에 얹어서 같이 저장/복원한다.
            hashtag_enabled: this.tagState.hashtags.enabled,
            // AI 글 생성 조건 체크 상태 - blog_generation_rules는 프리셋 라이브러리일 뿐이고,
            // "이 프로젝트에서 어떤 걸 체크했는지"는 별도 테이블 없이 여기 실어서 보존한다.
            selected_rule_ids: this._collectChecked('.pb-chk-generation-rule'),
            structure_template_id: this.structureTemplate,
            extra_instruction: document.getElementById('pb_extra_instruction') ? document.getElementById('pb_extra_instruction').value : '',
            // 카드별 길이 조정(%)/글 전개 구조 선택값 - 카드 본문 상태(draft/selected/
            // hidden/deleted)와는 무관한 UI 편의 값이다. 과거 저장된 데이터에는 이 필드가
            // 없을 수 있으므로 loadState()에서는 없으면 빈 값으로 안전하게 처리한다.
            length_control_state: this.lengthControlState,
            char_per_line: this.charPerLine
        };
    },
    
    // 전체 상태 저장 (임시 JSON fallback)
    ensurePostSaved: function() {
        return new Promise((resolve, reject) => {
            if (!this.projectId) {
                return reject(new Error('프로젝트를 먼저 생성해주세요.'));
            }
            
            const stateData = this.gatherData();
            const payload = {
                action: 'save_state',
                project_id: Number(this.projectId || 0),
                post_id: Number(this.postId || 0),
                builder_state: JSON.stringify(stateData)
            };
            
            const ctaEnabledEl = document.getElementById('pb_cta_enabled');
            if (ctaEnabledEl) {
                payload.cta_enabled = ctaEnabledEl.checked ? '1' : '0';
                payload.cta_content = document.getElementById('pb_cta_content') ? document.getElementById('pb_cta_content').value.trim() : '';
                const checkedFields = Array.from(document.querySelectorAll('.pb-chk-cta-field:checked')).map(el => el.value);
                payload.cta_company_fields = checkedFields.join(',');
            }

            fetch(PB_AJAX_URL, { 
                method: 'POST', 
                headers: { 
                    'Content-Type': 'application/json; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest' 
                },
                credentials: 'include',
                body: JSON.stringify(payload) 
            })
            .then(res => this._parseAjaxJson(res))
            .then(res => {
                if (res.ok && res.post_id > 0) {
                    this.postId = res.post_id;
                    if (typeof this._saveCtaSnapshot === 'function') {
                        this._saveCtaSnapshot();
                    }
                    resolve(res.post_id);
                } else {
                    reject(new Error(res.error || '자동저장에 실패했습니다.'));
                }
            })
            .catch(err => {
                // 다른 저장 함수(saveState 등)와 달리 여기는 LOGIN_REQUIRED를 구분하지 않고
                // 항상 "네트워크 오류"로 뭉뚱그려서, 실제로는 세션 만료로 login.php로
                // 리다이렉트된 상황(발행 직전 ensurePostSaved 호출 시 실제로 재현됨)을
                // "네트워크 문제"로 오인하게 만들었다 - 원인 파악이 안 돼 반복 재현되던 문제.
                if (err && err.message === 'LOGIN_REQUIRED') {
                    reject(new Error('로그인이 만료되었습니다. 새로고침 후 다시 로그인하고 시도해 주세요.'));
                } else {
                    reject(new Error('자동저장 중 네트워크 오류가 발생했습니다.'));
                }
            });
        });
    },

    // 전체 상태 저장 (임시 JSON fallback)
    saveState: function(_retryCount) {
        if (!this.projectId) {
            this._toast('프로젝트를 먼저 생성해주세요.', 'error');
            return;
        }
        _retryCount = _retryCount || 0;

        const stateData = this.gatherData();

        // 서버 저장이 실패해도(세션 만료 등) 최소한 이 브라우저에는 남도록 매번 로컬에도
        // 백업한다 - 실제로 서버 저장 실패로 생성한 제목 5개를 통째로 잃어버린 적이 있어서,
        // 최후의 보루로 둔다(onTopProjectChange가 서버에 저장된 게 없을 때 이걸로 복구를 물어본다).
        try {
            localStorage.setItem('pb_backup_' + this.projectId, JSON.stringify(Object.assign({}, stateData, { savedAt: Date.now() })));
        } catch (e) { /* storage 꽉 찼거나 비활성화된 경우 - best-effort라 무시 */ }

        if (_retryCount === 0) this.scheduleAutoTagGenerate();

        const payload = {
            action: 'save_state',
            project_id: Number(this.projectId || 0),
            post_id: Number(this.postId || 0),
            builder_state: JSON.stringify(stateData)
        };

        const ctaEnabledEl = document.getElementById('pb_cta_enabled');
        if (ctaEnabledEl) {
            payload.cta_enabled = ctaEnabledEl.checked ? '1' : '0';
            payload.cta_content = document.getElementById('pb_cta_content') ? document.getElementById('pb_cta_content').value.trim() : '';
            const checkedFields = Array.from(document.querySelectorAll('.pb-chk-cta-field:checked')).map(el => el.value);
            payload.cta_company_fields = checkedFields.join(',');
        }

        fetch(PB_AJAX_URL, { 
            method: 'POST', 
            headers: { 
                'Content-Type': 'application/json; charset=UTF-8',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest' 
            },
            credentials: 'include',
            body: JSON.stringify(payload) 
        })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            if (res.ok) {
                if (res.post_id > 0) {
                    this.postId = res.post_id;
                    // CTA 스냅샷 백그라운드 저장 (방안 B)
                    this._saveCtaSnapshot();
                }
                const now = new Date();
                const timeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
                document.getElementById('pb-saved-time').innerText = timeStr;
                // this._toast('저장 완료', 'success'); // Too noisy during partial saves
            } else if (_retryCount < 1) {
                setTimeout(() => this.saveState(_retryCount + 1), 2000);
            } else {
                // 실패를 조용히 삼키면 화면엔 카드가 있는데 DB엔 없는 상태로 남는다(실제로
                // 이렇게 초안 하나를 잃어버린 적이 있다) - 반드시 알려야 한다. 로컬 백업은
                // 남아있으니 완전히 사라지는 건 아니라는 것도 같이 안내한다.
                this._toast('저장 실패: ' + (res.error || '알 수 없는 오류') + ' (이 브라우저에 임시 백업은 남아있습니다)', 'error');
            }
        })
        .catch(err => {
            if (_retryCount < 1) {
                setTimeout(() => this.saveState(_retryCount + 1), 2000);
                return;
            }
            if (err.message === 'LOGIN_REQUIRED') {
                this._toast('로그인이 만료되어 자동저장이 실패했습니다(이 브라우저에 임시 백업은 남아있습니다). 새로고침 후 다시 로그인하고 다시 시도해주세요.', 'error');
            } else {
                this._toast('자동저장 중 네트워크 오류가 발생했습니다(이 브라우저에 임시 백업은 남아있습니다).', 'error');
            }
        });
    },

    // 부분 저장 로직 (DB 정규화 전환용)
    saveCardPartial: function(card) {
        if (!this.projectId || !this.postId) return;
        
        const payload = new URLSearchParams();
        payload.append('action', 'save_card');
        payload.append('project_id', this.projectId);
        payload.append('post_id', this.postId);
        payload.append('card_id', card.id);
        payload.append('card_type', card.type);
        payload.append('content', card.content);
        payload.append('status', card.state);
        payload.append('locked', card.locked ? 'true' : 'false');
        
        const idx = this.cards.findIndex(c => c.id === card.id);
        payload.append('sort_order', idx >= 0 ? idx : 0);

        fetch(PB_AJAX_URL, { method: 'POST', body: payload });
    },

    /* ==========================================
     * 4. AI Generation (Generate All Cards)
     * ========================================== */
    generateAllCards: function(acceptTemplateFallback) {
        if (!this._requireProject()) return;
        const rawMat = document.getElementById('pb_raw_material').value.trim();
        if(!rawMat) {
            alert('글감을 먼저 입력해주세요.');
            return;
        }

        // 클릭 시점에도 한 번 더 확인한다(버튼 disabled를 우회해서 호출된 경우 대비 -
        // updateProviderStatusUI()가 이미 disabled 처리했겠지만 이중 방어).
        this.updateProviderStatusUI();
        const genBtn = document.getElementById('pb_btn_generate_all');
        if (genBtn && genBtn.disabled) {
            return;
        }
        // 헤더의 "✨ 전체 AI 생성"과 2단계 패널의 "✨ 전체 자동 작성 시작"이 같은 함수를
        // 부르는 별개의 두 버튼이다 - 이 진행중 플래그 없이는 genBtn(2단계 버튼)만
        // disabled 처리되어서, 헤더 버튼을 또 누르면 첫 요청이 아직 끝나기 전에 같은
        // project_id로 두 번째 generate_all_cards 요청이 동시에 나갔다. 세션 파일 락 때문에
        // 두 번째 요청이 첫 요청 뒤에서 대기하다 OpenAI 호출까지 이어지면 호스팅의 게이트웨이
        // 타임아웃을 넘겨 실제로 네트워크 단에서 실패하고, 그 결과 성공 토스트와 "네트워크
        // 오류" 토스트가 동시에 뜨는 것처럼 보였다.
        if (this._generatingAll) {
            this._toast('이미 생성 중입니다. 잠시만 기다려주세요.', 'error');
            return;
        }

        // Locked 카드는 유지 방침 (준비 중 공급자 확인 후 재시도하는 경우는 이미 한 번
        // 물어봤으므로 같은 클릭 흐름 안에서 다시 묻지 않는다)
        const lockedCards = this.cards.filter(c => c.locked && c.state !== 'deleted');
        if (!acceptTemplateFallback && lockedCards.length > 0) {
            if(!confirm(`잠긴 카드 ${lockedCards.length}개는 유지하고 나머지 카드만 다시 생성합니다. 계속하시겠습니까?`)) {
                return;
            }
        }

        const btn = genBtn || document.querySelector('button[onclick="Builder.generateAllCards()"]');
        const headerBtn = document.getElementById('pb_btn_generate_all_header');
        const origText = btn.innerText;
        btn.innerText = 'AI 전체 생성 중... (10~20초 소요)';
        btn.disabled = true;
        if (headerBtn) headerBtn.disabled = true;
        this._generatingAll = true;

        this.toggleStep(2);
        document.getElementById('pb_card_canvas').innerHTML = `
            <div class="skeleton-card">
                <div class="skeleton-line w-1-3"></div>
                <div class="skeleton-line w-full"></div>
            </div>
            <div class="skeleton-card">
                <div class="skeleton-line w-1-3"></div>
                <div class="skeleton-line w-full" style="height: 60px;"></div>
                <div class="skeleton-line w-2-3"></div>
            </div>
            <div style="text-align:center; color:#64748b; margin-top:20px;">전체 포스팅 구조를 설계하고 초안을 작성 중입니다...</div>
        `;

        const payload = new URLSearchParams();
        payload.append('action', 'generate_all_cards');
        payload.append('project_id', this.projectId);
        payload.append('raw_material', rawMat);
        payload.append('locked_cards', JSON.stringify(lockedCards));
        payload.append('contact_fields', JSON.stringify(this._contactFields.filter(f => this.contactState.checked[f.key]).map(f => f.key)));
        payload.append('target_length', this.targetLength);
        payload.append('structure_template', this.structureTemplate);
        payload.append('selected_rule_ids', JSON.stringify(this._collectChecked('.pb-chk-generation-rule')));
        payload.append('extra_instruction', document.getElementById('pb_extra_instruction') ? document.getElementById('pb_extra_instruction').value : '');
        payload.append('char_per_line', this.charPerLine || 60);
        if (acceptTemplateFallback) payload.append('accept_template_fallback', '1');

        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            btn.disabled = false;
            btn.innerText = origText;
            if (headerBtn) headerBtn.disabled = false;
            this._generatingAll = false;

            if (res.needs_provider_confirm) {
                document.getElementById('pb_card_canvas').innerHTML = '<div style="text-align:center; padding:50px; color:#94a3b8;">공급자를 확인해 주세요.</div>';
                this._confirmProviderFallback(res.provider_label || '선택한 공급자', choice => {
                    if (choice === 'template') {
                        this.generateAllCards(true);
                    } else if (choice === 'switch') {
                        this._switchToChatGptProvider().then(switched => {
                            if (switched) this.generateAllCards(false);
                        });
                    }
                });
                return;
            }

            if (res.ok && Array.isArray(res.cards) && res.cards.length > 0) {
                // 프론트엔드 병합 로직
                // 서버에서 locked 카드가 반영된 전체 구조를 주거나 프론트에서 합쳐야 함.
                // 편의상 프론트에서 기존 카드를 리셋하고 서버에서 온 새 카드들을 쓰되,
                // locked 카드는 덮어쓰지 않음. (현재는 서버 프롬프트에 의해 재생성되거나 보호됨)
                // 완벽한 병합은 서버 응답과 매칭해야 하나, MVP 수준에선 통째로 할당합니다.

                let newCards = res.cards;
                
                // 만약 서버가 잠긴 카드 아이디를 그대로 반환했다면 유지, 아니면 프론트단에서 강제 덮어쓰기 방어
                lockedCards.forEach(lc => {
                    const match = newCards.find(nc => nc.id === lc.id || (nc.type === lc.type && nc.title === lc.title));
                    if (match) {
                        match.content = lc.content;
                        match.locked = true;
                    } else {
                        // 서버가 카드를 없애버렸다면 다시 밀어넣음
                        newCards.push(lc);
                    }
                });

                this.cards = newCards;
                this.saveState();
                this._toast('초안 생성이 완료되었습니다.', 'success');
                this.renderCards();
            } else {
                this._toast('생성 실패: ' + res.error, 'error');
                document.getElementById('pb_card_canvas').innerHTML = '<div style="color:red; padding:20px;">생성 실패: ' + res.error + '</div>';
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = origText;
            if (headerBtn) headerBtn.disabled = false;
            this._generatingAll = false;
            
            // 스피너 잔재를 없애고 원래 카드를 다시 그리거나 에러를 표시합니다.
            if (this.cards && this.cards.length > 0) {
                this.renderCards();
            } else {
                document.getElementById('pb_card_canvas').innerHTML = `<div style="color:red; padding:20px; text-align:center;">생성 중 오류가 발생했습니다. (${err.message})</div>`;
            }

            if (err.message === 'LOGIN_REQUIRED') {
                this._toast('로그인이 만료되었습니다. 새로고침 후 다시 로그인해주세요.', 'error');
            } else {
                this._toast('생성 실패: 네트워크 오류 또는 서버 에러가 발생했습니다.', 'error');
            }
        });
    },

    /* ==========================================
     * 5. Card Rendering & Interactions
     * ========================================== */
    renderCards: function() {
        const canvas = document.getElementById('pb_card_canvas');
        canvas.innerHTML = '';

        let html = '';

        // 제목 후보들은 본문 섹션과 같은 큰 카드로 하나씩 그리면(5개면 화면이 세로로
        // 아주 길어짐) 한눈에 비교하기 어렵다는 피드백이 있었다 - 압축된 한 줄짜리 행으로
        // 묶어서 "제목" 박스 하나 안에 전부 모아 그린다.
        const titleCards = this.cards.filter(c => c.type === 'title' && c.state !== 'deleted');
        if (titleCards.length > 0) {
            html += this._renderTitleGroup(titleCards);
        }

        this.cards.forEach(card => {
            if (card.type === 'title') return; // 위 그룹에서 이미 그림
            if (card.state === 'deleted') return;

            const isHidden = card.state === 'hidden';
            const isActive = this.activeCardId === card.id;

            let badgeText = '';
            let btnUseHtml = '';

            if (card.state === 'selected' || card.state === 'primary') {
                badgeText = '사용함';
                btnUseHtml = `<button class="btn-use active" onclick="Builder.setCardState('${card.id}', 'draft', event)">사용 해제</button>`;
            } else {
                badgeText = '사용 안 함';
                btnUseHtml = `<button class="btn-use" onclick="Builder.setCardState('${card.id}', 'selected', event)">사용하기</button>`;
            }

            let cardTitleStr = card.type === 'intro' ? '도입부' :
                               card.type === 'section' ? (card.title || '본문 섹션') :
                               card.type === 'cta' ? '결론 및 CTA' : '기타';

            html += `
            <div class="pb-card ${isActive ? 'active' : ''} ${isHidden ? 'state-hidden' : ''}" id="card_${card.id}" onclick="Builder.selectCard('${card.id}')">
                <div class="pb-card-header">
                    <div class="pb-card-title">
                        <span>${cardTitleStr}</span>
                        <span class="card-badge">${badgeText}</span>
                        ${card.locked ? '<span style="font-size:0.8rem; cursor:pointer;" title="잠금 됨 (AI 전체 재생성 시 보호됨)">🔒</span>' : '<span style="font-size:0.8rem; cursor:pointer; opacity:0.3;" title="수정/잠금 해제 상태" onclick="Builder.toggleLock(\''+card.id+'\', event)">🔓</span>'}
                    </div>
                    <div class="pb-card-actions">
                        ${btnUseHtml}
                        <button onclick="Builder.toggleHidden('${card.id}', event)">${isHidden ? '표시' : '숨기기'}</button>
                        <button onclick="Builder.deleteCard('${card.id}', event)" style="color:#ef4444; border-color:#fee2e2; background:#fef2f2;">삭제</button>
                        <button onclick="Builder.toggleCollapse('${card.id}', event)" title="카드 접기/펼치기" style="padding:0 5px; font-size:1.1rem; border:none; background:none; cursor:pointer; color:#718096;">${card.collapsed ? '▼' : '▲'}</button>
                    </div>
                </div>
                <div class="pb-card-body" style="${card.collapsed ? 'display:none;' : ''}">
                    <div class="pb-card-content">
                        <textarea class="pb-card-textarea" id="textarea_${card.id}" onchange="Builder.updateCardContent('${card.id}', this.value)" ${isHidden ? 'disabled' : ''}>${card.content}</textarea>
                    </div>
                    <div class="pb-card-attachments" onclick="event.stopPropagation()">
                        <div id="attach_list_${card.id}">${this._renderAttachmentList(card)}</div>
                        <input type="file" id="attach_file_${card.id}" accept="image/*" style="display:none;" onchange="Builder.uploadCardImage('${card.id}')">
                        <button type="button" class="btn btn_02" style="font-size:0.8rem; padding:3px 10px;" onclick="document.getElementById('attach_file_${card.id}').click()">📎 첨부파일 추가</button>
                    </div>
                </div>
            </div>`;
        });

        canvas.innerHTML = html;
        this.renderRightPanel();
        this.renderMinimap();
    },

    _escapeAttr: function(str) {
        return String(str == null ? '' : str).replace(/&/g, '&amp;').replace(/"/g, '&quot;');
    },

    // _escapeAttr()은 속성값(value="...")에만 안전하다 - <, >를 안 지운다.
    // 샘플 보기/AI 지시문 미리보기처럼 저장된 텍스트를 innerHTML의 "콘텐츠"로(태그 밖에)
    // 넣을 때는 반드시 이걸 써야 한다. 안 그러면 sample_content나 글감에 <script>나
    // </textarea> 같은 문자열이 들어있을 때 그대로 실행/이탈된다.
    _escapeHtml: function(str) {
        return String(str == null ? '' : str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    },

    // 제목 후보 전체를 하나의 "제목" 박스 안에 압축된 행으로 그린다. 체크박스가
    // 대표 지정/해제를 대신하고(기존 setPrimaryTitle/setCardState 상태를 그대로 씀),
    // styleLabel(우측 패널의 스타일 버튼이 붙여준 이름, 없으면 "기존")을 배지로 보여준다.
    _renderTitleGroup: function(titleCards) {
        let rows = '';
        titleCards.forEach(card => {
            const isHidden = card.state === 'hidden';
            const isActive = this.activeCardId === card.id;
            const isPrimary = card.state === 'primary';
            const label = card.styleLabel || '기존';

            rows += `
            <div class="pb-title-row ${isActive ? 'active' : ''} ${isHidden ? 'state-hidden' : ''}" id="card_${card.id}" onclick="Builder.selectCard('${card.id}')">
                <input type="checkbox" class="pb-title-check" title="대표 제목으로 지정" ${isPrimary ? 'checked' : ''} onclick="event.stopPropagation(); Builder.setTitleChecked('${card.id}', this.checked)">
                <span class="pb-title-style-badge">${label}</span>
                <input type="text" class="pb-title-input" id="textarea_${card.id}" value="${this._escapeAttr(card.content)}" onclick="event.stopPropagation()" onchange="Builder.updateCardContent('${card.id}', this.value)" ${isHidden ? 'disabled' : ''}>
                <div class="pb-title-actions">
                    ${card.locked ? '<span style="font-size:0.8rem;" title="잠금 됨 (AI 전체 재생성 시 보호됨)">🔒</span>' : '<span style="font-size:0.8rem; opacity:0.3; cursor:pointer;" title="수정/잠금 해제 상태" onclick="event.stopPropagation(); Builder.toggleLock(\''+card.id+'\', event)">🔓</span>'}
                    <button onclick="event.stopPropagation(); Builder.toggleHidden('${card.id}', event)">${isHidden ? '표시' : '숨기기'}</button>
                    <button onclick="event.stopPropagation(); Builder.deleteCard('${card.id}', event)" style="color:#ef4444; border-color:#fee2e2; background:#fef2f2;">삭제</button>
                </div>
            </div>`;
        });

        return `
        <div class="pb-card" style="cursor:default;">
            <div class="pb-card-header">
                <div class="pb-card-title">
                    <span>제목</span>
                    <span class="card-badge">${titleCards.length}개 후보 - 체크된 것이 대표 제목</span>
                </div>
            </div>
            <div class="pb-title-group" style="display:flex; flex-direction:column; gap:6px;">
                ${rows}
            </div>
        </div>`;
    },

    // 제목 행의 체크박스 - 체크하면 그 제목을 대표로, 해제하면 대표에서 뺀다
    // (기존 setPrimaryTitle/setCardState를 그대로 재사용).
    setTitleChecked: function(cardId, checked) {
        if (checked) this.setPrimaryTitle(cardId);
        else this.setCardState(cardId, 'draft');
    },

    renderMinimap: function() {
        const minimap = document.getElementById('pb_minimap');
        if (!minimap) return;

        let html = '<div style="padding:10px; font-weight:bold; font-size:0.9rem; color:#475569; border-bottom:1px solid #e2e8f0;">카드 미니맵</div>';

        // 제목 후보 5개가 "제목/제목/제목/제목/제목"으로 줄줄이 나와서 구분이 안 된다는
        // 피드백 - 화면에서도 제목들을 하나의 그룹으로 모았으니 미니맵도 항목 하나로 합친다.
        const titleCards = this.cards.filter(c => c.type === 'title' && c.state !== 'deleted');
        if (titleCards.length > 0) {
            const anyActive = titleCards.some(c => c.id === this.activeCardId);
            html += `
            <div class="minimap-item ${anyActive ? 'active' : ''}" onclick="Builder.scrollToCard('${titleCards[0].id}')">
                <span style="font-size:0.8rem; color:#94a3b8; width:20px; display:inline-block;">·</span>
                <span style="flex-grow:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">제목 (${titleCards.length}개 후보)</span>
            </div>
            `;
        }

        let idx = 0;
        this.cards.forEach((card) => {
            if (card.type === 'title') return;
            if (card.state === 'deleted') return;
            idx++;
            const isHidden = card.state === 'hidden';
            const isActive = this.activeCardId === card.id;

            let label = card.title || '본문';

            html += `
            <div class="minimap-item ${isActive ? 'active' : ''} ${isHidden ? 'hidden' : ''}" onclick="Builder.scrollToCard('${card.id}')" title="${this._escapeAttr(label)}">
                <span style="font-size:0.8rem; color:#94a3b8; width:20px; display:inline-block;">${idx}.</span>
                <span style="flex-grow:1; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">${label}</span>
                ${card.locked ? '<span style="font-size:0.7rem;">🔒</span>' : ''}
                ${card.state === 'primary' || card.state === 'selected' ? '<span style="color:#10b981; font-size:0.7rem;">●</span>' : ''}
            </div>
            `;
        });

        minimap.innerHTML = html;
    },

    scrollToCard: function(cardId) {
        this.selectCard(cardId);
        const el = document.getElementById('card_' + cardId);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    },

    selectCard: function(cardId) {
        this.activeCardId = cardId;
        const card = this.cards.find(c => c.id === cardId);
        if(card && card.collapsed) {
            card.collapsed = false;
            this.saveState();
        }
        this._saveUiState();
        this.renderCards(); // to update active styling
    },

    // 화면 상태(선택 카드/집중 편집 모드)만 브라우저에 저장한다. 본문·카드 데이터는
    // 기존과 동일하게 서버(builder_state)로만 저장되며 이 저장소와는 분리되어 있다.
    _saveUiState: function() {
        if (!this.projectId) return;
        try {
            localStorage.setItem('pb_ui_state_' + this.projectId, JSON.stringify({
                activeCardId: this.activeCardId,
                focusMode: document.getElementById('post-builder-app') ?
                    document.getElementById('post-builder-app').classList.contains('pb-focus-mode') : false
            }));
        } catch (e) { /* localStorage 사용 불가 시 조용히 무시 - 기능에 영향 없음 */ }
    },

    // 새로고침 직후 카드 목록이 그려진 다음에 호출한다. 저장된 activeCardId가
    // 지금 로드된 카드 중에 실제로 있을 때만 복원한다(삭제된 카드 ID 방지).
    _restoreUiState: function() {
        if (!this.projectId) return;
        let s;
        try {
            s = JSON.parse(localStorage.getItem('pb_ui_state_' + this.projectId) || 'null');
        } catch (e) { return; }
        if (!s) return;
        if (s.activeCardId && this.cards.some(c => c.id === s.activeCardId)) {
            this.activeCardId = s.activeCardId;
            this.renderCards();
        }
        if (s.focusMode) {
            this.toggleFocusMode();
        }
    },

    setPrimaryTitle: function(cardId, event) {
        if(event) event.stopPropagation();
        this.cards.forEach(c => {
            if(c.type === 'title') {
                if(c.id === cardId) {
                    c.state = 'primary';
                    this.saveCardPartial(c);
                }
                else if(c.state === 'primary') {
                    c.state = 'draft';
                    this.saveCardPartial(c);
                }
            }
        });
        this.saveState();
        this.renderCards();
    },

    setCardState: function(cardId, state, event) {
        if(event) event.stopPropagation();
        const card = this.cards.find(c => c.id === cardId);
        if(card) {
            card.state = state;
            this.saveCardPartial(card);
            this.saveState();
            this.renderCards();
        }
    },

    toggleHidden: function(cardId, event) {
        if(event) event.stopPropagation();
        const card = this.cards.find(c => c.id === cardId);
        if(card) {
            card.state = card.state === 'hidden' ? 'draft' : 'hidden';
            this.saveCardPartial(card);
            this.saveState();
            this.renderCards();
        }
    },

    toggleCollapse: function(cardId, event) {
        if(event) event.stopPropagation();
        const card = this.cards.find(c => c.id === cardId);
        if(card) {
            card.collapsed = !card.collapsed;
            if (card.collapsed && this.activeCardId === cardId) {
                this.activeCardId = null;
                this._saveUiState();
            }
            this.saveState();
            this.renderCards();
        }
    },

    toggleLock: function(cardId, event) {
        if(event) event.stopPropagation();
        const card = this.cards.find(c => c.id === cardId);
        if(card) {
            card.locked = !card.locked;
            this.saveCardPartial(card);
            this.saveState();
            this.renderCards();
        }
    },

    deleteCard: function(cardId, event) {
        if(event) event.stopPropagation();
        if(!confirm('정말 삭제하시겠습니까?')) return;
        const card = this.cards.find(c => c.id === cardId);
        if(card) {
            card.state = 'deleted';
            if(this.activeCardId === cardId) this.activeCardId = null;
            this.saveCardPartial(card);
            this.saveState();
            this.renderCards();
        }
    },

    updateCardContent: function(cardId, newValue) {
        const card = this.cards.find(c => c.id === cardId);
        if(card) {
            card.content = newValue;
            card.locked = true; // manual edit locks it
            this.saveCardPartial(card);
            this.saveState();
        }
    },

    // "본문에 그대로 추가" - AI를 거치지 않고 입력한 문장을 그대로 삽입한다.
    // regenerateCardAppend()(AI에게 지시하는 쪽)와 반드시 분리된 별도 함수로 둔다 -
    // 사용자의 문장이 실수로 AI 명령으로 처리되거나, 반대로 AI 지시문이 그대로 본문에
    // 꽂히는 사고를 막기 위함(다른 라이브러리에서 이미 한 번 겪은 문제 패턴).
    _joinWithBlankLine: function(a, b) {
        const at = (a || '').trim();
        const bt = (b || '').trim();
        if (!at) return bt;
        if (!bt) return at;
        return at + '\n\n' + bt;
    },

    insertRawContentIntoCard: function(cardId) {
        const textEl = document.getElementById('append_content_' + cardId);
        const posEl = document.getElementById('insert_position_' + cardId);
        const card = this.cards.find(c => c.id === cardId);
        if (!card) {
            alert('텍스트를 추가할 카드를 먼저 선택해 주세요.');
            return;
        }
        const text = textEl ? textEl.value.trim() : '';
        if (!text) {
            alert('추가할 텍스트를 입력해 주세요.');
            return;
        }
        const position = posEl ? posEl.value : 'end';

        if (position === 'replace') {
            if (!confirm('기존 카드 내용을 지우고 입력한 내용으로 완전히 교체하시겠습니까?')) return;
        }

        const prevContent = card.content;
        let newContent;
        if (position === 'start') {
            newContent = this._joinWithBlankLine(text, prevContent);
        } else if (position === 'replace') {
            newContent = text;
        } else {
            newContent = this._joinWithBlankLine(prevContent, text);
        }

        this.updateCardContent(cardId, newContent);
        this.renderCards();
        if (textEl) textEl.value = '';
        this._toast('카드에 텍스트를 추가했습니다.', 'success');

        // 최근 1건 실행취소 - previous_content를 메모리에 잠깐 들고 있다가 복원한다.
        this._lastRawInsert = { cardId: cardId, prevContent: prevContent };
        const undoEl = document.getElementById('insert_undo_' + cardId);
        if (undoEl) {
            undoEl.innerHTML = `텍스트가 추가되었습니다. <a href="javascript:;" onclick="Builder.undoRawInsert('${cardId}')" style="color:#4f46e5; text-decoration:underline;">실행 취소</a>`;
        }
    },

    undoRawInsert: function(cardId) {
        if (!this._lastRawInsert || this._lastRawInsert.cardId !== cardId) return;
        this.updateCardContent(cardId, this._lastRawInsert.prevContent);
        this.renderCards();
        this._lastRawInsert = null;
        const undoEl = document.getElementById('insert_undo_' + cardId);
        if (undoEl) undoEl.innerHTML = '';
        this._toast('추가한 텍스트를 되돌렸습니다.', 'success');
    },

    // 카드 하단 "첨부파일" - 실제 파일은 blog_images 이미지 라이브러리에 저장되고(중복
    // 검사·WebP 변환 등은 그 라이브러리가 이미 하는 걸 그대로 재사용), card.attachments
    // 배열에는 참조(image_id/url/filename)만 담아 builder_state JSON과 함께 저장/복원된다.
    _renderAttachmentList: function(card) {
        const attachments = card.attachments || [];
        if (attachments.length === 0) return '';
        return '<div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:8px;">' +
            attachments.map((a, i) => `
                <div style="position:relative; width:70px;">
                    <img src="${this._escapeAttr(a.url)}" style="width:70px; height:70px; object-fit:cover; border:1px solid #e2e8f0; border-radius:4px;" title="${this._escapeAttr(a.filename)}">
                    <button type="button" onclick="Builder.removeCardAttachment('${card.id}', ${i})" style="position:absolute; top:-6px; right:-6px; width:20px; height:20px; border-radius:50%; border:1px solid #fee2e2; background:#fef2f2; color:#ef4444; font-size:0.7rem; line-height:1; cursor:pointer;">✕</button>
                </div>`).join('') +
            '</div>';
    },

    uploadCardImage: function(cardId) {
        const card = this.cards.find(c => c.id === cardId);
        const fileInput = document.getElementById('attach_file_' + cardId);
        if (!card || !fileInput || !fileInput.files || !fileInput.files[0]) return;
        if (!this._requireProject()) return;

        const formData = new FormData();
        formData.append('action', 'upload_card_image');
        formData.append('project_id', this.projectId);
        formData.append('file', fileInput.files[0]);

        const listEl = document.getElementById('attach_list_' + cardId);
        if (listEl) listEl.insertAdjacentHTML('beforeend', '<span id="attach_uploading_' + cardId + '" style="font-size:0.8rem; color:#94a3b8;">업로드 중...</span>');

        fetch(PB_AJAX_URL, { method: 'POST', body: formData })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            fileInput.value = '';
            if (res.ok) {
                if (!card.attachments) card.attachments = [];
                card.attachments.push({ image_id: res.image_id, url: res.url, filename: res.filename });
                this.saveState();
                this.renderCards();
            } else {
                alert('업로드 실패: ' + (res.error || '알 수 없는 오류'));
                const el = document.getElementById('attach_uploading_' + cardId);
                if (el) el.remove();
            }
        })
        .catch(err => {
            alert(err.message === 'LOGIN_REQUIRED' ? '로그인이 만료되었습니다. 새로고침 후 다시 로그인해주세요.' : '네트워크 오류');
            const el = document.getElementById('attach_uploading_' + cardId);
            if (el) el.remove();
        });
    },

    // 파일 자체(및 blog_images 등록)는 지우지 않는다 - 이 카드에서 참조만 뗀다(다른
    // 카드/화면에서 재사용 중일 수 있어서 실제 삭제는 이미지 라이브러리에서 하게 한다).
    removeCardAttachment: function(cardId, idx) {
        const card = this.cards.find(c => c.id === cardId);
        if (!card || !card.attachments) return;
        card.attachments.splice(idx, 1);
        this.saveState();
        this.renderCards();
    },

    /* ==========================================
     * 6. Right Panel Rendering & AI Modify
     * ========================================== */
    renderRightPanel: function() {
        const panel = document.getElementById('pb_right_panel');

        // 1단계에서는 선택된 카드가 없는 게 당연하므로("클릭하시면 나타납니다" 안내는
        // 2/3단계용) - 키워드/해시태그 입력 패널을 우측에 보여준다.
        if (this.currentStep === 1) {
            panel.innerHTML = `
                <div class="pb-right-title">⚙️ 컨트롤 패널</div>
                ${this._renderCharPerLineControl()}
                ${this._renderTargetLengthControl()}
                <div class="pb-right-subtitle" style="display:flex; align-items:center; justify-content:space-between;">
                    <span>키워드 · 해시태그</span>
                    <button type="button" class="btn btn_02" onclick="Builder.toggleTagGroup()">${this.tagGroupCollapsed ? '펼치기 ▾' : '접기 ▴'}</button>
                </div>
                <div id="pb_tags_group" style="${this.tagGroupCollapsed ? 'display:none;' : ''}">
                    <div id="pb_tags_keywords" class="pb-tags-panel"></div>
                    <div id="pb_tags_hashtags" class="pb-tags-panel"></div>
                </div>
                <div id="pb_contact_panel"></div>
                ${this._renderStepNavigation()}
            `;
            if (!this.tagGroupCollapsed) {
                this.renderTagPanel('keywords');
                this.renderTagPanel('hashtags');
            }
            this.renderContactPanel();
            return;
        }

        // 4단계(CTA 작성·설정)에서 우측 패널에 CTA 요약 표시
        if (this.currentStep === 4) {
            const ctaEnabled = document.getElementById('pb_cta_enabled');
            const isEnabled = ctaEnabled ? ctaEnabled.checked : true;
            const profCount = this.ctaProfiles ? this.ctaProfiles.length : 0;
            const currentProf = this.ctaProfiles ? this.ctaProfiles.find(p => p.id === this.currentCtaProfileId) : null;
            const profName = currentProf ? this._escapeAttr(currentProf.profile_name) : '미선택';
            
            // 체크된 업체 정보 항목 수
            const checkedFields = document.querySelectorAll('.pb-chk-cta-field:checked');
            const checkedCount = checkedFields ? checkedFields.length : 0;
            
            // 맺음말 미리보기
            const ctaBody = document.getElementById('pb_cta_content');
            const ctaBodyText = ctaBody ? ctaBody.value.trim() : '';
            const ctaPreview = ctaBodyText ? (ctaBodyText.length > 80 ? ctaBodyText.substring(0, 80) + '…' : ctaBodyText) : '';

            let ctaSummaryHtml = `
                <div class="pb-right-title">⚙️ CTA 컨트롤 패널</div>
                <div style="padding:12px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:12px;">
                    <div style="font-size:0.85rem; font-weight:bold; color:#475569; margin-bottom:8px;">CTA 상태</div>
                    <div style="display:flex; align-items:center; gap:8px; margin-bottom:6px; font-size:0.85rem;">
                        <span style="width:12px; height:12px; border-radius:50%; background:${isEnabled ? '#10b981' : '#94a3b8'}; display:inline-block;"></span>
                        ${isEnabled ? '<span style="color:#047857;">활성</span>' : '<span style="color:#94a3b8;">비활성 (CTA 미포함)</span>'}
                    </div>
                </div>`;

            if (isEnabled) {
                ctaSummaryHtml += `
                <div style="padding:12px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:12px;">
                    <div style="font-size:0.85rem; font-weight:bold; color:#475569; margin-bottom:8px;">프로필 정보</div>
                    <div style="font-size:0.85rem; color:#334155; margin-bottom:4px;">
                        <strong>현재 프로필:</strong> ${profName}
                    </div>
                    <div style="font-size:0.85rem; color:#334155; margin-bottom:4px;">
                        <strong>등록 프로필:</strong> ${profCount}개
                    </div>
                    <div style="font-size:0.85rem; color:#334155;">
                        <strong>체크된 항목:</strong> ${checkedCount}개
                    </div>
                </div>`;

                if (ctaPreview) {
                    ctaSummaryHtml += `
                <div style="padding:12px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:12px;">
                    <div style="font-size:0.85rem; font-weight:bold; color:#475569; margin-bottom:8px;">맺음말 미리보기</div>
                    <div style="font-size:0.85rem; color:#64748b; line-height:1.5; white-space:pre-wrap;">${this._escapeAttr(ctaPreview)}</div>
                </div>`;
                }

                // 빠른 동작 버튼
                ctaSummaryHtml += `
                <div style="padding:12px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:12px;">
                    <div style="font-size:0.85rem; font-weight:bold; color:#475569; margin-bottom:8px;">빠른 동작</div>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <button type="button" class="btn btn_02" style="font-size:0.8rem;" onclick="Builder.saveCtaProfile(false)">현재 프로필 저장</button>
                        <button type="button" class="btn btn_02" style="font-size:0.8rem;" onclick="Builder.saveCtaProfile(true)">다른 이름으로 저장</button>
                        <button type="button" class="btn btn_02" style="font-size:0.8rem;" onclick="Builder.loadCtaProfiles()">프로필 새로고침</button>
                    </div>
                </div>`;
            }

            ctaSummaryHtml += this._renderStepNavigation();
            panel.innerHTML = ctaSummaryHtml;
            return;
        }

        if (this.currentStep === 5) {
            const kw4 = this.tagState.keywords.values.filter(v => v && v.trim() !== '');
            panel.innerHTML = `
                <div class="pb-right-title">⚙️ 컨트롤 패널</div>
                ${this._renderTargetLengthControl()}
                <div class="pb-right-subtitle">키워드 <span style="font-weight:normal; font-size:0.75rem; color:#94a3b8;">(생성 요청용 - 1단계에서 편집)</span></div>
                <div style="margin-bottom:12px; padding:10px 14px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; font-size:0.85rem;">
                    <div><strong>키워드</strong> (${kw4.length}개): ${kw4.length > 0 ? kw4.join(', ') : '<span style="color:#94a3b8;">미입력</span>'}</div>
                    <p style="margin:8px 0 0; color:#94a3b8; font-size:0.75rem;">수정하려면 1단계로 이동해서 편집하세요. 키워드는 본문 글감 안에 자연스럽게 들어가는 용도라 최종 글 하단에는 별도로 붙지 않습니다.</p>
                </div>
                <div class="pb-right-subtitle">최종 해시태그 <span style="font-weight:normal; font-size:0.75rem; color:#94a3b8;">(AI로 최종 생성된 해시태그 - 여기서 직접 관리)</span></div>
                <div id="pb_final_hashtags"></div>
                <p style="color:#94a3b8; font-size:0.8rem;">목표 글자수를 바꾸면 검수 결과가 그 기준으로 다시 표시됩니다. 실제 본문 길이를 바꾸려면 1단계에서 다시 생성하거나 카드를 직접 수정하세요.</p>
                ${this._renderStepNavigation()}
            `;
            this._renderFinalHashtagPanelInto();
            return;
        }

        if (!this.activeCardId) {
            panel.innerHTML = `
                <div class="pb-right-title">⚙️ 컨트롤 패널</div>
                <div style="text-align:center; color:#94a3b8; padding-top:100px; font-size:0.95rem;">중앙에서 카드를 클릭하시면<br>이곳에 전용 설정 패널이 나타납니다.</div>
                <div style="margin-top:auto;">
                    ${this._renderStepNavigation()}
                </div>
            `;
            return;
        }

        const card = this.cards.find(c => c.id === this.activeCardId);
        if (!card) return;

        let html = `<div class="pb-right-title">⚙️ 컨트롤 패널</div>`;
        html += `<div class="pb-right-subtitle">AI 빠른 수정 (${card.type === 'title' ? '제목' : '본문'})</div>`;
        
        if (card.locked) {
            html += `<div style="padding:10px; background:#fef3c7; border:1px solid #fde68a; border-radius:4px; margin-bottom:15px; font-size:0.85rem; color:#92400e;">이 카드는 🔒잠금 처리되었습니다. 일괄 재생성으로부터 보호받고 있습니다.</div>`;
        }

        if (card.type === 'title') {
            // 누르면 기존 제목을 덮어쓰지 않고 새 후보를 위의 "제목" 그룹에 추가한다
            // (regenerateCard의 4번째 인자가 그 후보에 붙는 스타일 배지 이름이 된다).
            html += `
            <div class="pb-control-group">
                <label>제목 스타일 변경 (새 후보로 추가됩니다)</label>
                <div class="pb-btn-grid">
                    <button onclick="Builder.regenerateCard('${card.id}', '더 매력적이고 자극적으로', false, '매력적으로')">매력적으로</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '정보 전달 중심으로 담백하게', false, '담백하게')">담백하게</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '질문형으로', false, '질문형')">질문형으로</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '숫자를 강조해서', false, '숫자 강조')">숫자 강조</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '궁금증을 유발하는 후킹 문구로', false, '궁금증 유발')">궁금증 유발</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '공감을 이끄는 감성적인 문구로', false, '공감형')">공감형</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '전문가처럼 신뢰감 있는 어조로', false, '전문가톤')">전문가톤</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '친근한 블로거 말투로', false, '친근하게')">친근하게</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '지금 당장 확인해야 할 것 같은 긴급함을 담아서', false, '긴급성 강조')">긴급성 강조</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '다른 곳과 비교하는 느낌으로', false, '비교형')">비교형</button>
                    <button onclick="Builder.regenerateCard('${card.id}', 'TOP 순위·리스트 느낌으로', false, 'TOP 리스트형')">TOP 리스트형</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '지역명과 핵심 키워드를 앞쪽에 강조해서', false, '키워드 강조')">키워드 강조</button>
                </div>
            </div>`;
        } else {
            html += `
            <div class="pb-control-group">
                <label>현재 글자 수</label>
                <div style="font-size:0.9rem; color:#334155;">${card.content.length.toLocaleString()}자</div>
            </div>
            <div class="pb-control-group">
                <label>길이 및 밀도 조절</label>
                <div class="pb-btn-grid" style="margin-bottom:8px;">
                    <button onclick="Builder.regenerateCard('${card.id}', '사례를 한두 가지 추가해서 풍부하게 해줘')">사례 추가</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '주요 내용을 번호표나 목록(리스트) 형태로 정리해줘')">목록형으로정리</button>
                </div>
                ${this._renderLengthControl(card)}
            </div>
            <div class="pb-control-group">
                <label>말투 및 톤 변경</label>
                <div class="pb-btn-grid">
                    <button onclick="Builder.regenerateCard('${card.id}', '전문가처럼 신뢰감 있는 말투로 변경해줘')">전문적으로</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '친근한 블로거처럼 다정하게 변경해줘')">친근하게</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '홍보 느낌을 줄이고 객관적인 정보처럼 변경해줘')">광고 느낌 줄이기</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '상담이나 문의를 적극적으로 유도하는 톤으로 변경해줘')">설득력 강화</button>
                </div>
            </div>
            <div class="pb-control-group">
                <label>직접 프롬프트 명령</label>
                <textarea id="custom_prompt_${card.id}" class="frm_input" style="height:60px;" placeholder="예: '울산'이라는 단어를 두 번 더 넣어줘"></textarea>
                <button type="button" class="btn btn_02" style="width:100%; margin-top:5px;" onclick="Builder.regenerateCardCustom('${card.id}')">명령 실행</button>
            </div>
            <div class="pb-control-group">
                <label>선택 카드에 텍스트 추가</label>
                <p style="font-size:0.75rem; color:#94a3b8; margin:0 0 6px;">입력한 문장을 그대로 넣을지, AI에게 반영을 맡길지 아래에서 골라 실행하세요. 두 버튼은 서로 다르게 동작합니다.</p>
                <select id="insert_position_${card.id}" class="frm_input" style="margin-bottom:6px; font-size:0.8rem;">
                    <option value="end">본문 맨 끝에 추가</option>
                    <option value="start">본문 맨 앞에 추가</option>
                    <option value="replace">기존 내용 전체 교체</option>
                </select>
                <textarea id="append_content_${card.id}" class="frm_input" style="height:80px;" placeholder="추가하거나 지시할 내용을 입력하세요"></textarea>
                <div style="display:flex; gap:6px; margin-top:5px;">
                    <button type="button" class="btn btn_01" style="flex:1;" title="입력한 문장을 수정 없이 선택 위치에 그대로 넣습니다" onclick="Builder.insertRawContentIntoCard('${card.id}')">본문에 그대로 추가</button>
                    <button type="button" class="btn btn_02" style="flex:1;" title="입력 내용을 지시문으로 보고 AI가 카드 문맥에 맞게 다시 씁니다" onclick="Builder.regenerateCardAppend('${card.id}')">AI에게 수정 지시</button>
                </div>
                <div id="insert_undo_${card.id}" style="margin-top:6px; font-size:0.78rem;"></div>
            </div>
            <div class="pb-control-group">
                <label>이미지 생성용 프롬프트</label>
                <div style="display:flex; gap:8px; align-items:center; margin-bottom:8px;">
                    <select id="img_prompt_count_${card.id}" class="frm_input" style="width:70px;">
                        ${[1,2,3,4,5].map(n => `<option value="${n}" ${n === 3 ? 'selected' : ''}>${n}개</option>`).join('')}
                    </select>
                    <button type="button" class="btn btn_02" style="flex:1;" onclick="Builder.generateImagePrompts('${card.id}')">생성</button>
                </div>
                <div id="img_prompts_${card.id}">${this._renderImagePrompts(card)}</div>
            </div>
            `;
        }

        html += this._renderStepNavigation();
        panel.innerHTML = html;
    },

    // 카드별 길이 조정(%)/글 전개 구조 선택값을 기억한다. 카드 본문 상태
    // (draft/selected/hidden/deleted)와는 별개의 UI 편의 값이며, gatherData()를 통해
    // builder_state에 함께 실려 저장되지만 과거 데이터에 없어도(undefined) 안전하게
    // 기본값(0, '')으로 취급한다.
    lengthControlState: {},

    _getLengthState: function(cardId) {
        if (!this.lengthControlState[cardId]) {
            this.lengthControlState[cardId] = { percent: 0, structure: '' };
        }
        return this.lengthControlState[cardId];
    },

    _lengthPresets: [-30, -20, -10, 0, 10, 20, 30, 50],

    _renderLengthControl: function(card) {
        const state = this._getLengthState(card.id);
        const presets = this._lengthPresets.map(p => {
            const label = p === 0 ? '원본' : (p > 0 ? '+' + p + '%' : p + '%');
            return `<button type="button" class="pb-length-preset-btn ${state.percent === p ? 'is-active' : ''}" onclick="Builder.setLengthPercent('${card.id}', ${p})">${label}</button>`;
        }).join('');

        return `
        <div class="pb-length-control" data-card-id="${card.id}">
            <div class="pb-length-control-head">
                <label class="pb-length-label" for="length_range_${card.id}">글 길이 조정</label>
                <output class="pb-length-output" id="length_output_${card.id}">${state.percent > 0 ? '+' : ''}${state.percent}%</output>
            </div>
            <input type="range" class="pb-length-range" id="length_range_${card.id}"
                   min="-50" max="100" step="10" value="${state.percent}"
                   aria-label="글 길이 조정 비율"
                   oninput="Builder.setLengthPercent('${card.id}', parseInt(this.value, 10))">
            <div class="pb-length-presets" role="group" aria-label="글 길이 빠른 설정">${presets}</div>
            <button type="button" class="btn btn_02 pb-length-apply" style="width:100%; margin-top:6px;" onclick="Builder.applyLengthRewrite('${card.id}')">선택한 길이로 다시 쓰기</button>
        </div>`;
    },

    setLengthPercent: function(cardId, percent) {
        const state = this._getLengthState(cardId);
        state.percent = percent;
        const output = document.getElementById('length_output_' + cardId);
        if (output) output.textContent = (percent > 0 ? '+' : '') + percent + '%';
        const range = document.getElementById('length_range_' + cardId);
        if (range) range.value = percent;
        document.querySelectorAll('.pb-length-control[data-card-id="' + cardId + '"] .pb-length-preset-btn').forEach(btn => {
            btn.classList.toggle('is-active', parseInt(btn.getAttribute('onclick').match(/,\s*(-?\d+)\)/)[1], 10) === percent);
        });
    },

    // 목표 글자 수를 계산해 프롬프트에 명시한다 - 단순히 "더 길게/짧게"라고만
    // 요청하지 않고 현재 글자 수 기준 목표치를 알려줘야 결과가 그 근처로 나온다.
    applyLengthRewrite: function(cardId) {
        const card = this.cards.find(c => c.id === cardId);
        if (!card) return;
        const state = this._getLengthState(cardId);
        const percent = state.percent;
        const currentLen = card.content.length;
        const targetLen = Math.round(currentLen * (1 + percent / 100));

        let instruction;
        if (percent === 0) {
            this._toast('길이 조정값이 0%입니다 - 원본 길이를 유지합니다.', 'error');
            return;
        } else if (percent > 0) {
            instruction = `현재 원고의 핵심 사실과 문체를 유지하면서 약 ${percent}% 길게 재작성하세요. 현재 분량은 약 ${currentLen}자이며 목표 분량은 약 ${targetLen}자입니다. 내용을 억지로 반복하지 말고 사례, 설명, 연결 문장으로 자연스럽게 확장하세요.`;
        } else {
            instruction = `현재 원고의 핵심 정보와 중요 키워드를 유지하면서 약 ${Math.abs(percent)}% 짧게 정리하세요. 현재 분량은 약 ${currentLen}자이며 목표 분량은 약 ${targetLen}자입니다. 중복 표현과 불필요한 수식어를 줄이되 문맥이 끊기지 않게 작성하세요.`;
        }
        this.regenerateCard(cardId, instruction);
    },

    regenerateCardCustom: function(cardId) {
        const prompt = document.getElementById('custom_prompt_' + cardId).value;
        if(!prompt) { alert('명령을 입력해주세요.'); return; }
        this.regenerateCard(cardId, prompt);
    },

    // "직접 프롬프트 명령"은 임의의 지시문(예: 말투 바꿔줘)이고, 이건 그와 달리 사용자가
    // 준 원문 그대로의 추가 정보/문장을 기존 글 속에 자연스럽게 녹여 넣으라는 지시로
    // 감싸서 보낸다 - 사용자가 입력한 텍스트 자체를 지시문으로 오인해 엉뚱하게 처리되지
    // 않도록 구분한다.
    regenerateCardAppend: function(cardId) {
        const extra = document.getElementById('append_content_' + cardId).value.trim();
        if(!extra) { alert('추가할 내용을 입력해주세요.'); return; }
        this.regenerateCard(cardId, '다음 내용을 자연스럽게 포함시켜서 기존 글을 재작성해줘:\n' + extra);
    },

    // 실제 이미지 API는 호출하지 않는다 - 사용자가 별도 이미지 생성 도구(달리/미드저니 등)에
    // 붙여넣을 영문 프롬프트 텍스트만 만들어 준다. card._imagePrompts에 임시로 들고 있다가
    // (서버에는 저장하지 않음) 우측 패널이 다시 그려질 때도 사라지지 않게 한다.
    _renderImagePrompts: function(card) {
        const prompts = card._imagePrompts;
        if (!prompts || prompts.length === 0) {
            return '<div style="color:#94a3b8; font-size:0.85rem;">아직 생성된 프롬프트가 없습니다.</div>';
        }
        return prompts.map((p, i) => `
            <div style="border:1px solid #e2e8f0; border-radius:4px; padding:8px; margin-bottom:6px; font-size:0.8rem; background:#fff;">
                <div style="color:#334155; margin-bottom:6px;">${this._escapeAttr(p)}</div>
                <button type="button" class="btn btn_02" style="font-size:0.75rem; padding:2px 8px;" onclick="Builder._copyImagePrompt(this, ${i}, '${card.id}')">복사</button>
            </div>`).join('');
    },

    _copyImagePrompt: function(btn, idx, cardId) {
        const card = this.cards.find(c => c.id === cardId);
        if (!card || !card._imagePrompts || !card._imagePrompts[idx]) return;
        navigator.clipboard.writeText(card._imagePrompts[idx]).then(() => {
            this._toast('프롬프트가 복사되었습니다.', 'success');
        });
    },

    generateImagePrompts: function(cardId) {
        const card = this.cards.find(c => c.id === cardId);
        if (!card) return;
        if (!this._requireProject()) return;
        if (!card.content || card.content.trim() === '') {
            alert('내용이 비어 있어 프롬프트를 만들 수 없습니다.');
            return;
        }

        const countSelect = document.getElementById('img_prompt_count_' + cardId);
        const count = countSelect ? countSelect.value : 3;
        const resultsDiv = document.getElementById('img_prompts_' + cardId);
        if (resultsDiv) resultsDiv.innerHTML = '<div style="color:#94a3b8; font-size:0.85rem;">생성 중...</div>';

        const payload = new URLSearchParams();
        payload.append('action', 'generate_image_prompts');
        payload.append('project_id', this.projectId);
        payload.append('count', count);
        payload.append('content_text', card.content);

        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            if (res.ok && Array.isArray(res.prompts)) {
                card._imagePrompts = res.prompts;
                if (resultsDiv) resultsDiv.innerHTML = this._renderImagePrompts(card);
            } else {
                if (resultsDiv) resultsDiv.innerHTML = '<div style="color:#ef4444; font-size:0.85rem;">생성 실패: ' + (res.error || '알 수 없는 오류') + '</div>';
            }
        })
        .catch(err => {
            const msg = err.message === 'LOGIN_REQUIRED' ? '로그인이 만료되었습니다. 새로고침 후 다시 로그인해주세요.' : '네트워크 오류';
            if (resultsDiv) resultsDiv.innerHTML = '<div style="color:#ef4444; font-size:0.85rem;">' + msg + '</div>';
        });
    },

    regenerateCard: function(cardId, prompt, acceptTemplateFallback, styleLabel) {
        const card = this.cards.find(c => c.id === cardId);
        if(!card) return;

        // 제목은 기존 후보를 덮어쓰지 않는다 - "기존" 제목과 새로 만든 스타일 제목을
        // 나란히 놓고 비교해서 고를 수 있어야 한다는 요구사항. 본문 섹션은 원래처럼
        // 그 자리에서 바로 교체한다(비교 대상이 아니라 편집 대상이라 다르게 취급).
        const isTitle = card.type === 'title';
        const prevContent = card.content;
        if (!isTitle) {
            card.content = 'AI가 재작성 중입니다... 잠시만 기다려주세요.';
            this.renderCards();
        }

        const payload = new URLSearchParams();
        payload.append('action', 'regenerate_card');
        payload.append('project_id', this.projectId);
        payload.append('post_id', this.postId);
        payload.append('card_id', cardId);
        payload.append('card_type', card.type);
        payload.append('card_title', card.title || '');
        payload.append('current_content', prevContent);
        payload.append('instruction', prompt);

        // Context
        payload.append('raw_material', document.getElementById('pb_raw_material').value);
        if (acceptTemplateFallback) payload.append('accept_template_fallback', '1');

        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            if (res.needs_provider_confirm) {
                if (!isTitle) { card.content = prevContent; this.renderCards(); }
                this._confirmProviderFallback(res.provider_label || '선택한 공급자', choice => {
                    if (choice === 'template') {
                        this.regenerateCard(cardId, prompt, true, styleLabel);
                    } else if (choice === 'switch') {
                        this._switchToChatGptProvider().then(switched => {
                            if (switched) this.regenerateCard(cardId, prompt, false, styleLabel);
                        });
                    }
                });
                return;
            }

            if (res.ok && res.new_content) {
                if (isTitle) {
                    const newCard = {
                        id: 'card_' + Date.now() + '_' + Math.floor(Math.random() * 1000),
                        type: 'title',
                        content: res.new_content,
                        state: 'draft',
                        locked: false,
                        styleLabel: styleLabel || ''
                    };
                    const idx = this.cards.indexOf(card);
                    this.cards.splice(idx + 1, 0, newCard);
                    this.activeCardId = newCard.id;
                    this.saveState();
                    this._toast('새 제목 후보가 추가되었습니다.', 'success');
                } else {
                    card.content = res.new_content;
                    card.locked = false; // AI generated unlocks it
                    this.saveCardPartial(card);
                    this.saveState();
                    this._toast('수정 완료', 'success');
                }
            } else {
                if (!isTitle) card.content = prevContent; // rollback
                alert('재작성 실패: ' + res.error);
            }
            this.renderCards();
        })
        .catch(err => {
            if (!isTitle) card.content = prevContent;
            this.renderCards();
            if (err.message === 'LOGIN_REQUIRED') {
                alert('로그인이 만료되었습니다. 새로고침 후 다시 로그인해주세요.');
            } else {
                alert('네트워크 오류');
            }
        });
    },

    /* ==========================================
     * 7. Final Assembly & Quality Check
     * ========================================== */
    inspectAll: function() {
        if (!this.projectId || !this.postId) return;

        const canvas = document.getElementById('pb_inspect_canvas');
        canvas.innerHTML = `
            <div style="text-align:center; padding:30px;">
                <p>AI가 글의 품질, 금지어, 맞춤법, 어색한 문맥을 점검 중입니다...</p>
                <div class="skeleton-line w-2-3" style="margin: 0 auto;"></div>
            </div>
        `;

        // Assemble title and body for quality check
        const primaryTitle = this.cards.find(c => c.type === 'title' && c.state === 'primary');
        const titleText = primaryTitle ? primaryTitle.content : '';
        const bodyCards = this.cards.filter(c => c.type !== 'title' && (c.state === 'selected' || c.state === 'primary'));
        let bodyText = '';
        bodyCards.forEach(c => { bodyText += (c.title ? c.title + '\n' : '') + c.content + '\n\n'; });

        // CTA 블록은 finishPost()에서 최종 조립 시에만 본문에 붙는데, 검수(run_quality_check)는
        // 그 이전(카드 상태)에서 실행된다. CTA에 전화번호를 넣기로 설정해놓고도 검수 시점엔
        // 아직 본문에 안 들어가 있어 "연락처 누락"이 항상 뜨는 문제가 있었다 - 검수용
        // 본문에도 finishPost()와 동일한 CTA 텍스트를 미리 붙여서 실제 최종 결과 기준으로
        // 검사되게 한다.
        const ctaEnabledEl = document.getElementById('pb_cta_enabled');
        let expectedPhone = '';
        if (ctaEnabledEl && ctaEnabledEl.checked) {
            const ctaContent = document.getElementById('pb_cta_content') ? document.getElementById('pb_cta_content').value.trim() : '';
            if (ctaContent) bodyText += ctaContent + '\n\n';
            document.querySelectorAll('.pb-chk-cta-field:checked').forEach(chk => {
                const input = document.getElementById('cta_val_' + chk.value);
                if (input && input.value) {
                    const labelText = chk.parentElement.textContent.trim();
                    bodyText += `${labelText}: ${input.value}\n`;
                    if (chk.value === 'primary_phone') expectedPhone = input.value;
                }
            });
        }

        const payload = new URLSearchParams();
        payload.append('action', 'run_quality_check');
        payload.append('project_id', this.projectId);
        payload.append('post_id', this.postId);
        payload.append('title_text', titleText);
        payload.append('body_text', bodyText);
        payload.append('expected_phone', expectedPhone);

        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.ok) {
                let html = `<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:15px; margin-bottom:15px;">
                                <h3 style="margin:0; font-size:1.2rem;">품질 검수 결과</h3>
                                <div style="font-size:1.5rem; font-weight:bold; color:${res.score >= 80 ? '#10b981' : (res.score >= 50 ? '#f59e0b' : '#ef4444')};">${res.score}점</div>
                            </div>`;

                // 검수 결과에 체크 항목만 있고 실제 등록된 키워드/해시태그는 안 보인다는
                // 피드백 - 1단계에서 입력/생성한 값을 그대로 요약해서 보여준다.
                this._ensureHashtagEnabledLength();
                const kw = this.tagState.keywords.values.filter(v => v && v.trim() !== '');
                const ht = this.tagState.hashtags.values.filter((v, i) => v && v.trim() !== '' && this.tagState.hashtags.enabled[i] !== false);
                const bodyLen = bodyText.trim().length;
                const lenDiff = bodyLen - this.targetLength;
                html += `<div style="margin-bottom:15px; padding:12px 15px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; font-size:0.9rem;">
                            <div><strong>키워드</strong> (${kw.length}개): ${kw.length > 0 ? kw.join(', ') : '<span style="color:#94a3b8;">미입력</span>'}</div>
                            <div style="margin-top:6px;"><strong>해시태그</strong> (${ht.length}개): ${ht.length > 0 ? ht.join(' ') : '<span style="color:#94a3b8;">미입력</span>'}</div>
                            <div style="margin-top:6px;"><strong>본문 글자수</strong>: ${bodyLen}자 / 목표 ${this.targetLength}자 (${lenDiff >= 0 ? '+' : ''}${lenDiff}자)</div>
                         </div>`;

                html += `<ul style="list-style:none; padding:0; margin:0;">`;
                
                res.checks.forEach(chk => {
                    let icon = chk.status === 'pass' ? '✅' : (chk.status === 'warn' ? '⚠️' : '❌');
                    let color = chk.status === 'pass' ? '#10b981' : (chk.status === 'warn' ? '#f59e0b' : '#ef4444');
                    const label = this.QUALITY_LABELS[chk.key] || chk.key;

                    // 중복 문장은 개수만 알려줘서는 고칠 수가 없다. 눌러서 어느 문장이
                    // 어디에 있는지 펼쳐볼 수 있게 한다. 서버 detail은 검수 실행 시점
                    // 기준이고 펼쳐지는 목록은 지금 편집 중인 본문 기준이라, 문구로
                    // 둘을 구분해 둔다.
                    const expandable = (chk.key === 'duplicate_sentences' && chk.status !== 'pass');

                    html += `<li style="padding:10px 0; border-bottom:1px solid #f1f5f9;">
                                <div><span style="margin-right:10px;">${icon}</span><strong style="color:#334155;">${label}</strong></div>
                                <div style="color:${color}; font-size:0.9rem; margin-top:5px; padding-left:28px;">서버 검수: ${chk.detail}</div>`;
                    if (expandable) {
                        html += `<div style="padding-left:28px; margin-top:6px;">
                                    <button type="button" class="pb-dupe-toggle" aria-expanded="false"
                                        aria-controls="pb_dupe_detail"
                                        onclick="Builder.toggleDuplicateDetail(this)">어느 문장인지 보기</button>
                                 </div>
                                 <div id="pb_dupe_detail" class="pb-dupe-detail" hidden></div>`;
                    }
                    html += `</li>`;
                });
                html += `</ul>`;
                
                if (res.summary.error > 0) {
                    html += `<div style="margin-top:20px; padding:15px; background:#fef2f2; color:#b91c1c; border-radius:4px; font-weight:bold;">수정이 필요한 치명적 오류가 ${res.summary.error}건 있습니다. 발행 전 반드시 수정해야 승인됩니다.</div>`;
                }

                canvas.innerHTML = html;
            } else {
                canvas.innerHTML = `<div style="color:red;">검수 실패: ${res.error}</div>`;
            }
        });
    },

    finishPost: function() {
        if (!this.cards || this.cards.length === 0) {
            alert('초안을 먼저 생성해 주세요.');
            return;
        }

        // Get primary title
        const primaryTitle = this.cards.find(c => c.type === 'title' && c.state === 'primary');
        const titleText = primaryTitle ? primaryTitle.content : '제목 미지정';

        // Get selected body cards in order
        const bodyCards = this.cards.filter(c => c.type !== 'title' && (c.state === 'selected' || c.state === 'primary'));
        
        if (bodyCards.length === 0) {
            alert('본문에 사용할 카드를 최소 1개 이상 [사용하기]로 선택해주세요.');
            return;
        }

        // Generate HTML
        let htmlOut = `<h1>${titleText}</h1>\n\n`;
        let textOut = `${titleText}\n\n`;
        // posts.title/body에 그대로 저장될 본문 - <h1> 제목은 title 컬럼에 따로 들어가므로
        // 여기엔 안 포함시킨다(발행 파이프라인이 title+body를 각자 다른 자리에 쓴다).
        let bodyHtmlOut = '';

        bodyCards.forEach(c => {
            if (c.title) {
                htmlOut += `<h2>${c.title}</h2>\n`;
                textOut += `[${c.title}]\n`;
                bodyHtmlOut += `<h2>${c.title}</h2>\n`;
            }
            htmlOut += `<p>${c.content.replace(/\n/g, '<br>')}</p>\n\n`;
            textOut += `${c.content}\n\n`;
            bodyHtmlOut += `<p>${c.content.replace(/\n/g, '<br>')}</p>\n\n`;
        });

        // 해시태그는 실제 블로그 글처럼 맨 아래 붙여준다. 키워드는 여기 따로 나열하지
        // 않는다 - 본문 자체에 자연스럽게 녹아있어야 하는 것이라 별도 목록으로 붙이면
        // 오히려 어색하다(사용자 확인 사항).
        this._ensureHashtagEnabledLength();
        const hashtagValues = this.tagState.hashtags.values.filter((v, i) => v && v.trim() !== '' && this.tagState.hashtags.enabled[i] !== false);
        if (hashtagValues.length > 0) {
            const hashtagLine = hashtagValues.map(v => (v.charAt(0) === '#' ? v : '#' + v)).join(' ');
            htmlOut += `<p>${hashtagLine}</p>\n`;
            textOut += `${hashtagLine}\n`;
            bodyHtmlOut += `<p>${hashtagLine}</p>\n`;
        }

        const ctaEnabledEl = document.getElementById('pb_cta_enabled');
        if (ctaEnabledEl && ctaEnabledEl.checked) {
            let ctaHtml = '';
            let ctaText = '';
            const ctaContent = document.getElementById('pb_cta_content') ? document.getElementById('pb_cta_content').value.trim() : '';
            if (ctaContent) {
                ctaHtml += `<p>${ctaContent.replace(/\n/g, '<br>')}</p>\n\n`;
                ctaText += `${ctaContent}\n\n`;
            }
            
            const checkedFields = Array.from(document.querySelectorAll('.pb-chk-cta-field:checked'));
            const customRows = document.querySelectorAll('.pb-custom-cta-row');
            
            if (checkedFields.length > 0 || customRows.length > 0) {
                ctaHtml += `<ul style="list-style:none; padding:0;">\n`;
                
                checkedFields.forEach(chk => {
                    const input = document.getElementById('cta_val_' + chk.value);
                    if (input && input.value) {
                        const labelText = chk.parentElement.textContent.trim();
                        ctaHtml += `<li><strong>${labelText}:</strong> ${input.value}</li>\n`;
                        ctaText += `${labelText}: ${input.value}\n`;
                    }
                });
                
                customRows.forEach(row => {
                    const k = row.querySelector('.cta-custom-key').value;
                    const v = row.querySelector('.cta-custom-val').value;
                    if (k && v) {
                        ctaHtml += `<li><strong>${this._escapeHtml(k)}:</strong> ${this._escapeHtml(v)}</li>\n`;
                        ctaText += `${k}: ${v}\n`;
                    }
                });
                
                ctaHtml += `</ul>\n`;
            }

            
            if (ctaHtml !== '') {
                htmlOut += `<br><br>\n` + ctaHtml;
                textOut += `\n\n` + ctaText;
                bodyHtmlOut += `<br><br>\n` + ctaHtml;
            }
        }

        document.getElementById('pb_preview_html').innerHTML = htmlOut;
        document.getElementById('pb_preview_text').value = textOut;

        // Save one last time
        this.saveState();

        // saveState()는 builder_state(카드 JSON)만 저장한다 - project_view.php의 "제목"/
        // "본문(현재)" 그리고 실제 발행 파이프라인(blog_publisher.lib.php)은 posts.title/body
        // 컬럼을 직접 읽으므로, "완료" 시점에 조립된 결과를 그 컬럼에도 명시적으로 반영해야
        // 카드 빌더에서 완성한 글이 검수/발행 단계에서 실제로 보이고 나간다.
        this.finalizePostColumns(titleText, bodyHtmlOut);

        // Slide up the panel
        document.getElementById('pb_bottom_panel').classList.add('active');
    },

    finalizePostColumns: function(title, bodyHtml) {
        if (!this.projectId || !this.postId) return;
        const payload = new URLSearchParams();
        payload.append('action', 'finalize_post');
        payload.append('project_id', this.projectId);
        payload.append('post_id', this.postId);
        payload.append('title', title);
        payload.append('body_html', bodyHtml);

        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            if (!res.ok) this._toast('완성본을 프로젝트에 반영하지 못했습니다: ' + (res.error || '알 수 없는 오류'), 'error');
        })
        .catch(() => this._toast('완성본 반영 중 네트워크 오류가 발생했습니다. 다시 "완료"를 눌러주세요.', 'error'));
    },

    // 집중 편집 모드 - 좌우 패널(단계 메뉴/카드 편집 패널)을 숨기고 중앙 작업 영역만
    // 크게 보여준다. 카드 데이터/상태는 전혀 건드리지 않는 순수 화면 토글이다.
    toggleFocusMode: function() {
        const app = document.getElementById('post-builder-app');
        const btn = document.getElementById('pb_btn_focus_toggle');
        if (!app) return;
        const on = app.classList.toggle('pb-focus-mode');
        if (btn) btn.classList.toggle('active', on);
        this._saveUiState();
    },

    copyHtml: function() {
        // HTML 미리보기(pb_preview_html)의 실제 마크업을 복사한다. 이전에는 텍스트
        // 미리보기(pb_preview_text)를 복사하고 있어 "HTML 복사" 버튼인데 서식 없는
        // 텍스트가 복사되는 문제가 있었다.
        const html = document.getElementById('pb_preview_html').innerHTML;
        const self = this;

        // navigator.clipboard.writeText()는 HTTPS(보안 컨텍스트)에서만 동작한다.
        // showform.kr이 HTTP로 운영되고 있어 이 API가 조용히 실패하고 아무 반응이
        // 없었다(성공/실패 토스트 모두 없음). document.execCommand 기반 폴백을 추가한다.
        function fallbackCopy(str) {
            const ta = document.createElement('textarea');
            ta.value = str;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            let ok = false;
            try {
                ok = document.execCommand('copy');
            } catch (e) {
                ok = false;
            }
            document.body.removeChild(ta);
            return ok;
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(html).then(function() {
                self._toast('HTML 코드가 복사되었습니다.', 'success');
            }).catch(function() {
                if (fallbackCopy(html)) {
                    self._toast('HTML 코드가 복사되었습니다.', 'success');
                } else {
                    self._toast('복사에 실패했습니다. 브라우저에서 클립보드 접근을 허용해주세요.', 'error');
                }
            });
        } else {
            if (fallbackCopy(html)) {
                self._toast('HTML 코드가 복사되었습니다.', 'success');
            } else {
                self._toast('복사에 실패했습니다. 브라우저에서 클립보드 접근을 허용해주세요.', 'error');
            }
        }
    },

    /* ==========================================
     * 간단 모달 헬퍼 - 기존 라이브러리 모달(pb-library-overlay)과 별개로,
     * 정적 마크업 없이도 필요할 때 바로 띄울 수 있는 오버레이/모달 하나만 재사용한다.
     * ========================================== */
    _showSimpleModal: function(title, bodyHtml, opts) {
        opts = opts || {};
        let overlay = document.getElementById('pb_simple_modal_overlay');
        let modal = document.getElementById('pb_simple_modal');
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'pb_simple_modal_overlay';
            overlay.style.cssText = 'display:none; position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:9998;';
            overlay.onclick = () => Builder._closeSimpleModal();
            document.body.appendChild(overlay);
        }
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'pb_simple_modal';
            modal.style.cssText = 'display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%); z-index:9999; background:#fff; border-radius:8px; box-shadow:0 10px 40px rgba(0,0,0,0.25); max-width:640px; width:92%; max-height:80vh; overflow:hidden; display:flex; flex-direction:column;';
            document.body.appendChild(modal);
        }
        modal.innerHTML = `
            <div style="display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid #e2e8f0;">
                <strong id="pb_simple_modal_title" style="font-size:1rem;">${this._escapeHtml(title)}</strong>
                <span onclick="Builder._closeSimpleModal()" style="cursor:pointer; font-size:1.3rem; color:#94a3b8; line-height:1;">&times;</span>
            </div>
            <div id="pb_simple_modal_body" style="padding:16px 18px; overflow-y:auto; -webkit-overflow-scrolling:touch;">${bodyHtml}</div>
        `;

        // 배경 스크롤 방지 - 모달이 열려 있는 동안 뒤 페이지가 같이 스크롤되지 않게 하고,
        // 닫을 때 원래 스크롤 위치로 되돌린다(모바일에서 특히 체감이 크다).
        this._simpleModalScrollY = window.scrollY;
        document.body.style.position = 'fixed';
        document.body.style.top = '-' + this._simpleModalScrollY + 'px';
        document.body.style.width = '100%';

        overlay.style.display = 'block';
        modal.style.display = 'flex';

        if (!this._simpleModalEscBound) {
            this._simpleModalEscBound = true;
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const m = document.getElementById('pb_simple_modal');
                    if (m && m.style.display !== 'none') Builder._closeSimpleModal();
                }
            });
        }
    },

    _closeSimpleModal: function() {
        const overlay = document.getElementById('pb_simple_modal_overlay');
        const modal = document.getElementById('pb_simple_modal');
        if (overlay) overlay.style.display = 'none';
        if (modal) modal.style.display = 'none';

        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        if (typeof this._simpleModalScrollY === 'number') {
            window.scrollTo(0, this._simpleModalScrollY);
        }
    },



    // 메인 카드 그리드(renderStructureList)의 "샘플 보기" 링크가 showLibrarySample()을
    // 호출하도록 되어 있는데 그 이름의 함수가 없었다 - 클릭해도 아무 반응이 없던 이유.
    // href="#"라 preventDefault도 같이 해준다(안 하면 페이지 맨 위로 스크롤됨).
    showLibrarySample: function(event, id) {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        this.openSampleModal(id);
    },

    /* ==========================================
     * 샘플 보기 - 전개 구조 카드마다 저장된 sample_title/sample_content를 보여준다.
     * 이전까지는 이 버튼 자체가 없어 "샘플 보기 → 내용 없음"으로 보였다.
     * ========================================== */
    openSampleModal: function(id) {
        const s = this.libraryItems.structure_template.find(item => item.id == id);
        if (!s) return;
        // innerHTML의 "콘텐츠"로 들어가는 값이므로 _escapeAttr가 아니라 _escapeHtml을 써야
        // <, >까지 이스케이프되어, sample_content에 <script>나 태그가 들어있어도 실행되지 않는다.
        const sampleTitle = s.sample_title ? this._escapeHtml(s.sample_title) : '(등록된 샘플 제목이 없습니다)';
        const sampleContent = s.sample_content ? this._escapeHtml(s.sample_content).replace(/\n/g, '<br>') : '(등록된 샘플 본문이 없습니다)';
        const outline = s.content_json && Array.isArray(s.content_json.sections)
            ? s.content_json.sections.map((sec, i) => `${i + 1}. ${this._escapeHtml(sec.title)}${sec.desc ? ' - ' + this._escapeHtml(sec.desc) : ''}`).join('<br>')
            : this._escapeHtml(s.content || '').replace(/\n/g, '<br>');

        const body = `
            <div style="font-size:0.85rem; color:#475569; line-height:1.6; max-height:60vh; overflow-y:auto;">
                <div style="margin-bottom:10px;"><strong>적용 카테고리</strong><br>${this._escapeHtml(s.category || '공통')}</div>
                <div style="margin-bottom:10px;"><strong>전개 순서</strong><br>${outline}</div>
                <div style="margin-bottom:10px;"><strong>샘플 제목</strong><br>${sampleTitle}</div>
                <div style="margin-bottom:14px; word-break:break-word;"><strong>샘플 도입부/요약</strong><br>${sampleContent}</div>
                <div style="text-align:right;">
                    <button type="button" class="btn btn_02" onclick="Builder.onStructureTemplateChange(${s.id}); Builder._closeSimpleModal();" style="padding:6px 14px;">이 구조 적용</button>
                    <button type="button" class="btn" onclick="Builder._closeSimpleModal();" style="padding:6px 14px;">닫기</button>
                </div>
            </div>
        `;
        this._showSimpleModal(`[${this._escapeHtml(s.title)}] 샘플 보기`, body);
    },

    /* ==========================================
     * 구조 카테고리 보기 - 카테고리명/개수만 보여주고 클릭하면 창이 닫히던 방식에서,
     * 카테고리를 누르면 그 자리에서 소속 구조 목록이 아코디언으로 펼쳐지고 그 안에서
     * 바로 라디오로 고른 뒤 "이 구조 적용"까지 끝내는 방식으로 바꾼다. 구조는 여러 개를
     * 조합하는 개념이 아니라(하나의 완전한 전개 틀) 항상 단일 선택이므로, 추가지시
     * 라이브러리의 "이어 붙이기/바꿔 넣기"는 여기 적용하지 않는다 - "이 구조 적용"
     * 하나로 확정한다.
     * ========================================== */
    // post_builder.php의 "구조 카테고리 카드로 보기" 버튼은 openStructureCategoryModal()을
    // 호출하도록 되어 있는데(다른 작업자가 버튼 markup을 이 이름으로 바꿔놓음), 정작 함수는
    // 여기 openIndustryModal이라는 이름으로만 존재해서 버튼을 눌러도 아무 반응이 없었다.
    // 함수명을 실제 호출부와 맞춘다.
    openStructureCategoryModal: function() {
        return this.openIndustryModal();
    },

    openIndustryModal: function() {
        this._industryModalExpanded = new Set();
        this._industryModalPendingId = this.structureTemplate || 0;
        this._industryModalKeyword = '';

        const body = `
            <input type="text" id="pb_industry_search" class="frm_input" placeholder="카테고리·구조명·설명 검색" style="width:100%; margin-bottom:10px;"
                   oninput="Builder._industryModalKeyword = this.value; Builder._renderIndustryAccordion();">
            <div style="text-align:right; margin-bottom:8px;">
                <span onclick="Builder._expandAllIndustryCategories()" style="font-size:0.78rem; color:#4f46e5; text-decoration:underline; cursor:pointer;">전체 펼쳐보기</span>
            </div>
            <div id="pb_industry_list" style="max-height:50vh; overflow-y:auto;"></div>
            <div style="display:flex; justify-content:space-between; align-items:center; margin-top:12px; padding-top:10px; border-top:1px solid #e2e8f0;">
                <span id="pb_industry_selected_label" style="font-size:0.82rem; color:#475569; font-weight:bold;"></span>
                <div>
                    <button type="button" class="btn" onclick="Builder._closeSimpleModal();" style="padding:6px 14px;">취소</button>
                    <button type="button" class="btn_submit btn" style="padding:6px 14px; background:#4f46e5; border-color:#4338ca; color:#fff;" onclick="Builder._applyIndustryModalSelection()">이 구조 적용</button>
                </div>
            </div>
        `;
        this._showSimpleModal('구조 카테고리 보기', body);
        this._renderIndustryAccordion();
    },

    _expandAllIndustryCategories: function() {
        const structures = this.libraryItems.structure_template;
        const cats = [...new Set(structures.map(s => s.category || '공통'))];
        this._industryModalExpanded = new Set(cats);
        this._renderIndustryAccordion();
    },

    _renderIndustryAccordion: function() {
        const listEl = document.getElementById('pb_industry_list');
        if (!listEl) return;

        const kw = (this._industryModalKeyword || '').trim().toLowerCase();
        const structures = this.libraryItems.structure_template;

        // 검색은 카테고리명뿐 아니라 구조명·설명(전개 단계 원문)까지 대상으로 한다.
        const matches = (s) => {
            if (!kw) return true;
            const cat = (s.category || '').toLowerCase();
            const title = (s.title || '').toLowerCase();
            const desc = (s.content || '').toLowerCase();
            return cat.includes(kw) || title.includes(kw) || desc.includes(kw);
        };

        const byCategory = {};
        structures.forEach(s => {
            if (!matches(s)) return;
            const cat = s.category || '공통';
            if (!byCategory[cat]) byCategory[cat] = [];
            byCategory[cat].push(s);
        });

        // 검색어가 있으면 결과가 있는 카테고리는 자동으로 펼친다.
        if (kw) {
            Object.keys(byCategory).forEach(c => this._industryModalExpanded.add(c));
        }

        const cats = Object.keys(byCategory).sort();
        if (cats.length === 0) {
            listEl.innerHTML = '<div style="color:#94a3b8; font-size:0.85rem; padding:10px 0;">검색 조건에 맞는 글 전개 구조가 없습니다.</div>';
            this._updateIndustrySelectedLabel();
            return;
        }

        listEl.innerHTML = cats.map(cat => {
            const items = byCategory[cat];
            const isOpen = this._industryModalExpanded.has(cat);
            const itemsHtml = items.map(s => {
                const outline = s.content_json && Array.isArray(s.content_json.sections)
                    ? s.content_json.sections.map(sec => sec.title).join(' → ')
                    : '';
                const checked = this._industryModalPendingId === parseInt(s.id, 10) ? 'checked' : '';
                return `
                    <label style="display:flex; align-items:flex-start; gap:8px; padding:8px 10px; border-radius:6px; cursor:pointer;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                        <input type="radio" name="pb_industry_structure_radio" value="${s.id}" ${checked}
                               onchange="Builder._industryModalPendingId = ${s.id}; Builder._updateIndustrySelectedLabel();" style="margin-top:3px;">
                        <span style="flex:1;">
                            <span style="font-weight:bold; font-size:0.88rem; color:#334155;">${this._escapeHtml(s.title)}</span><br>
                            ${outline ? `<span style="font-size:0.78rem; color:#64748b;">${this._escapeHtml(outline)}</span><br>` : ''}
                            <span onclick="event.preventDefault(); event.stopPropagation(); Builder.openSampleModal(${s.id});" style="font-size:0.75rem; color:#4f46e5; text-decoration:underline; cursor:pointer;">샘플 보기</span>
                        </span>
                    </label>`;
            }).join('');

            return `
                <div style="border:1px solid #e2e8f0; border-radius:6px; margin-bottom:8px; overflow:hidden;">
                    <div onclick="Builder._toggleIndustryCategory('${this._escapeAttr(cat).replace(/'/g, "\\'")}')"
                         style="display:flex; justify-content:space-between; align-items:center; padding:10px 12px; background:#f8fafc; cursor:pointer;">
                        <span style="font-size:0.9rem; font-weight:bold; color:#334155;">${isOpen ? '▼' : '▶'} ${this._escapeHtml(cat)}</span>
                        <span style="font-size:0.8rem; color:#64748b;">${items.length}개 구조</span>
                    </div>
                    ${isOpen ? `<div style="padding:6px 8px;">${itemsHtml}</div>` : ''}
                </div>`;
        }).join('');

        this._updateIndustrySelectedLabel();
    },

    _toggleIndustryCategory: function(cat) {
        if (this._industryModalExpanded.has(cat)) {
            this._industryModalExpanded.delete(cat);
        } else {
            this._industryModalExpanded.add(cat);
        }
        this._renderIndustryAccordion();
    },

    _updateIndustrySelectedLabel: function() {
        const labelEl = document.getElementById('pb_industry_selected_label');
        if (!labelEl) return;
        const s = this.libraryItems.structure_template.find(item => parseInt(item.id, 10) === this._industryModalPendingId);
        labelEl.innerText = s ? `선택됨: ${s.title}` : '선택된 구조 없음';
    },

    _applyIndustryModalSelection: function() {
        if (!this._industryModalPendingId) {
            alert('적용할 글 전개 구조를 선택해 주세요.');
            return;
        }
        // 이미 다른 구조가 적용되어 있었다면, 교체됨을 명확히 알린다(요청사항: 확인창).
        if (this.structureTemplate && this.structureTemplate !== this._industryModalPendingId) {
            if (!confirm('현재 적용된 글 전개 구조를 선택한 구조로 교체하시겠습니까?')) return;
        }
        this.onStructureTemplateChange(this._industryModalPendingId);
        this._closeSimpleModal();
        const s = this.libraryItems.structure_template.find(item => parseInt(item.id, 10) === this._industryModalPendingId);
        this._toast(s ? `'${s.title}' 글 전개 구조가 적용되었습니다.` : '구조가 적용되었습니다.', 'success');
    },

    /* ==========================================
     * AI 지시문 미리보기 - "체크했는데 AI가 반영 안 했다"를 확인할 수 있도록,
     * generate_all_cards가 실제로 조립할 sys_prompt를 AI 호출 없이 미리 보여준다.
     * ========================================== */
    // AI 지시문 미리보기 모달의 복사 버튼 3개(시스템/사용자/전체)가 공용으로 쓴다.
    // copyHtml()의 폴백 복사 로직과 동일한 방식을 재사용한다(HTTP 환경에서 clipboard API가
    // 조용히 실패하는 문제가 이미 한 번 있었다).
    _copyPreviewText: function(which) {
        const store = this._lastPreviewPrompt;
        if (!store) return;
        const text = which === 'sys' ? store.sys : (which === 'user' ? store.user : store.full);
        const label = which === 'sys' ? '시스템 지시문을' : (which === 'user' ? '사용자 지시문을' : 'AI 지시문을');
        const self = this;

        function fallbackCopy(str) {
            const ta = document.createElement('textarea');
            ta.value = str;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            let ok = false;
            try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
            document.body.removeChild(ta);
            return ok;
        }

        function done(ok) {
            self._toast(ok ? (label + ' 복사했습니다.') : '복사에 실패했습니다. 브라우저에서 클립보드 접근을 허용해주세요.', ok ? 'success' : 'error');
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => done(true)).catch(() => done(fallbackCopy(text)));
        } else {
            done(fallbackCopy(text));
        }
    },

    previewPrompt: function() {
        // 빠르게 여러 번 눌러도 요청이 하나만 나가도록 한다(테스트 10 - 중복 클릭 방지).
        if (this._previewPromptBusy) return;
        this._previewPromptBusy = true;

        const rawMaterial = document.getElementById('pb_raw_material') ? document.getElementById('pb_raw_material').value.trim() : '';
        const checkedRules = Array.from(document.querySelectorAll('.pb-chk-generation-rule:checked')).map(el => el.value);
        const extraInstruction = document.getElementById('pb_extra_instruction') ? document.getElementById('pb_extra_instruction').value.trim() : '';

        const payload = new URLSearchParams();
        payload.append('action', 'preview_prompt');
        payload.append('project_id', this.projectId);
        payload.append('raw_material', rawMaterial);
        payload.append('structure_template', this.structureTemplate);
        payload.append('target_length', this.targetLength);
        payload.append('selected_rule_ids', JSON.stringify(checkedRules));
        payload.append('extra_instruction', extraInstruction);
        payload.append('char_per_line', this.charPerLine || 60);

        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            this._previewPromptBusy = false;
            if (!res.ok) {
                this._toast(res.error || '지시문 미리보기를 불러오지 못했습니다.', 'error');
                return;
            }
            const sm = res.summary;
            // 복사 버튼이 DOM에서 다시 읽지 않고 원본 문자열을 그대로 쓰도록 보관한다
            // (textarea에 들어간 값은 HTML 엔티티로 escape돼 있어 그대로 복사하면 안 된다).
            this._lastPreviewPrompt = { sys: res.sys_prompt, user: res.user_prompt, full: res.full_prompt };
            const body = `
                <div style="font-size:0.85rem; color:#475569; margin-bottom:10px; display:flex; flex-wrap:wrap; gap:12px;">
                    <span>전개 구조: <strong>${sm.structure}개</strong> (${this._escapeHtml(sm.structure_name)})</span>
                    <span>생성 조건: <strong>${sm.generation_conditions}개</strong></span>
                    <span>추가 지시: <strong>${sm.additional_instruction}개</strong></span>
                    <span>총 길이: <strong>${sm.total_length}자</strong></span>
                </div>
                <textarea readonly style="width:100%; height:340px; font-size:0.8rem; font-family:monospace; padding:10px; border:1px solid #cbd5e1; border-radius:6px; resize:vertical;">${this._escapeHtml(res.full_prompt)}</textarea>
                <div style="text-align:right; margin-top:10px; display:flex; gap:8px; justify-content:flex-end; flex-wrap:wrap;">
                    <button type="button" class="btn btn_02" onclick="Builder._copyPreviewText('sys')" style="padding:6px 14px;">시스템 지시문 복사</button>
                    <button type="button" class="btn btn_02" onclick="Builder._copyPreviewText('user')" style="padding:6px 14px;">사용자 지시문 복사</button>
                    <button type="button" class="btn btn_02" onclick="Builder._copyPreviewText('full')" style="padding:6px 14px;">전체 복사</button>
                    <button type="button" class="btn" onclick="Builder._closeSimpleModal();" style="padding:6px 14px;">닫기</button>
                </div>
            `;
            this._showSimpleModal('AI에 전달될 최종 지시문 미리보기', body);
        })
        .catch(() => {
            this._previewPromptBusy = false;
            this._toast('통신 오류로 지시문 미리보기를 불러오지 못했습니다.', 'error');
        });
    },

    /* ==========================================
     * 1단계 필수값 검사 - 화면에 "* (필수)"로 표시해 놓고 실제로는 검사하지 않던
     * 문제를 고친다. 2단계 이상으로 "전진"할 때만 검사하고, 뒤로 가거나 같은 단계
     * 안에서 탭을 눌러 재진입하는 경우는 막지 않는다.
     * ========================================== */
    // 카드를 체크하면 화면엔 "선택된 구조: N개"가 뜨지만, 그건 아직 "체크만 한"
    // stagedStructures 상태이지 실제 "적용된" pb_selected_structure_ids가 아니다.
    // 검증은 반드시 실제 적용 필드(pb_selected_structure_ids)를 기준으로 해야 하는데,
    // this.structureTemplate(옛날에 쓰던 단일 값, 지금은 갱신되지 않음)을 보고 있어서
    // 체크해도 "구조를 선택하라"는 경고가 계속 뜨는 버그가 있었다.
    _getAppliedStructureIds: function() {
        const idsEl = document.getElementById('pb_selected_structure_ids');
        return idsEl ? idsEl.value.split(',').filter(Boolean) : [];
    },

    _validateStep1: function() {
        const missing = [];
        const rawMaterial = document.getElementById('pb_raw_material') ? document.getElementById('pb_raw_material').value.trim() : '';
        if (!rawMaterial) missing.push({ label: '글감', focusId: 'pb_raw_material' });
        if (this._getAppliedStructureIds().length === 0) missing.push({ label: '글 전개 구조', focusId: 'pb_structure_list' });
        return { ok: missing.length === 0, missing: missing };
    },

    _canAdvanceFromStep: function(fromStep, toStep) {
        // 전진(숫자가 커지는 이동)일 때만 검사한다 - 뒤로가기/탭 재클릭은 항상 허용.
        if (toStep <= fromStep) return true;
        if (fromStep === 1) {
            // 체크만 해두고 "바로 사용" 버튼을 따로 누르지 않아도 되도록, 적용된 구조가
            // 없는데 체크(staged)된 구조는 있으면 다음 단계로 넘어가기 직전에 자동으로
            // 적용한다 - 사용자가 이중으로 버튼을 눌러야 하는 절차를 없앤다.
            if (this._getAppliedStructureIds().length === 0 && this.stagedStructures && this.stagedStructures.length > 0) {
                this.applyStagedStructures('replace');
            }

            const v = this._validateStep1();
            if (!v.ok) {
                // 글감 자체가 비어있는 경우, "글감을 입력하세요"라는 오류만 던지지 않고
                // AI 추천/라이브러리/직접입력 중 고를 수 있는 안내를 보여준다(빈 화면 방지).
                const onlyMissingRawMaterial = v.missing.length === 1 && v.missing[0].label === '글감';
                if (onlyMissingRawMaterial) {
                    this._showEmptyTopicChoiceModal();
                    return false;
                }

                // 항목이 1개면 구체적으로, 여러 개면 목록으로 안내한다("이동할 수 없습니다"
                // 같은 뭉뚱그린 문구는 어디를 고쳐야 할지 알 수 없어 금지 - 지시서 4.4).
                // "구조을(를)"처럼 조사가 안 맞는 자동 조합 대신, 항목별로 자연스러운 문구를 쓴다.
                const phraseMap = { '글감': '글감을 입력해 주세요.', '글 전개 구조': '글 전개 구조를 1개 이상 선택해 주세요.' };
                const msg = v.missing.length === 1
                    ? toStep + '단계로 이동하려면 ' + (phraseMap[v.missing[0].label] || (v.missing[0].label + '을(를) 입력해 주세요.'))
                    : '다음 항목을 먼저 입력해 주세요.\n- ' + v.missing.map(m => m.label).join('\n- ');
                alert(msg);
                const target = document.getElementById(v.missing[0].focusId);
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    if (typeof target.focus === 'function') target.focus();
                }
                return false; // 현재 단계에 그대로 머문다 - 빈 화면으로 전환되지 않는다.
            }
        }
        return true;
    },

    /* ==========================================
     * "글감 없이 시작" - AI 글감 자동 발굴
     * ========================================== */
    _usedTopicTitles: [],

    toggleTopicIdeaPanel: function(show) {
        const panel = document.getElementById('pb_topic_idea_panel');
        const hint = document.getElementById('pb_topic_empty_hint');
        if (panel) panel.style.display = show ? 'block' : 'none';
        if (hint) hint.style.display = show ? 'none' : 'block';
    },

    _showEmptyTopicChoiceModal: function() {
        this._showSimpleModal('아직 입력된 글감이 없습니다', `
            <p style="color:#64748b; font-size:0.9rem; margin-bottom:16px;">아래 중 하나를 선택해 주세요.</p>
            <div style="display:flex; flex-direction:column; gap:8px;">
                <button type="button" class="btn btn_submit" style="background:#4f46e5; border-color:#4338ca; color:#fff; text-align:left; padding:12px 16px;" onclick="Builder._closeSimpleModal(); Builder.toggleTopicIdeaPanel(true); document.getElementById('pb_topic_idea_panel').scrollIntoView({behavior:'smooth'});">✨ AI에게 글감 추천받기</button>
                <button type="button" class="btn btn_02" style="text-align:left; padding:12px 16px;" onclick="Builder._closeSimpleModal(); Builder.openLibraryModal('additional_instruction');">📚 글감 라이브러리 열기</button>
                <button type="button" class="btn btn_02" style="text-align:left; padding:12px 16px;" onclick="Builder._closeSimpleModal(); document.getElementById('pb_raw_material').focus();">✍️ 직접 입력 계속하기</button>
            </div>
        `);
    },

    generateTopicIdeas: function() {
        const btn = document.getElementById('pb_topic_generate_btn');
        const val = id => (document.getElementById(id) ? document.getElementById(id).value.trim() : '');
        const industry = val('pb_topic_industry');
        const product = val('pb_topic_product');
        const keywords = val('pb_topic_keywords');
        if (!industry && !product && !keywords) {
            alert('업종, 상품·서비스, 핵심 키워드 중 최소 1개는 입력해 주세요.');
            return;
        }
        const countEl = document.querySelector('input[name="pb_topic_count"]:checked');

        btn.disabled = true;
        btn.textContent = '생성 중...';

        const payload = new URLSearchParams();
        payload.append('action', 'generate_topic_ideas');
        payload.append('project_id', this.projectId);
        payload.append('industry', industry);
        payload.append('region', val('pb_topic_region'));
        payload.append('product', product);
        payload.append('purpose', val('pb_topic_purpose'));
        payload.append('target_customer', val('pb_topic_target'));
        payload.append('content_type', val('pb_topic_content_type'));
        payload.append('season', val('pb_topic_season'));
        payload.append('brand_name', val('pb_topic_brand'));
        payload.append('must_keywords', keywords);
        payload.append('exclude', val('pb_topic_exclude'));
        payload.append('count', countEl ? countEl.value : '10');
        payload.append('exclude_titles', JSON.stringify(this._usedTopicTitles));

        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            btn.disabled = false;
            btn.textContent = '글감 추천 생성';
            if (!res.ok) {
                this._toast(res.error || '글감 추천 생성에 실패했습니다.', 'error');
                return;
            }
            this._renderTopicIdeas(res.ideas || []);
        })
        .catch(() => {
            btn.disabled = false;
            btn.textContent = '글감 추천 생성';
            this._toast('통신 오류가 발생했습니다.', 'error');
        });
    },

    _renderTopicIdeas: function(ideas) {
        const container = document.getElementById('pb_topic_idea_results');
        if (!container) return;
        if (ideas.length === 0) {
            container.style.display = 'none';
            return;
        }
        this._lastTopicIdeas = ideas;
        const cardsHtml = ideas.map(idea => `
            <div class="pb-ai-panel" style="padding:14px 16px; background:#fff; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:10px;">
                <div style="font-weight:bold; font-size:0.95rem; color:#1e293b; margin-bottom:6px;">${this._escapeHtml(idea.title || '')}</div>
                <div style="font-size:0.82rem; color:#64748b; line-height:1.6;">
                    <div><strong>핵심 주제:</strong> ${this._escapeHtml(idea.topic || '')}</div>
                    <div><strong>추천 키워드:</strong> ${this._escapeHtml((idea.keywords || []).join(', '))}</div>
                    <div><strong>예상 독자:</strong> ${this._escapeHtml(idea.target_reader || '')}</div>
                    <div><strong>글의 목적:</strong> ${this._escapeHtml(idea.purpose || '')}</div>
                    <div><strong>추천 글 구조:</strong> ${this._escapeHtml(idea.structure || '')}</div>
                    <div><strong>차별화 포인트:</strong> ${this._escapeHtml(idea.differentiator || '')}</div>
                </div>
                <div style="display:flex; gap:6px; margin-top:10px; justify-content:flex-end;">
                    <button type="button" class="btn btn_02" onclick="Builder._removeTopicIdea('${idea.id}')">삭제</button>
                    <button type="button" class="btn btn_submit" style="background:#4f46e5; border-color:#4338ca; color:#fff;" onclick="Builder._applyTopicIdea('${idea.id}')">이 글감 사용</button>
                </div>
            </div>
        `).join('');
        container.innerHTML = `<div style="font-size:0.85rem; font-weight:bold; color:#475569; margin:10px 0;">추천 글감 (${ideas.length}개)</div>` + cardsHtml;
        container.style.display = 'block';
    },

    _removeTopicIdea: function(id) {
        this._lastTopicIdeas = (this._lastTopicIdeas || []).filter(i => i.id !== id);
        this._renderTopicIdeas(this._lastTopicIdeas);
    },

    _applyTopicIdea: function(id) {
        const idea = (this._lastTopicIdeas || []).find(i => i.id === id);
        if (!idea) return;

        const rawMaterial = [
            '제목: ' + (idea.title || ''),
            '핵심 주제: ' + (idea.topic || ''),
            '차별화 포인트: ' + (idea.differentiator || '')
        ].filter(Boolean).join('\n');
        const rawEl = document.getElementById('pb_raw_material');
        if (rawEl) rawEl.value = rawMaterial;

        const extraEl = document.getElementById('pb_extra_instruction');
        if (extraEl) {
            const extra = [
                idea.target_reader ? ('예상 독자: ' + idea.target_reader) : '',
                idea.purpose ? ('글 목적: ' + idea.purpose) : '',
                idea.structure ? ('추천 전개 구조 참고: ' + idea.structure) : ''
            ].filter(Boolean).join('\n');
            extraEl.value = extra;
        }

        this._usedTopicTitles.push(idea.title);
        this.toggleTopicIdeaPanel(false);
        const results = document.getElementById('pb_topic_idea_results');
        if (results) results.style.display = 'none';
        if (typeof PromptManager !== 'undefined') PromptManager.triggerUpdate();
        this._toast('선택한 글감이 반영되었습니다.', 'success');
        if (rawEl) rawEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
};

document.addEventListener('DOMContentLoaded', () => { Builder.init(); });
// Esc로 집중 편집 모드 종료 (모드가 꺼져 있으면 아무 동작 안 함)
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const app = document.getElementById('post-builder-app');
        if (app && app.classList.contains('pb-focus-mode')) {
            Builder.toggleFocusMode();
        }
    }
});
