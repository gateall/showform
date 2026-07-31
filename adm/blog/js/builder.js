/**
 * 블로그 자동화 - 통합 포스팅 제작 폼 (Unified Post Builder)
 */
const Builder = {
    currentStep: 1,
    maxStep: 12,
    projectId: 0,
    postId: 0,
    AI_TIMEOUT_MS: 45000, // 서버 curl 타임아웃(40초, blog_ai_service.lib.php)보다 약간 길게 잡아 서버측 에러 메시지가 먼저 오도록 함

    init: function() {
        this.updateStickyBar();
        this.bindEvents();
    },

    bindEvents: function() {
        // Auto-save triggers can be added here
    },

    // 버튼 disable + 라벨 변경. 이미 처리 중(중복 클릭)이면 false를 반환해 호출부가 중단하게 한다.
    // btn이 없으면(버튼 참조를 못 넘기는 기존 호출부) 가드 없이 항상 진행시킨다.
    _setBusy: function(btn, busyLabel) {
        if (!btn) return true;
        if (btn.disabled) return false;
        btn.dataset.mgrOrigLabel = btn.innerText;
        btn.disabled = true;
        btn.innerText = busyLabel;
        return true;
    },

    _clearBusy: function(btn) {
        if (!btn) return;
        btn.disabled = false;
        if (btn.dataset.mgrOrigLabel !== undefined) {
            btn.innerText = btn.dataset.mgrOrigLabel;
        }
    },

    // manager 공통 레이아웃(footer.php)이 이미 #mgrToastContainer + mgrToast()를 제공한다 - 새로 안 만듦.
    _toast: function(message, tone) {
        if (typeof window.mgrToast === 'function') window.mgrToast(message, tone);
    },

    _isAbortError: function(err) {
        return !!(err && err.name === 'AbortError');
    },

    // AI 호출처럼 오래 걸릴 수 있는 요청이 끝없이 매달리지 않도록 타임아웃을 건다.
    _fetchWithTimeout: function(url, options, timeoutMs) {
        const controller = new AbortController();
        const timer = setTimeout(function() { controller.abort(); }, timeoutMs || this.AI_TIMEOUT_MS);
        const opts = Object.assign({}, options, { signal: controller.signal });
        return fetch(url, opts).finally(function() { clearTimeout(timer); });
    },

    toggleStep: function(step) {
        document.querySelectorAll('.pb-step').forEach(el => el.classList.remove('active'));
        document.getElementById('step-' + step).classList.add('active');
        this.currentStep = step;
        this.updateStickyBar();
    },
    
    nextStep: function() {
        if (this.currentStep < this.maxStep) {
            this.saveCurrentStep();
            this.toggleStep(this.currentStep + 1);
            // 스크롤 맨 위로 올리거나 해당 스텝으로 부드럽게 이동
            document.getElementById('step-' + this.currentStep).scrollIntoView({behavior: 'smooth', block: 'start'});
        }
    },
    
    prevStep: function() {
        if (this.currentStep > 1) {
            this.saveCurrentStep();
            this.toggleStep(this.currentStep - 1);
            document.getElementById('step-' + this.currentStep).scrollIntoView({behavior: 'smooth', block: 'start'});
        }
    },
    
    updateStickyBar: function() {
        const stepTitles = [
            "", "1. 기본정보", "2. 방향성", "3. 키워드", "4. 제목/목차", "5. 도입부",
            "6. 본문구간", "7. 이미지", "8. 업체정보", "9. 마무리", "10. SEO점검", "11. 미리보기", "12. 완성"
        ];
        
        // title 갱신
        const titleEl = document.getElementById('pb_sticky_step_name');
        if (titleEl && stepTitles[this.currentStep]) {
            titleEl.innerText = stepTitles[this.currentStep];
        }
    },
    
    gatherData: function() {
        const data = {
            advertiser_id: document.getElementById('pb_advertiser_id') ? document.getElementById('pb_advertiser_id').value : '',
            site_id: document.getElementById('pb_site_id') ? document.getElementById('pb_site_id').value : '',
            post_type: document.getElementById('pb_post_type') ? document.getElementById('pb_post_type').value : '',
            target_date: document.getElementById('pb_target_date') ? document.getElementById('pb_target_date').value : '',
            target_audience: document.getElementById('pb_target_audience') ? document.getElementById('pb_target_audience').value : '',
            tone: document.getElementById('pb_tone') ? document.getElementById('pb_tone').value : '',
            main_keyword: document.getElementById('pb_main_keyword') ? document.getElementById('pb_main_keyword').value : '',
            sub_keywords: document.getElementById('pb_sub_keywords') ? document.getElementById('pb_sub_keywords').value : '',
            post_title: document.getElementById('pb_post_title') ? document.getElementById('pb_post_title').value : '',
            intro_text: document.getElementById('pb_intro_text') ? document.getElementById('pb_intro_text').value : '',
            closing_text: document.getElementById('pb_closing_text') ? document.getElementById('pb_closing_text').value : '',
            company_name: document.getElementById('pb_company_name') ? document.getElementById('pb_company_name').value : '',
            company_tel: document.getElementById('pb_company_tel') ? document.getElementById('pb_company_tel').value : '',
            company_addr: document.getElementById('pb_company_addr') ? document.getElementById('pb_company_addr').value : '',
            company_link: document.getElementById('pb_company_link') ? document.getElementById('pb_company_link').value : ''
        };
        
        // TOC
        const tocs = [];
        document.querySelectorAll('.pb-toc-item').forEach(el => tocs.push(el.value));
        data.toc_list = tocs;
        
        // Body blocks
        const blocks = [];
        document.querySelectorAll('.pb-block').forEach(el => {
            const inputs = el.querySelectorAll('.frm_input');
            if (inputs.length >= 2) {
                blocks.push({
                    title: inputs[0].value,
                    content: inputs[1].value
                });
            }
        });
        data.body_blocks = blocks;
        
        return data;
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
        
        fetch('post_builder_ajax.php', {
            method: 'POST',
            body: payload
        })
        .then(res => res.json())
        .then(res => {
            if (res.ok) {
                if (res.project_id > 0) this.projectId = res.project_id;
                if (res.post_id > 0) this.postId = res.post_id;

                const now = new Date();
                const timeStr = now.getHours().toString().padStart(2, '0') + ':' +
                                now.getMinutes().toString().padStart(2, '0');
                document.getElementById('pb-saved-time').innerText = timeStr;

                this._toast(res.message || '저장되었습니다.', 'success');
            } else {
                alert('저장 실패: ' + res.error);
            }
        })
        .catch(err => {
            alert('저장 요청 실패(네트워크를 확인해 주세요): ' + err);
        });
    },

    saveCurrentStep: function() {
        this.saveStep(this.currentStep);
    },

    saveAll: function() {
        // 저장 성공 여부는 위 saveStep()의 비동기 응답에서 토스트로 알린다 -
        // 여기서 곧바로 alert()를 띄우면 실패했을 때도 "저장되었습니다"가 먼저 뜨는 오탐이 발생했었다.
        this.saveStep('all');
    },
    
    addTocItem: function() {
        const ul = document.getElementById('pb_toc_list');
        const li = document.createElement('li');
        li.innerHTML = '<input type="text" class="frm_input pb-toc-item" placeholder="새 목차 입력"> <button type="button" class="btn_del" onclick="this.parentElement.remove()">X</button>';
        ul.appendChild(li);
    },
    
    addBodyBlock: function() {
        const container = document.getElementById('pb_body_blocks');
        const blockId = Date.now();
        const html = `
            <div class="pb-block" id="block-${blockId}">
                <div class="pb-block-header">
                    <strong>본문 블록</strong>
                    <div>
                        <button type="button" class="btn btn_02" onclick="Builder.generateBlockAI(${blockId}, this)">AI 작성</button>
                        <button type="button" class="btn_del" onclick="document.getElementById('block-${blockId}').remove()">삭제</button>
                    </div>
                </div>
                <div class="pb-form-group">
                    <label>소제목</label>
                    <input type="text" class="frm_input" style="width:100%;" placeholder="선택사항">
                </div>
                <div class="pb-form-group" style="margin-top:10px;">
                    <label>내용</label>
                    <textarea class="frm_input" rows="4" style="width:100%;"></textarea>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
    },
    
    callAi: function(type, blockTitle, targetElementId, btn) {
        if (this.projectId === 0) {
            alert('기본정보(1단계)를 먼저 저장해 주세요.');
            return;
        }
        if (!this._setBusy(btn, 'AI 생성 중...')) return;

        const stateData = this.gatherData();
        const payload = new URLSearchParams();
        payload.append('action', 'ai_generate');
        payload.append('project_id', this.projectId);
        payload.append('type', type);
        payload.append('block_title', blockTitle);
        payload.append('builder_state', JSON.stringify(stateData));

        const targetEl = document.getElementById(targetElementId);
        targetEl.value = "AI 생성 중...";

        this._fetchWithTimeout('post_builder_ajax.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.ok) {
                targetEl.value = res.generated_text;
                this._toast('AI 생성이 완료되었습니다.', 'success');
            } else {
                alert('AI 생성 실패: ' + res.error);
                targetEl.value = "";
            }
        })
        .catch(err => {
            targetEl.value = "";
            alert(this._isAbortError(err) ? 'AI 응답이 너무 오래 걸려 요청을 중단했습니다. 잠시 후 다시 시도해 주세요.' : 'AI 요청 실패: ' + err);
        })
        .finally(() => { this._clearBusy(btn); });
    },

    analyzeMaterial: function() {
        const materialEl = document.getElementById('pb_material_input');
        const material = materialEl.value.trim();
        if (!material) {
            alert('분석할 글감을 입력해 주세요.');
            return;
        }

        const btn = document.getElementById('pb_material_btn');
        if (!this._setBusy(btn, '분석 중...')) return;

        const payload = new URLSearchParams();
        payload.append('action', 'analyze_material');
        payload.append('project_id', this.projectId);
        payload.append('material', material);

        this._fetchWithTimeout('post_builder_ajax.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (!res.ok) {
                alert('분석 실패: ' + res.error);
                return;
            }

            const a = res.analysis;

            // 기존 필드 재사용 - 새 필드를 만들지 않고 1~3단계 항목에 자동 입력
            if (a.main_keyword) document.getElementById('pb_main_keyword').value = a.main_keyword;
            if (a.sub_keywords) document.getElementById('pb_sub_keywords').value = a.sub_keywords;
            if (a.target_audience) document.getElementById('pb_target_audience').value = a.target_audience;

            const typeMap = {'정보형': 'info', '상품소개형': 'product', '후기형': 'review', 'FAQ형': 'faq'};
            const postTypeSelect = document.getElementById('pb_post_type');
            if (a.recommended_format && postTypeSelect) {
                for (const key in typeMap) {
                    if (a.recommended_format.indexOf(key) !== -1) {
                        postTypeSelect.value = typeMap[key];
                        break;
                    }
                }
            }

            // 사이드바 요약 카드 - AI가 반환한 텍스트이므로 innerHTML이 아닌 텍스트 노드로만 삽입한다
            const list = document.getElementById('pb_material_summary_list');
            list.innerHTML = '';
            const items = [
                ['주제', a.topic],
                ['목적', a.purpose],
                ['예상 독자', a.target_audience],
                ['추천 형식', a.recommended_format],
                ['대표 키워드', a.main_keyword],
                ['보조 키워드', a.sub_keywords],
                ['예상 분량', a.recommended_length]
            ];
            items.forEach(function(pair) {
                if (!pair[1]) return;
                const li = document.createElement('li');
                const strong = document.createElement('strong');
                strong.textContent = pair[0] + ': ';
                li.appendChild(strong);
                li.appendChild(document.createTextNode(pair[1]));
                list.appendChild(li);
            });
            document.getElementById('pb_material_summary_panel').style.display = 'block';

            alert('글감 분석이 완료되어 관련 항목이 자동으로 입력되었습니다. 각 단계에서 자유롭게 수정하세요.');
        })
        .catch(err => {
            alert(this._isAbortError(err) ? 'AI 응답이 너무 오래 걸려 요청을 중단했습니다. 잠시 후 다시 시도해 주세요.' : '분석 요청 실패: ' + err);
        })
        .finally(() => { this._clearBusy(btn); });
    },

    generateDirection: function(btn) { this.callAi('direction', '', 'pb_target_audience', btn); },
    recommendKeywords: function(btn) { this.callAi('titles', '', 'pb_post_title', btn); }, // 타이틀 생성으로 임시 매핑
    generateTitles: function(btn) { this.callAi('titles', '', 'pb_post_title', btn); },
    generateIntro: function(btn) { this.callAi('intro', '', 'pb_intro_text', btn); },
    generateBlockAI: function(id, btn) {
        const block = document.getElementById('block-' + id);
        const inputs = block.querySelectorAll('.frm_input');
        if (inputs.length < 2) return;
        const blockTitle = inputs[0].value;
        if (!blockTitle) {
            alert('소제목을 먼저 입력해 주세요.');
            return;
        }
        if (!this._setBusy(btn, 'AI 생성 중...')) return;

        inputs[1].value = "AI 생성 중...";

        const stateData = this.gatherData();
        const payload = new URLSearchParams();
        payload.append('action', 'ai_generate');
        payload.append('project_id', this.projectId);
        payload.append('type', 'block');
        payload.append('block_title', blockTitle);
        payload.append('builder_state', JSON.stringify(stateData));

        this._fetchWithTimeout('post_builder_ajax.php', { method: 'POST', body: payload })
        .then(res => res.json())
        .then(res => {
            if (res.ok) {
                inputs[1].value = res.generated_text;
                this._toast('AI 생성이 완료되었습니다.', 'success');
            } else {
                alert('AI 생성 실패: ' + res.error);
                inputs[1].value = "";
            }
        })
        .catch(err => {
            inputs[1].value = "";
            alert(this._isAbortError(err) ? 'AI 응답이 너무 오래 걸려 요청을 중단했습니다. 잠시 후 다시 시도해 주세요.' : 'AI 요청 실패: ' + err);
        })
        .finally(() => { this._clearBusy(btn); });
    },
    
    uploadImages: function(btn) {
        const fileInput = document.getElementById('pb_image_upload');
        if (fileInput.files.length === 0) {
            alert('업로드할 이미지를 선택해 주세요.');
            return;
        }
        if (this.projectId === 0) {
            alert('기본정보(1단계)를 먼저 저장해 주세요.');
            return;
        }
        if (!this._setBusy(btn, '업로드 중...')) return;

        const formData = new FormData();
        formData.append('action', 'upload_images');
        formData.append('project_id', this.projectId);
        for (let i = 0; i < fileInput.files.length; i++) {
            formData.append('images[]', fileInput.files[i]);
        }

        fetch('post_builder_ajax.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(res => {
            if (res.ok !== false) { // res.ok가 명시적으로 false가 아니면 성공 처리
                this._toast(res.message, 'success');
                const grid = document.getElementById('pb_image_list');
                if (res.images) {
                    // img.name은 업로더가 올린 파일의 원본 파일명(사용자 입력) - innerHTML 문자열조합 대신
                    // textContent로만 넣어 파일명에 HTML이 섞여도 실행되지 않게 한다.
                    res.images.forEach(img => {
                        const item = document.createElement('div');
                        item.className = 'pb-img-item';
                        item.style.cssText = 'display:inline-block; margin:5px; text-align:center;';
                        const imgEl = document.createElement('img');
                        imgEl.src = img.url;
                        imgEl.style.cssText = 'width:100px; height:100px; object-fit:cover; border:1px solid #ddd; border-radius:4px;';
                        const small = document.createElement('small');
                        small.textContent = img.name;
                        item.appendChild(imgEl);
                        item.appendChild(document.createElement('br'));
                        item.appendChild(small);
                        grid.appendChild(item);
                    });
                }
                fileInput.value = '';
            } else {
                alert('업로드 실패: ' + res.error);
            }
        })
        .catch(err => {
            alert('업로드 요청 실패: ' + err);
        })
        .finally(() => { this._clearBusy(btn); });
    },
    
    runSeoCheck: function(btn) {
        if (this.projectId === 0) {
            alert('기본정보(1단계)를 먼저 저장해 주세요.');
            return;
        }
        if (!this._setBusy(btn, '검사 중...')) return;

        document.getElementById('pb_seo_results').innerHTML = "검사 중...";
        const payload = new URLSearchParams();
        payload.append('action', 'seo_check');
        payload.append('builder_state', JSON.stringify(this.gatherData()));

        fetch('post_builder_ajax.php', {
            method: 'POST',
            body: payload
        })
        .then(res => res.json())
        .then(res => {
            if (res.ok !== false) {
                document.getElementById('pb_seo_results').innerHTML = res.html;
            } else {
                alert('SEO 점검 실패: ' + res.error);
            }
        })
        .catch(err => {
            alert('SEO 점검 요청 실패: ' + err);
        })
        .finally(() => { this._clearBusy(btn); });
    },
    renderPreview: function() {
        document.getElementById('pb_preview_area').innerHTML = '<p>미리보기 렌더링 결과입니다...</p>';
    },
    completePost: function(btn) {
        if(confirm("모든 단계를 종합하여 최종 포스팅을 생성하시겠습니까?")) {
            if (!this._setBusy(btn, '완성 처리 중...')) return;
            this.saveStep('all');

            const payload = new URLSearchParams();
            payload.append('action', 'complete_post');
            payload.append('project_id', this.projectId);
            payload.append('builder_state', JSON.stringify(this.gatherData()));

            fetch('post_builder_ajax.php', {
                method: 'POST',
                body: payload
            })
            .then(res => res.json())
            .then(res => {
                if (res.ok) {
                    this._toast(res.message, 'success');
                    document.getElementById('pb_preview_area').innerHTML = res.html;
                    document.getElementById('pb_preview_text_area').value = res.text || '';
                    document.getElementById('pb_post_actions').style.display = 'block';
                    document.getElementById('pb_result_panel').style.display = 'block';
                } else {
                    alert('완성 실패: ' + res.error);
                }
            })
            .catch(err => {
                alert('완성 요청 실패: ' + err);
            })
            .finally(() => { this._clearBusy(btn); });
        }
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
            btnText.classList.add('active');
        } else {
            htmlBox.style.display = 'block';
            textBox.style.display = 'none';
            btnText.classList.remove('active');
            btnHtml.classList.add('active');
        }
    },

    copyHtml: function() {
        const html = document.getElementById('pb_preview_area').innerHTML;
        navigator.clipboard.writeText(html).then(() => {
            this._toast('HTML 내용이 클립보드에 복사되었습니다.', 'success');
        }).catch(err => {
            alert('복사 실패: ' + err);
        });
    },

    copyText: function() {
        const text = document.getElementById('pb_preview_text_area').value;
        navigator.clipboard.writeText(text).then(() => {
            this._toast('텍스트 내용이 클립보드에 복사되었습니다.', 'success');
        }).catch(err => {
            alert('복사 실패: ' + err);
        });
    },

    exportFile: function(type, btn) {
        if (!this._setBusy(btn, '다운로드 준비 중...')) return;

        const payload = new URLSearchParams();
        payload.append('action', 'export_file');
        payload.append('project_id', this.projectId);
        payload.append('type', type);
        payload.append('builder_state', JSON.stringify(this.gatherData()));

        fetch('post_builder_ajax.php', {
            method: 'POST',
            body: payload
        })
        .then(res => {
            if (!res.ok) throw new Error('서버 응답 오류');
            return res.blob();
        })
        .then(blob => {
            const ext = (type === 'html' || type === 'json') ? type : 'txt';
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'post.' + ext;
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(url);
            this._toast('다운로드를 시작했습니다.', 'success');
        })
        .catch(err => {
            alert('다운로드 실패: ' + err);
        })
        .finally(() => { this._clearBusy(btn); });
    },

    showPublishModal: function(btn) {
        if(confirm("선택한 사이트로 바로 발행 대기열에 등록하시겠습니까? (스케줄러가 자동 처리합니다)")) {
            if (!this._setBusy(btn, '등록 중...')) return;
            const payload = new URLSearchParams();
            payload.append('action', 'publish_post');
            payload.append('project_id', this.projectId);
            payload.append('post_id', this.postId);
            payload.append('site_id', document.getElementById('pb_site_id').value);

            fetch('post_builder_ajax.php', {
                method: 'POST',
                body: payload
            })
            .then(res => res.json())
            .then(res => {
                if(res.ok) {
                    this._toast(res.message, 'success');
                } else {
                    alert('발행 등록 실패: ' + res.error);
                }
            })
            .catch(err => {
                alert('발행 등록 요청 실패: ' + err);
            })
            .finally(() => { this._clearBusy(btn); });
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    Builder.init();
});
