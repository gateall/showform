<?php
include_once('./_common.php');

if (!$is_admin) {
    alert('관리자만 접근 가능합니다.', G5_BBS_URL.'/login.php?url='.urlencode($_SERVER['REQUEST_URI']));
}
auth_check_menu($auth, '360000', 'w');

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
if (!$post_id) {
    alert('잘못된 접근입니다. (post_id 누락)', G5_ADMIN_URL.'/blog/blog_dashboard.php');
}

// Fetch post info
$post = sql_fetch("SELECT * FROM g5_blog_posts WHERE id = '{$post_id}'");
if (!$post) {
    alert('존재하지 않는 포스팅입니다.', G5_ADMIN_URL.'/blog/blog_dashboard.php');
}

// Load Builder State
$builder_state = [];
if (!empty($post['builder_state'])) {
    $builder_state = json_decode($post['builder_state'], true);
}
// Default State if empty
if (empty($builder_state)) {
    $builder_state = [
        'basic' => ['project_id' => $post['project_id'], 'tone' => '자연스러운 블로그체', 'target_audience' => ''],
        'title' => ['main_keyword' => '', 'sub_keywords' => '', 'title_text' => $post['title']],
        'body'  => [
            ['id' => 'block_1', 'subtitle' => '독자의 문제 또는 관심사', 'content' => '']
        ],
        'seo'   => ['hashtags' => '']
    ];
}

$g5['title'] = '쇼폼 블로그 스튜디오';

include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/blog-studio/css/blog-studio.css?ver='.time().'">', 0);
?>
<script>
    // Initial State Injected from PHP
    window.StudioState = <?php echo json_encode($builder_state, JSON_UNESCAPED_UNICODE); ?>;
    window.POST_ID = <?php echo $post_id; ?>;
    window.G5_URL = '<?php echo G5_URL; ?>';
</script>
<script src="<?php echo G5_URL; ?>/blog-studio/js/blog-studio.js?ver=<?php echo time(); ?>"></script>

<div class="blog-studio-shell">
    <!-- Top bar -->
    <header class="studio-topbar">
        <div class="topbar-left">
            <span class="studio-logo">쇼폼 스튜디오</span>
            <span class="project-title" id="top_title_display"><?php echo htmlspecialchars($post['title'] ? $post['title'] : "새 포스팅 (#{$post_id})"); ?></span>
            <span class="status-badge" id="top_status">작성 중</span>
            <span class="save-status" id="top_save_time">자동 저장됨</span>
            <span class="progress-text" id="top_progress">완성률 0%</span>
        </div>
        <div class="topbar-right">
            <span class="ai-status">AI: 1순위 지정 안됨</span>
            <button type="button" class="studio-btn btn-outline" onclick="toggleFocusMode()">집중 모드</button>
            <button type="button" class="studio-btn btn-outline" onclick="previewPost()">미리보기</button>
            <button type="button" class="studio-btn btn-primary" onclick="saveAll()">전체 저장</button>
            <button type="button" class="studio-btn btn-success" onclick="completePost()">작성 완료</button>
            <a href="javascript:void(0);" onclick="returnAdmin()" class="studio-btn btn-dark">관리자로 이동</a>
        </div>
    </header>

    <!-- Main Layout -->
    <div class="blog-studio-layout">
        <!-- Left Column: Content Editor -->
        <main class="studio-main-content">
            <div id="builder_sections">
                <!-- Sections will be rendered by JS -->
            </div>
            <button type="button" class="studio-btn btn-outline" style="width: 100%; margin-top: 10px; padding: 15px;" onclick="addBodyBlock()">+ 새 본문 단락 추가</button>
        </main>

        <!-- Right Column: AI Quick Tools -->
        <aside class="studio-quick-tools">
            <div class="quick-tools-inner">
                <h3>AI 자동화 도구</h3>
                <div class="tool-group">
                    <button type="button" class="tool-btn" onclick="openAIAgentSettings()">🤖 AI 에이전트 / 모델 설정</button>
                    <button type="button" class="tool-btn" onclick="generateAll()">✨ 전체 자동 생성 (원클릭)</button>
                </div>
                
                <h3>단락별 AI 도구</h3>
                <div class="tool-group">
                    <button type="button" class="tool-btn" onclick="generateTitle()">제목 생성</button>
                    <button type="button" class="tool-btn" onclick="generateFocusBody()">선택 단락 본문 생성</button>
                    <button type="button" class="tool-btn" onclick="generateImage()">선택 단락 이미지 생성</button>
                    <button type="button" class="tool-btn" onclick="generateKeywords()">키워드/해시태그 추천</button>
                </div>
                
                <h3>첨부 도구</h3>
                <div class="tool-group">
                    <button type="button" class="tool-btn outline">장소/지도 첨부</button>
                    <button type="button" class="tool-btn outline">동영상 첨부</button>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- Mobile Overlay & FAB -->
<div class="mobile-overlay" id="mobileOverlay" onclick="toggleMobileTools()"></div>
<button class="mobile-fab" onclick="toggleMobileTools()">🛠️ AI 도구</button>

<?php
include_once(G5_PATH.'/tail.sub.php');
?>
