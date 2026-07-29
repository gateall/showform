/**
 * 블로그 자동화 - 통합 포스팅 제작 폼 (Unified Post Builder)
 */
const Builder = {
    currentStep: 1,
    maxStep: 12,
    projectId: 0,
    postId: 0,
    
    init: function() {
        this.updateStickyBar();
        this.bindEvents();
        console.log("Builder Initialized");
    },
    
    bindEvents: function() {
        // Auto-save triggers can be added here
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
        console.log("Saving step " + step + "...");
        
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
                
                // alert(res.message);
            } else {
                alert('저장 실패: ' + res.error);
            }
        });
    },
    
    saveCurrentStep: function() {
        this.saveStep(this.currentStep);
    },
    
    saveAll: function() {
        console.log("Saving all steps...");
        this.saveStep('all');
        alert("전체 임시저장 되었습니다.");
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
                        <button type="button" class="btn btn_02" onclick="Builder.generateBlockAI(${blockId})">AI 작성</button>
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
    
    callAi: function(type, blockTitle, targetElementId) {
        if (this.projectId === 0) {
            alert('기본정보(1단계)를 먼저 저장해 주세요.');
            return;
        }
        const stateData = this.gatherData();
        const payload = new URLSearchParams();
        payload.append('action', 'ai_generate');
        payload.append('type', type);
        payload.append('block_title', blockTitle);
        payload.append('builder_state', JSON.stringify(stateData));
        
        document.getElementById(targetElementId).value = "AI 생성 중...";
        
        fetch('post_builder_ajax.php', {
            method: 'POST',
            body: payload
        })
        .then(res => res.json())
        .then(res => {
            if (res.ok) {
                document.getElementById(targetElementId).value = res.generated_text;
            } else {
                alert('AI 생성 실패: ' + res.error);
                document.getElementById(targetElementId).value = "";
            }
        });
    },

    generateDirection: function() { this.callAi('direction', '', 'pb_target_audience'); },
    recommendKeywords: function() { this.callAi('titles', '', 'pb_post_title'); }, // 타이틀 생성으로 임시 매핑
    generateTitles: function() { this.callAi('titles', '', 'pb_post_title'); },
    generateIntro: function() { this.callAi('intro', '', 'pb_intro_text'); },
    generateBlockAI: function(id) {
        const block = document.getElementById('block-' + id);
        const inputs = block.querySelectorAll('.frm_input');
        if (inputs.length < 2) return;
        const blockTitle = inputs[0].value;
        if (!blockTitle) {
            alert('소제목을 먼저 입력해 주세요.');
            return;
        }
        
        inputs[1].value = "AI 생성 중...";
        
        const stateData = this.gatherData();
        const payload = new URLSearchParams();
        payload.append('action', 'ai_generate');
        payload.append('type', 'block');
        payload.append('block_title', blockTitle);
        payload.append('builder_state', JSON.stringify(stateData));
        
        fetch('post_builder_ajax.php', {
            method: 'POST',
            body: payload
        })
        .then(res => res.json())
        .then(res => {
            if (res.ok) {
                inputs[1].value = res.generated_text;
            } else {
                alert('AI 생성 실패: ' + res.error);
                inputs[1].value = "";
            }
        });
    },
    
    uploadImages: function() {
        const fileInput = document.getElementById('pb_image_upload');
        if (fileInput.files.length === 0) {
            alert('업로드할 이미지를 선택해 주세요.');
            return;
        }
        if (this.projectId === 0) {
            alert('기본정보(1단계)를 먼저 저장해 주세요.');
            return;
        }
        
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
                alert(res.message);
                const grid = document.getElementById('pb_image_list');
                if(res.images) {
                    res.images.forEach(img => {
                        grid.innerHTML += `
                            <div class="pb-img-item" style="display:inline-block; margin:5px; text-align:center;">
                                <img src="${img.url}" style="width:100px; height:100px; object-fit:cover; border:1px solid #ddd; border-radius:4px;"><br>
                                <small>${img.name}</small>
                            </div>
                        `;
                    });
                }
                fileInput.value = '';
            } else {
                alert('업로드 실패: ' + res.error);
            }
        });
    },
    
    runSeoCheck: function() { 
        if (this.projectId === 0) {
            alert('기본정보(1단계)를 먼저 저장해 주세요.');
            return;
        }
        
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
        });
    },
    renderPreview: function() {
        document.getElementById('pb_preview_area').innerHTML = '<p>미리보기 렌더링 결과입니다...</p>';
    },
    completePost: function() {
        if(confirm("모든 단계를 종합하여 최종 포스팅을 생성하시겠습니까?")) {
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
                    alert(res.message);
                    document.getElementById('pb_preview_area').innerHTML = res.html;
                    document.getElementById('pb_post_actions').style.display = 'block';
                } else {
                    alert('완성 실패: ' + res.error);
                }
            });
        }
    },
    
    copyHtml: function() {
        const html = document.getElementById('pb_preview_area').innerHTML;
        navigator.clipboard.writeText(html).then(() => {
            alert('HTML 내용이 클립보드에 복사되었습니다.');
        }).catch(err => {
            alert('복사 실패: ' + err);
        });
    },
    
    showPublishModal: function() {
        if(confirm("선택한 사이트로 바로 발행 대기열에 등록하시겠습니까? (스케줄러가 자동 처리합니다)")) {
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
                    alert(res.message);
                } else {
                    alert('발행 등록 실패: ' + res.error);
                }
            });
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    Builder.init();
});
