<?php
$sub_menu = '360050';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '블로그 포스팅 원스톱 챗봇 작성';

include_once(__DIR__ . '/../layout/header.php');
?>
<link rel="stylesheet" href="<?php echo G5_ADMIN_URL; ?>/blog/css/builder.css?ver=<?php echo G5_SERVER_TIME; ?>">
<script src="<?php echo G5_ADMIN_URL; ?>/blog/js/builder.js?ver=<?php echo G5_SERVER_TIME; ?>"></script>

<div id="post-builder-app">
    <!-- 1. 상단 컨트롤 패널 (Top) -->
    <header class="pb-header pb-panel-area">
        <div class="pb-header-left">
            <select id="top_advertiser_id" class="frm_input" style="width:180px;" onchange="Builder.onTopAdvertiserChange(this.value)">
                <option value="">광고주 선택 ▼</option>
                <?php
                $adv_res = sql_query("select id, name from ".bp_table('advertisers')." order by name");
                while($row = sql_fetch_array($adv_res)) echo "<option value='{$row['id']}'>".get_text($row['name'])."</option>";
                ?>
            </select>
            <select id="top_project_id" class="frm_input" style="width:220px;" onchange="Builder.onTopProjectChange(this.value)" disabled>
                <option value="">프로젝트 선택 ▼</option>
            </select>
            <button type="button" class="btn btn_03" id="btn_new_project_toggle" onclick="Builder.toggleNewProjectForm()">새 프로젝트 등록</button>
            <span style="margin-left: 10px; font-size: 0.9rem; color: #666;">상태: <strong id="pb-status-display">작성 전</strong></span>
        </div>
        <div class="pb-header-right">
            <span style="font-size: 0.85rem; color: #888;">마지막 저장: <span id="pb-saved-time">-</span></span>
            <button type="button" class="btn btn_02" onclick="Builder.saveAll()">임시저장</button>
            <button type="button" class="btn btn_01" onclick="location.href='post_list.php'">작업 목록</button>
        </div>
    </header>

    <!-- 2. 좌측 메뉴 (Left) - 4단계 워크스페이스 -->
    <nav class="pb-sidebar-left pb-panel-area">
        <ul class="pb-nav-list" id="pb_nav_list">
            <li class="pb-nav-item active" onclick="Builder.toggleStep(1)">1. 기획 및 글감 <span class="nav-status" id="nav-status-1"></span></li>
            <li class="pb-nav-item" onclick="Builder.toggleStep(2)">2. 본문 작성 <span class="nav-status" id="nav-status-2"></span></li>
            <li class="pb-nav-item" onclick="Builder.toggleStep(3)">3. 최적화 및 이미지 <span class="nav-status" id="nav-status-3"></span></li>
            <li class="pb-nav-item" onclick="Builder.toggleStep(4)">4. 검수 및 발행 <span class="nav-status" id="nav-status-4"></span></li>
        </ul>
    </nav>

    <!-- 3. 중앙 작업 영역 (Center) -->
    <main class="pb-main pb-panel-area">
        
        <!-- 0단계: 신규 프로젝트 폼 -->
        <div id="step-0" class="pb-step-view">
            <h2 class="pb-step-title">새 프로젝트 등록</h2>
            <div class="pb-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="pb-form-group">
                    <label>광고주</label>
                    <select id="inline_advertiser_id" class="frm_input">
                        <option value="">선택하세요</option>
                        <?php
                        $adv_res = sql_query("select id, name from ".bp_table('advertisers')." order by name");
                        while($row = sql_fetch_array($adv_res)) echo "<option value='{$row['id']}'>".get_text($row['name'])."</option>";
                        ?>
                    </select>
                </div>
                <div class="pb-form-group">
                    <label>핵심 주제 (프로젝트명)</label>
                    <input type="text" id="inline_topic" class="frm_input" placeholder="예: 여름 펜션 홍보">
                </div>
                <div class="pb-form-group">
                    <label>글 목적</label>
                    <select id="inline_content_type" class="frm_input">
                        <option value="info">정보형</option>
                        <option value="promo">홍보형</option>
                        <option value="review">후기형</option>
                    </select>
                </div>
                <div class="pb-form-group">
                    <label>타깃 독자</label>
                    <input type="text" id="inline_target_audience" class="frm_input">
                </div>
            </div>
            <div style="margin-top:20px; text-align:right;">
                <button type="button" class="btn btn_01" onclick="Builder.toggleStep(1)">취소</button>
                <button type="button" class="btn_submit btn" onclick="Builder.saveInlineProject()">등록 후 계속 작성</button>
            </div>
        </div>

        <!-- 1단계: 기획 및 글감 -->
        <div id="step-1" class="pb-step-view active">
            <h2 class="pb-step-title">1. 기획 및 글감 수집</h2>
            
            <div class="pb-form-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px;">
                <div class="pb-form-group" style="margin-bottom:0;">
                    <label>광고주 선택</label>
                    <select id="pb_advertiser_id" class="frm_input">
                        <option value="">선택하세요</option>
                        <?php
                        $adv_res = sql_query("select id, name from ".bp_table('advertisers')." order by name");
                        while($row = sql_fetch_array($adv_res)) echo "<option value='{$row['id']}'>{$row['name']}</option>";
                        ?>
                    </select>
                </div>
                <div class="pb-form-group" style="margin-bottom:0;">
                    <label>사이트 선택</label>
                    <select id="pb_site_id" class="frm_input">
                        <option value="">광고주를 먼저 선택하세요</option>
                    </select>
                    <script>
                        const ADV_SITES = {
                            <?php
                            $sites_res = sql_query("select id, advertiser_id, name from ".bp_table('sites')." order by name");
                            $adv_sites = [];
                            while($s = sql_fetch_array($sites_res)) {
                                $adv_sites[$s['advertiser_id']][] = $s;
                            }
                            foreach ($adv_sites as $adv => $sites) {
                                echo "'$adv': " . json_encode($sites) . ",\n";
                            }
                            ?>
                        };
                        document.getElementById('pb_advertiser_id').addEventListener('change', function() {
                            const adv = this.value;
                            const siteSelect = document.getElementById('pb_site_id');
                            siteSelect.innerHTML = '<option value="">선택하세요</option>';
                            if(ADV_SITES[adv]) {
                                ADV_SITES[adv].forEach(s => {
                                    siteSelect.innerHTML += `<option value="${s.id}">${s.name}</option>`;
                                });
                            }
                        });
                    </script>
                </div>
            </div>

            <h3 style="font-size: 1.1rem; color: #334155; margin-bottom:15px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">AI 글감 자동 수집 도구</h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div style="border:1px solid #cbd5e1; border-radius:6px; padding:15px; background:#f8fafc;">
                    <h3 style="margin-top:0; font-size:1rem; color:#334155;">🔗 URL / 뉴스 분석</h3>
                    <div style="display:flex; gap:5px;">
                        <input type="text" class="frm_input" placeholder="http://..." style="flex:1;">
                        <button type="button" class="btn btn_02">분석</button>
                    </div>
                </div>
                <div style="border:1px solid #cbd5e1; border-radius:6px; padding:15px; background:#f8fafc;">
                    <h3 style="margin-top:0; font-size:1rem; color:#334155;">▶️ 유튜브 링크 분석</h3>
                    <div style="display:flex; gap:5px;">
                        <input type="text" class="frm_input" placeholder="https://youtube.com/..." style="flex:1;">
                        <button type="button" class="btn btn_02">분석</button>
                    </div>
                </div>
                <div style="border:1px solid #cbd5e1; border-radius:6px; padding:15px; background:#f8fafc;">
                    <h3 style="margin-top:0; font-size:1rem; color:#334155;">📄 파일 분석 (PDF/이미지)</h3>
                    <div style="display:flex; gap:5px;">
                        <input type="file" class="frm_input" style="flex:1;">
                        <button type="button" class="btn btn_02">분석</button>
                    </div>
                </div>
                <div style="border:1px solid #cbd5e1; border-radius:6px; padding:15px; background:#f8fafc;">
                    <h3 style="margin-top:0; font-size:1rem; color:#334155;">🎯 경쟁사 키워드 분석</h3>
                    <div style="display:flex; gap:5px;">
                        <input type="text" class="frm_input" placeholder="경쟁사 블로그 주소..." style="flex:1;">
                        <button type="button" class="btn btn_02">분석</button>
                    </div>
                </div>
            </div>
            
            <div style="margin-top:20px;">
                <label style="font-weight:bold; display:block; margin-bottom:5px;">수집된 최종 글감 (직접 메모도 가능합니다)</label>
                <textarea id="pb_extracted_material" class="frm_input" rows="8" placeholder="수집 도구를 통해 가져온 내용이나, 작성하고 싶은 핵심 내용을 이곳에 자유롭게 적어주세요. 우측 AI 비서가 이 내용을 바탕으로 글을 씁니다."></textarea>
            </div>
        </div>

        <!-- 2단계: 본문 작성 -->
        <div id="step-2" class="pb-step-view">
            <h2 class="pb-step-title">2. 원스톱 본문 작성</h2>
            <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">우측 <strong>AI 비서</strong>에게 지시를 내리고, [본문에 적용하기] 버튼을 누르면 이 화면이 채워집니다.</p>
            
            <div class="pb-form-group">
                <label>포스팅 제목</label>
                <input type="text" id="pb_post_title" class="frm_input" placeholder="AI 비서에게 매력적인 제목 5개를 뽑아달라고 요청해 보세요.">
            </div>
            
            <div class="pb-form-group">
                <label>본문 내용 (블록/전체 에디터)</label>
                <textarea id="pb_body_content" class="frm_input" style="height: 350px;" placeholder="AI 비서와 대화하며 서론, 본론, 결론을 순차적으로 덧붙이거나, 전체 글을 한 번에 생성해 보세요."></textarea>
            </div>
            
            <div class="pb-form-group">
                <label>하단 업체/공통 정보 삽입</label>
                <textarea id="pb_closing_text" class="frm_input" rows="4" placeholder="영업시간, 전화번호, 지도 링크 등 글 하단에 고정으로 들어갈 내용을 작성하세요."></textarea>
            </div>
        </div>

        <!-- 3단계: 최적화 및 이미지 -->
        <div id="step-3" class="pb-step-view">
            <h2 class="pb-step-title">3. SEO 최적화 및 이미지</h2>
            
            <div class="pb-form-group">
                <label>메인/보조 타겟 키워드 설정</label>
                <input type="text" id="pb_keywords" class="frm_input" placeholder="쉼표(,)로 구분해 키워드를 입력하세요. 예: 강남역 맛집, 강남 삼겹살">
            </div>
            
            <div id="pb_seo_results" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:20px; min-height:100px; margin-bottom: 25px;">
                <p style="color:#64748b; text-align:center; margin-top:20px;">
                    우측 챗봇 비서에게 <b>"본문 SEO 점수 확인해 줘"</b> 라고 요청하시면<br>키워드 밀도와 구조를 분석해 드립니다.
                </p>
            </div>

            <div style="border:1px solid #cbd5e1; border-radius:6px; padding:15px; background:#fff; margin-bottom:25px;">
                <h3 style="margin-top:0; font-size:1rem; color:#334155;">📝 SEO 메타 설명 자동 생성</h3>
                <p style="font-size:0.85rem; color:#666; margin-bottom:10px;">2단계의 제목·본문과 위 키워드를 분석해 검색결과용 메타 설명을 만듭니다. 이미 입력된 값이 있으면 자동으로 덮어쓰지 않고, AI로 다시 생성할 때만 확인 후 대체합니다.</p>
                <textarea id="pb_meta_description" class="frm_input" rows="3" style="width:100%;" placeholder="AI로 생성하거나 직접 입력하세요 (80~160자 권장)" oninput="Builder.updateMetaLength()"></textarea>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-top:8px;">
                    <span id="pb_meta_length_counter" style="font-size:0.8rem; color:#888;">0 / 80~160자</span>
                    <div style="display:flex; gap:8px;">
                        <button type="button" class="btn btn_02" onclick="Builder.generateSeoMeta(this)">AI로 생성</button>
                        <button type="button" class="btn_submit btn" onclick="Builder.saveSeoMeta(this)">저장</button>
                    </div>
                </div>
            </div>

            <h3 style="font-size: 1.1rem; color: #334155; margin-bottom:15px; border-bottom:1px solid #e2e8f0; padding-bottom:10px;">이미지 삽입 (자동 생성 / 직접 업로드)</h3>
            <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">우측 비서에게 "키워드에 어울리는 이미지 2개 그려줘" 라고 요청할 수 있습니다.</p>
            
            <div id="pb_image_list" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:15px;"></div>
            <div>
                <input type="file" id="pb_image_upload" multiple accept="image/*" class="frm_input" style="width:auto;">
                <button type="button" class="btn btn_02" onclick="Builder.uploadImages(this)">PC에서 이미지 업로드</button>
            </div>
        </div>

        <!-- 4단계: 검수 및 발행 -->
        <div id="step-4" class="pb-step-view">
            <h2 class="pb-step-title">4. 검수 및 발행</h2>
            
            <div style="display:flex; gap:5px; margin-bottom:10px;">
                <button type="button" class="btn btn_03 active" id="pb_tab_btn_html" onclick="Builder.switchPreviewTab('html')" style="background:#fff; color:#333; font-weight:bold;">블로그 모바일뷰(HTML)</button>
                <button type="button" class="btn btn_03" id="pb_tab_btn_text" onclick="Builder.switchPreviewTab('text')">원시 텍스트</button>
            </div>
            
            <div id="pb_preview_area" style="border:1px solid #cbd5e1; border-radius:6px; padding:20px; min-height:400px; background:#fff;">
                <p style="color:#64748b; text-align:center; margin-top:150px;">우측 비서에게 "최종 검수용 HTML 코드 만들어줘" 라고 요청하세요.</p>
            </div>
            <textarea id="pb_preview_text_area" class="frm_input" style="width:100%; display:none; min-height:400px;" readonly></textarea>
            
            <div style="margin-top:20px; text-align:right; border-top: 1px solid #e2e8f0; padding-top: 20px;">
                <div style="display:inline-block; margin-right: 15px;">
                    <label>예약 발행 일시: </label>
                    <input type="datetime-local" id="pb_target_date" class="frm_input" style="width: 200px; display:inline-block;">
                </div>
                <button type="button" class="btn_submit btn" onclick="alert('발행 큐에 등록되었습니다!')" style="background:#4f46e5; border-color:#4338ca;">🚀 네이버 블로그로 발행하기</button>
            </div>
        </div>

    </main>

    <!-- 4. 우측 챗봇 비서 (Right) -->
    <aside class="pb-sidebar-right pb-panel-area">
        <div class="pb-chat-header">
            <span>🤖 AI 콘텐츠 운영 비서</span>
            <span style="font-size:0.8rem; font-weight:normal; color:#10b981; display:flex; align-items:center; gap:5px;" id="pb_chat_status"><span style="display:inline-block; width:8px; height:8px; background:#10b981; border-radius:50%;"></span> 온라인</span>
        </div>
        
        <div class="pb-chat-messages" id="pb_chat_messages">
            <!-- 기본 환영 메시지 -->
            <div class="pb-chat-bubble pb-chat-ai">
                안녕하세요! <b>ShowForm AI 콘텐츠 비서</b>입니다.<br><br>
                이제 수동으로 버튼을 누를 필요 없이 저와 대화하며 포스팅을 작성할 수 있습니다.<br><br>
                1단계에서 글감을 수집하신 후, 저에게 <b>"이 글감을 바탕으로 클릭을 유도하는 제목 5개 추천해 줘"</b> 라고 말씀해 보세요!
            </div>
        </div>
        
        <div class="pb-chat-input-area">
            <textarea id="pb_chat_input" placeholder="AI 비서에게 요청할 내용을 입력하세요 (Enter 전송, Shift+Enter 줄바꿈)..." onkeydown="if(event.key === 'Enter' && !event.shiftKey) { event.preventDefault(); Builder.sendChatMessage(); }"></textarea>
            <button class="pb-chat-send-btn" onclick="Builder.sendChatMessage()" title="전송">➤</button>
        </div>
    </aside>

</div>

<?php
include_once(__DIR__ . '/../layout/footer.php');
