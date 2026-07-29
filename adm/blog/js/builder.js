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
    
    saveStep: function(step) {
        // TODO: AJAX call to post_builder_ajax.php?action=save_step&step=N
        console.log("Saving step " + step + "...");
        
        const now = new Date();
        const timeStr = now.getHours().toString().padStart(2, '0') + ':' + 
                        now.getMinutes().toString().padStart(2, '0');
        document.getElementById('pb-saved-time').innerText = timeStr;
        
        // 임시 알럿 (실제론 toast 알림 사용 권장)
        // alert(step + '단계가 저장되었습니다.');
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
    
    /* ----------------- AI 및 보조 버튼 더미 함수 ----------------- */
    generateDirection: function() { alert('AI 기획안 추천 (API 연결 예정)'); },
    recommendKeywords: function() { alert('키워드 추천 (API 연결 예정)'); },
    generateTitles: function() { alert('AI 제목 생성 (API 연결 예정)'); },
    generateIntro: function() { alert('AI 도입부 생성 (API 연결 예정)'); },
    generateBlockAI: function(id) { alert('블록 ' + id + ' AI 작성 (API 연결 예정)'); },
    uploadImages: function() { alert('이미지 업로드 (AJAX 연결 예정)'); },
    loadCompanyInfo: function() { alert('광고주/사이트 정보 불러오기 완료'); },
    runSeoCheck: function() { 
        document.getElementById('pb_seo_results').innerHTML = '<span style="color:green;font-weight:bold;">양호</span> (검색어 포함, 분량 적절)';
    },
    renderPreview: function() {
        document.getElementById('pb_preview_area').innerHTML = '<p>미리보기 렌더링 결과입니다...</p>';
    },
    completePost: function() {
        if(confirm("모든 단계를 종합하여 최종 포스팅을 생성하시겠습니까?")) {
            alert("포스팅이 완성되었습니다!");
            document.getElementById('pb_post_actions').style.display = 'block';
        }
    },
    showPublishModal: function() {
        alert("즉시/예약 발행 팝업을 띄웁니다.");
    }
};

document.addEventListener('DOMContentLoaded', () => {
    Builder.init();
});
