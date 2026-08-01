/**
 * 블로그 자동화 - 통합 포스팅 제작 폼 (Unified Post Builder)
 * Card-Based Studio UI Version (OpenAI 연동)
 */
// post_builder_ajax.php는 /adm/blog/에만 존재한다. 이 스크립트는 /manager/blog/post_builder.php에서도
// 로드되는데, fetch()의 상대경로는 현재 페이지 URL 기준으로 풀리므로 상대경로로 부르면 404가 난다.
// post_builder.php가 심어둔 window.PB_ADMIN_URL(=G5_ADMIN_URL)을 우선 쓰고, 없으면(=adm에서 직접 열렸을 때) 상대경로로 폴백한다.
const PB_AJAX_URL = (typeof window !== 'undefined' && window.PB_ADMIN_URL) ? window.PB_ADMIN_URL + '/blog/post_builder_ajax.php' : 'post_builder_ajax.php';

const Builder = {
    currentStep: 1,
    maxStep: 4,
    projectId: 0,
    postId: 0,
    cards: [], // { id, type, title, content, state: 'draft|primary|selected|hidden|deleted', locked: boolean }
    activeCardId: null,

    init: function() {
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
        keywords: { count: 5, values: [] },
        hashtags: { count: 5, values: [] }
    },

    renderTagPanel: function(type) {
        const containerId = type === 'hashtags' ? 'pb_tags_hashtags' : 'pb_tags_keywords';
        const container = document.getElementById(containerId);
        if (!container) return;

        const label = type === 'hashtags' ? '해시태그' : '키워드';
        const state = this.tagState[type];
        const values = state.values;

        let inputs = '';
        for (let i = 0; i < state.count; i++) {
            const v = values[i] || '';
            inputs += `<input type="text" class="frm_input pb-tag-input" style="width:120px;" placeholder="${label} ${i + 1}" value="${this._escapeAttr(v)}" onchange="Builder.onTagInputChange('${type}', ${i}, this.value)">`;
        }

        container.innerHTML = `
        <div style="margin-top:12px; padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">
            <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                <span style="font-size:0.85rem; font-weight:bold; color:#475569;">${label}</span>
                <div style="display:flex; align-items:center; gap:8px;">
                    <span style="font-size:0.8rem; color:#666;">개수</span>
                    <select class="frm_input" style="width:60px;" onchange="Builder.onTagCountChange('${type}', this.value)">
                        ${[3,4,5,6,7,8,9,10].map(n => `<option value="${n}" ${n === state.count ? 'selected' : ''}>${n}</option>`).join('')}
                    </select>
                    <button type="button" class="btn btn_02" onclick="Builder.generateTags('${type}')">AI로 채우기</button>
                </div>
            </div>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">${inputs}</div>
        </div>`;
    },

    onTagCountChange: function(type, count) {
        this.tagState[type].count = parseInt(count, 10);
        this.renderTagPanel(type);
    },

    onTagInputChange: function(type, idx, value) {
        this.tagState[type].values[idx] = value;
        this.saveTags(type);
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

    generateTags: function(type) {
        if (!this._requireProject()) return;
        const rawMat = document.getElementById('pb_raw_material').value.trim();
        const state = this.tagState[type];
        const seeds = state.values.filter(v => v && v.trim() !== '');

        const payload = new URLSearchParams();
        payload.append('action', 'generate_tags');
        payload.append('project_id', this.projectId);
        payload.append('tag_type', type);
        payload.append('count', state.count);
        payload.append('raw_material', rawMat);
        payload.append('seed_values', JSON.stringify(seeds));

        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            if (res.ok && Array.isArray(res.values)) {
                state.values = res.values;
                this.renderTagPanel(type);
                this.saveTags(type);
            } else {
                alert('생성 실패: ' + (res.error || '알 수 없는 오류'));
            }
        })
        .catch(err => {
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
        return res.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                if (text.indexOf('login.php') !== -1 || text.indexOf('로그인') !== -1) {
                    throw new Error('LOGIN_REQUIRED');
                }
                throw e;
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
                }
                this.applyProjectData(res.project, res.advertiser, res.latest_post);
            }
        });
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
        document.querySelectorAll('.pb-nav-item').forEach(el => el.classList.remove('active'));
        if (step > 0 && step <= 4) {
            const navItems = document.querySelectorAll('.pb-nav-item');
            if(navItems[step - 1]) navItems[step - 1].classList.add('active');
        }

        document.querySelectorAll('.pb-step-view').forEach(el => el.classList.remove('active'));
        
        // step 2 and 3 share the same canvas
        const targetViewId = (step === 2 || step === 3) ? 'step-23' : 'step-' + step;
        const stepEl = document.getElementById(targetViewId);
        if (stepEl) stepEl.classList.add('active');

        if (step === 2 || step === 3) {
            document.getElementById('pb_step23_title').innerText = step === 2 ? '2. 구성·초안 검토' : '3. 편집·최적화';
            if (this.cards.length === 0) {
                document.getElementById('pb_card_canvas').innerHTML = '<div style="text-align:center; padding:50px; color:#94a3b8;">아직 생성된 초안이 없습니다.<br>1단계에서 [전체 자동 작성 시작]을 눌러주세요.</div>';
            } else {
                this.renderCards();
            }
        }
        
        if (step === 4) {
            this.inspectAll();
        }

        this.currentStep = step;
        this.activeCardId = null;
        this.renderRightPanel();
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
        })
        .catch(err => {
            this._toast(err.message === 'LOGIN_REQUIRED' ? '로그인이 만료되어 이전 내용을 불러오지 못했습니다. 새로고침 후 다시 로그인해주세요.' : '이전 내용을 불러오는 중 오류가 발생했습니다.', 'error');
        });
    },

    gatherData: function() {
        return {
            raw_material: document.getElementById('pb_raw_material') ? document.getElementById('pb_raw_material').value : '',
            cards: this.cards
        };
    },
    
    // 전체 상태 저장 (임시 JSON fallback)
    saveState: function() {
        if (!this.projectId) {
            this._toast('프로젝트를 먼저 생성해주세요.', 'error');
            return;
        }
        
        const stateData = this.gatherData();
        const payload = new URLSearchParams();
        payload.append('action', 'save_state');
        payload.append('project_id', this.projectId);
        payload.append('post_id', this.postId);
        payload.append('builder_state', JSON.stringify(stateData));
        
        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => this._parseAjaxJson(res))
        .then(res => {
            if (res.ok) {
                if (res.post_id > 0) this.postId = res.post_id;
                const now = new Date();
                const timeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
                document.getElementById('pb-saved-time').innerText = timeStr;
                // this._toast('저장 완료', 'success'); // Too noisy during partial saves
            } else {
                // 실패를 조용히 삼키면 화면엔 카드가 있는데 DB엔 없는 상태로 남는다(실제로
                // 이렇게 초안 하나를 잃어버린 적이 있다) - 반드시 알려야 한다.
                this._toast('저장 실패: ' + (res.error || '알 수 없는 오류'), 'error');
            }
        })
        .catch(err => {
            if (err.message === 'LOGIN_REQUIRED') {
                this._toast('로그인이 만료되어 자동저장이 실패했습니다. 새로고침 후 다시 로그인하고, 화면의 내용을 다시 저장해주세요.', 'error');
            } else {
                this._toast('자동저장 중 네트워크 오류가 발생했습니다.', 'error');
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
            if (err.message === 'LOGIN_REQUIRED') {
                this._toast('로그인이 만료되었습니다. 새로고침 후 다시 로그인해주세요.', 'error');
            } else {
                this._toast('네트워크 오류', 'error');
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
                    </div>
                </div>
                <div class="pb-card-content">
                    <textarea class="pb-card-textarea" id="textarea_${card.id}" onchange="Builder.updateCardContent('${card.id}', this.value)" ${isHidden ? 'disabled' : ''}>${card.content}</textarea>
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
            <div class="minimap-item ${isActive ? 'active' : ''} ${isHidden ? 'hidden' : ''}" onclick="Builder.scrollToCard('${card.id}')">
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
        this.renderCards(); // to update active styling
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

    /* ==========================================
     * 6. Right Panel Rendering & AI Modify
     * ========================================== */
    renderRightPanel: function() {
        const panel = document.getElementById('pb_right_panel');

        // 1단계에서는 선택된 카드가 없는 게 당연하므로("클릭하시면 나타납니다" 안내는
        // 2/3단계용) - 키워드/해시태그 입력 패널을 우측에 보여준다.
        if (this.currentStep === 1) {
            panel.innerHTML = `
                <div class="pb-right-title">키워드 · 해시태그</div>
                <div id="pb_tags_keywords" class="pb-tags-panel"></div>
                <div id="pb_tags_hashtags" class="pb-tags-panel"></div>
            `;
            this.renderTagPanel('keywords');
            this.renderTagPanel('hashtags');
            return;
        }

        if (!this.activeCardId) {
            panel.innerHTML = '<div style="text-align:center; color:#94a3b8; padding-top:150px; font-size:0.95rem;">중앙에서 카드를 클릭하시면<br>이곳에 전용 설정 패널이 나타납니다.</div>';
            return;
        }

        const card = this.cards.find(c => c.id === this.activeCardId);
        if (!card) return;

        let html = `<div class="pb-right-title">AI 빠른 수정 (${card.type === 'title' ? '제목' : '본문'})</div>`;
        
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
                <label>길이 및 밀도 조절</label>
                <div class="pb-btn-grid">
                    <button onclick="Builder.regenerateCard('${card.id}', '길이를 현재보다 더 짧게 요약해줘')">더 짧게</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '내용을 구체적으로 더 길게 설명해줘')">더 길게</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '사례를 한두 가지 추가해서 풍부하게 해줘')">사례 추가</button>
                    <button onclick="Builder.regenerateCard('${card.id}', '주요 내용을 번호표나 목록(리스트) 형태로 정리해줘')">목록형으로정리</button>
                </div>
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
            `;
        }

        panel.innerHTML = html;
    },

    regenerateCardCustom: function(cardId) {
        const prompt = document.getElementById('custom_prompt_' + cardId).value;
        if(!prompt) { alert('명령을 입력해주세요.'); return; }
        this.regenerateCard(cardId, prompt);
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

        const payload = new URLSearchParams();
        payload.append('action', 'run_quality_check');
        payload.append('project_id', this.projectId);
        payload.append('post_id', this.postId);
        payload.append('title_text', titleText);
        payload.append('body_text', bodyText);

        fetch(PB_AJAX_URL, { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.ok) {
                let html = `<div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:15px; margin-bottom:15px;">
                                <h3 style="margin:0; font-size:1.2rem;">품질 검수 결과</h3>
                                <div style="font-size:1.5rem; font-weight:bold; color:${res.score >= 80 ? '#10b981' : (res.score >= 50 ? '#f59e0b' : '#ef4444')};">${res.score}점</div>
                            </div>
                            <ul style="list-style:none; padding:0; margin:0;">`;
                
                res.checks.forEach(chk => {
                    let icon = chk.status === 'pass' ? '✅' : (chk.status === 'warn' ? '⚠️' : '❌');
                    let color = chk.status === 'pass' ? '#10b981' : (chk.status === 'warn' ? '#f59e0b' : '#ef4444');
                    html += `<li style="padding:10px 0; border-bottom:1px solid #f1f5f9;">
                                <div><span style="margin-right:10px;">${icon}</span><strong style="color:#334155;">${chk.key}</strong></div>
                                <div style="color:${color}; font-size:0.9rem; margin-top:5px; padding-left:28px;">${chk.detail}</div>
                             </li>`;
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

        bodyCards.forEach(c => {
            if (c.title) {
                htmlOut += `<h2>${c.title}</h2>\n`;
                textOut += `[${c.title}]\n`;
            }
            htmlOut += `<p>${c.content.replace(/\n/g, '<br>')}</p>\n\n`;
            textOut += `${c.content}\n\n`;
        });

        document.getElementById('pb_preview_html').innerHTML = htmlOut;
        document.getElementById('pb_preview_text').value = textOut;

        // Save one last time
        this.saveState();

        // Slide up the panel
        document.getElementById('pb_bottom_panel').classList.add('active');
    },

    copyHtml: function() {
        const text = document.getElementById('pb_preview_text').value;
        navigator.clipboard.writeText(text).then(() => {
            this._toast('HTML 코드가 복사되었습니다.', 'success');
        });
    }
};

document.addEventListener('DOMContentLoaded', () => { Builder.init(); });
