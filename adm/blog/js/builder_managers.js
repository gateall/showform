
(function(window) {
    if (!window.Builder) window.Builder = {};

    // 라이브러리(글전개 구조 / AI 생성조건) 액션은 post_builder_ajax.php가 아니라
    // manager/blog/ajax.builder.php에만 있다. 그쪽을 부르지 않으면 'Unknown action'이
    // 돌아오고, 두 매니저가 필터 없는 공용 목록으로 폴백해 서로의 카드가 섞여 보인다.
    // libraryUrl이 없는 옛 페이지에서는 기존처럼 ajaxUrl로 폴백한다.
    function libraryAjaxUrl() {
        var cfg = window.POST_BUILDER_CONFIG || {};
        return cfg.libraryUrl || cfg.ajaxUrl;
    }

    // 세션이 풀리면 AJAX 엔드포인트가 JSON 대신 로그인 화면 HTML을 돌려준다.
    // 그대로 r.json()에 넘기면 조용히 reject되고, 아무도 그 rejection을 받지 않아서
    // 버튼을 눌러도 화면에 아무 변화가 없다("안 열린다"의 정체). 여기서 본문을
    // 먼저 읽어보고 무슨 일이 났는지 말이 되는 메시지로 바꿔 던진다.
    function pbReadJson(response) {
        return response.text().then(function(text) {
            try {
                return JSON.parse(text);
            } catch (e) {
                if (/<!doctype|<html/i.test(text)) {
                    throw new Error('로그인이 풀렸거나 접근 권한이 없습니다. 새로고침 후 다시 로그인해 주세요.');
                }
                throw new Error('서버 응답을 해석할 수 없습니다: ' + text.slice(0, 80));
            }
        });
    }

    // 목록을 못 불러왔다고 모달을 닫아버리면 사용자는 이유를 알 수 없다.
    // 모달은 열어둔 채 그 자리에 이유를 적는다.
    function pbShowLoadError(containerId, message) {
        var el = document.getElementById(containerId);
        if (!el) { alert(message); return; }
        el.innerHTML = '<div style="padding:24px; text-align:center; color:#b91c1c; font-size:14px; line-height:1.6;">'
            + '목록을 불러오지 못했습니다.<br><span style="color:#64748b; font-size:13px;">'
            + String(message).replace(/</g, '&lt;') + '</span></div>';
    }

    // =========================================================================
    // Structure Manager (글전개 구조 관리)
    // =========================================================================
    Builder.StructureManager = {
        state: {
            items: [],
            categories: [],
            selectedIds: [], // array of IDs (single select mode will just use index 0)
            currentTab: 'select', // select, manage, category
            filter: {
                category: '',
                status: '1',
                search: '',
                favOnly: false,
                sort: 'sort_asc'
            }
        },

        openModal: function(defaultTab = 'select') {
            this.state.selectedIds = [];
            this.state.filter = { category: '', status: '1', search: '', favOnly: false, sort: 'sort_asc' };

            // AIConditionManager.openModal과 같은 이유로 모달을 먼저 띄운다.
            const setVal = (id, value) => { const el = document.getElementById(id); if (el) el.value = value; };
            const setChecked = (id, value) => { const el = document.getElementById(id); if (el) el.checked = value; };
            setVal('sm_filter_search', '');
            setVal('sm_filter_category', '');
            setVal('sm_filter_status', '1');
            setChecked('sm_filter_fav', false);
            setVal('sm_filter_sort', 'sort_asc');

            const formWrap = document.getElementById('sm_form_wrap');
            if (formWrap) formWrap.style.display = 'none';

            const overlay = document.getElementById('pb-library-overlay');
            if (overlay) overlay.style.display = 'block';
            const modal = document.getElementById('structureManagerModal');
            if (modal) modal.style.display = 'flex';
            this.switchTab(defaultTab);

            this.loadCategories(() => {
                this.loadItems(() => {
                    // 기 적용된 구조가 있다면 로드
                    const idsStr = document.getElementById('pb_selected_structure_ids');
                    if (idsStr && idsStr.value) {
                        this.state.selectedIds = idsStr.value.split(',').filter(Boolean);
                    } else if (Builder.selectedStructures && Builder.selectedStructures.length > 0) {
                        this.state.selectedIds = [...Builder.selectedStructures];
                    }
                    this.renderAccordion();
                    this.renderList();
                });
            });
        },

        closeModal: function() {
            document.getElementById('pb-library-overlay').style.display = 'none';
            document.getElementById('structureManagerModal').style.display = 'none';
        },

        switchTab: function(tab) {
            this.state.currentTab = tab;
            const tabs = ['select', 'manage', 'category'];
            tabs.forEach(t => {
                const btn = document.getElementById('sm_tab_btn_' + t);
                const panel = document.getElementById('sm_tab_' + t);
                if (btn) {
                    btn.style.borderBottomColor = (t === tab) ? '#3182ce' : 'transparent';
                    btn.style.color = (t === tab) ? '#3182ce' : '#718096';
                }
                if (panel) panel.style.display = (t === tab) ? (t === 'select' ? 'flex' : 'block') : 'none';
            });
        },

        loadItems: function(callback) {
            const formData = new FormData();
            formData.append('action', 'load_structures');
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) {
                        this.state.items = res.items || [];
                        if (callback) callback();
                    } else {
                        pbShowLoadError('sm_list', res.error || '알 수 없는 오류');
                    }
                })
                .catch(err => {
                    console.error('[StructureManager] 구조 목록 로드 실패:', err);
                    pbShowLoadError('sm_list', err.message || err);
                });
        },

        loadCategories: function(callback) {
            const formData = new FormData();
            formData.append('action', 'load_structure_categories');
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) {
                        this.state.categories = res.categories || [];
                        this.updateCategorySelects();
                    } else {
                        console.error('[StructureManager] 카테고리 로드 실패:', res.error);
                    }
                })
                .catch(err => { console.error('[StructureManager] 카테고리 로드 실패:', err); })
                // 카테고리 실패가 구조 목록까지 막지 않도록 성공/실패 무관하게 넘긴다.
                .then(() => { if (callback) callback(); });
        },

        updateCategorySelects: function() {
            const selects = [
                {id: 'sm_filter_category', defaultOption: '전체 카테고리 ▼', allowNew: false},
                {id: 'sm_category', defaultOption: '카테고리 선택 ▼', allowNew: true}
            ];
            selects.forEach(s => {
                const el = document.getElementById(s.id);
                if (!el) return;
                const val = el.value;
                el.innerHTML = `<option value="">${s.defaultOption}</option>`;
                this.state.categories.forEach(c => {
                    el.innerHTML += `<option value="${c.category_name}">${c.category_name}</option>`;
                });
                if (s.allowNew) el.innerHTML += `<option value="__NEW__">+ 새 카테고리 추가</option>`;
                el.value = val;
            });
            this.renderCategoryList();
        },

        // ---------- 탭: 선택 (Accordion) ----------
        renderAccordion: function() {
            const container = document.getElementById('sm_accordion_container');
            if(!container) return;
            
            const keyword = (document.getElementById('sm_accordion_search').value || '').toLowerCase();
            const groups = {};
            
            this.state.items.filter(item => item.is_active == 1).forEach(item => {
                if (keyword) {
                    if (!item.title.toLowerCase().includes(keyword) && 
                        !item.content.toLowerCase().includes(keyword) && 
                        !(item.category || '미분류').toLowerCase().includes(keyword)) return;
                }
                const cat = item.category || '미분류';
                if (!groups[cat]) groups[cat] = [];
                groups[cat].push(item);
            });
            
            let html = '';
            for (let cat in groups) {
                html += `<div class="pb-accordion-cat" style="margin-bottom:10px;">
                            <div style="background:#f1f5f9; padding:8px 12px; font-weight:bold; color:#334155; cursor:pointer; border-radius:6px; display:flex; justify-content:space-between; align-items:center;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display==='none'?'block':'none'">
                                <span>📁 ${cat} <span style="font-size:12px; color:#64748b; font-weight:normal;">(${groups[cat].length})</span></span>
                                <span style="color:#94a3b8; font-size:12px;">▼</span>
                            </div>
                            <div class="pb-accordion-content" style="display:block; padding:8px 0 8px 12px;">`;
                groups[cat].forEach(item => {
                    const isSelected = this.state.selectedIds.includes(item.id.toString());
                    html += `
                        <label style="display:block; padding:8px 10px; border:1px solid ${isSelected?'#3b82f6':'#e2e8f0'}; border-radius:6px; margin-bottom:6px; cursor:pointer; background:${isSelected?'#eff6ff':'#fff'}; transition:all 0.2s;">
                            <div style="display:flex; align-items:flex-start; gap:10px;">
                                <input type="radio" name="sm_select_radio" value="${item.id}" ${isSelected?'checked':''} onchange="Builder.StructureManager.toggleSelection('${item.id}')" style="margin-top:4px;">
                                <div style="flex:1;">
                                    <div style="font-weight:600; color:#1e293b; margin-bottom:4px;">
                                        ${item.is_favorite == 1 ? '★ ' : ''}${item.title}
                                        ${item.is_default_checked == 1 ? '<span style="background:#10b981; color:#fff; font-size:10px; padding:2px 4px; border-radius:4px; margin-left:4px;">기본</span>' : ''}
                                    </div>
                                    <div style="font-size:12px; color:#64748b; line-height:1.4;">${(item.description || item.content).substring(0, 100)}...</div>
                                </div>
                            </div>
                        </label>
                    `;
                });
                html += `</div></div>`;
            }
            container.innerHTML = html || '<div style="padding:20px; text-align:center; color:#94a3b8;">검색 결과가 없습니다.</div>';
            this.updateSelectionCount();
        },

        toggleSelection: function(id) {
            // 글전개 구조는 싱글 선택 모드
            this.state.selectedIds = [id.toString()];
            this.renderAccordion();
        },

        clearSelection: function() {
            this.state.selectedIds = [];
            this.renderAccordion();
        },

        updateSelectionCount: function() {
            const el = document.getElementById('sm_accordion_selected_count');
            if(el) el.innerText = this.state.selectedIds.length;
        },

        toggleAllAccordions: function(expand) {
            document.querySelectorAll('#sm_accordion_container .pb-accordion-content').forEach(el => {
                el.style.display = expand ? 'block' : 'none';
            });
        },

        applySelected: function(mode) {
            if (this.state.selectedIds.length === 0) {
                alert('적용할 구조를 선택해주세요.');
                return;
            }
            // 기존 post_builder.php와 builder.js의 글전개 구조 목록(Builder.selectedStructures) 연동
            if (mode === 'replace') {
                Builder.selectedStructures = [...this.state.selectedIds];
            } else {
                this.state.selectedIds.forEach(id => {
                    if (!Builder.selectedStructures.includes(id)) {
                        Builder.selectedStructures.push(id);
                    }
                });
            }
            
            // 전역 libraryItems에 구조 주입 (Builder.js 호환성)
            if (!Builder.libraryItems['structure_template']) {
                Builder.libraryItems['structure_template'] = [];
            }
            this.state.items.forEach(item => {
                if (!Builder.libraryItems['structure_template'].some(i => i.id == item.id)) {
                    // 호환성을 위해 property mapping
                    Builder.libraryItems['structure_template'].push({
                        ...item,
                        instruction_type: 'structure_template',
                        content_json: item.content ? JSON.parse(item.content || '{}') : null
                    });
                }
            });
            
            Builder.renderStructureList();
            this.closeModal();
        },

        // ---------- 탭: 관리 (Manage) ----------
        renderList: function() {
            const listEl = document.getElementById('sm_list');
            if(!listEl) return;
            
            const cat = document.getElementById('sm_filter_category').value;
            const status = document.getElementById('sm_filter_status').value;
            const search = document.getElementById('sm_filter_search').value.toLowerCase();
            const favOnly = document.getElementById('sm_filter_fav').checked;
            const sort = document.getElementById('sm_filter_sort').value;
            
            let filtered = this.state.items.filter(item => {
                if (cat && item.category !== cat) return false;
                if (status !== 'all' && item.is_active != status) return false;
                if (favOnly && item.is_favorite != 1) return false;
                if (search && !item.title.toLowerCase().includes(search) && !item.content.toLowerCase().includes(search)) return false;
                return true;
            });
            
            filtered.sort((a, b) => {
                if (sort === 'recent_used') return (b.last_used_at || '') > (a.last_used_at || '') ? 1 : -1;
                if (sort === 'most_used') return (b.usage_count || 0) - (a.usage_count || 0);
                if (sort === 'recent_added') return b.id - a.id;
                // 기본 정렬: 카테고리 > 정렬순서 > ID
                if (a.category !== b.category) return (a.category || '') > (b.category || '') ? 1 : -1;
                return a.id - b.id;
            });
            
            let html = '';
            filtered.forEach(item => {
                html += `
                    <div style="background:#fff; border:1px solid #e2e8f0; padding:15px; border-radius:8px; display:flex; justify-content:space-between; gap:15px; align-items:flex-start;">
                        <div style="flex:1;">
                            <div style="display:flex; gap:8px; margin-bottom:6px; align-items:center;">
                                <span style="background:#e2e8f0; color:#475569; padding:2px 6px; font-size:11px; border-radius:4px;">${item.category || '미분류'}</span>
                                <strong style="color:${item.is_active == 1 ? '#1e293b' : '#94a3b8'}; font-size:15px;">
                                    ${item.is_favorite == 1 ? '<span style="color:#eab308; cursor:pointer;" onclick="Builder.StructureManager.toggleFavorite('+item.id+',0)">★</span>' : '<span style="color:#cbd5e1; cursor:pointer;" onclick="Builder.StructureManager.toggleFavorite('+item.id+',1)">☆</span>'} 
                                    ${item.title}
                                </strong>
                                ${item.is_default_checked == 1 ? '<span style="background:#10b981; color:#fff; font-size:10px; padding:2px 4px; border-radius:4px;">기본</span>' : ''}
                                ${item.is_active == 0 ? '<span style="background:#ef4444; color:#fff; font-size:10px; padding:2px 4px; border-radius:4px;">사용중지</span>' : ''}
                            </div>
                            <div style="font-size:13px; color:#64748b; margin-bottom:8px; line-height:1.5;">${(item.description || item.content).substring(0, 150)}...</div>
                            <div style="font-size:11px; color:#94a3b8; display:flex; gap:10px;">
                                <span>사용 횟수: ${item.usage_count || 0}</span>
                                <span>최근 사용: ${item.last_used_at || '-'}</span>
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <button class="btn btn_02" style="padding:4px 10px; font-size:12px;" onclick="Builder.StructureManager.showForm(${item.id})">수정</button>
                            <button class="btn btn_02" style="padding:4px 10px; font-size:12px;" onclick="Builder.StructureManager.duplicateItem(${item.id})">복사</button>
                            <button class="btn btn_02" style="padding:4px 10px; font-size:12px; color:#ef4444; border-color:#fca5a5;" onclick="Builder.StructureManager.deleteItem(${item.id})">삭제</button>
                        </div>
                    </div>
                `;
            });
            listEl.innerHTML = html || '<div style="text-align:center; padding:30px; color:#94a3b8;">항목이 없습니다.</div>';
        },

        // ---------- 폼 (Create / Update) ----------
        showForm: function(id) {
            document.getElementById('sm_id').value = id;
            document.getElementById('sm_form_title').innerText = id ? '구조 수정' : '새 구조 등록';
            
            if (id === 0) {
                document.getElementById('sm_title').value = '';
                document.getElementById('sm_category').value = '';
                document.getElementById('sm_content').value = '';
                document.getElementById('sm_item_code').value = '';
                document.getElementById('sm_expected_length').value = '';
                document.getElementById('sm_description').value = '';
                document.getElementById('sm_recommended_purpose').value = '';
                document.getElementById('sm_recommended_industries').value = '';
                document.getElementById('sm_tags').value = '';
                document.getElementById('sm_is_default_checked').checked = false;
                document.getElementById('sm_is_favorite_checked').checked = false;
                document.getElementById('sm_is_active').checked = false;
            } else {
                const item = this.state.items.find(i => i.id == id);
                if (item) {
                    document.getElementById('sm_title').value = item.title;
                    document.getElementById('sm_category').value = item.category || '';
                    document.getElementById('sm_content').value = item.content;
                    document.getElementById('sm_item_code').value = item.item_code || '';
                    document.getElementById('sm_expected_length').value = item.expected_length || '';
                    document.getElementById('sm_description').value = item.description || '';
                    document.getElementById('sm_recommended_purpose').value = item.recommended_purpose || '';
                    document.getElementById('sm_recommended_industries').value = item.recommended_industries || '';
                    document.getElementById('sm_tags').value = item.tags || '';
                    document.getElementById('sm_is_default_checked').checked = item.is_default_checked == 1;
                    document.getElementById('sm_is_favorite_checked').checked = item.is_favorite == 1;
                    document.getElementById('sm_is_active').checked = item.is_active == 0; // 0 means inactive checkbox
                }
            }
            
            document.getElementById('sm_tab_manage').style.display = 'none';
            document.getElementById('sm_form_wrap').style.display = 'block';
        },

        hideForm: function() {
            document.getElementById('sm_form_wrap').style.display = 'none';
            document.getElementById('sm_tab_manage').style.display = 'block';
        },

        saveItem: function() {
            const formData = new FormData();
            formData.append('action', 'save_structure');
            formData.append('id', document.getElementById('sm_id').value);
            formData.append('title', document.getElementById('sm_title').value);
            formData.append('category', document.getElementById('sm_category').value);
            formData.append('content', document.getElementById('sm_content').value);
            formData.append('item_code', document.getElementById('sm_item_code').value);
            formData.append('expected_length', document.getElementById('sm_expected_length').value);
            formData.append('description', document.getElementById('sm_description').value);
            formData.append('recommended_purpose', document.getElementById('sm_recommended_purpose').value);
            formData.append('recommended_industries', document.getElementById('sm_recommended_industries').value);
            formData.append('tags', document.getElementById('sm_tags').value);
            formData.append('is_default_checked', document.getElementById('sm_is_default_checked').checked ? 'Y' : 'N');
            formData.append('is_favorite_checked', document.getElementById('sm_is_favorite_checked').checked ? 'Y' : 'N');
            formData.append('is_active', document.getElementById('sm_is_active').checked ? '0' : '1');

            if (!formData.get('title') || !formData.get('content')) {
                alert('제목과 내용을 입력해주세요.');
                return;
            }

            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) {
                        this.hideForm();
                        this.loadItems(() => { this.renderList(); });
                    } else {
                        alert(res.error || '저장에 실패했습니다.');
                    }
                })
                .catch(err => { console.error(err); alert('통신 오류'); });
        },

        deleteItem: function(id) {
            if (!confirm('정말 삭제하시겠습니까?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_structure');
            formData.append('id', id);
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) this.loadItems(() => { this.renderList(); });
                    else alert(res.error || '삭제 실패');
                });
        },

        duplicateItem: function(id) {
            const formData = new FormData();
            formData.append('action', 'duplicate_structure');
            formData.append('id', id);
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) this.loadItems(() => { this.renderList(); });
                    else alert(res.error || '복사 실패');
                });
        },

        toggleFavorite: function(id, isFav) {
            const formData = new FormData();
            formData.append('action', 'toggle_favorite_structure');
            formData.append('id', id);
            formData.append('is_favorite', isFav);
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) this.loadItems(() => { this.renderList(); });
                });
        },

        // ---------- 카테고리 관리 ----------
        saveCategory: function() {
            const catName = document.getElementById('sm_new_cat_name').value.trim();
            if (!catName) return alert('카테고리명을 입력하세요.');
            const formData = new FormData();
            formData.append('action', 'save_structure_category');
            formData.append('category_name', catName);
            
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) {
                        document.getElementById('sm_new_cat_name').value = '';
                        this.loadCategories();
                    } else {
                        alert(res.error || '실패');
                    }
                });
        },
        
        deleteCategory: function(id) {
            if (!confirm('정말 삭제하시겠습니까? 속한 항목은 미분류가 됩니다.')) return;
            const formData = new FormData();
            formData.append('action', 'delete_structure_category');
            formData.append('id', id);
            
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) this.loadCategories();
                    else alert(res.error || '실패');
                });
        },

        renderCategoryList: function() {
            const el = document.getElementById('sm_category_list');
            if(!el) return;
            let html = '';
            this.state.categories.forEach(c => {
                html += `
                    <div style="display:flex; justify-content:space-between; padding:8px 12px; background:#fff; border:1px solid #e2e8f0; border-radius:6px;">
                        <span>${c.category_name}</span>
                        <button class="btn btn_02" style="padding:2px 8px; font-size:12px;" onclick="Builder.StructureManager.deleteCategory(${c.id})">삭제</button>
                    </div>
                `;
            });
            el.innerHTML = html;
        }
    };


    // =========================================================================
    // AI Condition Manager (AI 글 생성 조건 관리)
    // =========================================================================
    Builder.AIConditionManager = {
        state: {
            items: [],
            categories: [],
            selectedIds: [], // Multi select
            currentTab: 'select'
        },

        openModal: function(defaultTab = 'select') {
            this.state.selectedIds = [];

            // 모달을 먼저 띄우고 목록은 그 다음에 채운다. 예전에는 로딩이 전부 끝난
            // 뒤에야 표시했는데, 중간 어느 단계든 실패하면 콜백 사슬이 끊겨 버튼을
            // 눌러도 화면에 아무 변화가 없었다. 창이 먼저 떠 있으면 최소한 실패
            // 사실을 그 안에 적을 수 있다.
            // getElementById 결과도 전부 확인한다 - 마크업이 한 버전 뒤처져 있으면
            // 여기서 TypeError가 나면서 역시 조용히 안 열린다.
            const setVal = (id, value) => { const el = document.getElementById(id); if (el) el.value = value; };
            const setChecked = (id, value) => { const el = document.getElementById(id); if (el) el.checked = value; };
            setVal('aic_filter_search', '');
            setVal('aic_filter_category', '');
            setVal('aic_filter_status', '1');
            setChecked('aic_filter_fav', false);
            setVal('aic_filter_sort', 'sort_asc');

            const formWrap = document.getElementById('aic_form_wrap');
            if (formWrap) formWrap.style.display = 'none';

            const overlay = document.getElementById('pb-library-overlay');
            if (overlay) overlay.style.display = 'block';
            const modal = document.getElementById('aiConditionManagerModal');
            if (modal) modal.style.display = 'flex';
            this.switchTab(defaultTab);

            this.loadCategories(() => {
                this.loadItems(() => {
                    // 기 적용된 조건 로드
                    const applied = Builder.generationConditions || [];
                    this.state.selectedIds = applied.map(c => c.id.toString());

                    this.renderAccordion();
                    this.renderList();
                });
            });
        },

        closeModal: function() {
            document.getElementById('pb-library-overlay').style.display = 'none';
            document.getElementById('aiConditionManagerModal').style.display = 'none';
        },

        switchTab: function(tab) {
            this.state.currentTab = tab;
            const tabs = ['select', 'manage', 'category'];
            tabs.forEach(t => {
                const btn = document.getElementById('aic_tab_btn_' + t);
                const panel = document.getElementById('aic_tab_' + t);
                if (btn) {
                    btn.style.borderBottomColor = (t === tab) ? '#3182ce' : 'transparent';
                    btn.style.color = (t === tab) ? '#3182ce' : '#718096';
                }
                if (panel) panel.style.display = (t === tab) ? (t === 'select' ? 'flex' : 'block') : 'none';
            });
        },

        loadItems: function(callback) {
            const formData = new FormData();
            formData.append('action', 'load_ai_conditions');
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) {
                        this.state.items = res.items || [];
                        if (callback) callback();
                    } else {
                        pbShowLoadError('aic_list', res.error || '알 수 없는 오류');
                    }
                })
                .catch(err => {
                    console.error('[AIConditionManager] 조건 목록 로드 실패:', err);
                    pbShowLoadError('aic_list', err.message || err);
                });
        },

        loadCategories: function(callback) {
            const formData = new FormData();
            formData.append('action', 'load_ai_condition_categories');
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) {
                        this.state.categories = res.categories || [];
                        this.updateCategorySelects();
                    } else {
                        console.error('[AIConditionManager] 카테고리 로드 실패:', res.error);
                    }
                })
                .catch(err => { console.error('[AIConditionManager] 카테고리 로드 실패:', err); })
                // 카테고리는 필터 드롭다운 채우기용이다. 못 불러왔다고 조건 목록까지
                // 막으면 모달이 통째로 비어버리므로, 성공/실패 무관하게 다음으로 넘긴다.
                .then(() => { if (callback) callback(); });
        },

        updateCategorySelects: function() {
            const selects = [
                {id: 'aic_filter_category', defaultOption: '전체 카테고리 ▼', allowNew: false},
                {id: 'aic_category', defaultOption: '카테고리 선택 ▼', allowNew: true}
            ];
            selects.forEach(s => {
                const el = document.getElementById(s.id);
                if (!el) return;
                const val = el.value;
                el.innerHTML = `<option value="">${s.defaultOption}</option>`;
                this.state.categories.forEach(c => {
                    el.innerHTML += `<option value="${c.category_name}">${c.category_name}</option>`;
                });
                if (s.allowNew) el.innerHTML += `<option value="__NEW__">+ 새 카테고리 추가</option>`;
                el.value = val;
            });
            this.renderCategoryList();
        },

        renderAccordion: function() {
            const container = document.getElementById('aic_accordion_container');
            if(!container) return;
            
            const keyword = (document.getElementById('aic_accordion_search').value || '').toLowerCase();
            const groups = {};
            
            this.state.items.filter(item => item.is_active == 1).forEach(item => {
                if (keyword) {
                    if (!item.title.toLowerCase().includes(keyword) && 
                        !item.content.toLowerCase().includes(keyword) && 
                        !(item.category || '미분류').toLowerCase().includes(keyword)) return;
                }
                const cat = item.category || '미분류';
                if (!groups[cat]) groups[cat] = [];
                groups[cat].push(item);
            });
            
            let html = '';
            for (let cat in groups) {
                html += `<div class="pb-accordion-cat" style="margin-bottom:10px;">
                            <div style="background:#f1f5f9; padding:8px 12px; font-weight:bold; color:#334155; cursor:pointer; border-radius:6px; display:flex; justify-content:space-between; align-items:center;" onclick="this.nextElementSibling.style.display = this.nextElementSibling.style.display==='none'?'block':'none'">
                                <span>📁 ${cat} <span style="font-size:12px; color:#64748b; font-weight:normal;">(${groups[cat].length})</span></span>
                                <span style="color:#94a3b8; font-size:12px;">▼</span>
                            </div>
                            <div class="pb-accordion-content" style="display:block; padding:8px 0 8px 12px;">`;
                groups[cat].forEach(item => {
                    const isSelected = this.state.selectedIds.includes(item.id.toString());
                    html += `
                        <label style="display:block; padding:8px 10px; border:1px solid ${isSelected?'#10b981':'#e2e8f0'}; border-radius:6px; margin-bottom:6px; cursor:pointer; background:${isSelected?'#ecfdf5':'#fff'}; transition:all 0.2s;">
                            <div style="display:flex; align-items:flex-start; gap:10px;">
                                <input type="checkbox" name="aic_select_check" value="${item.id}" ${isSelected?'checked':''} onchange="Builder.AIConditionManager.toggleSelection('${item.id}', this.checked)" style="margin-top:4px;">
                                <div style="flex:1;">
                                    <div style="font-weight:600; color:#1e293b; margin-bottom:4px;">
                                        ${item.is_favorite == 1 ? '★ ' : ''}${item.title}
                                        ${item.is_default_checked == 1 ? '<span style="background:#10b981; color:#fff; font-size:10px; padding:2px 4px; border-radius:4px; margin-left:4px;">기본</span>' : ''}
                                    </div>
                                    <div style="font-size:12px; color:#64748b; line-height:1.4;">${(item.description || item.content).substring(0, 100)}...</div>
                                </div>
                            </div>
                        </label>
                    `;
                });
                html += `</div></div>`;
            }
            container.innerHTML = html || '<div style="padding:20px; text-align:center; color:#94a3b8;">검색 결과가 없습니다.</div>';
            this.updateSelectionCount();
        },

        toggleSelection: function(id, isChecked) {
            id = id.toString();
            if (isChecked) {
                if (!this.state.selectedIds.includes(id)) this.state.selectedIds.push(id);
            } else {
                this.state.selectedIds = this.state.selectedIds.filter(i => i !== id);
            }
            this.renderAccordion();
        },

        clearSelection: function() {
            this.state.selectedIds = [];
            this.renderAccordion();
        },

        updateSelectionCount: function() {
            const el = document.getElementById('aic_accordion_selected_count');
            if(el) el.innerText = this.state.selectedIds.length;
        },

        toggleAllAccordions: function(expand) {
            document.querySelectorAll('#aic_accordion_container .pb-accordion-content').forEach(el => {
                el.style.display = expand ? 'block' : 'none';
            });
        },

        applySelected: function(mode) {
            // mode: append or replace
            // 연동: builder.js의 Builder.generationConditions 에 반영해야 한다.
            if (mode === 'replace') {
                Builder.generationConditions = [];
            }
            
            // selectedIds에 있는 조건들을 찾아서 Builder.generationConditions 에 추가
            this.state.selectedIds.forEach(id => {
                // 이미 있는지 확인
                if (!Builder.generationConditions.some(c => c.id == id)) {
                    const item = this.state.items.find(i => i.id == id);
                    if (item) {
                        Builder.generationConditions.push({
                            id: item.id,
                            title: item.title,
                            content: item.content
                        });
                    }
                }
            });
            
            Builder.renderGenerationConditions();
            this.closeModal();
        },

        renderList: function() {
            const listEl = document.getElementById('aic_list');
            if(!listEl) return;
            
            const cat = document.getElementById('aic_filter_category').value;
            const status = document.getElementById('aic_filter_status').value;
            const search = document.getElementById('aic_filter_search').value.toLowerCase();
            const favOnly = document.getElementById('aic_filter_fav').checked;
            const sort = document.getElementById('aic_filter_sort').value;
            
            let filtered = this.state.items.filter(item => {
                if (cat && item.category !== cat) return false;
                if (status !== 'all' && item.is_active != status) return false;
                if (favOnly && item.is_favorite != 1) return false;
                if (search && !item.title.toLowerCase().includes(search) && !item.content.toLowerCase().includes(search)) return false;
                return true;
            });
            
            filtered.sort((a, b) => {
                if (sort === 'recent_used') return (b.last_used_at || '') > (a.last_used_at || '') ? 1 : -1;
                if (sort === 'most_used') return (b.usage_count || 0) - (a.usage_count || 0);
                if (sort === 'recent_added') return b.id - a.id;
                if (a.category !== b.category) return (a.category || '') > (b.category || '') ? 1 : -1;
                return a.id - b.id;
            });
            
            let html = '';
            filtered.forEach(item => {
                html += `
                    <div style="background:#fff; border:1px solid #e2e8f0; padding:15px; border-radius:8px; display:flex; justify-content:space-between; gap:15px; align-items:flex-start;">
                        <div style="flex:1;">
                            <div style="display:flex; gap:8px; margin-bottom:6px; align-items:center;">
                                <span style="background:#e2e8f0; color:#475569; padding:2px 6px; font-size:11px; border-radius:4px;">${item.category || '미분류'}</span>
                                <strong style="color:${item.is_active == 1 ? '#1e293b' : '#94a3b8'}; font-size:15px;">
                                    ${item.is_favorite == 1 ? '<span style="color:#eab308; cursor:pointer;" onclick="Builder.AIConditionManager.toggleFavorite('+item.id+',0)">★</span>' : '<span style="color:#cbd5e1; cursor:pointer;" onclick="Builder.AIConditionManager.toggleFavorite('+item.id+',1)">☆</span>'} 
                                    ${item.title}
                                </strong>
                                ${item.is_default_checked == 1 ? '<span style="background:#10b981; color:#fff; font-size:10px; padding:2px 4px; border-radius:4px;">기본</span>' : ''}
                                ${item.is_active == 0 ? '<span style="background:#ef4444; color:#fff; font-size:10px; padding:2px 4px; border-radius:4px;">사용중지</span>' : ''}
                            </div>
                            <div style="font-size:13px; color:#64748b; margin-bottom:8px; line-height:1.5;">${(item.description || item.content).substring(0, 150)}...</div>
                            <div style="font-size:11px; color:#94a3b8; display:flex; gap:10px;">
                                <span>사용 횟수: ${item.usage_count || 0}</span>
                                <span>최근 사용: ${item.last_used_at || '-'}</span>
                            </div>
                        </div>
                        <div style="display:flex; flex-direction:column; gap:6px;">
                            <button class="btn btn_02" style="padding:4px 10px; font-size:12px;" onclick="Builder.AIConditionManager.showForm(${item.id})">수정</button>
                            <button class="btn btn_02" style="padding:4px 10px; font-size:12px;" onclick="Builder.AIConditionManager.duplicateItem(${item.id})">복사</button>
                            <button class="btn btn_02" style="padding:4px 10px; font-size:12px; color:#ef4444; border-color:#fca5a5;" onclick="Builder.AIConditionManager.deleteItem(${item.id})">삭제</button>
                        </div>
                    </div>
                `;
            });
            listEl.innerHTML = html || '<div style="text-align:center; padding:30px; color:#94a3b8;">항목이 없습니다.</div>';
        },

        showForm: function(id) {
            document.getElementById('aic_id').value = id;
            document.getElementById('aic_form_title').innerText = id ? '조건 수정' : '새 조건 등록';
            
            if (id === 0) {
                document.getElementById('aic_title').value = '';
                document.getElementById('aic_category').value = '';
                document.getElementById('aic_content').value = '';
                document.getElementById('aic_description').value = '';
                document.getElementById('aic_recommended_purpose').value = '';
                document.getElementById('aic_recommended_industries').value = '';
                document.getElementById('aic_tags').value = '';
                document.getElementById('aic_is_default_checked').checked = false;
                document.getElementById('aic_is_favorite_checked').checked = false;
                document.getElementById('aic_is_active').checked = false;
            } else {
                const item = this.state.items.find(i => i.id == id);
                if (item) {
                    document.getElementById('aic_title').value = item.title;
                    document.getElementById('aic_category').value = item.category || '';
                    document.getElementById('aic_content').value = item.content;
                    document.getElementById('aic_description').value = item.description || '';
                    document.getElementById('aic_recommended_purpose').value = item.recommended_purpose || '';
                    document.getElementById('aic_recommended_industries').value = item.recommended_industries || '';
                    document.getElementById('aic_tags').value = item.tags || '';
                    document.getElementById('aic_is_default_checked').checked = item.is_default_checked == 1;
                    document.getElementById('aic_is_favorite_checked').checked = item.is_favorite == 1;
                    document.getElementById('aic_is_active').checked = item.is_active == 0; 
                }
            }
            
            document.getElementById('aic_tab_manage').style.display = 'none';
            document.getElementById('aic_form_wrap').style.display = 'block';
        },

        hideForm: function() {
            document.getElementById('aic_form_wrap').style.display = 'none';
            document.getElementById('aic_tab_manage').style.display = 'block';
        },

        saveItem: function() {
            const formData = new FormData();
            formData.append('action', 'save_ai_condition');
            formData.append('id', document.getElementById('aic_id').value);
            formData.append('title', document.getElementById('aic_title').value);
            formData.append('category', document.getElementById('aic_category').value);
            formData.append('content', document.getElementById('aic_content').value);
            formData.append('description', document.getElementById('aic_description').value);
            formData.append('recommended_purpose', document.getElementById('aic_recommended_purpose').value);
            formData.append('recommended_industries', document.getElementById('aic_recommended_industries').value);
            formData.append('tags', document.getElementById('aic_tags').value);
            formData.append('is_default_checked', document.getElementById('aic_is_default_checked').checked ? 'Y' : 'N');
            formData.append('is_favorite_checked', document.getElementById('aic_is_favorite_checked').checked ? 'Y' : 'N');
            formData.append('is_active', document.getElementById('aic_is_active').checked ? '0' : '1');

            if (!formData.get('title') || !formData.get('content')) {
                alert('제목과 내용을 입력해주세요.');
                return;
            }

            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) {
                        this.hideForm();
                        this.loadItems(() => { this.renderList(); });
                    } else {
                        alert(res.error || '저장에 실패했습니다.');
                    }
                });
        },

        deleteItem: function(id) {
            if (!confirm('정말 삭제하시겠습니까?')) return;
            const formData = new FormData();
            formData.append('action', 'delete_ai_condition');
            formData.append('id', id);
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) this.loadItems(() => { this.renderList(); });
                    else alert(res.error || '삭제 실패');
                });
        },

        duplicateItem: function(id) {
            const formData = new FormData();
            formData.append('action', 'duplicate_ai_condition');
            formData.append('id', id);
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) this.loadItems(() => { this.renderList(); });
                    else alert(res.error || '복사 실패');
                });
        },

        toggleFavorite: function(id, isFav) {
            const formData = new FormData();
            formData.append('action', 'toggle_favorite_ai_condition');
            formData.append('id', id);
            formData.append('is_favorite', isFav);
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) this.loadItems(() => { this.renderList(); });
                });
        },

        saveCategory: function() {
            const catName = document.getElementById('aic_new_cat_name').value.trim();
            if (!catName) return alert('카테고리명을 입력하세요.');
            const formData = new FormData();
            formData.append('action', 'save_ai_condition_category');
            formData.append('category_name', catName);
            
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) {
                        document.getElementById('aic_new_cat_name').value = '';
                        this.loadCategories();
                    } else alert(res.error || '실패');
                });
        },
        
        deleteCategory: function(id) {
            if (!confirm('정말 삭제하시겠습니까? 속한 항목은 미분류가 됩니다.')) return;
            const formData = new FormData();
            formData.append('action', 'delete_ai_condition_category');
            formData.append('id', id);
            
            fetch(libraryAjaxUrl(), { method: 'POST', body: formData })
                .then(pbReadJson)
                .then(res => {
                    if (res.success) this.loadCategories();
                    else alert(res.error || '실패');
                });
        },

        renderCategoryList: function() {
            const el = document.getElementById('aic_category_list');
            if(!el) return;
            let html = '';
            this.state.categories.forEach(c => {
                html += `
                    <div style="display:flex; justify-content:space-between; padding:8px 12px; background:#fff; border:1px solid #e2e8f0; border-radius:6px;">
                        <span>${c.category_name}</span>
                        <button class="btn btn_02" style="padding:2px 8px; font-size:12px;" onclick="Builder.AIConditionManager.deleteCategory(${c.id})">삭제</button>
                    </div>
                `;
            });
            el.innerHTML = html;
        }
    };

})(window);
