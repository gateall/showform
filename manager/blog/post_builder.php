<?php
$sub_menu = '360050';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '쇼폼 AI 스튜디오';

include_once(__DIR__ . '/../layout/header.php');
?>
<link rel="stylesheet" href="<?php echo G5_ADMIN_URL; ?>/blog/css/builder.css?ver=<?php echo G5_SERVER_TIME; ?>">
<script>
    // builder.js는 /adm/blog/에서 로드되지만 이 페이지는 /manager/blog/에서 서비스된다.
    // fetch()의 상대경로는 <script src>가 아니라 "현재 페이지 URL" 기준으로 풀리므로,
    // post_builder_ajax.php(=/adm/blog/에만 존재)를 상대경로로 부르면 /manager/blog/ 밑에서
    // 찾다가 실패한다. 그래서 절대경로를 명시적으로 넘겨준다.
    window.PB_ADMIN_URL = <?php echo json_encode(G5_ADMIN_URL); ?>;
</script>
<script src="<?php echo G5_ADMIN_URL; ?>/blog/js/builder.js?ver=<?php echo G5_SERVER_TIME; ?>"></script>

<div id="post-builder-app" class="studio-mode">
    <!-- 1. 상단 컨트롤 패널 -->
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
            <button type="button" class="btn btn_02" onclick="Builder.saveState()">임시저장</button>
            <button type="button" class="btn btn_02" onclick="Builder.generateAllCards()" style="background:#4f46e5; border-color:#4338ca; color:#fff;">✨ 전체 AI 생성</button>
            <button type="button" class="btn btn_01" onclick="Builder.inspectAll()">전체 검수</button>
            <button type="button" class="btn_submit btn" onclick="Builder.finishPost()">최종 조립 및 완성본 보기</button>
        </div>
    </header>

    <div class="pb-body-wrapper">
        <!-- 2. 좌측 메뉴 -->
        <nav class="pb-sidebar-left pb-panel-area">
            <ul class="pb-nav-list" id="pb_nav_list">
                <li class="pb-nav-item active" onclick="Builder.toggleStep(1)">
                    1. 글감 입력·분석
                    <div class="nav-status" id="nav-status-1">입력 대기</div>
                </li>
                <li class="pb-nav-item" onclick="Builder.toggleStep(2)">
                    2. 구성·초안 검토
                    <div class="nav-status" id="nav-status-2">대기 중</div>
                </li>
                <li class="pb-nav-item" onclick="Builder.toggleStep(3)">
                    3. 편집·최적화
                    <div class="nav-status" id="nav-status-3">대기 중</div>
                </li>
                <li class="pb-nav-item" onclick="Builder.toggleStep(4)">
                    4. 검수·완성
                    <div class="nav-status" id="nav-status-4">대기 중</div>
                </li>
            </ul>
            <div id="pb_minimap" style="margin-top:20px; overflow-y:auto; flex:1;"></div>
        </nav>

        <!-- 3. 중앙 작업 영역 -->
        <main class="pb-main pb-panel-area" id="pb_center_workspace">
            
            <!-- 0단계: 신규 프로젝트 폼 (선택 중심 UI) -->
            <div id="step-0" class="pb-step-view">
                <h2 class="pb-step-title">새 프로젝트 등록</h2>
                <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">
                    필수 항목은 <strong style="color:#dc2626;">광고주(상호)</strong>와 <strong style="color:#dc2626;">핵심 주제</strong> 두 가지뿐입니다. 나머지는 선택 사항이며 비워두면 AI가 알아서 채웁니다.
                </p>

                <div class="pb-form-grid">
                    <div class="pb-form-group">
                        <label>광고주 <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="inline_advertiser_search" class="frm_input" list="advertiser_datalist"
                               placeholder="상호명 검색 또는 선택 (최근 사용 순)" autocomplete="off"
                               oninput="Builder.onAdvertiserSearchInput(this)">
                        <datalist id="advertiser_datalist">
                            <?php
                            // 최근에 프로젝트를 만든 광고주를 우선 노출한다.
                            $adv_res = sql_query(" select a.id, a.name
                                                    from ".bp_table('advertisers')." a
                                                    left join (
                                                        select advertiser_id, max(created_at) as last_used
                                                        from ".bp_table('content_projects')."
                                                        group by advertiser_id
                                                    ) p on p.advertiser_id = a.id
                                                    order by (p.last_used is null), p.last_used desc, a.name asc ");
                            $adv_map = array();
                            while ($row = sql_fetch_array($adv_res)) {
                                $adv_map[(int)$row['id']] = $row['name'];
                                echo "<option data-id='" . (int)$row['id'] . "' value='" . get_text($row['name']) . "'></option>";
                            }
                            ?>
                        </datalist>
                        <input type="hidden" id="inline_advertiser_id" value="">
                        <div style="margin-top:8px;">
                            <button type="button" class="btn btn_02" style="font-size:0.85rem;" onclick="Builder.toggleNewAdvertiserForm()">+ 새 광고주·상호 등록</button>
                        </div>

                        <!-- 새 광고주 등록 서브폼 -->
                        <div id="pb_new_advertiser_form" class="pb-subform">
                            <div class="pb-subform-section-title">기본 정보</div>
                            <div class="pb-subform-grid">
                                <div class="pb-form-group"><label>상호명 / 업체명 <span style="color:#dc2626;">*</span></label>
                                    <input type="text" id="new_adv_name" class="frm_input"></div>
                                <div class="pb-form-group"><label>대표자명</label>
                                    <input type="text" id="new_adv_ceo_name" class="frm_input"></div>
                            </div>
                            <div class="pb-form-group"><label>업종 <span style="font-weight:normal; color:#94a3b8; font-size:0.8rem;">(복수 선택 가능)</span></label>
                                <div class="pb-check-scroll">
                                    <?php foreach (array('홈페이지 제작','광고·마케팅','통신·인터넷','음식점','숙박·펜션','이사·물류','교육·학원','병원·의료','법률·세무','부동산','인테리어','자동차','쇼핑몰','제조업','기업 서비스','기타') as $opt): ?>
                                        <label><input type="checkbox" class="pb-chk-new-adv-industry" value="<?php echo $opt; ?>"> <?php echo $opt; ?></label>
                                    <?php endforeach; ?>
                                </div>
                                <input type="text" id="new_adv_industry_detail" class="frm_input" placeholder="세부 업종 / 기타 직접입력" style="margin-top:8px;"></div>

                            <div class="pb-subform-section-title">연락처</div>
                            <div class="pb-subform-grid">
                                <div class="pb-form-group"><label>전화번호</label>
                                    <input type="text" id="new_adv_phone" class="frm_input" placeholder="대표 전화"></div>
                                <div class="pb-form-group"><label>보조 전화</label>
                                    <input type="text" id="new_adv_sub_phone" class="frm_input" placeholder="휴대폰 등"></div>
                                <div class="pb-form-group"><label>이메일</label>
                                    <input type="email" id="new_adv_email" class="frm_input"></div>
                                <div class="pb-form-group"><label>홈페이지 주소</label>
                                    <input type="text" id="new_adv_domain" class="frm_input"></div>
                            </div>

                            <div class="pb-subform-section-title">위치·서비스</div>
                            <div class="pb-subform-grid">
                                <div class="pb-form-group"><label>주소</label>
                                    <input type="text" id="new_adv_address" class="frm_input"></div>
                                <div class="pb-form-group"><label>영업지역</label>
                                    <input type="text" id="new_adv_service_region" class="frm_input" placeholder="예: 울산,부산"></div>
                            </div>
                            <div class="pb-form-group"><label>주요 상품·서비스</label>
                                <input type="text" id="new_adv_core_service" class="frm_input"></div>
                            <div class="pb-form-group"><label>업체 소개</label>
                                <textarea id="new_adv_intro_text" class="frm_input" rows="2"></textarea></div>

                            <div class="pb-subform-section-title">기타</div>
                            <div class="pb-form-group"><label>기타 참고사항</label>
                                <textarea id="new_adv_memo" class="frm_input" rows="2"></textarea></div>

                            <div style="text-align:right;">
                                <button type="button" class="btn btn_01" onclick="Builder.toggleNewAdvertiserForm()">취소</button>
                                <button type="button" class="btn_submit btn" onclick="Builder.saveInlineAdvertiser()">광고주 등록</button>
                            </div>
                        </div>

                        <!-- 기존 광고주의 업종 표시/보완 -->
                        <div id="pb_adv_industry_panel" style="display:none; margin-top:12px;">
                            <label style="font-size:0.85rem; color:#666;">업종 (광고주 공통 정보 - 선택한 광고주에 저장됩니다)</label>
                            <div class="pb-check-scroll" id="pb_adv_industry_checklist">
                                <?php foreach (array('홈페이지 제작','광고·마케팅','통신·인터넷','음식점','숙박·펜션','이사·물류','교육·학원','병원·의료','법률·세무','부동산','인테리어','자동차','쇼핑몰','제조업','기업 서비스','기타') as $opt): ?>
                                    <label><input type="checkbox" class="pb-chk-adv-industry" value="<?php echo $opt; ?>"> <?php echo $opt; ?></label>
                                <?php endforeach; ?>
                            </div>
                            <input type="text" id="pb_adv_industry_detail" class="frm_input" placeholder="세부 업종 / 기타 직접입력" style="margin-top:8px;">
                            <button type="button" class="btn btn_02" style="margin-top:8px; font-size:0.85rem;" onclick="Builder.saveAdvertiserIndustry()">업종 저장</button>
                        </div>
                    </div>

                    <div class="pb-form-group">
                        <label>핵심 주제 <span style="color:#dc2626;">*</span></label>
                        <input type="text" id="inline_topic" class="frm_input" placeholder="예: 여름 펜션 홍보" oninput="Builder.autoGenProjectName()">
                        <label style="margin-top:12px;">프로젝트명 (자동 생성, 수정 가능)</label>
                        <input type="text" id="inline_project_name" class="frm_input" placeholder="광고주명 + 핵심주제 + 날짜로 자동 생성됩니다" oninput="this.dataset.manual='true'">
                        <label style="margin-top:12px;">업체·상품·서비스 추가 설명 <span style="font-weight:normal; color:#94a3b8; font-size:0.8rem;">(이번 프로젝트에서만 참고할 특이사항)</span></label>
                        <textarea id="inline_project_notes" class="frm_input" rows="3"></textarea>
                    </div>
                </div>

                <div class="pb-form-grid">
                    <div class="pb-form-group">
                        <label>글 목적 <span style="font-weight:normal; color:#94a3b8; font-size:0.8rem;">(복수 선택 가능)</span></label>
                        <div class="pb-check-scroll" id="pb_purpose_checklist">
                            <?php foreach (array('정보 제공','상품·서비스 홍보','상담·문의 유도','구매·예약 유도','브랜드 인지도 향상','검색 노출·SEO','후기·사례 소개','행사·이벤트 안내','지역 고객 유입','비교·추천','문제 해결','공지사항') as $opt): ?>
                                <label><input type="checkbox" class="pb-chk-purpose" value="<?php echo $opt; ?>"> <?php echo $opt; ?></label>
                            <?php endforeach; ?>
                            <label><input type="checkbox" id="pb_purpose_other_chk" onchange="Builder.toggleOther('pb_purpose_other_wrap', this)"> 기타</label>
                        </div>
                        <div class="pb-inline-other" id="pb_purpose_other_wrap">
                            <input type="text" id="pb_purpose_other" class="frm_input" placeholder="기타 목적 직접 입력">
                        </div>
                    </div>
                    <div class="pb-form-group">
                        <label>글 유형 <span style="font-weight:normal; color:#94a3b8; font-size:0.8rem;">(복수 선택, 대표 글 유형 1개 지정)</span></label>
                        <div class="pb-check-scroll" id="pb_content_type_checklist">
                            <?php foreach (array('정보형','홍보형','후기형','사례 소개형','비교형','추천형','문제 해결형','질문·답변형','뉴스·소식형','지역 키워드형','제품 소개형','서비스 소개형') as $opt): ?>
                                <label><input type="checkbox" class="pb-chk-content-type" name="content_type_chk" value="<?php echo $opt; ?>" onchange="Builder.onContentTypeCheck(this)">
                                    <?php echo $opt; ?>
                                    <span class="pb-primary-radio"><input type="radio" name="content_type_primary" class="pb-radio-content-type-primary" value="<?php echo $opt; ?>"> 대표</span>
                                </label>
                            <?php endforeach; ?>
                            <label><input type="checkbox" id="pb_content_type_other_chk" onchange="Builder.toggleOther('pb_content_type_other_wrap', this)"> 직접 입력</label>
                        </div>
                        <div class="pb-inline-other" id="pb_content_type_other_wrap">
                            <input type="text" id="pb_content_type_other" class="frm_input" placeholder="글 유형 직접 입력">
                        </div>
                    </div>
                </div>

                <div class="pb-form-grid pb-form-grid-4">
                    <div class="pb-form-group">
                        <label>독자 유형</label>
                        <div class="pb-check-scroll">
                            <?php foreach (array('일반 소비자','개인사업자','자영업자','소상공인','중소기업 담당자','기업 경영자','마케팅 담당자','구매 담당자','주부','직장인','학생','시니어','전문가','기존 고객','잠재 고객','기타') as $opt): ?>
                                <label><input type="checkbox" class="pb-chk-reader-type" value="<?php echo $opt; ?>"> <?php echo $opt; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="pb-form-group">
                        <label>연령대</label>
                        <div class="pb-check-scroll">
                            <?php foreach (array('전 연령','10대','20대','30대','40대','50대','60대 이상') as $opt): ?>
                                <label><input type="checkbox" class="pb-chk-age-group" value="<?php echo $opt; ?>"> <?php echo $opt; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="pb-form-group">
                        <label>고객 상태</label>
                        <div class="pb-check-scroll">
                            <?php foreach (array('정보를 처음 찾는 고객','상품을 비교 중인 고객','구매를 고민 중인 고객','즉시 상담이 필요한 고객','재구매 고객','업체 변경을 검토하는 고객') as $opt): ?>
                                <label><input type="checkbox" class="pb-chk-customer-stage" value="<?php echo $opt; ?>"> <?php echo $opt; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="pb-form-group">
                        <label>지역</label>
                        <div class="pb-check-scroll">
                            <?php foreach (array('전국','서울','경기','인천','부산','대구','울산','대전','광주','세종','강원','충북','충남','전북','전남','경북','경남','제주') as $opt): ?>
                                <label><input type="checkbox" class="pb-chk-region" value="<?php echo $opt; ?>"> <?php echo $opt; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="pb-form-group">
                    <label>타깃 독자 상세 설명</label>
                    <textarea id="inline_target_audience_detail" class="frm_input" rows="2" placeholder="예: 울산 지역에서 전기자격증 취득을 준비하는 30~50대 직장인과 재취업 준비자"></textarea>
                </div>

                <div style="margin-top:20px; text-align:right;">
                    <button type="button" class="btn btn_01" onclick="Builder.toggleStep(1)">취소</button>
                    <button type="button" class="btn_submit btn" onclick="Builder.saveInlineProject()">등록 후 계속 작성</button>
                </div>
            </div>

            <!-- 1단계: 글감 입력·분석 -->
            <div id="step-1" class="pb-step-view active">
                <h2 class="pb-step-title">1. 글감 입력</h2>
                <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">업체 소개, 상품 설명, 메모 등을 자유롭게 붙여 넣으세요. 빈칸을 여러 개 채울 필요 없이 AI가 알아서 분석하여 전체 글을 작성합니다.</p>
                <textarea id="pb_raw_material" class="frm_input" style="width: 100%; height: 350px; resize: none; font-size:1rem; padding:15px;" placeholder="여기에 내용을 복사해 붙여넣으세요..."></textarea>

                <div class="pb-ai-panel" style="margin-top:12px; padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">
                    <div style="font-size:0.85rem; font-weight:bold; color:#475569; margin-bottom:10px;">프로젝트 AI 설정</div>
                    <div class="pb-ai-panel-row" style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
                        <div>
                            <span style="font-size:0.85rem; color:#666; display:block; margin-bottom:4px;">AI 사용</span>
                            <div class="ai-toggle-grid" style="display:flex; gap:6px;">
                                <div class="ai-toggle-btn on" id="pb_ai_toggle_on" onclick="Builder.setAiDisabled(false)" style="padding:6px 16px; border:2px solid #cbd5e1; border-radius:6px; cursor:pointer; font-size:0.85rem; font-weight:bold;">사용함</div>
                                <div class="ai-toggle-btn off" id="pb_ai_toggle_off" onclick="Builder.setAiDisabled(true)" style="padding:6px 16px; border:2px solid #cbd5e1; border-radius:6px; cursor:pointer; font-size:0.85rem; font-weight:bold;">사용 안 함</div>
                            </div>
                            <input type="hidden" id="pb_ai_disabled" value="N">
                        </div>
                        <div style="flex:1; min-width:220px;">
                            <span style="font-size:0.85rem; color:#666; display:block; margin-bottom:4px;">AI 에이전트</span>
                            <select id="pb_ai_provider_id" class="frm_input" onchange="Builder.saveAiProviderPref()">
                                <option value="">기본값(전역 활성 공급자)</option>
                                <?php
                                $ai_providers_res = sql_query("select id, display_name, provider_code, default_model, is_active, api_key_enc from ".bp_table('ai_providers')." order by display_name");
                                $ai_providers_count = 0;
                                while ($ap = sql_fetch_array($ai_providers_res)) {
                                    $ai_providers_count++;
                                    $ap_is_live_code = bp_ai_provider_is_live($ap['provider_code']);
                                    // "usable" = 지금 선택하면 실제로 AI가 호출된다(코드 지원 + 키 있음 + 활성).
                                    // bp_ai_get_provider_meta()가 판단하는 조건과 동일하게 맞춰야, 여기서 "사용
                                    // 가능"으로 보여준 것을 골라도 서버가 다시 준비 중이라고 막는 불일치가 없다.
                                    $ap_usable = $ap_is_live_code && !empty($ap['api_key_enc']) && $ap['is_active'] === 'Y';
                                    if (!$ap_is_live_code) {
                                        $ap_status = '준비 중';
                                    } elseif (empty($ap['api_key_enc'])) {
                                        $ap_status = 'API 키 없음';
                                    } elseif ($ap['is_active'] !== 'Y') {
                                        $ap_status = '사용 안 함';
                                    } else {
                                        $ap_status = '사용 가능';
                                    }
                                    $ap_model = $ap['default_model'] !== '' ? $ap['default_model'] : '기본 모델';
                                    $ap_label = get_text($ap['display_name']) . ' · ' . get_text($ap_model) . ' · ' . $ap_status;
                                    echo "<option value='" . (int)$ap['id'] . "' data-live='" . ($ap_usable ? '1' : '0') . "'>" . $ap_label . "</option>";
                                }
                                ?>
                            </select>
                            <?php if ($ai_providers_count === 0) { ?>
                            <p class="pb-control-warning" style="font-size:0.8rem; color:#b45309; margin:4px 0 0;">등록된 AI 공급자가 없습니다. 설정 &gt; AI 설정에서 먼저 등록해 주세요.</p>
                            <?php } ?>
                        </div>
                        <div>
                            <span style="font-size:0.85rem; color:transparent; display:block; margin-bottom:4px;">.</span>
                            <a href="./settings.php?tab=ai" target="_blank" class="btn btn_02" style="font-size:0.85rem;">AI API 설정 관리</a>
                        </div>
                    </div>
                    <p style="font-size:0.8rem; color:#94a3b8; margin:8px 0 0;">프로젝트마다 다른 AI 에이전트를 지정하거나, AI 없이 수동으로만 작성할 수 있습니다. 변경 즉시 저장됩니다.</p>
                </div>

                <div style="margin-top: 15px; display:flex; gap: 10px; justify-content:flex-end;">
                    <button type="button" class="btn_submit btn" onclick="Builder.generateAllCards()" style="background:#4f46e5; border-color:#4338ca; padding:10px 20px; font-size:1.05rem;">✨ 전체 자동 작성 시작</button>
                </div>
            </div>

            <!-- 2, 3단계 공용: 카드 기반 에디터 캔버스 -->
            <div id="step-23" class="pb-step-view">
                <h2 class="pb-step-title" id="pb_step23_title">2. 초안 검토 및 조립</h2>
                <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">AI가 생성한 카드들을 검토하고 불필요한 카드는 비노출/삭제하세요. 카드를 클릭하면 우측에서 세부 수정을 할 수 있습니다.</p>
                
                <div id="pb_card_canvas" style="display:flex; flex-direction:column; gap:15px; padding-bottom:100px;">
                    <!-- JS에서 카드가 렌더링됩니다 -->
                    <div style="text-align:center; padding:50px; color:#94a3b8;">
                        아직 생성된 초안이 없습니다.<br>1단계에서 [전체 자동 작성 시작]을 눌러주세요.
                    </div>
                </div>
            </div>

            <!-- 4단계: 검수 및 완성 -->
            <div id="step-4" class="pb-step-view">
                <h2 class="pb-step-title">4. 자동 검수 및 완성</h2>
                <div id="pb_inspect_canvas" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:20px;">
                    <p style="color:#64748b; text-align:center; padding:40px;">상단의 [전체 검수] 버튼을 눌러 품질을 점검하거나, [최종 조립 및 완성본 보기]를 눌러 글을 완성하세요.</p>
                </div>
            </div>

        </main>

        <!-- 4. 우측 컨텍스트 패널 -->
        <aside class="pb-sidebar-right pb-panel-area" id="pb_right_panel">
            <div style="text-align:center; color:#94a3b8; padding-top:150px; font-size:0.95rem;">
                중앙에서 카드를 클릭하시면<br>이곳에 전용 설정 패널이 나타납니다.
            </div>
        </aside>
    </div>

    <!-- 하단 완성본 패널 (슬라이드 업) -->
    <div id="pb_bottom_panel" class="pb-bottom-panel">
        <div class="pb-bottom-header">
            <h3 style="margin:0; font-size:1.1rem; color:#fff;">최종 완성본</h3>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn_02" onclick="Builder.copyHtml()">HTML 복사</button>
                <button type="button" class="btn_submit btn" style="background:#10b981; border-color:#059669;" onclick="alert('발행 기능은 준비 중입니다.')">🚀 즉시 발행</button>
                <button type="button" class="btn btn_01" onclick="document.getElementById('pb_bottom_panel').classList.remove('active')">닫기 ✕</button>
            </div>
        </div>
        <div class="pb-bottom-body" style="display:flex; height: calc(100% - 55px);">
            <div style="flex:1; padding:20px; overflow-y:auto; border-right:1px solid #e2e8f0; background:#fff;" id="pb_preview_html">
                <!-- HTML Preview -->
            </div>
            <div style="flex:1; padding:0; overflow-y:auto; background:#1e293b;">
                <textarea id="pb_preview_text" style="width:100%; height:100%; border:none; resize:none; background:transparent; color:#e2e8f0; font-family:monospace; font-size:0.9rem; padding:20px;" readonly></textarea>
            </div>
        </div>
    </div>
</div>

<?php
include_once(__DIR__ . '/../layout/footer.php');
