<?php
$sub_menu = '360050';
include_once('./_common.php');
$auth_error = auth_check_menu($auth, $sub_menu, 'w', true);
if ($auth_error) {
    // auth_check()가 만드는 메시지는 원래 JS alert()에 넣을 목적이라 개행이 리터럴
    // '\n'으로 들어 있다. 그대로 HTML로 내보내면 화면에 \n이 글자로 보인다.
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    die(str_replace('\n', '<br>', htmlspecialchars($auth_error, ENT_QUOTES, 'UTF-8'))
        . ' <a href="' . SF_MANAGER_URL . '/">통합관리자 홈으로 이동</a>');
}

// $g5['table_prefix']는 Gnuboard 코어에 정의돼 있지 않다(코어는 G5_TABLE_PREFIX 상수를
// 쓴다). 아래 "글 전개 구조 선택" 카테고리 드롭다운이 이 값을 직접 참조해 DB를 조회하는데,
// 값이 비어 있으면 존재하지 않는 테이블명(prefix 없는 blog_library_categories)을 조회하게
// 되어 카테고리가 하나도 안 나오는 문제가 있었다.
if (!isset($g5['table_prefix']) || $g5['table_prefix'] === '') {
    $g5['table_prefix'] = defined('G5_TABLE_PREFIX') ? G5_TABLE_PREFIX : 'g5_';
}

$g5['title'] = '쇼폼 AI 스튜디오';
$page_title = '쇼폼 AI 스튜디오'; // manager/layout/header.php 용 타이틀

// 이 화면은 백엔드를 둘 쓴다. 하나로 합치기 전까지는 어느 쪽이 어떤 액션을 갖고
// 있는지가 곧 버그의 원인이 되므로 여기서 둘 다 명시한다.
//
// - ajaxUrl     : 빌더 본체 액션(단계 이동, 카드 저장, 발행 등). post_builder_ajax.php는
//                 /adm/blog/에만 존재한다.
// - libraryUrl  : 글전개 구조 / AI 생성조건 라이브러리 액션. load_ai_conditions 같은
//                 신규 액션과 instruction_type 필터는 manager/blog/ajax.builder.php에만
//                 있다. builder_managers.js가 이걸 ajaxUrl로 부르면 post_builder_ajax.php가
//                 'Unknown action'을 돌려주고, 매니저는 필터 없는 Builder.libraryItems로
//                 폴백해 AI 생성조건 모달에 글구조 카드가 섞여 나온다.
$post_builder_ajax_url = G5_ADMIN_URL . '/blog/post_builder_ajax.php';
$post_builder_library_url = SF_MANAGER_URL . '/blog/ajax.builder.php';

include_once(__DIR__ . '/../layout/header.php');
?>
<link rel="stylesheet" href="<?php echo G5_ADMIN_URL; ?>/blog/css/builder.css?ver=<?php echo G5_SERVER_TIME; ?>">
<script>
window.POST_BUILDER_CONFIG = Object.freeze({
    ajaxUrl: <?= json_encode($post_builder_ajax_url, JSON_UNESCAPED_SLASHES) ?>,
    libraryUrl: <?= json_encode($post_builder_library_url, JSON_UNESCAPED_SLASHES) ?>
});
</script>
<script src="<?php echo G5_ADMIN_URL; ?>/blog/js/builder.js?ver=<?php echo G5_SERVER_TIME; ?>"></script>
<script src="<?php echo G5_ADMIN_URL; ?>/blog/js/builder_managers.js?ver=<?php echo G5_SERVER_TIME; ?>"></script>
<script src="<?php echo G5_ADMIN_URL; ?>/blog/js/prompt_manager.js?ver=<?php echo G5_SERVER_TIME; ?>"></script>

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
            <button type="button" class="btn btn_02" onclick="Builder.editAdvertiser()">광고주 수정</button>
            <span style="margin-left: 10px; font-size: 0.9rem; color: #666;">상태: <strong id="pb-status-display">작성 전</strong></span>
        </div>
        <div class="pb-header-right">
            <span style="font-size: 0.85rem; color: #888;">마지막 저장: <span id="pb-saved-time">-</span></span>
            <button type="button" class="btn btn_02" onclick="Builder.saveState()">임시저장</button>
            <button type="button" class="btn btn_02" onclick="Builder.loadLocalBackup()">임시저장 불러오기</button>
            <button type="button" class="btn btn_02" id="pb_btn_generate_all_header" onclick="Builder.generateAllCards()" style="background:#4f46e5; border-color:#4338ca; color:#fff;">✨ 전체 AI 생성</button>
            <button type="button" class="btn btn_02 pb-focus-toggle-btn" id="pb_btn_focus_toggle" onclick="Builder.toggleFocusMode()">🔍 집중 편집</button>
            <button type="button" class="btn btn_01" onclick="Builder.inspectAll()">전체 검수</button>
            <button type="button" class="btn_submit btn" onclick="Builder.finishPost()">최종 조립 및 완성본 보기</button>
        </div>
    </header>

    <div class="pb-body-wrapper">
        <!-- 2. 좌측 메뉴 -->
        <nav class="pb-sidebar-left pb-panel-area">
            <div style="font-size:0.85rem; font-weight:bold; color:#475569; padding:4px 4px 10px;">📝 글작성단계</div>
            <ul class="pb-nav-list" id="pb_nav_list">
                <li class="pb-nav-item active" onclick="Builder.toggleStep(1)">
                    🖊️ 1단계 · 글감 입력·분석
                    <div class="nav-status" id="nav-status-1">입력 대기</div>
                </li>
                <li class="pb-nav-item" onclick="Builder.toggleStep(2)">
                    🧩 2단계 · 구성·초안 검토
                    <div class="nav-status" id="nav-status-2">대기 중</div>
                </li>
                <li class="pb-nav-item" onclick="Builder.toggleStep(3)">
                    ✂️ 3단계 · 편집·최적화
                    <div class="nav-status" id="nav-status-3">대기 중</div>
                </li>
                <li class="pb-nav-item" onclick="Builder.toggleStep(4)">
                    📣 4단계 · CTA 작성·설정
                    <div class="nav-status" id="nav-status-4">대기 중</div>
                </li>
                <li class="pb-nav-item" onclick="Builder.toggleStep(5)">
                    📋 5단계 · 검수·완성
                    <div class="nav-status" id="nav-status-5">대기 중</div>
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
                                <div class="pb-check-scroll industry-checkbox-list">
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
                            <div class="pb-check-scroll industry-checkbox-list" id="pb_adv_industry_checklist">
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
                                <label><input type="checkbox" class="pb-chk-purpose" data-prompt-key="purpose_<?php echo md5($opt); ?>" data-prompt-template="글의 주요 목적은 '<?php echo $opt; ?>'입니다." value="<?php echo $opt; ?>"> <?php echo $opt; ?></label>
                            <?php endforeach; ?>
                            <label><input type="checkbox" id="pb_purpose_other_chk" onchange="Builder.toggleOther('pb_purpose_other_wrap', this)"> 기타</label>
                        </div>
                        <div class="pb-inline-other" id="pb_purpose_other_wrap">
                            <input type="text" id="pb_purpose_other" class="frm_input" placeholder="기타 목적 직접 입력" data-prompt-key="purpose_other" data-prompt-template="글의 추가 목적은 '{value}'입니다.">
                        </div>
                    </div>
                    <div class="pb-form-group">
                        <label>글 유형 <span style="font-weight:normal; color:#94a3b8; font-size:0.8rem;">(복수 선택, 대표 글 유형 1개 지정)</span></label>
                        <div class="pb-check-scroll" id="pb_content_type_checklist">
                            <?php foreach (array('정보형','홍보형','후기형','사례 소개형','비교형','추천형','문제 해결형','질문·답변형','뉴스·소식형','지역 키워드형','제품 소개형','서비스 소개형') as $opt): ?>
                                <label><input type="checkbox" class="pb-chk-content-type" name="content_type_chk" data-prompt-key="content_type_<?php echo md5($opt); ?>" data-prompt-template="이 글은 '<?php echo $opt; ?>' 형식으로 작성합니다." value="<?php echo $opt; ?>" onchange="Builder.onContentTypeCheck(this)">
                                    <?php echo $opt; ?>
                                    <span class="pb-primary-radio"><input type="radio" name="content_type_primary" class="pb-radio-content-type-primary" value="<?php echo $opt; ?>"> 대표</span>
                                </label>
                            <?php endforeach; ?>
                            <label><input type="checkbox" id="pb_content_type_other_chk" onchange="Builder.toggleOther('pb_content_type_other_wrap', this)"> 직접 입력</label>
                        </div>
                        <div class="pb-inline-other" id="pb_content_type_other_wrap">
                            <input type="text" id="pb_content_type_other" class="frm_input" placeholder="글 유형 직접 입력" data-prompt-key="content_type_other" data-prompt-template="추가적인 글 형식은 '{value}'입니다.">
                        </div>
                    </div>
                </div>

                <div class="pb-form-grid pb-form-grid-4">
                    <div class="pb-form-group">
                        <label>독자 유형</label>
                        <div class="pb-check-scroll">
                            <?php foreach (array('일반 소비자','개인사업자','자영업자','소상공인','중소기업 담당자','기업 경영자','마케팅 담당자','구매 담당자','주부','직장인','학생','시니어','전문가','기존 고객','잠재 고객','기타') as $opt): ?>
                                <label><input type="checkbox" class="pb-chk-reader-type" data-prompt-key="reader_<?php echo md5($opt); ?>" data-prompt-template="예상 독자에는 '<?php echo $opt; ?>'이(가) 포함됩니다." value="<?php echo $opt; ?>"> <?php echo $opt; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="pb-form-group">
                        <label>연령대</label>
                        <div class="pb-check-scroll">
                            <?php foreach (array('전 연령','10대','20대','30대','40대','50대','60대 이상') as $opt): ?>
                                <label><input type="checkbox" class="pb-chk-age-group" data-prompt-key="age_<?php echo md5($opt); ?>" data-prompt-template="주요 타깃 연령대는 '<?php echo $opt; ?>'입니다." value="<?php echo $opt; ?>"> <?php echo $opt; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="pb-form-group">
                        <label>고객 상태</label>
                        <div class="pb-check-scroll">
                            <?php foreach (array('정보를 처음 찾는 고객','상품을 비교 중인 고객','구매를 고민 중인 고객','즉시 상담이 필요한 고객','재구매 고객','업체 변경을 검토하는 고객') as $opt): ?>
                                <label><input type="checkbox" class="pb-chk-customer-stage" data-prompt-key="stage_<?php echo md5($opt); ?>" data-prompt-template="이 글의 대상 고객은 '<?php echo $opt; ?>' 단계에 있습니다." value="<?php echo $opt; ?>"> <?php echo $opt; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="pb-form-group">
                        <label>지역</label>
                        <div class="pb-check-scroll service-area-checkbox-list">
                            <?php foreach (array('전국','서울','경기','인천','부산','대구','울산','대전','광주','세종','강원','충북','충남','전북','전남','경북','경남','제주') as $opt): ?>
                                <label><input type="checkbox" class="pb-chk-region" data-prompt-key="region_<?php echo md5($opt); ?>" data-prompt-template="주요 영업지역은 '<?php echo $opt; ?>'입니다." value="<?php echo $opt; ?>"> <?php echo $opt; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="pb-form-group">
                    <label>타깃 독자 상세 설명</label>
                    <textarea id="inline_target_audience_detail" class="frm_input" rows="2" placeholder="예: 울산 지역에서 전기자격증 취득을 준비하는 30~50대 직장인과 재취업 준비자" data-prompt-key="target_audience_detail" data-prompt-template="타깃 독자 상세: {value}"></textarea>
                </div>

                <div style="margin-top:20px; text-align:right;">
                    <button type="button" class="btn btn_01" onclick="Builder.toggleStep(1)">취소</button>
                    <button type="button" class="btn_submit btn" onclick="Builder.saveInlineProject()">등록 후 계속 작성</button>
                </div>
            </div>

            <!-- 1단계: 글감 입력·분석 -->
            <div id="step-1" class="pb-step-view active">
                <h2 class="pb-step-title">🖊️ 1단계 · 글감 입력·분석</h2>
                <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">업체 소개, 상품 설명, 메모 등을 자유롭게 붙여 넣으세요. 빈칸을 여러 개 채울 필요 없이 AI가 알아서 분석하여 전체 글을 작성합니다.</p>

                <!-- 글감이 아예 없는 사용자를 위한 안내 카드 -->
                <div id="pb_topic_empty_hint" class="pb-ai-panel" style="margin-bottom:12px; padding:16px; background:#eef2ff; border:1px solid #c7d2fe; border-radius:6px; text-align:center;">
                    <div style="font-weight:bold; color:#312e81; margin-bottom:4px;">작성할 글감이 아직 없으신가요?</div>
                    <div style="font-size:0.85rem; color:#4338ca; margin-bottom:10px;">업종과 홍보 목적만 선택하면 AI가 제목과 글감 후보를 자동으로 추천합니다.</div>
                    <button type="button" class="btn btn_submit" style="background:#4f46e5; border-color:#4338ca; color:#fff;" onclick="Builder.toggleTopicIdeaPanel(true)">✨ AI로 글감 찾기</button>
                </div>

                <!-- 글감 자동 생성 기준 패널 -->
                <div id="pb_topic_idea_panel" class="pb-ai-panel" style="display:none; margin-bottom:12px; padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                        <div style="font-size:0.9rem; font-weight:bold; color:#475569;">🔎 글감 생성 기준</div>
                        <button type="button" class="btn btn_02" onclick="Builder.toggleTopicIdeaPanel(false)">닫기</button>
                    </div>
                    <div class="pb-form-grid pb-form-grid-4">
                        <div class="pb-form-group"><label>업종</label>
                            <input type="text" id="pb_topic_industry" class="frm_input" placeholder="예: 베트남 음식점" data-prompt-key="topic_industry" data-prompt-template="글감 생성 기준 - 업종: {value}"></div>
                        <div class="pb-form-group"><label>지역</label>
                            <input type="text" id="pb_topic_region" class="frm_input" placeholder="예: 경산" data-prompt-key="topic_region" data-prompt-template="글감 생성 기준 - 지역: {value}"></div>
                        <div class="pb-form-group"><label>상품·서비스</label>
                            <input type="text" id="pb_topic_product" class="frm_input" placeholder="예: 쌀국수, 분보남보" data-prompt-key="topic_product" data-prompt-template="글감 생성 기준 - 상품·서비스: {value}"></div>
                        <div class="pb-form-group"><label>글 목적</label>
                            <input type="text" id="pb_topic_purpose" class="frm_input" placeholder="예: 방문 유도" data-prompt-key="topic_purpose" data-prompt-template="글감 생성 기준 - 글 목적: {value}"></div>
                        <div class="pb-form-group"><label>대상 고객</label>
                            <input type="text" id="pb_topic_target" class="frm_input" placeholder="예: 20~50대 직장인" data-prompt-key="topic_target" data-prompt-template="글감 생성 기준 - 대상 고객: {value}"></div>
                        <div class="pb-form-group"><label>글 유형</label>
                            <input type="text" id="pb_topic_content_type" class="frm_input" placeholder="예: 후기형" data-prompt-key="topic_content_type" data-prompt-template="글감 생성 기준 - 글 유형: {value}"></div>
                        <div class="pb-form-group"><label>계절·시기</label>
                            <input type="text" id="pb_topic_season" class="frm_input" placeholder="예: 여름 성수기" data-prompt-key="topic_season" data-prompt-template="글감 생성 기준 - 계절·시기: {value}"></div>
                        <div class="pb-form-group"><label>브랜드명</label>
                            <input type="text" id="pb_topic_brand" class="frm_input" placeholder="예: 스마트톡톡" data-prompt-key="topic_brand" data-prompt-template="글감 생성 기준 - 브랜드명: {value}"></div>
                    </div>
                    <div class="pb-form-grid">
                        <div class="pb-form-group"><label>핵심 키워드 (쉼표로 구분)</label>
                            <input type="text" id="pb_topic_keywords" class="frm_input" placeholder="예: 경산 맛집, 베트남 쌀국수" data-prompt-key="topic_keywords" data-prompt-template="글감 생성 기준 - 필수 키워드: {value}"></div>
                        <div class="pb-form-group"><label>제외할 내용</label>
                            <input type="text" id="pb_topic_exclude" class="frm_input" placeholder="예: 배달 관련 내용 제외" data-prompt-key="topic_exclude" data-prompt-template="글감 생성 기준 - 제외할 내용: {value}"></div>
                    </div>
                    <div style="display:flex; align-items:center; gap:16px; margin-top:8px; flex-wrap:wrap;">
                        <div style="font-size:0.85rem; color:#666;">
                            추천 개수
                            <label style="margin-left:8px;"><input type="radio" name="pb_topic_count" value="5"> 5개</label>
                            <label style="margin-left:8px;"><input type="radio" name="pb_topic_count" value="10" checked> 10개</label>
                            <label style="margin-left:8px;"><input type="radio" name="pb_topic_count" value="20"> 20개</label>
                        </div>
                        <button type="button" class="btn btn_submit" id="pb_topic_generate_btn" style="background:#4f46e5; border-color:#4338ca; color:#fff;" onclick="Builder.generateTopicIdeas()">글감 추천 생성</button>
                    </div>
                </div>

                <!-- 글감 후보 카드 목록 -->
                <div id="pb_topic_idea_results" style="display:none; margin-bottom:12px;"></div>

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
                            <select id="pb_ai_provider_id" class="frm_input" onchange="Builder.saveAiProviderPref(); Builder.updateProviderStatusUI();">
                                <option value="">기본값(전역 활성 공급자)</option>
                                <?php
                                $ai_providers_res = sql_query("select id, display_name, provider_code, default_model, is_active, api_key_enc, last_test_status from ".bp_table('ai_providers')." order by display_name");
                                $ai_providers_count = 0;
                                // 상태 문구(사람이 읽는 라벨) => 버튼 게이팅에 쓸 기계용 키.
                                $ap_status_key_map = array(
                                    '사용 중' => 'active',
                                    '테스트 필요' => 'needs_test',
                                    '연결 오류' => 'error',
                                    '사용 안 함' => 'disabled',
                                    'API 키 필요' => 'no_key',
                                );
                                while ($ap = sql_fetch_array($ai_providers_res)) {
                                    $ai_providers_count++;
                                    $ap_is_live_code = bp_ai_provider_is_live($ap['provider_code']);
                                    // data-live: "지금 선택하면 실제로 AI 호출을 시도한다"(코드 지원 + 키 있음 +
                                    // 활성) - bp_ai_get_provider()의 판단 조건과 동일. 연결 테스트 통과 여부와는
                                    // 별개로, [ChatGPT로 변경] 대체 대상을 찾는 기존 로직이 그대로 쓴다.
                                    $ap_usable = $ap_is_live_code && !empty($ap['api_key_enc']) && $ap['is_active'] === 'Y';
                                    // 상태 문구는 하드코딩된 "준비 중"이 아니라, 실제 등록 상태 + 저장된 연결
                                    // 테스트 결과(last_test_status)로 판단한다 - bp_ai_provider_status_label().
                                    $ap_status = bp_ai_provider_status_label($ap);
                                    $ap_status_key = isset($ap_status_key_map[$ap_status]) ? $ap_status_key_map[$ap_status] : 'needs_test';
                                    $ap_model = $ap['default_model'] !== '' ? $ap['default_model'] : '기본 모델';
                                    $ap_label = get_text($ap['display_name']) . ' · ' . get_text($ap_model) . ' · ' . $ap_status;
                                    echo "<option value='" . (int)$ap['id'] . "' data-live='" . ($ap_usable ? '1' : '0') . "' data-status='" . $ap_status_key . "'>" . $ap_label . "</option>";
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

                <!-- AI 글 생성 조건, 추가 지시문 및 전개 구조 컨테이너 (JS에서 렌더링) -->
                <div id="pb_library_container" style="margin-top:12px;">
                    <!-- 글 전개 구조 영역 -->
                    <div class="pb-ai-panel" style="padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:12px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                            <span style="font-size:0.85rem; font-weight:bold; color:#475569;">글 전개 구조 선택 <span style="font-weight:normal; color:#dc2626; font-size:0.78rem;">* (필수)</span></span>
                            <button type="button" class="btn btn_02" style="font-size:0.8rem;" onclick="Builder.StructureManager.openModal('manage')">+ 구조 등록/관리</button>
                        </div>
                        
                        <div class="pb-filter-bar" style="display:flex; gap:10px; margin-bottom:10px; align-items:center; flex-wrap:wrap;">
                            <select id="pb_filter_category" class="frm_input" style="width:150px; padding:4px 8px; font-size:0.8rem;" onchange="Builder.renderStructureList()">
                                <option value="">전체 구조 보기</option>
                                <?php
                                $cat_res = sql_query("SELECT id, category_name FROM {$g5['table_prefix']}blog_library_categories WHERE library_type='structure' AND is_active=1 ORDER BY sort_order ASC");
                                while ($c = sql_fetch_array($cat_res)) {
                                    echo '<option value="'.htmlspecialchars($c['category_name']).'">'.get_text($c['category_name']).'</option>';
                                }
                                ?>
                            </select>
                            <select id="pb_filter_industry" class="frm_input" style="width:120px; padding:4px 8px; font-size:0.8rem;" onchange="Builder.renderStructureList()">
                                <option value="">전체 업종</option>
                            </select>
                            <select id="pb_filter_purpose" class="frm_input" style="width:120px; padding:4px 8px; font-size:0.8rem;" onchange="Builder.renderStructureList()">
                                <option value="">전체 목적</option>
                            </select>
                            <input type="text" id="pb_filter_keyword" class="frm_input" placeholder="검색어 입력" style="width:200px; padding:4px 8px; font-size:0.8rem;" onkeyup="Builder.renderStructureList()">
                            <label style="font-size:0.8rem; display:flex; align-items:center; gap:4px;"><input type="checkbox" id="pb_filter_favorite" onchange="Builder.renderStructureList()"> 즐겨찾기만</label>
                        </div>
                        
                        <div style="margin-bottom:15px;">
                            <button type="button" class="btn btn_02" onclick="Builder.openStructureCategoryModal()" style="padding:6px 14px; font-size:0.8rem;">📁 구조 카테고리 카드로 보기</button>
                        </div>

                        <!-- 적용 중인 구조 영역 (Applied Summary) - 카드 목록보다 먼저 보이는 위치로 이동.
                             카드가 많아지면(15개+) 목록 맨 아래에 있던 이전 위치는 스크롤을 끝까지 내려야만
                             보여서 사실상 안 보이는 것과 같았다. -->
                        <div id="pb_applied_summary" style="padding:12px; background:#fff; border:2px solid #e2e8f0; border-radius:6px; margin-bottom:10px;">
                            <!-- JS에서 렌더링 -->
                            <div style="color:#64748b; font-size:0.85rem; text-align:center;">
                                현재 적용된 글 전개 구조가 없습니다.
                            </div>
                        </div>

                        <!-- 체크(Staged) 영역 - 카드를 체크하면 여기가 즉시 나타난다. position:sticky로
                             카드 목록을 스크롤해도 화면 상단에 계속 보이게 한다(카드가 많아도 놓치지 않도록). -->
                        <div id="pb_staged_actions_bar" style="display:none; position:sticky; top:0; z-index:50; margin-bottom:15px; padding:12px; background:#f0f9ff; border:1px solid #bae6fd; border-radius:6px; flex-direction:column; gap:10px; box-shadow:0 2px 8px rgba(0,0,0,0.08);">
                            <div style="font-weight:bold; color:#0284c7;">
                                선택된 구조: <span id="pb_staged_count">0</span>개
                            </div>
                            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                                <button type="button" class="btn btn_01" style="background:#0284c7; border-color:#0284c7; color:#fff;" onclick="Builder.applyStagedStructures('replace')">체크한 구조 바로 사용</button>
                                <button type="button" class="btn btn_02" style="background:#fff; color:#0369a1; border-color:#bae6fd;" onclick="Builder.applyStagedStructures('append')">현재 구조 뒤에 추가</button>
                                <button type="button" class="btn btn_02" style="background:#fff; color:#64748b; border-color:#cbd5e1;" onclick="Builder.clearStagedStructures()">선택 해제</button>
                            </div>
                        </div>

                        <input type="hidden" id="pb_selected_structure_ids" name="pb_selected_structure_ids" value="">
                        <!-- pb_selected_structure_ids는 id 목록이라 문장으로 쓸 수 없다.
                             updateSelectedStructureSummary()가 여기에 사람이 읽을 수 있는
                             제목을 채워야 지시문 미리보기에 "선택한 글 전개 구조"가 반영된다. -->
                        <input type="hidden" id="pb_structure_prompt_value" data-prompt-key="applied_structure" data-prompt-template="선택한 글 전개 구조: {value}" value="">

                        <div id="pb_structure_list" class="pb-card-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 10px; margin-bottom: 15px;"></div>

                        <div id="pb_structure_preview" style="display:none; margin-top:10px; padding:10px; background:#fff; border:1px solid #cbd5e1; border-radius:4px;">
                            <!-- 예상 목차 미리보기 -->
                        </div>
                    </div>

                    <!-- AI 글 생성 조건 -->
                    <div class="pb-ai-panel" style="padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:12px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                            <span style="font-size:0.85rem; font-weight:bold; color:#475569;">AI 글 생성 조건 <span style="font-weight:normal; color:#94a3b8; font-size:0.78rem;">- 체크한 조건만 AI 지시문으로 전달됩니다</span></span>
                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                <button type="button" class="btn btn_02" style="font-size:0.78rem;" onclick="Builder.toggleAllGenerationConditions(true)">전체 선택</button>
                                <button type="button" class="btn btn_02" style="font-size:0.78rem;" onclick="Builder.toggleAllGenerationConditions(false)">선택 해제</button>
                                <button type="button" class="btn btn_02" style="font-size:0.8rem;" onclick="Builder.AIConditionManager.openModal('manage')">+ 생성 조건 등록/관리</button>
                            </div>
                        </div>
                        <div id="pb_generation_condition_list" class="pb-check-scroll">
                            <!-- JS에서 동적 추가 -->
                            <div style="color:#94a3b8; font-size:0.85rem;">로딩 중...</div>
                        </div>
                    </div>

                    <!-- 이번 글 추가 지시 -->
                    <div class="pb-ai-panel" style="padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;">
                        <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px; flex-wrap:wrap; gap:8px;">
                            <label style="font-size:0.8rem; color:#666; font-weight:bold;">이번 글 추가 지시 <span style="font-weight:normal; color:#94a3b8; font-size:0.78rem;">(체크 조건으로 해결 안 되는 사항을 직접 입력)</span></label>
                            <button type="button" class="btn btn_02" style="font-size:0.8rem;" onclick="Builder.openLibraryModal('additional_instruction')">라이브러리 불러오기</button>
                        </div>
                        <textarea id="pb_extra_instruction" class="frm_input" rows="2" placeholder="예: 40~50대 자영업자가 이해하기 쉬운 표현으로 작성" data-prompt-key="extra_instruction" data-prompt-template="이번 글 추가 지시: {value}"></textarea>
                    </div>
                </div>

                <!-- 본문 목표 글자수/키워드/해시태그 입력창은 우측 컨트롤 패널(#pb_right_panel)
                     안에서만 존재하는데, 그 컨테이너는 단계가 바뀔 때마다 innerHTML로
                     통째로 교체된다. 1단계를 벗어나는 순간 입력창 자체가 사라져서
                     지시문 미리보기에서도 값이 같이 사라지는 문제가 있었다 - 값은 여기
                     (1단계 화면 자체, 단계 이동에 사라지지 않는 위치)의 숨은 필드에
                     복제해 두고, builder.js가 값이 바뀔 때마다 이 필드를 갱신한다. -->
                <input type="hidden" id="pb_target_length_prompt_value" data-prompt-key="target_length" data-prompt-template="본문 목표 글자수: 약 {value}자" value="">
                <input type="hidden" id="pb_char_per_line_prompt_value" data-prompt-key="char_per_line" data-prompt-template="가로 글자 수: 최대 {value}자 내외" value="60">
                <input type="hidden" id="pb_keywords_prompt_value" data-prompt-key="keywords_summary" data-prompt-template="SEO 키워드: {value}" value="">
                <input type="hidden" id="pb_hashtags_prompt_value" data-prompt-key="hashtags_summary" data-prompt-template="해시태그: {value}" value="">

                <div id="pb_generate_blocked_msg" style="display:none; margin-top:10px; padding:10px 14px; border:1px solid #f59e0b; border-radius:6px; background:#fffbeb; font-size:0.85rem; color:#92400e; text-align:right;"></div>

                <?php include_once(dirname(__FILE__) . '/tpl/step_prompt_panel.php'); pb_render_step_prompt_panel(1, '글감, 목적, 글 유형, 독자, 연령대, 고객 상태, 지역, 타깃 설정을 선택하면 AI 지시문이 표시됩니다.'); ?>

                <div style="margin-top: 15px; display:flex; gap: 10px; justify-content:flex-end;">
                    <button type="button" class="btn_submit btn" id="pb_btn_generate_all" onclick="Builder.generateAllCards()" style="background:#4f46e5; border-color:#4338ca; color:#fff; padding:10px 20px; font-size:1.05rem;">✨ 전체 자동 작성 시작</button>
                </div>
                <div class="pb-step-footer-nav">
                    <span></span>
                    <button type="button" class="btn btn_02" onclick="Builder.toggleStep(2)">다음 단계 →</button>
                </div>
            </div>

            <!-- 2, 3단계 공용: 카드 기반 에디터 캔버스 -->
            <div id="step-23" class="pb-step-view">
                <h2 class="pb-step-title" id="pb_step23_title">🧩 2단계 · 구성·초안 검토</h2>
                <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">AI가 생성한 카드들을 검토하고 불필요한 카드는 비노출/삭제하세요. 카드를 클릭하면 우측에서 세부 수정을 할 수 있습니다.</p>

                <div id="pb_card_canvas" style="display:flex; flex-direction:column; gap:15px; padding-bottom:100px;">
                    <!-- JS에서 카드가 렌더링됩니다 -->
                    <div style="text-align:center; padding:50px; color:#94a3b8;">
                        아직 생성된 초안이 없습니다.<br>1단계에서 [전체 자동 작성 시작]을 눌러주세요.
                    </div>
                </div>
                <?php
                pb_render_step_prompt_panel(2, '글 전개 구조, 초안 구성, 제목과 본문 구성 조건을 선택하면 AI 지시문이 표시됩니다.');
                pb_render_step_prompt_panel(3, '편집 방식, 문장 길이, SEO, 키워드, 이미지와 해시태그 조건이 표시됩니다.');
                ?>
                <div class="pb-step-footer-nav">
                    <button type="button" class="btn btn_02" id="pb_step23_prev_btn" onclick="Builder.toggleStep(1)">← 이전 단계</button>
                    <button type="button" class="btn_submit btn" id="pb_step23_next_btn" onclick="Builder.toggleStep(3)">다음 단계 →</button>
                </div>
            </div>

            <!-- 4단계: CTA 작성 및 설정 -->
            <div id="step-4" class="pb-step-view">
                <h2 class="pb-step-title">📣 4단계 · CTA 작성·설정</h2>
                <p style="font-size:0.9rem; color:#666; margin-bottom:15px;">이 글에 추가할 업체의 연락처나 맺음말(Call to Action)을 설정합니다.</p>

                <div class="pb-form-group" style="margin-bottom: 20px;">
                    <label style="display:flex; align-items:center; gap:8px; font-weight:bold; font-size:1rem; cursor:pointer;">
                        <input type="checkbox" id="pb_cta_enabled" style="width:20px; height:20px;" onchange="Builder.toggleCtaEnabled()" checked> 
                        본문 하단에 CTA 영역 포함하기
                    </label>
                    <p style="font-size:0.85rem; color:#64748b; margin-top:5px; margin-left:30px;">
                        체크를 해제하면 이 글에는 CTA 블록이 추가되지 않습니다.
                    </p>
                </div>

                <div id="pb_cta_editor_area">
                    <div class="pb-ai-panel" style="padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:12px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                            <div style="font-size:0.9rem; font-weight:bold; color:#475569;">업체 정보 프로필 선택</div>
                            <div style="display:flex; gap:8px;">
                                <select id="pb_cta_profile_select" class="frm_input" style="min-width:200px;" onchange="Builder.onCtaProfileChange()">
                                    <option value="">불러오는 중...</option>
                                </select>
                                <button type="button" class="btn btn_02" onclick="Builder.createNewCtaProfile()">+ 새 프로필 추가</button>
                            </div>
                        </div>

                        <div id="pb_cta_fields_container">
                            <div style="font-size:0.85rem; font-weight:bold; color:#475569; margin-bottom:10px;">기본 정보 (체크한 항목만 본문에 삽입됨)</div>
                            <div id="pb_cta_standard_fields" style="display:flex; flex-direction:column; gap:8px; margin-bottom:15px; padding:10px; border:1px solid #cbd5e1; border-radius:4px; background:#fff;">
                                <!-- JS에서 동적 생성됨: 체크박스 + 텍스트박스 -->
                            </div>

                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                                <div style="font-size:0.85rem; font-weight:bold; color:#475569;">사용자 정의 항목 (SNS, 팩스 등)</div>
                                <button type="button" class="btn btn_02" style="padding:4px 10px; font-size:0.8rem;" onclick="Builder.addCustomCtaField()">+ 항목 추가</button>
                            </div>
                            <div id="pb_cta_custom_fields" style="display:flex; flex-direction:column; gap:8px; padding:10px; border:1px solid #cbd5e1; border-radius:4px; background:#fff;">
                                <!-- JS에서 동적 생성됨 -->
                            </div>
                        </div>
                    </div>

                    <div class="pb-ai-panel" style="padding:14px 16px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; margin-bottom:12px;">
                        <div style="font-size:0.85rem; font-weight:bold; color:#475569; margin-bottom:10px;">맺음말 (CTA 직접 입력)</div>
                        <textarea id="pb_cta_content" class="frm_input" style="width:100%; height:100px; resize:vertical; padding:10px;" placeholder="예: 상담이 필요하시다면 언제든 아래 번호로 연락주세요." data-prompt-key="cta_content" data-prompt-template="맺음말(CTA): {value}"></textarea>
                    </div>

                    <div style="display:flex; justify-content:flex-end; gap:8px; margin-bottom:20px;">
                        <button type="button" class="btn btn_02" onclick="Builder.deleteCtaProfile()">삭제</button>
                        <button type="button" class="btn btn_01" style="background:#0284c7; color:#fff; border-color:#0369a1;" onclick="Builder.saveCtaProfile(true)">다른 이름으로 저장</button>
                        <button type="button" class="btn btn_submit" style="background:#4f46e5; border-color:#4338ca; color:#fff;" onclick="Builder.saveCtaProfile(false)">현재 프로필 저장</button>
                    </div>
                </div>

                <?php pb_render_step_prompt_panel(4, 'CTA 문구, 업체 정보, 연락처와 직접 입력한 행동 유도 지시문이 표시됩니다.'); ?>
                <div class="pb-step-footer-nav">
                    <button type="button" class="btn btn_02" onclick="Builder.toggleStep(3)">← 이전 단계</button>
                    <button type="button" class="btn_submit btn" onclick="Builder.toggleStep(5)">다음 단계 →</button>
                </div>
            </div>

            <!-- 5단계: 검수 및 완성 -->
            <div id="step-5" class="pb-step-view">
                <h2 class="pb-step-title">📋 5단계 · 검수·완성</h2>
                <div style="text-align:right; margin-bottom:10px;">
                    <button type="button" class="btn btn_01" onclick="Builder.inspectAll()">🔍 전체 검수</button>
                </div>
                <div id="pb_inspect_canvas" style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px; padding:20px;">
                    <p style="color:#64748b; text-align:center; padding:40px;">위 [전체 검수] 버튼을 눌러 품질을 점검하거나, [최종 조립 및 완성본 보기]를 눌러 글을 완성하세요.</p>
                </div>
                <?php pb_render_step_prompt_panel(5, '최종 검수 조건과 이전 단계의 누적 지시문이 최종 작업지시문 형태로 표시됩니다.'); ?>
                <div class="pb-step-footer-nav">
                    <button type="button" class="btn btn_02" onclick="Builder.toggleStep(4)">← 이전 단계</button>
                    <button type="button" class="btn_submit btn" style="background:#4f46e5; border-color:#4338ca; color:#fff;" onclick="Builder.finishPost()">✅ 최종 조립 및 완성본 보기</button>
                </div>
            </div>

        </main>

        <!-- 4. 우측 컨텍스트 패널 및 프롬프트 뷰어 -->
        <aside class="pb-sidebar-right pb-panel-area" style="display:flex; flex-direction:column;">
            <div id="pb_right_panel" style="flex:1; overflow-y:auto; padding-bottom:20px;">
                <div style="text-align:center; color:#94a3b8; padding-top:150px; font-size:0.95rem;">
                    중앙에서 카드를 클릭하시면<br>이곳에 전용 설정 패널이 나타납니다.
                </div>
            </div>
            
            <!-- 공통 프롬프트 표시 영역 -->
            <div id="pb_prompt_panel" style="height:350px; border-top:2px solid #cbd5e1; padding:15px; background:#f8fafc; display:flex; flex-direction:column; box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.05);">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <div>
                        <h4 style="margin:0; display:inline; font-size:0.9rem; color:#1e293b;">🤖 AI 프롬프트 실시간 미리보기</h4>
                        <span style="font-size:0.75rem; color:#64748b;">(현재 단계 반영)</span>
                    </div>
                    <button type="button" class="pb-step-prompt-toggle" aria-label="공통 프롬프트 미리보기 접기" onclick="PromptManager.toggleCommonPromptPanel();">
                        <span id="pb_common_prompt_toggle_icon" aria-hidden="true">▲</span>
                    </button>
                </div>
                <div id="pb_prompt_panel_body" style="flex:1; display:flex; flex-direction:column; min-height:0;">
                    <textarea id="pb_prompt_preview" readonly class="frm_input" style="flex:1; width:100%; resize:none; font-size:0.8rem; padding:10px; background:#fff; line-height:1.4; color:#334155; border:1px solid #cbd5e1;"></textarea>

                    <div style="margin-top:10px;">
                        <label style="font-size:0.8rem; font-weight:bold; color:#475569;">추가 지시사항 직접 입력</label>
                        <input type="text" id="user_custom_prompt" class="frm_input" style="width:100%; margin-top:5px; font-size:0.8rem;" placeholder="여기에 입력한 내용은 최종 지시문에 합산됩니다.">
                    </div>

                    <div style="margin-top:12px; display:flex; gap:5px; justify-content:space-between;">
                        <button type="button" class="btn btn_02" style="flex:1; font-size:0.8rem;" onclick="PromptManager.copyToClipboard()">전체 복사</button>
                        <button type="button" class="btn btn_01" style="flex:1; font-size:0.8rem; background:#4f46e5; border-color:#4338ca; color:#fff;" onclick="PromptManager.generateWorkOrder()">에이전트 지시문</button>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <!-- 하단 완성본 패널 (슬라이드 업) -->
    <div id="pb_bottom_panel" class="pb-bottom-panel">
        <div class="pb-bottom-header">
            <h3 style="margin:0; font-size:1.1rem; color:#fff;">최종 완성본</h3>
            <div style="display:flex; gap:10px;">
                <button type="button" class="btn btn_02" onclick="Builder.copyHtml()">HTML 복사</button>
                <button type="button" class="btn_submit btn" style="background:#10b981; border-color:#059669;" onclick="Builder.showPublishModal()">🚀 즉시/예약 발행</button>
                <button type="button" class="btn btn_01" onclick="document.getElementById('pb_bottom_panel').classList.remove('active')">닫기 ✕</button>
            </div>
        </div>
        <div class="pb-bottom-body" style="display:flex; height: calc(100% - 55px);">
            <div style="flex:1; padding:20px; overflow-y:auto; border-right:1px solid #e2e8f0; background:#fff;" id="pb_preview_html">
            </div>
            <div style="flex:1; padding:0; overflow-y:auto; background:#1e293b;">
                <textarea id="pb_preview_text" style="width:100%; height:100%; border:none; resize:none; background:transparent; color:#e2e8f0; font-family:monospace; font-size:0.9rem; padding:20px;" readonly></textarea>
            </div>
        </div>
    </div>
</div>

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
  
  <div style="padding:15px 22px 0; border-bottom:1px solid #e2e8f0; padding-bottom:15px;">
    <div style="display:flex; gap:10px; margin-bottom:10px;">
      <div style="flex:1;">
        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px; color:#4a5568;">카테고리 ID (선택)</label>
        <input type="text" id="pb-pub-category-id" placeholder="예: 1 (빈 칸이면 기본값)" style="width:100%; padding:6px 8px; border:1px solid #cbd5e0; border-radius:4px; font-size:13px; box-sizing:border-box;">
      </div>
      <div style="flex:1;">
        <label style="display:block; font-size:12px; font-weight:bold; margin-bottom:4px; color:#4a5568;">대표 이미지 URL (선택)</label>
        <input type="text" id="pb-pub-thumbnail" placeholder="http://..." style="width:100%; padding:6px 8px; border:1px solid #cbd5e0; border-radius:4px; font-size:13px; box-sizing:border-box;">
      </div>
    </div>
    <div style="display:flex; gap:15px; font-size:13px; color:#4a5568;">
      <label style="cursor:pointer; display:flex; align-items:center; gap:4px;">
        <input type="checkbox" id="pb-pub-require-thumb" value="Y"> 대표 이미지 필수 체크
      </label>
      <label style="cursor:pointer; display:flex; align-items:center; gap:4px;">
        <input type="checkbox" id="pb-pub-is-public" value="Y" checked> 공개 발행 (지원 시)
      </label>
    </div>
  </div>

  <div id="pb-pub-panel-imm" style="padding:15px 22px;">
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

<div id="pb-library-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9000;"></div>

<!-- ============================================== -->
<!-- 1. 글전개 구조 전용 모달 (Structure Manager Modal) -->
<!-- ============================================== -->
<div id="structureManagerModal" class="pb-manager-modal pb-manager-modal--structure"
     style="display:none; position:fixed; z-index:9001; background:#fff;
     box-shadow:0 20px 60px rgba(0,0,0,0.25); flex-direction:column;">
  <div style="display:flex; flex-direction:column; background:#fff; border-bottom:1px solid #e2e8f0; border-radius:12px 12px 0 0;">
    <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 22px 10px;">
        <h3 style="margin:0; font-size:17px; display:flex; gap:10px; align-items:center;">
            <span>글 전개 구조 관리</span>
        </h3>
        <button onclick="Builder.StructureManager.closeModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#718096;">✕</button>
    </div>
    <!-- 글 전개 구조는 세 탭을 그대로 둔다. 조건과 달리 메인 화면에 구조를 고르는
         전용 UI가 따로 없어, 선택 탭이 실제 진입점 역할을 한다. -->
    <div id="sm_tabs" class="pb-manager-tabs" style="padding:0 22px;">
        <button id="sm_tab_btn_select" class="pb-manager-tab" onclick="Builder.StructureManager.switchTab('select')" style="border:none; border-bottom:3px solid #3182ce; background:none; color:#3182ce; font-weight:700; padding:8px 14px; cursor:pointer; font-size:14px;">글 구조 선택</button>
        <button id="sm_tab_btn_manage" class="pb-manager-tab" onclick="Builder.StructureManager.switchTab('manage')" style="border:none; border-bottom:3px solid transparent; background:none; color:#718096; font-weight:600; padding:8px 14px; cursor:pointer; font-size:14px;">글 구조 관리</button>
        <button id="sm_tab_btn_category" class="pb-manager-tab" onclick="Builder.StructureManager.switchTab('category')" style="border:none; border-bottom:3px solid transparent; background:none; color:#718096; font-weight:600; padding:8px 14px; cursor:pointer; font-size:14px;">카테고리 관리</button>
    </div>
  </div>
  
  <div style="padding:18px 22px; flex:1; overflow-y:auto; background:#f8fafc; position:relative;">
    <div id="sm_tab_select" style="display:none; height:100%; display:flex; flex-direction:column;">
        <div style="display:flex; gap:8px; margin-bottom:12px; align-items:center;">
            <input type="text" id="sm_accordion_search" class="frm_input" style="flex:1; padding:6px 10px;" placeholder="카테고리명, 구조명, 설명 검색..." oninput="Builder.StructureManager.renderAccordion()">
            <button type="button" class="btn btn_02" onclick="Builder.StructureManager.toggleAllAccordions(true)" style="padding:6px 10px;">전체 펼치기</button>
            <button type="button" class="btn btn_02" onclick="Builder.StructureManager.toggleAllAccordions(false)" style="padding:6px 10px;">전체 접기</button>
        </div>
        <div id="sm_accordion_container" style="flex:1; overflow-y:auto; background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:10px;">
        </div>
        <div style="position:sticky; bottom:-18px; margin-top:12px; padding:12px 16px; background:#fff; border:1px solid #cbd5e1; border-radius:8px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 -2px 10px rgba(0,0,0,0.05); z-index:10;">
            <div style="font-weight:bold; color:#1e293b; font-size:14px;">
                선택된 구조: <span id="sm_accordion_selected_count" style="color:#2563eb;">0</span>개
            </div>
            <div style="display:flex; gap:8px;">
                <button type="button" class="btn btn_02" onclick="Builder.StructureManager.clearSelection()">선택 해제</button>
                <button type="button" class="btn btn_01" style="background:#10b981; border-color:#10b981;" onclick="Builder.StructureManager.applySelected('append')">현재 구조 뒤에 추가</button>
                <button type="button" class="btn btn_01" style="background:#4f46e5; border-color:#4f46e5;" onclick="Builder.StructureManager.applySelected('replace')">선택한 구조로 교체</button>
            </div>
        </div>
    </div>
    <div id="sm_form_wrap" style="display:none; background:#fff; padding:20px; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:15px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
            <h4 style="margin:0; font-size:15px; color:#475569;" id="sm_form_title">구조 등록</h4>
            <button onclick="Builder.StructureManager.hideForm()" style="background:none;border:none;cursor:pointer;color:#94a3b8;">✕ 닫기</button>
        </div>
        <input type="hidden" id="sm_id" value="0">
        <div class="pb-form-grid">
            <div class="pb-form-group">
                <label>제목(구조명) *</label>
                <input type="text" id="sm_title" class="frm_input">
            </div>
            <div class="pb-form-group">
                <label>카테고리 *</label>
                <select id="sm_category" class="frm_input" onchange="if(this.value==='__NEW__') Builder.StructureManager.switchTab('category');">
                </select>
            </div>
        </div>
        <div class="pb-form-grid" id="sm_structure_fields" style="display:none; margin-top:10px;">
            <div class="pb-form-group">
                <label>구조 코드 * <span style="font-weight:normal; color:#94a3b8; font-size:0.8rem;">(영문, 숫자, 언더바)</span></label>
                <input type="text" id="sm_item_code" class="frm_input" placeholder="예: pas, aida, top_n">
            </div>
            <div class="pb-form-group">
                <label>예상 글자 수</label>
                <input type="text" id="sm_expected_length" class="frm_input" placeholder="예: 1000~1500자">
            </div>
        </div>
        <div class="pb-form-group" id="sm_content_group">
            <label>지시문 내용 *</label>
            <textarea id="sm_content" class="frm_input" rows="4"></textarea>
        </div>
        <div class="pb-form-group" id="sm_steps_group" style="display:none;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                <label style="margin:0;">구조 단계 설정</label>
                <button type="button" class="btn btn_02" style="font-size:12px; padding:4px 8px;" onclick="Builder.StructureManager.addStep()">+ 단계 추가</button>
            </div>
            <div id="sm_steps_container" style="display:flex; flex-direction:column; gap:10px; padding:10px; background:#f1f5f9; border-radius:6px; border:1px solid #e2e8f0;">
            </div>
        </div>
        <div class="pb-form-group">
            <label>간단 설명</label>
            <input type="text" id="sm_description" class="frm_input" placeholder="이 지시문이 어떤 역할을 하는지 설명">
        </div>
        <div class="pb-form-grid">
            <div class="pb-form-group">
                <label>추천 용도</label>
                <input type="text" id="sm_recommended_purpose" class="frm_input" placeholder="예: 지역 업체 소개">
            </div>
            <div class="pb-form-group">
                <label>추천 업종</label>
                <input type="text" id="sm_recommended_industries" class="frm_input" placeholder="예: 이사, 통신, 부동산">
            </div>
        </div>
        <div class="pb-form-group">
            <label>키워드/태그</label>
            <input type="text" id="sm_tags" class="frm_input" placeholder="콤마(,)로 구분">
        </div>
        <div class="pb-form-grid" style="align-items:center; margin-top:10px;">
            <label><input type="checkbox" id="sm_is_default_checked" value="Y"> 기본 적용 (항상 적용)</label>
            <label><input type="checkbox" id="sm_is_favorite_checked" value="Y"> ★ 즐겨찾기</label>
            <label><input type="checkbox" id="sm_is_active" value="0"> 사용 중지 (보관)</label>
        </div>
        <div style="text-align:right; margin-top:15px; border-top:1px solid #e2e8f0; padding-top:15px; position:sticky; bottom:-20px; background:#fff; z-index:5;">
            <button type="button" class="btn btn_02" onclick="Builder.StructureManager.hideForm()">취소</button>
            <button type="button" class="btn_submit btn" onclick="Builder.StructureManager.saveItem()">저장</button>
        </div>
    </div>
    <div id="sm_tab_manage" style="display:none;">
        <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
            <button id="sm_add_btn" class="btn btn_01" style="padding:4px 10px; font-size:12px;" onclick="Builder.StructureManager.showForm(0)">+ 새 항목 등록</button>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:15px; background:#fff; padding:12px; border-radius:8px; border:1px solid #e2e8f0; align-items:center;">
            <select id="sm_filter_category" class="frm_input" style="width:auto; padding:4px 8px;" onchange="Builder.StructureManager.renderList()">
                <option value="">전체 카테고리 ▼</option>
            </select>
            <select id="sm_filter_status" class="frm_input" style="width:auto; padding:4px 8px;" onchange="Builder.StructureManager.renderList()">
                <option value="1">사용 중 ▼</option>
                <option value="all">전체 상태</option>
                <option value="0">사용 중지</option>
            </select>
            <input type="text" id="sm_filter_search" class="frm_input" style="flex:1; min-width:120px; padding:4px 8px;" placeholder="검색어 입력..." onkeyup="if(event.keyCode===13) Builder.StructureManager.renderList()">
            <button class="btn btn_02" style="padding:4px 10px;" onclick="Builder.StructureManager.renderList()">검색</button>
            <label style="display:flex; align-items:center; gap:4px; font-size:13px; cursor:pointer;">
                <input type="checkbox" id="sm_filter_fav" onchange="Builder.StructureManager.renderList()"> ★ 즐겨찾기만
            </label>
            <select id="sm_filter_sort" class="frm_input" style="width:auto; padding:4px 8px;" onchange="Builder.StructureManager.renderList()">
                <option value="sort_asc">기본 정렬순 ▼</option>
                <option value="recent_used">최근 사용순</option>
                <option value="most_used">많이 사용한 순</option>
                <option value="recent_added">최근 등록순</option>
            </select>
        </div>
        <div id="sm_list" class="pb-library-grid">
        </div>
    </div>
    <div id="sm_tab_category" style="display:none; padding:10px;">
        <div style="display:flex; gap:10px; margin-bottom:15px;">
            <input type="text" id="sm_new_cat_name" class="frm_input" placeholder="새 카테고리명" style="flex:1;">
            <button class="btn btn_01" onclick="Builder.StructureManager.saveCategory(0)">추가</button>
        </div>
        <div id="sm_category_list" style="display:flex; flex-direction:column; gap:6px;">
        </div>
    </div>
  </div>
</div>

<!-- ============================================== -->
<!-- 2. AI 글 생성 조건 전용 모달 (AI Condition Manager Modal) -->
<!-- ============================================== -->
<div id="aiConditionManagerModal" class="pb-manager-modal pb-manager-modal--condition"
     style="display:none; position:fixed; z-index:9001; background:#fff;
     box-shadow:0 20px 60px rgba(0,0,0,0.25); flex-direction:column;">
  <div style="display:flex; flex-direction:column; background:#fff; border-bottom:1px solid #e2e8f0; border-radius:12px 12px 0 0;">
    <div style="display:flex; justify-content:space-between; align-items:center; padding:14px 22px 10px;">
        <h3 style="margin:0; font-size:17px; display:flex; gap:10px; align-items:center;">
            <span>AI 생성 조건 라이브러리 관리</span>
        </h3>
        <button onclick="Builder.AIConditionManager.closeModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:#718096;">✕</button>
    </div>
    <!-- 조건을 "고르는" 일은 메인 화면의 체크박스가 이미 하고 있다. 여기에 선택 탭을
         또 두면 같은 일을 하는 화면이 둘이 되어 어느 쪽 체크가 진짜인지 헷갈린다.
         이 모달은 라이브러리 관리만 맡는다. -->
    <div id="aic_tabs" class="pb-manager-tabs" style="padding:0 22px;">
        <button id="aic_tab_btn_manage" class="pb-manager-tab" onclick="Builder.AIConditionManager.switchTab('manage')" style="border:none; border-bottom:3px solid #3182ce; background:none; color:#3182ce; font-weight:700; padding:8px 14px; cursor:pointer; font-size:14px;">조건 관리</button>
        <button id="aic_tab_btn_category" class="pb-manager-tab" onclick="Builder.AIConditionManager.switchTab('category')" style="border:none; border-bottom:3px solid transparent; background:none; color:#718096; font-weight:600; padding:8px 14px; cursor:pointer; font-size:14px;">카테고리 관리</button>
    </div>
  </div>
  
  <div style="padding:18px 22px; flex:1; overflow-y:auto; background:#f8fafc; position:relative;">
    <div id="aic_tab_select" style="display:none; height:100%; display:flex; flex-direction:column;">
        <div style="display:flex; gap:8px; margin-bottom:12px; align-items:center;">
            <input type="text" id="aic_accordion_search" class="frm_input" style="flex:1; padding:6px 10px;" placeholder="카테고리명, 조건명, 설명 검색..." oninput="Builder.AIConditionManager.renderAccordion()">
            <button type="button" class="btn btn_02" onclick="Builder.AIConditionManager.toggleAllAccordions(true)" style="padding:6px 10px;">전체 펼치기</button>
            <button type="button" class="btn btn_02" onclick="Builder.AIConditionManager.toggleAllAccordions(false)" style="padding:6px 10px;">전체 접기</button>
        </div>
        <div id="aic_accordion_container" style="flex:1; overflow-y:auto; background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:10px;">
        </div>
        <div style="position:sticky; bottom:-18px; margin-top:12px; padding:12px 16px; background:#fff; border:1px solid #cbd5e1; border-radius:8px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 -2px 10px rgba(0,0,0,0.05); z-index:10;">
            <div style="font-weight:bold; color:#1e293b; font-size:14px;">
                선택된 조건: <span id="aic_accordion_selected_count" style="color:#2563eb;">0</span>개
            </div>
            <div style="display:flex; gap:8px;">
                <button type="button" class="btn btn_02" onclick="Builder.AIConditionManager.clearSelection()">선택 해제</button>
                <button type="button" class="btn btn_01" style="background:#10b981; border-color:#10b981;" onclick="Builder.AIConditionManager.applySelected('append')">현재 조건에 추가</button>
                <button type="button" class="btn btn_01" style="background:#4f46e5; border-color:#4f46e5;" onclick="Builder.AIConditionManager.applySelected('replace')">선택한 조건으로 교체</button>
            </div>
        </div>
    </div>
    <div id="aic_form_wrap" style="display:none; background:#fff; padding:20px; border:1px solid #e2e8f0; border-radius:8px; margin-bottom:15px;">
        <div style="display:flex; justify-content:space-between; margin-bottom:15px;">
            <h4 style="margin:0; font-size:15px; color:#475569;" id="aic_form_title">조건 등록</h4>
            <button onclick="Builder.AIConditionManager.hideForm()" style="background:none;border:none;cursor:pointer;color:#94a3b8;">✕ 닫기</button>
        </div>
        <input type="hidden" id="aic_id" value="0">
        <div class="pb-form-grid">
            <div class="pb-form-group">
                <label>제목(조건명) *</label>
                <input type="text" id="aic_title" class="frm_input">
            </div>
            <div class="pb-form-group">
                <label>카테고리 *</label>
                <select id="aic_category" class="frm_input" onchange="if(this.value==='__NEW__') Builder.AIConditionManager.switchTab('category');">
                </select>
            </div>
        </div>
        <div class="pb-form-group">
            <label>지시문 내용 *</label>
            <textarea id="aic_content" class="frm_input" rows="4"></textarea>
        </div>
        <div class="pb-form-group">
            <label>간단 설명</label>
            <input type="text" id="aic_description" class="frm_input" placeholder="이 조건이 어떤 역할을 하는지 설명">
        </div>
        <div class="pb-form-grid">
            <div class="pb-form-group">
                <label>추천 용도</label>
                <input type="text" id="aic_recommended_purpose" class="frm_input" placeholder="예: 지역 업체 소개">
            </div>
            <div class="pb-form-group">
                <label>추천 업종</label>
                <input type="text" id="aic_recommended_industries" class="frm_input" placeholder="예: 이사, 통신, 부동산">
            </div>
        </div>
        <div class="pb-form-group">
            <label>키워드/태그</label>
            <input type="text" id="aic_tags" class="frm_input" placeholder="콤마(,)로 구분">
        </div>
        <div class="pb-form-grid" style="align-items:center; margin-top:10px;">
            <label><input type="checkbox" id="aic_is_default_checked" value="Y"> 기본 적용 (항상 적용)</label>
            <label><input type="checkbox" id="aic_is_favorite_checked" value="Y"> ★ 즐겨찾기</label>
            <label><input type="checkbox" id="aic_is_active" value="0"> 사용 중지 (보관)</label>
        </div>
        <div style="text-align:right; margin-top:15px; border-top:1px solid #e2e8f0; padding-top:15px; position:sticky; bottom:-20px; background:#fff; z-index:5;">
            <button type="button" class="btn btn_02" onclick="Builder.AIConditionManager.hideForm()">취소</button>
            <button type="button" class="btn_submit btn" onclick="Builder.AIConditionManager.saveItem()">저장</button>
        </div>
    </div>
    <div id="aic_tab_manage" style="display:none;">
        <div style="display:flex; justify-content:space-between; margin-bottom:12px;">
            <button id="aic_add_btn" class="btn btn_01" style="padding:4px 10px; font-size:12px;" onclick="Builder.AIConditionManager.showForm(0)">+ 새 항목 등록</button>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:15px; background:#fff; padding:12px; border-radius:8px; border:1px solid #e2e8f0; align-items:center;">
            <select id="aic_filter_category" class="frm_input" style="width:auto; padding:4px 8px;" onchange="Builder.AIConditionManager.renderList()">
                <option value="">전체 카테고리 ▼</option>
            </select>
            <select id="aic_filter_status" class="frm_input" style="width:auto; padding:4px 8px;" onchange="Builder.AIConditionManager.renderList()">
                <option value="1">사용 중 ▼</option>
                <option value="all">전체 상태</option>
                <option value="0">사용 중지</option>
            </select>
            <input type="text" id="aic_filter_search" class="frm_input" style="flex:1; min-width:120px; padding:4px 8px;" placeholder="검색어 입력..." onkeyup="if(event.keyCode===13) Builder.AIConditionManager.renderList()">
            <button class="btn btn_02" style="padding:4px 10px;" onclick="Builder.AIConditionManager.renderList()">검색</button>
            <label style="display:flex; align-items:center; gap:4px; font-size:13px; cursor:pointer;">
                <input type="checkbox" id="aic_filter_fav" onchange="Builder.AIConditionManager.renderList()"> ★ 즐겨찾기만
            </label>
            <select id="aic_filter_sort" class="frm_input" style="width:auto; padding:4px 8px;" onchange="Builder.AIConditionManager.renderList()">
                <option value="sort_asc">기본 정렬순 ▼</option>
                <option value="recent_used">최근 사용순</option>
                <option value="most_used">많이 사용한 순</option>
                <option value="recent_added">최근 등록순</option>
            </select>
        </div>
        <div id="aic_list" class="pb-library-grid pb-library-grid--wide">
        </div>
    </div>
    <div id="aic_tab_category" style="display:none; padding:10px;">
        <div style="display:flex; gap:10px; margin-bottom:15px;">
            <input type="text" id="aic_new_cat_name" class="frm_input" placeholder="새 카테고리명" style="flex:1;">
            <button class="btn btn_01" onclick="Builder.AIConditionManager.saveCategory(0)">추가</button>
        </div>
        <div id="aic_category_list" style="display:flex; flex-direction:column; gap:6px;">
        </div>
    </div>
  </div>
</div>


<script>
(function() {
    var _pbTab     = 'immediate';
    var _pbTargets = [];
    var _pbBusy    = false;
    var _pbPostId  = 0;
    var _pbVersionId = 0;

    Builder.showPublishModal = function() {
        _pbBusy   = false;
        _pbTab    = 'immediate';
        _pbPostId = Builder.postId || 0;
        _pbVersionId = 0;
        Builder.switchPubTab('immediate');
        var d = new Date(Date.now() + 3600000);
        var p = function(n){ return ('0'+n).slice(-2); };
        document.getElementById('pb-pub-scheduled-at').value =
            d.getFullYear()+'-'+p(d.getMonth()+1)+'-'+p(d.getDate())+'T'+p(d.getHours())+':00';
        document.getElementById('pb-pub-result').style.display = 'none';
        var btn = document.getElementById('pb-pub-submit-btn');
        // 최종본 조립(assemble_final)은 이제 모달을 여는 시점이 아니라 "발행 시작"을
        // 눌렀을 때 submitPublish() 안에서 Builder.ensurePostSaved() → assemble_final
        // 순서로 한 번에 처리한다(post_id 미확보로 인한 "잘못된 파라미터" 실패와,
        // 그로 인한 이중 알림 문제를 그쪽에서 함께 해결함). 여기서는 발행 대상 목록만
        // 미리 보여준다.
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
        const ajaxUrl = window.POST_BUILDER_CONFIG?.ajaxUrl;
        if (!ajaxUrl || ajaxUrl.includes('undefined')) {
            throw new Error(`포스트 빌더 AJAX URL이 설정되지 않았습니다: ${ajaxUrl}`);
        }
        return ajaxUrl;
    }

    // r.json()을 바로 쓰면, 세션 만료로 서버가 JSON 대신 로그인 페이지 HTML을 반환할 때
    // "Uncaught (in promise) SyntaxError: Unexpected token '<'" 형태로 원인이 감춰진다.
    // 여기서 먼저 텍스트로 받아 로그인 페이지 여부를 판별해 명확한 Error로 바꿔준다.
    function _pbParseJson(response) {
        return response.text().then(function(text) {
            try {
                return JSON.parse(text);
            } catch (e) {
                // 판별 기준: (1) JSON 파싱에 실패했고 (2) 응답이 JSON Content-Type이 아니며
                // (3) 로그인 페이지 고유 신호가 있을 때만 세션 만료로 본다. 단순히 본문에
                // '로그인'이라는 단어가 있다고 세션 만료로 처리하면, 정상 데이터나 다른
                // 오류 페이지를 오진단할 수 있다. 또한 한글 매칭에만 의존하면 파일 인코딩이
                // 깨졌을 때 조용히 실패하므로, ASCII 신호(login.php 경로, verify_mb_key)를
                // 함께 확인한다.
                var contentType = (response.headers.get('content-type') || '').toLowerCase();
                if (contentType.indexOf('application/json') !== -1) {
                    // Content-Type은 JSON인데 파싱이 깨졌다면 대부분 JSON 앞뒤에 PHP 경고문
                    // (Warning/Notice/Deprecated)이나 BOM 같은 잡출력이 섞인 경우다. 원인을
                    // 추측하지 않도록 응답 원문 앞부분을 화면에 그대로 노출한다.
                    var head = text.slice(0, 300).replace(/</g, '&lt;');
                    var jsonStart = text.indexOf('{');
                    var arrStart = text.indexOf('[');
                    var firstBrace = (jsonStart === -1) ? arrStart
                        : (arrStart === -1 ? jsonStart : Math.min(jsonStart, arrStart));
                        
                    console.error('손상된 JSON 응답', {
                        status: response.status,
                        contentType: contentType,
                        url: response.url,
                        rawText: text.slice(0, 2000)
                    });
                    
                    throw new Error(
                        '서버 JSON 응답 형식이 손상되었습니다. (HTTP ' + response.status + ')\n' +
                        '[진단] 응답 길이: ' + text.length + '자, JSON 시작 위치: ' + firstBrace
                    );
                }
                var isLoginPage =
                    text.indexOf('/manager/login.php') !== -1 ||
                    text.indexOf('login.php') !== -1 ||
                    text.indexOf('logout.php') !== -1 ||
                    text.indexOf('verify_mb_key') !== -1 ||
                    text.indexOf('정상적으로 로그인하여 접근하시기 바랍니다') !== -1;
                if (response.status === 401 || isLoginPage) {
                    var loginErr = new Error('로그인이 만료되었거나 세션이 끊어졌습니다.');
                    loginErr.code = 'LOGIN_REQUIRED';
                    throw loginErr;
                }
                throw new Error('서버가 올바르지 않은 응답을 반환했습니다. (HTTP ' + response.status + ')');
            }
        });
    }

    // 세션 만료 오류를 발행 모달에 공통으로 표시한다(재로그인 링크 + 실제 복구 경로 안내).
    // 처리했으면 true를 반환하므로, 각 catch에서 이 값이 true면 일반 오류 표시를 건너뛴다.
    function _pbHandleLoginRequired(err, targetEl) {
        if (!err || err.code !== 'LOGIN_REQUIRED') return false;
        var el = targetEl || document.getElementById('pb-pub-result');
        if (!el) return true;
        // location.href 전체를 넣으면 중첩 쿼리스트링/#/&로 복귀 주소가 깨질 수 있어
        // 경로+쿼리+해시만 인코딩해서 넘긴다(외부 URL 차단은 login.php의 check_url_host 담당).
        var backUrl = encodeURIComponent(location.pathname + location.search + location.hash);
        el.style.display = '';
        el.innerHTML =
            '<div style="color:#991b1b; white-space:pre-line;">' + err.message + '</div>' +
            '<div style="margin-top:8px;">' +
            '<a href="/manager/login.php?url=' + backUrl + '" class="btn btn_01" style="font-size:13px;">다시 로그인하기</a>' +
            '</div>' +
            '<div style="margin-top:6px; font-size:12px; color:#718096;">' +
            '로그인 후 이 화면으로 돌아옵니다. 작성 내용이 비어 있으면 상단 <b>임시저장 불러오기</b> 버튼으로 ' +
            '마지막 자동저장 시점의 내용을 복구할 수 있습니다.' +
            '</div>';
        return true;
    }

    function _pbStatusBadge(s) {
        // publish_status가 아직 한 번도 발행 시도가 없던 대상은 DB에 null로 들어있어
        // (m[s]||s) 로직이 문자열 "null"을 그대로 화면에 찍고 있었다.
        if (!s) s = 'draft';
        var m = {draft:'미발행',pending:'대기중',processing:'발행중',scheduled:'예약됨',published:'발행완료',failed:'실패'};
        var bg = s==='published' ? '#d1fae5' : s==='failed' ? '#fee2e2' : '#fef3c7';
        return '<span style="font-size:10px;font-weight:700;padding:2px 7px;border-radius:99px;background:'+bg+';">'+(m[s]||s)+'</span>';
    }

    function _pbLoadTargets() {
        let pId = Builder.projectId || 0;
        if (!pId) {
            ['pb-pub-targets-imm','pb-pub-targets-sch'].forEach(function(id){
                document.getElementById(id).innerHTML = '<span style="color:red;">프로젝트 정보를 확인할 수 없습니다.</span>';
            });
            return;
        }

        let ajaxUrl;
        try {
            ajaxUrl = _pbAjaxUrl();
        } catch(e) {
            ['pb-pub-targets-imm','pb-pub-targets-sch'].forEach(function(id){
                document.getElementById(id).innerHTML = '<span style="color:red;">포스트 빌더 처리 주소가 설정되지 않았습니다. 관리자에게 문의해 주세요.</span>';
            });
            return;
        }

        // 이 페이지엔 jQuery($)가 로드되어 있지 않다 - $.post를 쓰면 클릭하는 순간
        // "$ is not defined"로 곧바로 끊겨서 이 함수를 호출한 showPublishModal()의
        // 뒤쪽 코드(모달을 실제로 보이게 하는 부분)까지 실행되지 못해 모달 자체가
        // 열리지 않는 문제가 있었다 - fetch로 교체.
        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'get_post_targets', post_id: _pbPostId || 0, project_id: pId })
        }).then(_pbParseJson).then(function(res) {
            _pbTargets = res.ok ? (res.targets || []) : [];
            _pbRenderTargets('pb-pub-targets-imm');
            _pbRenderTargets('pb-pub-targets-sch');
        }).catch(function(err) {
            _pbTargets = [];
            _pbRenderTargets('pb-pub-targets-imm');
            _pbRenderTargets('pb-pub-targets-sch');
            // 세션 만료면 "연결된 발행 사이트가 없습니다"로 잘못 보이므로 원인을 명시한다.
            _pbHandleLoginRequired(err);
        });
    }

    function _pbRenderTargets(elId) {
        var el = document.getElementById(elId);
        if (!_pbTargets.length) {
            var advId = (Builder.advertiserInfo && Builder.advertiserInfo.id) ? Builder.advertiserInfo.id : 0;
            el.innerHTML = '<p style="color:#718096;text-align:center;">연결된 발행 사이트가 없습니다.</p>' +
                '<p style="text-align:center;"><a href="./site_form.php?advertiser_id=' + advId + '" target="_blank" class="btn btn_02">+ 새 사이트 등록 (새 탭)</a></p>';
            return;
        }
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
                    '<strong>'+t.site_name+'</strong> <small style="color:#718096;">('+t.platform+')</small> '+
                    _pbStatusBadge(t.publish_status)+note+'</label>';
        });
        el.innerHTML = html;
    }

    Builder.submitPublish = async function() {
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
        var catId = document.getElementById('pb-pub-category-id').value;
        var thumb = document.getElementById('pb-pub-thumbnail').value;
        var reqThumb = document.getElementById('pb-pub-require-thumb').checked ? 'Y' : 'N';
        var isPub = document.getElementById('pb-pub-is-public').checked ? 'Y' : 'N';

        _pbBusy = true;
        var submitBtn = document.getElementById('pb-pub-submit-btn');
        submitBtn.disabled = true; submitBtn.textContent = '처리 중...';
        var resultEl = document.getElementById('pb-pub-result');
        resultEl.style.display = ''; 

        try {
            // 1. 자동저장 (새 글 대응)
            resultEl.innerHTML = '⏳ 작성 내용을 자동저장 중입니다...';
            if (typeof Builder.ensurePostSaved === 'function') {
                const savedPostId = await Builder.ensurePostSaved();
                _pbPostId = savedPostId;
            }

            // 2. 최종본 준비
            resultEl.innerHTML = '⏳ 최종본 스냅샷을 생성 중입니다...';
            const cardsJsonStr = JSON.stringify(Builder.cards || []);
            const cardsDebugInfo = `[진단] 카드 개수: ${(Builder.cards||[]).length}, 직렬화 길이: ${cardsJsonStr.length}자\n[진단] 앞부분: ${cardsJsonStr.slice(0, 150)}`;
            
            const assemblePayload = {
                action: 'assemble_final',
                post_id: Number(_pbPostId || 0),
                project_id: Number(Builder.projectId || 0),
                char_per_line: typeof Builder.charPerLine !== 'undefined' ? Number(Builder.charPerLine) : 60,
                cards: Array.isArray(Builder.cards) ? Builder.cards : []
            };

            const assembleRes = await fetch(_pbAjaxUrl(), {
                method: 'POST',
                headers: { 
                    'Content-Type': 'application/json; charset=UTF-8',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include', // or 'same-origin' based on existing setup, 'include' is safer for Gnuboard
                body: JSON.stringify(assemblePayload)
            }).then(_pbParseJson);

            if (!assembleRes.ok) {
                throw new Error('최종본 준비 실패: ' + (assembleRes.error || '알 수 없는 오류') + '\n' + cardsDebugInfo);
            }
            _pbVersionId = assembleRes.version_id;

            // 3. 채널별 발행 요청
            resultEl.innerHTML = '⏳ 발행 요청 중...';
            var tlist = Array.prototype.slice.call(checks);
            var total = tlist.length, results = [];
            
            for (var i = 0; i < total; i++) {
                var chk = tlist[i];
                var sId = parseInt(chk.value, 10);
                try {
                    const pubRes = await fetch(_pbAjaxUrl(), {
                        method: 'POST',
                        headers: { 
                            'Content-Type': 'application/x-www-form-urlencoded',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        credentials: 'include',
                        body: new URLSearchParams({
                            action:'publish', post_id:_pbPostId, project_id: Builder.projectId || 0, version_id: _pbVersionId,
                            site_id:sId, schedule_type:schedType, scheduled_at:schedAt,
                            category_id:catId, thumbnail:thumb, require_thumbnail:reqThumb, is_public:isPub
                        })
                    }).then(_pbParseJson);
                    results.push({siteId:sId, ok:pubRes.ok, data:pubRes});
                } catch(e) {
                    results.push({siteId:sId, ok:false, data:{error: (e && e.message) || '서버 오류', errors:[]}});
                }
                _pbRenderResults(results, total);
            }
        } catch (err) {
            _pbBusy = false;
            submitBtn.disabled = false; submitBtn.textContent = '발행 시작';
            if (!_pbHandleLoginRequired(err, resultEl)) {
                resultEl.innerHTML = '<div style="color:#991b1b; white-space:pre-line;">' + err.message + '</div>';
            }
        }
    };

    Builder.retryPublish = function(siteId) {
        var panelSel = (_pbTab==='immediate') ? '#pb-pub-panel-imm .pb-pub-chk' : '#pb-pub-panel-sch .pb-pub-chk';
        var checks = document.querySelectorAll(panelSel);
        checks.forEach(function(chk) {
            chk.checked = (parseInt(chk.value, 10) === siteId);
        });
        Builder.submitPublish();
    };

    function _pbRenderResults(results, total) {
        var html = '<ul style="margin:0;padding:0;list-style:none;">';
        results.forEach(function(r) {
            var t = _pbTargets.find(function(pt){ return pt.site_id === r.siteId; });
            var name = t ? t.site_name : 'Site #'+r.siteId;
            var isNaver = t && (t.platform === 'naver' || t.platform === 'naver_blog');

            if (r.ok) {
                if (isNaver && r.data.job_status === 'published') {
                    // Naver manual workflow UI
                    var url = r.data.published_url || '';
                    html += '<li style="color:#065f46;background:#d1fae5;padding:10px 12px;border-radius:6px;margin-bottom:8px;">';
                    html += '<div style="margin-bottom:6px;font-weight:bold;">📦 수동 발행 패키지 생성 완료: ' + name + '</div>';
                    html += '<div style="display:flex;gap:6px;flex-wrap:wrap;">';
                    if (url) {
                        html += '<a href="' + url + '" target="_blank" class="btn btn_02" style="font-size:12px;padding:4px 8px;">이미지 패키지(ZIP) 다운로드</a>';
                    }
                    html += '<button type="button" class="btn btn_02" style="font-size:12px;padding:4px 8px;" onclick="Builder.copyNaverContent()">네이버용 텍스트 복사</button>';
                    html += '<button type="button" class="btn btn_02" style="font-size:12px;padding:4px 8px;" onclick="window.open(\'https://blog.naver.com/GoBlogWrite.naver\', \'_blank\')">네이버 글쓰기 열기</button>';
                    // 이번 발행 응답의 target_id를 우선 사용한다 - _pbTargets는 발행 이전에
                    // 로드되므로, 이번에 새로 생성된 타겟은 거기에 0으로 들어있다.
                    var mtId = (r.data && r.data.target_id) ? r.data.target_id : (t ? t.target_id : 0);
                    html += '<button type="button" class="btn btn_01" style="font-size:12px;padding:4px 8px;" onclick="Builder.markManualPublished(' + mtId + ')">수동 발행 완료 기록</button>';
                    html += '</div></li>';
                } else {
                    var lbl = r.data.job_status==='published' ? '✅ 발행 완료' :
                              r.data.job_status==='scheduled' ? '🕐 예약 등록됨' : '⏳ 처리 중';
                    var url = r.data.published_url ? ' — <a href="'+r.data.published_url+'" target="_blank">바로가기</a>' : '';
                    html += '<li style="color:#065f46;background:#d1fae5;padding:6px 10px;border-radius:6px;margin-bottom:4px;">'+lbl+': <b>'+name+'</b>'+url+'</li>';
                }
                
                if (r.data.warnings && r.data.warnings.length > 0) {
                    var wlist = r.data.warnings.map(function(w){ return '<li>'+w+'</li>'; }).join('');
                    html += '<li style="color:#92400e;background:#fef3c7;padding:6px 10px;border-radius:6px;margin-bottom:4px;font-size:12px;"><ul style="margin:0;padding-left:15px;">'+wlist+'</ul></li>';
                }
            } else {
                var errStr = r.data.error || '오류';
                if (r.data.errors && r.data.errors.length > 0) {
                    errStr = '<ul style="margin:4px 0 0;padding-left:15px;font-size:12px;">' + r.data.errors.map(function(e){ return '<li>'+e+'</li>'; }).join('') + '</ul>';
                }
                html += '<li style="color:#991b1b;background:#fee2e2;padding:6px 10px;border-radius:6px;margin-bottom:4px;">❌ 실패: <b>'+name+'</b> — '+errStr+
                        ' <button type="button" class="btn btn_02" style="padding:2px 6px; font-size:11px; margin-left:10px; cursor:pointer;" onclick="Builder.retryPublish('+r.siteId+')">다시 시도</button></li>';
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

    Builder.copyNaverContent = function() {
        var text = '';
        var tags = '';
        if (Builder.cards) {
            Builder.cards.forEach(function(c) {
                if (c.deleted) return;
                if (!c.content || !c.content.trim()) return;
                
                if (c.type === 'title') {
                    text += c.content.trim() + '\n\n';
                } else {
                    if (c.title) text += c.title.trim() + '\n';
                    text += c.content.trim() + '\n\n';
                }
            });
        }
        
        var tagsInput = document.getElementById('pb-pub-tags');
        if (tagsInput && tagsInput.value.trim()) {
            var tagArr = tagsInput.value.split(',').map(function(t){ return '#' + t.trim(); });
            text += '\n' + tagArr.join(' ');
        }
        
        // navigator.clipboard 는 보안 컨텍스트(HTTPS)에서만 존재한다. 이 사이트는 http로
        // 서비스되고 있어서 그대로 두면 항상 "지원하지 않는 브라우저"로 끝나 복사가 아예
        // 불가능했다. builder.js가 쓰는 것과 같은 execCommand 폴백을 사용한다.
        function _pbFallbackCopy(str) {
            var ta = document.createElement('textarea');
            ta.value = str;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            var ok = false;
            try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
            document.body.removeChild(ta);
            return ok;
        }

        function _pbCopyDone(ok) {
            alert(ok
                ? '네이버 블로그용 텍스트(제목, 본문, 태그)가 클립보드에 복사되었습니다.\n\n네이버 글쓰기 화면에 붙여넣기 하세요.'
                : '클립보드 복사에 실패했습니다. 브라우저에서 클립보드 접근을 허용해 주세요.');
        }

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text)
                .then(function() { _pbCopyDone(true); })
                .catch(function() { _pbCopyDone(_pbFallbackCopy(text)); });
        } else {
            _pbCopyDone(_pbFallbackCopy(text));
        }
    };

    Builder.markManualPublished = function(targetId) {
        if (!targetId) {
            alert('대상 정보를 찾을 수 없습니다.');
            return;
        }
        
        let ajaxUrl;
        try {
            ajaxUrl = _pbAjaxUrl();
        } catch(e) {
            alert('포스트 빌더 처리 주소가 설정되지 않았습니다. 관리자에게 문의해 주세요.');
            return;
        }

        var url = prompt('네이버 블로그에 등록 완료된 게시글 URL을 입력하세요.\n예) https://blog.naver.com/아이디/게시물번호');
        if (url === null) return;
        if (!url.trim()) {
            alert('URL을 입력해야 등록이 완료됩니다.');
            return;
        }
        
        fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'mark_manual_published',
                post_target_id: targetId,
                published_url: url.trim()
            })
        }).then(_pbParseJson).then(function(res) {
            if (res.ok) {
                alert('발행 완료로 기록되었습니다.');
                _pbLoadTargets(); // 타겟 목록 새로고침
                document.getElementById('pb-pub-result').innerHTML = ''; // 결과창 비우기
            } else {
                alert('기록 실패: ' + (res.error || '알 수 없는 오류'));
            }
        }).catch(function(err) {
            if (_pbHandleLoginRequired(err)) return;
            alert((err && err.message) || '서버 통신 오류');
        });
    };

}());
</script>

<?php
include_once(__DIR__ . '/../layout/footer.php');
