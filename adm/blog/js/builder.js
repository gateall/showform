/**
 * 블로그 자동화 - 통합 포스팅 제작 폼 (Unified Post Builder)
 * 3-Split Chatbot Workspace Version (OpenAI 연동)
 */
const Builder = {
    currentStep: 1,
    maxStep: 4,
    projectId: 0,
    postId: 0,
    AI_TIMEOUT_MS: 45000,
    chatMessageCounter: 0,
    chatHistory: [],
    undoStack: [],

    init: function() {
        this.bindEvents();
        this.toggleStep(1); // Start at step 1
        
        const loadProjectId = localStorage.getItem('pb_load_project_id');
        if (loadProjectId) {
            localStorage.removeItem('pb_load_project_id');
            this.onTopProjectChange(loadProjectId);
        }
    },

    bindEvents: function() {
        // Handling events if needed
    },

    _toast: function(message, tone) {
        if (typeof window.mgrToast === 'function') window.mgrToast(message, tone);
        else alert(message);
    },

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
                if (res.latest_post) {
                    if(!confirm("이전에 작성 중이던 내용이 있습니다. 불러오시겠습니까?")) {
                        return;
                    }
                }
                this.projectId = res.project.id;
                this.applyProjectData(res.project, res.advertiser, res.latest_post);
                
                const advSelect = document.getElementById('top_advertiser_id');
                if (advSelect && res.project.advertiser_id) {
                    if (advSelect.value != res.project.advertiser_id) {
                        advSelect.value = res.project.advertiser_id;
                        this.onTopAdvertiserChange(res.project.advertiser_id, res.project.id);
                    } else {
                        const projSelect = document.getElementById('top_project_id');
                        if (projSelect) projSelect.value = res.project.id;
                    }
                }
            }
        });
    },

    toggleNewProjectForm: function() {
        this.toggleStep(0); 
        const advId = document.getElementById('top_advertiser_id').value;
        if (advId) {
            document.getElementById('inline_advertiser_id').value = advId;
        }
    },

    saveInlineProject: function() {
        const payload = new URLSearchParams();
        payload.append('action', 'save_project');
        payload.append('advertiser_id', document.getElementById('inline_advertiser_id').value);
        payload.append('topic', document.getElementById('inline_topic').value);
        payload.append('content_type', document.getElementById('inline_content_type').value);
        payload.append('target_audience', document.getElementById('inline_target_audience').value);

        fetch('ajax.builder.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.success) {
                this._toast('프로젝트가 등록되었습니다. 작성 화면에 적용됩니다.', 'success');
                this.projectId = res.project_id;
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
        });
    },

    applyProjectData: function(project, advertiser, latestPost) {
        if(latestPost) {
            this.postId = latestPost.id;
            if(document.getElementById('pb_post_title')) document.getElementById('pb_post_title').value = latestPost.title;
        } else {
            this.postId = 0;
        }
        document.getElementById('pb-status-display').innerText = '진행 중';

        // Load builder_state
        this.loadState();
        if (this.postId) this.loadSeoMeta();
    },

    loadState: function() {
        if (!this.projectId) return;
        const payload = new URLSearchParams();
        payload.append('action', 'load_state');
        payload.append('project_id', this.projectId);
        
        fetch('post_builder_ajax.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.ok && res.builder_state) {
                const s = res.builder_state;
                if(s.post_title) document.getElementById('pb_post_title').value = s.post_title;
                if(s.body_content) document.getElementById('pb_body_content').value = s.body_content;
                if(s.material) document.getElementById('pb_extracted_material').value = s.material;
                if(s.closing_text) document.getElementById('pb_closing_text').value = s.closing_text;
                if(s.keywords) document.getElementById('pb_keywords').value = s.keywords;
                if(s.chat_history) {
                    this.chatHistory = s.chat_history;
                    this.renderChatHistory();
                }
            }
        });
    },

    gatherData: function() {
        return {
            advertiser_id: document.getElementById('pb_advertiser_id') ? document.getElementById('pb_advertiser_id').value : '',
            site_id: document.getElementById('pb_site_id') ? document.getElementById('pb_site_id').value : '',
            post_title: document.getElementById('pb_post_title') ? document.getElementById('pb_post_title').value : '',
            body_content: document.getElementById('pb_body_content') ? document.getElementById('pb_body_content').value : '',
            material: document.getElementById('pb_extracted_material') ? document.getElementById('pb_extracted_material').value : '',
            closing_text: document.getElementById('pb_closing_text') ? document.getElementById('pb_closing_text').value : '',
            keywords: document.getElementById('pb_keywords') ? document.getElementById('pb_keywords').value : '',
            chat_history: this.chatHistory
        };
    },
    
    saveStep: function(step) {
        const stateData = this.gatherData();
        const payload = new URLSearchParams();
        payload.append('action', step === 'all' ? 'save_all' : 'save_step');
        payload.append('step', step);
        payload.append('project_id', this.projectId);
        payload.append('post_id', this.postId);
        payload.append('advertiser_id', stateData.advertiser_id);
        payload.append('site_id', stateData.site_id);
        payload.append('builder_state', JSON.stringify(stateData));
        
        fetch('post_builder_ajax.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.ok) {
                if (res.project_id > 0) this.projectId = res.project_id;
                if (res.post_id > 0) this.postId = res.post_id;

                const now = new Date();
                const timeStr = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
                document.getElementById('pb-saved-time').innerText = timeStr;

                this._toast(res.message || '저장되었습니다.', 'success');
            } else {
                alert('저장 실패: ' + res.error);
            }
        })
        .catch(err => {
            alert('저장 요청 실패: ' + err);
        });
    },

    saveAll: function() {
        this.saveStep('all');
    },

    toggleStep: function(step) {
        document.querySelectorAll('.pb-nav-item').forEach(el => el.classList.remove('active'));
        if (step > 0 && step <= 4) {
            const navItems = document.querySelectorAll('.pb-nav-item');
            if(navItems[step - 1]) navItems[step - 1].classList.add('active');
        }

        document.querySelectorAll('.pb-step-view').forEach(el => el.classList.remove('active'));
        const stepEl = document.getElementById('step-' + step);
        if (stepEl) stepEl.classList.add('active');

        this.currentStep = step;
    },

    uploadImages: function(btn) {
        const fileInput = document.getElementById('pb_image_upload');
        if (fileInput.files.length === 0) { alert('이미지를 선택하세요.'); return; }
        
        btn.disabled = true;
        btn.innerText = '업로드 중...';
        
        setTimeout(() => {
            this._toast('이미지가 임시 업로드 되었습니다.', 'success');
            btn.disabled = false;
            btn.innerText = 'PC에서 이미지 업로드';
            fileInput.value = '';
        }, 800);
    },

    switchPreviewTab: function(tab) {
        const htmlBox = document.getElementById('pb_preview_area');
        const textBox = document.getElementById('pb_preview_text_area');
        const btnHtml = document.getElementById('pb_tab_btn_html');
        const btnText = document.getElementById('pb_tab_btn_text');
        if (tab === 'text') {
            htmlBox.style.display = 'none';
            textBox.style.display = 'block';
            btnHtml.classList.remove('active');
            btnHtml.style.background = '#f1f5f9';
            btnText.classList.add('active');
            btnText.style.background = '#fff';
        } else {
            htmlBox.style.display = 'block';
            textBox.style.display = 'none';
            btnText.classList.remove('active');
            btnText.style.background = '#f1f5f9';
            btnHtml.classList.add('active');
            btnHtml.style.background = '#fff';
        }
    },

    /*========================================================================
     * CHATBOT AI ASSISTANT LOGIC (OpenAI Integration)
     *========================================================================*/
    
    renderChatHistory: function() {
        const chatBox = document.getElementById('pb_chat_messages');
        // keep the first welcome message
        const welcome = chatBox.firstElementChild;
        chatBox.innerHTML = '';
        if(welcome) chatBox.appendChild(welcome);

        this.chatHistory.forEach(msg => {
            if(msg.role === 'user') {
                this._appendChatBubble('user', msg.content);
            } else if (msg.role === 'assistant') {
                let html = msg.html;
                if (!html) {
                    html = msg.content; // fallback
                }
                this._appendChatBubble('ai', html);
            }
        });
        this.scrollToBottom();
    },

    sendChatMessage: function() {
        if (!this.projectId) {
            alert('기획(1단계) 탭에서 먼저 [광고주]를 지정하고 [새 프로젝트 등록]을 완료해 주세요. (임시저장 필수)');
            return;
        }

        const inputEl = document.getElementById('pb_chat_input');
        let text = inputEl.value.trim();
        if(!text) return;
        
        inputEl.value = '';
        inputEl.disabled = true;
        
        this._appendChatBubble('user', text);
        this.chatHistory.push({role: 'user', content: text});
        this.scrollToBottom();

        // 렌더링 로딩
        const loadingId = 'pb_loading_' + Date.now();
        this._appendChatBubble('ai', '<span class="loading-dots">AI가 생각 중입니다...</span>', loadingId);

        const stateData = this.gatherData();
        const payload = new URLSearchParams();
        payload.append('action', 'chat_assistant');
        payload.append('project_id', this.projectId);
        payload.append('step', this.currentStep);
        payload.append('user_message', text);
        payload.append('chat_history', JSON.stringify(this.chatHistory));
        payload.append('builder_state', JSON.stringify(stateData));

        fetch('post_builder_ajax.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            const loadingEl = document.getElementById(loadingId);
            if(loadingEl) loadingEl.remove();
            
            if (res.ok && res.ai_response) {
                const ai = res.ai_response;
                this.processAiResponse(ai);
            } else {
                this._toast('AI 요청 실패: ' + (res.error || '알 수 없는 오류'), 'error');
                this._appendChatBubble('ai', '죄송합니다. 오류가 발생했습니다.<br>' + (res.error || '알 수 없는 오류'));
                this.chatHistory.push({role: 'assistant', content: res.error, html: '오류 발생'});
            }
        })
        .catch(err => {
            const loadingEl = document.getElementById(loadingId);
            if(loadingEl) loadingEl.remove();
            this._toast('네트워크 오류', 'error');
            this._appendChatBubble('ai', '서버와 연결할 수 없습니다. 잠시 후 다시 시도해 주세요.');
            inputEl.value = text; // 복구
        })
        .finally(() => {
            inputEl.disabled = false;
            inputEl.focus();
            this.scrollToBottom();
            this.saveStep('all'); // 대화 후 자동 저장
        });
    },

    processAiResponse: function(aiResponse) {
        this.chatMessageCounter++;
        const tempId = 'ai_ans_' + this.chatMessageCounter;
        
        let htmlContent = aiResponse.message || '';
        
        // If there's an action, provide a button
        if (aiResponse.action && aiResponse.action !== 'chat' && aiResponse.content) {
            htmlContent += `<div style="margin: 10px 0; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; font-size: 0.9rem; white-space: pre-wrap;" id="${tempId}">${aiResponse.content}</div>`;
            
            htmlContent += `<div style="display:flex; gap: 5px; margin-top:10px;">
                <button type="button" class="pb-chat-apply-btn" onclick="Builder.applyAction('${aiResponse.action}', '${aiResponse.target}', '${tempId}')">본문에 적용하기</button>
            </div>`;
        }

        this._appendChatBubble('ai', htmlContent);
        
        // Store in history
        this.chatHistory.push({
            role: 'assistant',
            content: aiResponse.content || aiResponse.message, 
            html: htmlContent
        });
    },

    _appendChatBubble: function(sender, htmlContent, id = '') {
        const chatBox = document.getElementById('pb_chat_messages');
        const bubble = document.createElement('div');
        if (id) bubble.id = id;
        bubble.className = 'pb-chat-bubble ' + (sender === 'user' ? 'pb-chat-user' : 'pb-chat-ai');
        bubble.innerHTML = htmlContent;
        chatBox.appendChild(bubble);
        this.scrollToBottom();
    },

    applyAction: function(action, targetId, contentId) {
        const target = document.getElementById(targetId);
        const source = document.getElementById(contentId);
        if (!target || !source) {
            alert('적용 대상 폼 요소나 텍스트를 찾을 수 없습니다.');
            return;
        }

        const textToApply = source.innerText;
        const previousValue = target.value;

        // Undo 상태 저장
        this.undoStack.push({
            targetId: targetId,
            previousValue: previousValue
        });

        if (action === 'replace_title') {
            target.value = textToApply;
        } else if (action === 'append_body') {
            if (target.value.trim() !== '') {
                target.value += '\n\n' + textToApply;
            } else {
                target.value = textToApply;
            }
        } else if (action === 'replace_body') {
            if (target.value.trim() !== '') {
                if(!confirm('기존 본문 내용이 삭제되고 새 내용으로 교체됩니다. 계속하시겠습니까?')) {
                    this.undoStack.pop();
                    return;
                }
            }
            target.value = textToApply;
        } else {
            // 기본 교체
            target.value = textToApply;
        }

        this._toast('중앙 에디터에 적용되었습니다. (되돌리려면 실행 취소)', 'success');
        this.renderUndoButton();
    },

    renderUndoButton: function() {
        let undoBtn = document.getElementById('pb_undo_btn');
        if (this.undoStack.length > 0) {
            if (!undoBtn) {
                undoBtn = document.createElement('button');
                undoBtn.id = 'pb_undo_btn';
                undoBtn.className = 'btn btn_03';
                undoBtn.style.cssText = 'position: absolute; bottom: 20px; right: 20px; background: #ef4444; color: white; border: none; font-weight: bold; z-index: 100; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);';
                undoBtn.innerText = '↺ 방금 적용 실행 취소';
                undoBtn.onclick = () => this.undoLastAction();
                document.getElementById('post-builder-app').appendChild(undoBtn);
            }
        } else {
            if (undoBtn) undoBtn.remove();
        }
    },

    undoLastAction: function() {
        if (this.undoStack.length === 0) return;
        const lastAction = this.undoStack.pop();
        const target = document.getElementById(lastAction.targetId);
        if (target) {
            target.value = lastAction.previousValue;
            this._toast('이전 상태로 되돌렸습니다.', 'info');
        }
        this.renderUndoButton();
    },

    /*========================================================================
     * SEO 메타 설명 자동 생성 (3단계) - 챗봇 시뮬레이션과 별개로 실제 AI 호출/DB 저장까지 동작한다.
     *========================================================================*/

    loadSeoMeta: function() {
        if (!this.postId) return; // 아직 저장된 포스트가 없으면 조용히 스킵(신규 작성 중)
        const payload = new URLSearchParams();
        payload.append('action', 'load_seo_meta');
        payload.append('post_id', this.postId);
        fetch('post_builder_ajax.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.ok && res.meta_description) {
                document.getElementById('pb_meta_description').value = res.meta_description;
                this.updateMetaLength();
            }
        });
    },

    updateMetaLength: function() {
        const el = document.getElementById('pb_meta_description');
        const counter = document.getElementById('pb_meta_length_counter');
        if (!el || !counter) return;
        const len = el.value.length;
        const ok = (len >= 80 && len <= 160);
        counter.innerText = len + ' / 80~160자';
        counter.style.color = ok ? '#10b981' : '#dc2626';
    },

    generateSeoMeta: function(btn) {
        const existing = document.getElementById('pb_meta_description').value.trim();
        if (existing !== '' && !confirm('이미 입력된 메타 설명이 있습니다. AI로 새로 생성하면 대체됩니다. 계속하시겠습니까?')) {
            return;
        }
        const title = document.getElementById('pb_post_title') ? document.getElementById('pb_post_title').value : '';
        const body = document.getElementById('pb_body_content') ? document.getElementById('pb_body_content').value : '';
        const keywordsRaw = document.getElementById('pb_keywords') ? document.getElementById('pb_keywords').value : '';
        const mainKeyword = keywordsRaw.split(',')[0].trim();

        if (!title && !body) {
            alert('2단계에서 제목이나 본문을 먼저 작성해 주세요.');
            return;
        }

        btn.disabled = true;
        const originalLabel = btn.innerText;
        btn.innerText = '생성 중...';

        const payload = new URLSearchParams();
        payload.append('action', 'generate_seo_meta');
        payload.append('post_title', title);
        payload.append('body_content', body);
        payload.append('main_keyword', mainKeyword);

        fetch('post_builder_ajax.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerText = originalLabel;
            if (!res.ok) {
                alert('생성 실패: ' + res.error);
                return;
            }
            document.getElementById('pb_meta_description').value = res.meta_description;
            this.updateMetaLength();
            if (!res.length_ok) {
                this._toast('생성된 메타 설명이 권장 길이(80~160자)를 벗어났습니다. 수정 후 저장해 주세요.', 'warning');
            } else {
                this._toast('메타 설명이 생성되었습니다. 확인 후 저장하세요.', 'success');
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = originalLabel;
            alert('생성 요청 실패: ' + err);
        });
    },

    saveSeoMeta: function(btn) {
        if (!this.postId) {
            alert('먼저 프로젝트를 저장(임시저장)한 후 이용해 주세요.');
            return;
        }
        const value = document.getElementById('pb_meta_description').value.trim();
        btn.disabled = true;
        const originalLabel = btn.innerText;
        btn.innerText = '저장 중...';

        const payload = new URLSearchParams();
        payload.append('action', 'save_seo_meta');
        payload.append('post_id', this.postId);
        payload.append('meta_description', value);

        fetch('post_builder_ajax.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            btn.disabled = false;
            btn.innerText = originalLabel;
            if (res.ok) {
                this._toast(res.message, 'success');
            } else {
                alert('저장 실패: ' + res.error);
            }
        })
        .catch(err => {
            btn.disabled = false;
            btn.innerText = originalLabel;
            alert('저장 요청 실패: ' + err);
        });
    }
};

document.addEventListener('DOMContentLoaded', () => { Builder.init(); });
