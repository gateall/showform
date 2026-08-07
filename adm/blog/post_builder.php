<?php
$sub_menu = '360050';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '블로그 포스팅 통합 제작';
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/blog/css/builder.css?ver='.G5_SERVER_TIME.'">', 0);
add_javascript('<script src="'.G5_ADMIN_URL.'/blog/js/builder.js?ver='.G5_SERVER_TIME.'"></script>', 0);

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>

<div id="post-builder-app">
    <!-- 상단 상태바 -->
    <header class="pb-header">
        <div class="pb-status-info">
            <h2 id="pb-title-display">새 포스팅 작성 중</h2>
            <div class="pb-meta">
                <span id="pb-advertiser-display">광고주 미지정</span> | 
                <span id="pb-site-display">사이트 미지정</span>
            </div>
        </div>
        <div class="pb-status-actions">
            <span class="pb-last-saved">마지막 저장: <span id="pb-saved-time">-</span></span>
            <button type="button" class="btn btn_02" onclick="Builder.saveAll()">전체 임시저장</button>
            <button type="button" class="btn btn_01" onclick="Builder.loadList()">목록/불러오기</button>
        </div>
    </header>

    <div class="pb-container">
        <!-- 메인 작성 영역 -->
        <main class="pb-main">
            
            <!-- 1단계: 기본정보 -->
            <section class="pb-step active" id="step-1">
                <div class="pb-step-header" onclick="Builder.toggleStep(1)">
                    <h3>1. 제작 기본정보</h3>
                    <span class="pb-step-indicator"></span>
                </div>
                <div class="pb-step-body">
                    <div class="pb-form-grid">
                        <div class="pb-form-group">
                            <label>광고주 선택</label>
                            <select id="pb_advertiser_id" class="frm_input">
                                <option value="">선택하세요</option>
                                <?php
                                $adv_res = sql_query("select id, name from ".bp_table('advertisers')." order by name");
                                while($row = sql_fetch_array($adv_res)) echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                ?>
                            </select>
                        </div>
                        <div class="pb-form-group">
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
                        <div class="pb-form-group">
                            <label>포스팅 유형</label>
                            <select id="pb_post_type" class="frm_input">
                                <option value="info">정보 제공형</option>
                                <option value="product">상품 소개형</option>
                                <option value="review">후기형</option>
                                <option value="faq">FAQ형</option>
                            </select>
                        </div>
                        <div class="pb-form-group">
                            <label>발행 예정일</label>
                            <input type="date" id="pb_target_date" class="frm_input">
                        </div>
                    </div>
                    <div class="pb-step-actions">
                        <button type="button" class="btn_submit btn" onclick="Builder.saveStep(1)">기본정보 저장</button>
                    </div>
                </div>
            </section>

            <!-- 2단계: 기획 (목적과 방향) -->
            <section class="pb-step" id="step-2">
                <div class="pb-step-header" onclick="Builder.toggleStep(2)">
                    <h3>2. 글의 목적과 방향</h3>
                    <span class="pb-step-indicator"></span>
                </div>
                <div class="pb-step-body">
                    <div class="pb-form-group">
                        <label>주요 독자 및 목적</label>
                        <textarea id="pb_target_audience" class="frm_input" rows="3" placeholder="예: 20~30대 직장인, 피로 회복제 홍보"></textarea>
                    </div>
                    <div class="pb-form-group">
                        <label>글의 분위기(말투)</label>
                        <select id="pb_tone" class="frm_input">
                            <option value="전문적인 설명">전문적인 설명 (~습니다)</option>
                            <option value="친근한 상담">친근한 상담 (~해요)</option>
                            <option value="실제 후기">실제 후기 (~했어요)</option>
                            <option value="자연스러운 블로그">자연스러운 블로그체</option>
                        </select>
                    </div>
                    <div class="pb-step-actions">
                        <button type="button" class="btn btn_02" onclick="Builder.generateDirection()">AI 기획안 추천</button>
                        <button type="button" class="btn_submit btn" onclick="Builder.saveStep(2)">방향 저장</button>
                    </div>
                </div>
            </section>

            <!-- 3단계: 키워드 설정 -->
            <section class="pb-step" id="step-3">
                <div class="pb-step-header" onclick="Builder.toggleStep(3)">
                    <h3>3. 키워드 설정</h3>
                    <span class="pb-step-indicator"></span>
                </div>
                <div class="pb-step-body">
                    <div class="pb-form-group">
                        <label>대표 키워드 (1개)</label>
                        <input type="text" id="pb_main_keyword" class="frm_input" style="width:100%;">
                    </div>
                    <div class="pb-form-group">
                        <label>보조 키워드 (쉼표 구분)</label>
                        <input type="text" id="pb_sub_keywords" class="frm_input" style="width:100%;">
                    </div>
                    <div class="pb-step-actions">
                        <button type="button" class="btn btn_02" onclick="Builder.recommendKeywords()">키워드 조합 추천</button>
                        <button type="button" class="btn_submit btn" onclick="Builder.saveStep(3)">키워드 저장</button>
                    </div>
                </div>
            </section>

            <!-- 4단계: 제목과 목차 -->
            <section class="pb-step" id="step-4">
                <div class="pb-step-header" onclick="Builder.toggleStep(4)">
                    <h3>4. 제목과 목차</h3>
                    <span class="pb-step-indicator"></span>
                </div>
                <div class="pb-step-body">
                    <div class="pb-form-group">
                        <label>메인 제목</label>
                        <input type="text" id="pb_post_title" class="frm_input" style="width:100%;">
                    </div>
                    <div class="pb-form-group">
                        <label>목차 구성</label>
                        <ul id="pb_toc_list" class="pb-sortable-list">
                            <li><input type="text" class="frm_input pb-toc-item" value="독자의 문제 또는 관심사"> <button type="button" class="btn_del" onclick="this.parentElement.remove()">X</button></li>
                            <li><input type="text" class="frm_input pb-toc-item" value="서비스 소개"> <button type="button" class="btn_del" onclick="this.parentElement.remove()">X</button></li>
                        </ul>
                        <button type="button" class="btn btn_03" onclick="Builder.addTocItem()">+ 목차 추가</button>
                    </div>
                    <div class="pb-step-actions">
                        <button type="button" class="btn btn_02" onclick="Builder.generateTitles()">AI 제목 생성</button>
                        <button type="button" class="btn_submit btn" onclick="Builder.saveStep(4)">저장</button>
                    </div>
                </div>
            </section>

            <!-- 5단계: 도입부 -->
            <section class="pb-step" id="step-5">
                <div class="pb-step-header" onclick="Builder.toggleStep(5)">
                    <h3>5. 도입부 작성</h3>
                    <span class="pb-step-indicator"></span>
                </div>
                <div class="pb-step-body">
                    <textarea id="pb_intro_text" class="frm_input" rows="5" style="width:100%;"></textarea>
                    <div class="pb-step-actions">
                        <button type="button" class="btn btn_02" onclick="Builder.generateIntro()">AI 도입부 생성</button>
                        <button type="button" class="btn_submit btn" onclick="Builder.saveStep(5)">도입부 저장</button>
                    </div>
                </div>
            </section>

            <!-- 6단계: 본문 구간 작성 (핵심) -->
            <section class="pb-step" id="step-6">
                <div class="pb-step-header" onclick="Builder.toggleStep(6)">
                    <h3>6. 본문 구간 작성</h3>
                    <span class="pb-step-indicator"></span>
                </div>
                <div class="pb-step-body" style="background:#f9f9f9; padding:20px; border-radius:8px;">
                    <p class="help_txt">목차별로 블록을 나누어 작성합니다. 블록 단위로 AI 생성이 가능합니다.</p>
                    <div id="pb_body_blocks">
                        <!-- JS Dynamic Blocks -->
                    </div>
                    <button type="button" class="btn btn_03" onclick="Builder.addBodyBlock()" style="width:100%; margin-top:10px;">+ 새 본문 블록 추가</button>
                    
                    <div class="pb-step-actions" style="margin-top:20px;">
                        <button type="button" class="btn_submit btn" onclick="Builder.saveStep(6)">전체 본문 저장</button>
                    </div>
                </div>
            </section>

            <!-- 7단계: 이미지 -->
            <section class="pb-step" id="step-7">
                <div class="pb-step-header" onclick="Builder.toggleStep(7)">
                    <h3>7. 이미지 구성</h3>
                    <span class="pb-step-indicator"></span>
                </div>
                <div class="pb-step-body">
                    <p>본문에 삽입될 이미지를 업로드하고 순서를 정합니다. (이미지는 드래그로 본문 블록에 매핑 가능합니다)</p>
                    <div id="pb_image_list" class="pb-image-grid"></div>
                    <div style="margin-top:15px;">
                        <input type="file" id="pb_image_upload" multiple accept="image/*">
                        <button type="button" class="btn btn_02" onclick="Builder.uploadImages()">업로드</button>
                    </div>
                    <div class="pb-step-actions">
                        <button type="button" class="btn_submit btn" onclick="Builder.saveStep(7)">저장</button>
                    </div>
                </div>
            </section>
            
            <!-- 8단계: 업체 정보 -->
            <section class="pb-step" id="step-8">
                <div class="pb-step-header" onclick="Builder.toggleStep(8)">
                    <h3>8. 업체·상품 정보</h3>
                    <span class="pb-step-indicator"></span>
                </div>
                <div class="pb-step-body">
                    <div class="pb-form-grid">
                        <div class="pb-form-group">
                            <label>상호명</label>
                            <input type="text" id="pb_company_name" class="frm_input">
                        </div>
                        <div class="pb-form-group">
                            <label>전화번호</label>
                            <input type="text" id="pb_company_tel" class="frm_input">
                        </div>
                        <div class="pb-form-group">
                            <label>주소</label>
                            <input type="text" id="pb_company_addr" class="frm_input">
                        </div>
                        <div class="pb-form-group">
                            <label>지도/홈페이지 링크</label>
                            <input type="text" id="pb_company_link" class="frm_input">
                        </div>
                    </div>
                    <div class="pb-step-actions">
                        <button type="button" class="btn btn_02" onclick="Builder.loadCompanyInfo()">광고주 정보 불러오기</button>
                        <button type="button" class="btn_submit btn" onclick="Builder.saveStep(8)">저장</button>
                    </div>
                </div>
            </section>

            <!-- 9단계: 마무리 -->
            <section class="pb-step" id="step-9">
                <div class="pb-step-header" onclick="Builder.toggleStep(9)">
                    <h3>9. 마무리와 행동 유도</h3>
                    <span class="pb-step-indicator"></span>
                </div>
                <div class="pb-step-body">
                    <textarea id="pb_closing_text" class="frm_input" rows="4" style="width:100%;" placeholder="문의 안내 및 맺음말"></textarea>
                    <div class="pb-step-actions">
                        <button type="button" class="btn_submit btn" onclick="Builder.saveStep(9)">마무리 저장</button>
                    </div>
                </div>
            </section>

            <!-- 10단계: 검색 최적화 -->
            <section class="pb-step" id="step-10">
                <div class="pb-step-header" onclick="Builder.toggleStep(10)">
                    <h3>10. 검색 최적화(SEO)</h3>
                    <span class="pb-step-indicator"></span>
                </div>
                <div class="pb-step-body">
                    <div id="pb_seo_results" class="pb-seo-box">
                        점검을 실행해주세요.
                    </div>
                    <div class="pb-step-actions">
                        <button type="button" class="btn btn_02" onclick="Builder.runSeoCheck()">최적화 점검 실행</button>
                    </div>
                </div>
            </section>

            <!-- 11단계: 미리보기 -->
            <section class="pb-step" id="step-11">
                <div class="pb-step-header" onclick="Builder.toggleStep(11)">
                    <h3>11. 최종 미리보기</h3>
                    <span class="pb-step-indicator"></span>
                </div>
                <div class="pb-step-body">
                    <div id="pb_preview_area" class="pb-preview-box">
                        미리보기 영역
                    </div>
                    <div class="pb-step-actions">
                        <button type="button" class="btn btn_02" onclick="Builder.renderPreview()">미리보기 갱신</button>
                    </div>
                </div>
            </section>

        </main>
        
        <!-- 우측 사이드바 (데스크톱용 상태 패널) -->
        <aside class="pb-sidebar">
            <div class="pb-panel">
                <h3>진행 상태</h3>
                <ul id="pb_progress_list">
                    <!-- JS로 채워짐 -->
                </ul>
            </div>
            <div class="pb-panel">
                <h3>최종 액션</h3>
                <button type="button" class="btn_submit btn" style="width:100%; padding:15px; font-size:16px;" onclick="Builder.completePost()">포스팅 완성하기</button>
                
                <div id="pb_post_actions" style="display:none; margin-top:20px;">
                    <hr>
                    <button type="button" class="btn btn_01" style="width:100%; margin-bottom:5px;" onclick="Builder.copyHtml()">HTML 복사</button>
                    <button type="button" class="btn btn_02" style="width:100%; margin-bottom:5px;" onclick="Builder.showPublishModal()">즉시/예약 발행</button>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- 하단 고정 바 (Sticky Bar) -->
<div class="pb-sticky-bar">
    <button type="button" class="btn btn_01" onclick="Builder.prevStep()">이전 단계</button>
    <span class="pb-current-step-label">현재: <span id="pb_sticky_step_name">1. 기본정보</span></span>
    <button type="button" class="btn btn_02" onclick="Builder.saveCurrentStep()">현재 단계 저장</button>
    <button type="button" class="btn_submit btn" onclick="Builder.nextStep()">다음 단계</button>
</div>

<!-- 발행 모달 -->
<div id="pb-publish-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9000;"></div>
<div id="pb-publish-modal" style="display:none; position:fixed; top:50%; left:50%; transform:translate(-50%,-50%);
     z-index:9001; width:min(580px,92vw); max-height:80vh; overflow-y:auto;
     background:#fff; border-radius:12px; box-shadow:0 20px 60px rgba(0,0,0,0.25); flex-direction:column;">
  <div style="display:flex; justify-content:space-between; align-items:center; padding:18px 22px 14px; border-bottom:1px solid #e2e8f0;">
    <h3 style="margin:0; font-size:17px;">🚀 발행 설정</h3>
    <button onclick="Builder.closePublishModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#718096;">✕</button>
  </div>
  <div style="display:flex; border-bottom:2px solid #e2e8f0; padding:12px 22px 0;">
    <button id="pb-pub-tab-imm" onclick="Builder.switchPubTab('immediate')" style="border:none; border-bottom:3px solid #3182ce; background:none; color:#3182ce; font-weight:700; padding:6px 14px; cursor:pointer;">⚡ 즉시</button>
    <button id="pb-pub-tab-sch" onclick="Builder.switchPubTab('scheduled')" style="border:none; border-bottom:3px solid transparent; background:none; color:#718096; font-weight:600; padding:6px 14px; cursor:pointer;">🕐 예약</button>
  </div>
  <div id="pb-pub-panel-imm" style="padding:18px 22px;">
    <p style="font-size:13px;color:#718096;margin:0 0 12px;">지금 바로 각 사이트에 발행합니다.</p>
    <div id="pb-pub-targets-imm"><span style="color:#999;">로딩 중...</span></div>
  </div>
  <div id="pb-pub-panel-sch" style="display:none; padding:18px 22px;">
    <p style="font-size:13px;color:#718096;margin:0 0 12px;">지정 시각에 자동으로 발행됩니다.</p>
    <div id="pb-pub-targets-sch"><span style="color:#999;">로딩 중...</span></div>
    <div style="margin-top:12px; padding-top:12px; border-top:1px solid #e2e8f0; display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
      <label style="font-size:13px;font-weight:600;">예약 시각</label>
      <input type="datetime-local" id="pb-pub-scheduled-at" style="flex:1;min-width:180px;padding:7px 10px;border:1px solid #cbd5e0;border-radius:6px;font-size:14px;">
      <span style="font-size:11px;color:#718096;width:100%;">현재보다 미래 시각만 가능합니다.</span>
    </div>
  </div>
  <div id="pb-pub-result" style="display:none; margin:0 22px 14px; padding:12px 14px; background:#f7fafc; border:1px solid #e2e8f0; border-radius:8px;"></div>
  <div style="display:flex; justify-content:flex-end; gap:8px; padding:14px 22px 18px; border-top:1px solid #e2e8f0;">
    <button class="btn btn_02" onclick="Builder.closePublishModal()" id="pb-pub-cancel-btn">취소</button>
    <button class="btn btn_01" id="pb-pub-submit-btn" onclick="Builder.submitPublish()" style="background:linear-gradient(135deg,#667eea,#764ba2);color:#fff;border:none;font-weight:700;">발행 시작</button>
  </div>
</div>

<script>
(function() {
    var _pbTab     = 'immediate';
    var _pbTargets = [];
    var _pbBusy    = false;
    var _pbPostId  = 0;

    Builder.showPublishModal = function() {
        _pbBusy   = false;
        _pbTab    = 'immediate';
        _pbPostId = (typeof Builder.getPostId === 'function') ? Builder.getPostId() : 0;
        Builder.switchPubTab('immediate');
        // 예약 시각 기본값: 1시간 뒤
        var d = new Date(Date.now() + 3600000);
        var p = function(n){ return ('0'+n).slice(-2); };
        document.getElementById('pb-pub-scheduled-at').value =
            d.getFullYear()+'-'+p(d.getMonth()+1)+'-'+p(d.getDate())+'T'+p(d.getHours())+':00';
        document.getElementById('pb-pub-result').style.display = 'none';
        var btn = document.getElementById('pb-pub-submit-btn');
        btn.disabled = false; btn.textContent = '발행 시작';
        _pbLoadTargets();
        document.getElementById('pb-publish-overlay').style.display = '';
        document.getElementById('pb-publish-modal').style.display = 'flex';
    };

    Builder.closePublishModal = function() {
        if (_pbBusy) return;
        document.getElementById('pb-publish-overlay').style.display = 'none';
        document.getElementById('pb-publish-modal').style.display = 'none';
    };

    Builder.switchPubTab = function(type) {
        _pbTab = type;
        var isImm = (type === 'immediate');
        var ti = document.getElementById('pb-pub-tab-imm');
        var ts = document.getElementById('pb-pub-tab-sch');
        ti.style.borderBottomColor = isImm ? '#3182ce' : 'transparent';
        ti.style.color = isImm ? '#3182ce' : '#718096';
        ts.style.borderBottomColor = isImm ? 'transparent' : '#3182ce';
        ts.style.color = isImm ? '#718096' : '#3182ce';
        document.getElementById('pb-pub-panel-imm').style.display = isImm ? '' : 'none';
        document.getElementById('pb-pub-panel-sch').style.display = isImm ? 'none' : '';
        document.getElementById('pb-pub-result').style.display = 'none';
    };

    function _pbAjaxUrl() {
        return (typeof SF_MANAGER_URL !== 'undefined') ?
            SF_MANAGER_URL + '/blog/post_builder_ajax.php' :
            'post_builder_ajax.php';
    }

    function _pbStatusBadge(s) {
        var m = {draft:'작성중',ready:'승인대기',queued:'발행대기',publishing:'발행중',scheduled:'예약됨',published:'발행완료',failed:'실패',cancelled:'취소됨'};
        var bg = s==='published' ? '#d1fae5' : s==='failed' ? '#fee2e2' : (s==='publishing'||s==='queued') ? '#e0e7ff' : '#fef3c7';
        return '<span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;background:'+bg+';">'+(m[s]||s)+'</span>';
    }

    function _pbLoadTargets() {
        if (!_pbPostId) {
            ['pb-pub-targets-imm','pb-pub-targets-sch'].forEach(function(id){
                document.getElementById(id).innerHTML = '<span style="color:red;">포스트를 먼저 완성해주세요.</span>';
            });
            return;
        }
        $.post(_pbAjaxUrl(), { action: 'get_post_targets', post_id: _pbPostId }, function(res) {
            _pbTargets = res.ok ? (res.targets||[]) : [];
            _pbRenderTargets('pb-pub-targets-imm');
            _pbRenderTargets('pb-pub-targets-sch');
        }, 'json');
    }

    function _pbRenderTargets(elId) {
        var el = document.getElementById(elId);
        if (!_pbTargets.length) { el.innerHTML = '<p style="color:#718096;text-align:center;">연결된 사이트가 없습니다.</p>'; return; }
        var html = '';
        _pbTargets.forEach(function(t) {
            var busy = t.has_active_job;
            var pub  = (t.publish_status === 'published');
            var chk  = (!busy && !pub) ? 'checked' : '';
            var dis  = (busy || pub)   ? 'disabled' : '';
            var note = busy ? '<small style="color:#e6a817;">처리중</small>' : '';
            if (pub && t.published_url) note = '<a href="'+t.published_url+'" target="_blank" style="font-size:11px;">발행URL</a>';
            html += '<label style="display:flex;align-items:center;gap:8px;padding:8px 12px;border:1px solid #e2e8f0;border-radius:6px;margin-bottom:6px;font-size:14px;cursor:pointer;'+
                    (dis?'opacity:.6;cursor:default;':'')+'">'+
                    '<input type="checkbox" class="pb-pub-chk" value="'+t.site_id+'" '+chk+' '+dis+'>'+
                    '<strong>'+t.site_name+'</strong> <small style="color:#718096;">('+t.platform+')</small>'+
                    _pbStatusBadge(t.publish_status)+note+'</label>';
        });
        el.innerHTML = html;
    }

    Builder.submitPublish = function() {
        if (_pbBusy) return;
        var panelSel = (_pbTab==='immediate') ? '#pb-pub-panel-imm .pb-pub-chk:checked' : '#pb-pub-panel-sch .pb-pub-chk:checked';
        var checks = document.querySelectorAll(panelSel);
        if (!checks.length) { alert('발행할 사이트를 선택해주세요.'); return; }
        var schedType = (_pbTab==='immediate') ? 'immediate' : 'scheduled';
        var schedAt   = '';
        if (schedType === 'scheduled') {
            schedAt = document.getElementById('pb-pub-scheduled-at').value;
            if (!schedAt) { alert('예약 시각을 입력해주세요.'); return; }
            schedAt = schedAt.replace('T',' ')+':00';
        }
        _pbBusy = true;
        var submitBtn = document.getElementById('pb-pub-submit-btn');
        submitBtn.disabled = true; submitBtn.textContent = '발행 중...';
        var resultEl = document.getElementById('pb-pub-result');
        resultEl.style.display = ''; resultEl.innerHTML = '⏳ 발행 요청 중...';
        var tlist = Array.prototype.slice.call(checks);
        var total = tlist.length, results = [];
        
        // 1. assemble_final (조립 후 버전 ID 획득)
        var postTitle = document.getElementById('pb_post_title') ? document.getElementById('pb_post_title').value : '새 포스팅';
        $.post(_pbAjaxUrl(), {
            action:'assemble_final', post_id:_pbPostId,
            title: postTitle, cards: JSON.stringify(Builder.cards || [])
        }, function(assemRes) {
            if (!assemRes.ok) {
                resultEl.innerHTML = '❌ 본문 조립 실패: ' + (assemRes.error || '알 수 없는 오류');
                _pbBusy = false;
                submitBtn.disabled = false; submitBtn.textContent = '발행 시작';
                return;
            }
            var versionId = assemRes.version_id;
            // 2. publish (각 사이트별 발행/예약 등록)
            tlist.forEach(function(chk) {
                var siteId = parseInt(chk.value, 10);
                $.post(_pbAjaxUrl(), {
                    action:'publish', post_id:_pbPostId,
                    site_id:siteId, schedule_type:schedType, scheduled_at:schedAt,
                    version_id:versionId
                }, function(res) {
                    results.push({siteId:siteId, ok:res.ok, data:res});
                    _pbRenderResults(results, total);
                }, 'json').fail(function(xhr){
                    var e=''; try{ e=JSON.parse(xhr.responseText).error; }catch(x){}
                    results.push({siteId:siteId, ok:false, data:{error:e||'서버 오류'}});
                    _pbRenderResults(results, total);
                });
            });
        }, 'json').fail(function(xhr){
            var e=''; try{ e=JSON.parse(xhr.responseText).error; }catch(x){}
            resultEl.innerHTML = '❌ 본문 조립 통신 실패: ' + (e || '서버 오류');
            _pbBusy = false;
            submitBtn.disabled = false; submitBtn.textContent = '발행 시작';
        });
    };

    function _pbRenderResults(results, total) {
        var html = '<ul style="margin:0;padding:0;list-style:none;">';
        results.forEach(function(r) {
            var t = _pbTargets.find(function(pt){ return pt.site_id === r.siteId; });
            var name = t ? t.site_name : 'Site #'+r.siteId;
            if (r.ok) {
                var lbl = r.data.job_status==='published' ? '✅ 발행 완료' :
                          r.data.job_status==='scheduled' ? '🕐 예약 등록됨' : '⏳ 처리 중';
                var url = r.data.published_url ? ' — <a href="'+r.data.published_url+'" target="_blank">바로가기</a>' : '';
                html += '<li style="color:#065f46;background:#d1fae5;padding:6px 10px;border-radius:6px;margin-bottom:4px;">'+lbl+': <b>'+name+'</b>'+url+'</li>';
            } else {
                html += '<li style="color:#991b1b;background:#fee2e2;padding:6px 10px;border-radius:6px;margin-bottom:4px;">❌ 실패: <b>'+name+'</b> — '+(r.data.error||'오류')+'</li>';
            }
        });
        html += '</ul>';
        if (results.length >= total) {
            _pbBusy = false;
            var btn = document.getElementById('pb-pub-submit-btn');
            btn.disabled = false; btn.textContent = '발행 시작';
        }
        document.getElementById('pb-pub-result').innerHTML = html;
    }
}());
</script>

<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
