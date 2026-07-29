/* blog-studio.js */
document.addEventListener('DOMContentLoaded', () => {
    renderStudio();
    calculateProgress();
    
    // Auto-save every 30 seconds if there are changes
    setInterval(() => {
        saveAll(true);
    }, 30000);
});

// Render the entire studio based on window.StudioState
function renderStudio() {
    const container = document.getElementById('builder_sections');
    container.innerHTML = '';
    
    // 1. Basic Info Section
    container.appendChild(createSection('sec_basic', '기본 설정', `
        <div class="form-row">
            <label>글의 말투 (Tone)</label>
            <select class="studio-input" onchange="updateState('basic', 'tone', this.value)">
                <option value="전문적인 설명" ${window.StudioState.basic.tone === '전문적인 설명' ? 'selected' : ''}>전문적인 설명 (~습니다)</option>
                <option value="자연스러운 블로그체" ${window.StudioState.basic.tone === '자연스러운 블로그체' ? 'selected' : ''}>자연스러운 블로그체 (~해요)</option>
                <option value="친근한 소통형" ${window.StudioState.basic.tone === '친근한 소통형' ? 'selected' : ''}>친근한 소통형</option>
            </select>
        </div>
        <div class="form-row mt-2">
            <label>주 타겟층</label>
            <input type="text" class="studio-input" value="${window.StudioState.basic.target_audience}" onchange="updateState('basic', 'target_audience', this.value)" placeholder="예: 30대 직장인 남성">
        </div>
    `));
    
    // 2. Title & Keywords Section
    container.appendChild(createSection('sec_title', '제목 및 키워드 기획', `
        <div class="form-row">
            <label>메인 키워드</label>
            <input type="text" class="studio-input" value="${window.StudioState.title.main_keyword}" onchange="updateState('title', 'main_keyword', this.value)" placeholder="예: 강남 맛집">
        </div>
        <div class="form-row mt-2">
            <label>포스팅 제목 (메인 타이틀)</label>
            <div style="display:flex; gap:10px;">
                <input type="text" class="studio-input" style="flex:1;" value="${window.StudioState.title.title_text}" onchange="updateState('title', 'title_text', this.value); document.getElementById('top_title_display').innerText = this.value || '새 포스팅';" placeholder="여기에 제목을 입력하세요">
                <button type="button" class="studio-btn btn-outline" onclick="generateTitle()">AI 자동 생성</button>
            </div>
        </div>
    `));
    
    // 3. Body Sections (Dynamic)
    window.StudioState.body.forEach((block, index) => {
        container.appendChild(createBodySection(block, index));
    });
}

function createSection(id, title, htmlContent) {
    const section = document.createElement('section');
    section.className = 'studio-section';
    section.id = id;
    section.innerHTML = `
        <div class="sec-header" onclick="toggleSection(this)">
            <h2>${title}</h2>
            <div class="sec-status">
                <span class="badge badge-gray">대기 중</span>
                <span class="toggle-icon">▼</span>
            </div>
        </div>
        <div class="sec-body">
            ${htmlContent}
        </div>
    `;
    return section;
}

function createBodySection(block, index) {
    const section = document.createElement('section');
    section.className = 'studio-section body-section';
    section.id = block.id;
    section.dataset.index = index;
    
    section.innerHTML = `
        <div class="sec-header" onclick="toggleSection(this)">
            <h2>본문 ${index + 1}단락 - <input type="text" class="transparent-input" value="${block.subtitle}" onchange="updateBodyState('${block.id}', 'subtitle', this.value)" onclick="event.stopPropagation()"></h2>
            <div class="sec-status">
                <button type="button" class="btn-icon" onclick="removeBodyBlock('${block.id}'); event.stopPropagation();">🗑️</button>
                <span class="toggle-icon">▼</span>
            </div>
        </div>
        <div class="sec-body">
            <textarea class="studio-textarea" rows="6" onchange="updateBodyState('${block.id}', 'content', this.value)" placeholder="여기에 본문 내용을 작성하세요. AI 자동 생성 버튼을 누르면 이 영역이 채워집니다.">${block.content}</textarea>
        </div>
    `;
    return section;
}

function toggleSection(headerEl) {
    const bodyEl = headerEl.nextElementSibling;
    if (bodyEl.style.display === 'none') {
        bodyEl.style.display = 'block';
    } else {
        bodyEl.style.display = 'none';
    }
}

function updateState(category, key, value) {
    window.StudioState[category][key] = value;
    calculateProgress();
}

function updateBodyState(blockId, key, value) {
    const block = window.StudioState.body.find(b => b.id === blockId);
    if (block) {
        block[key] = value;
    }
    calculateProgress();
}

function addBodyBlock() {
    const newId = 'block_' + Date.now();
    window.StudioState.body.push({
        id: newId,
        subtitle: '새로운 단락',
        content: ''
    });
    renderStudio();
    calculateProgress();
}

function removeBodyBlock(blockId) {
    if(confirm('이 단락을 삭제하시겠습니까?')) {
        window.StudioState.body = window.StudioState.body.filter(b => b.id !== blockId);
        renderStudio();
        calculateProgress();
    }
}

function calculateProgress() {
    let score = 0;
    let total = 3; // Basic, Title, At least 1 body
    
    if (window.StudioState.basic.target_audience) score++;
    if (window.StudioState.title.main_keyword && window.StudioState.title.title_text) score++;
    
    const validBodies = window.StudioState.body.filter(b => b.content.length > 50).length;
    if (validBodies > 0) score++;
    
    const progress = Math.round((score / total) * 100);
    document.getElementById('top_progress').innerText = `완성률 ${progress > 100 ? 100 : progress}%`;
}

function saveAll(isAuto = false) {
    if (!isAuto) {
        document.getElementById('top_save_time').innerText = "저장 중...";
    }
    
    fetch(window.G5_URL + '/blog-studio/ajax/save_post.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            post_id: window.POST_ID,
            state: window.StudioState
        })
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            const d = new Date();
            document.getElementById('top_save_time').innerText = `${d.getHours()}:${d.getMinutes()} 저장됨`;
            if (!isAuto) alert("저장되었습니다.");
        } else {
            if (!isAuto) alert("저장 실패: " + data.message);
        }
    })
    .catch(e => {
        if (!isAuto) alert("네트워크 오류 발생");
    });
}

function completePost() {
    saveAll();
    alert("작성이 완료되었습니다! 포스팅 목록으로 이동합니다.");
    window.location.href = window.G5_URL + '/adm/blog/project_list.php';
}

function previewPost() {
    alert("미리보기 화면 (개발 예정)");
}

// AI Actions
function openAIAgentSettings() { 
    window.location.href = window.G5_URL + '/adm/blog/ai_provider_list.php'; 
}

async function generateAll() { 
    if(!confirm("제목과 모든 본문을 AI가 자동으로 작성합니다. 계속하시겠습니까?")) return;
    
    // 1. Generate Title
    await generateTitle();
    
    // 2. Generate each body block
    for (let block of window.StudioState.body) {
        await generateFocusBody(block.id);
    }
    
    alert("전체 자동 생성이 완료되었습니다!");
}

async function generateTitle() { 
    if(!window.StudioState.title.main_keyword) {
        alert("메인 키워드를 먼저 입력해주세요.");
        return;
    }
    
    document.getElementById('top_title_display').innerText = "제목 생성 중...";
    try {
        const res = await fetch(window.G5_URL + '/blog-studio/ajax/generate_ai.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'title', state: window.StudioState })
        });
        const data = await res.json();
        if(data.success) {
            updateState('title', 'title_text', data.data);
            renderStudio();
        } else {
            alert("생성 실패: " + data.message);
        }
    } catch(e) {
        alert("네트워크 오류");
    }
    document.getElementById('top_title_display').innerText = window.StudioState.title.title_text;
}

async function generateFocusBody(specificBlockId = null) { 
    // Find active block or use specific
    let blockId = specificBlockId;
    if(!blockId) {
        // Default to the first empty block for demo purposes if not specified
        const emptyBlock = window.StudioState.body.find(b => !b.content);
        if(emptyBlock) blockId = emptyBlock.id;
        else blockId = window.StudioState.body[0].id;
    }

    if(!window.StudioState.title.title_text) {
        alert("먼저 제목을 생성하거나 입력해주세요.");
        return;
    }

    document.getElementById('top_save_time').innerText = "AI 본문 작성 중...";
    try {
        const res = await fetch(window.G5_URL + '/blog-studio/ajax/generate_ai.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'body', block_id: blockId, state: window.StudioState })
        });
        const data = await res.json();
        if(data.success) {
            updateBodyState(blockId, 'content', data.data);
            renderStudio();
        } else {
            alert("생성 실패: " + data.message);
        }
    } catch(e) {
        alert("네트워크 오류");
    }
    document.getElementById('top_save_time').innerText = "작성 완료";
}

function generateImage() { alert("현재 단락에 어울리는 이미지를 AI가 생성합니다."); }
function generateKeywords() { alert("키워드와 해시태그를 추천합니다."); }
