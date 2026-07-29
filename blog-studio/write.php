<?php
include_once('./_common.php');

if (!$is_admin) {
    alert('관리자만 접근 가능합니다.', G5_BBS_URL.'/login.php?url='.urlencode($_SERVER['REQUEST_URI']));
}

$post_id = isset($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
if (!$post_id) {
    alert('잘못된 접근입니다. (post_id 누락)', G5_ADMIN_URL.'/blog/blog_dashboard.php');
}

// Fetch post info to verify existence
$post = sql_fetch("SELECT * FROM g5_blog_posts WHERE id = '{$post_id}'");
if (!$post) {
    alert('존재하지 않는 포스팅입니다.', G5_ADMIN_URL.'/blog/blog_dashboard.php');
}

$g5['title'] = '쇼폼 블로그 스튜디오';

// Include head.sub.php for essential HTML meta/scripts, but NOT the Gnuboard theme head.
// We want a completely custom UI without the generic website header.
include_once(G5_PATH.'/head.sub.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_URL.'/blog-studio/css/blog-studio.css">', 0);
add_javascript('<script src="'.G5_URL.'/blog-studio/js/blog-studio.js"></script>', 0);
?>

<div class="blog-studio-shell">
    <!-- Top bar -->
    <header class="studio-topbar">
        <div class="topbar-left">
            <span class="studio-logo">쇼폼 블로그 스튜디오</span>
            <span class="project-title"><?php echo htmlspecialchars($post['title'] ? $post['title'] : "새 포스팅 (#{$post_id})"); ?></span>
            <span class="status-badge">작성 중</span>
            <span class="save-status">저장 완료</span>
            <span class="progress-text">완성률 0%</span>
        </div>
        <div class="topbar-right">
            <span class="ai-status">AI: 1순위 미지정</span>
            <button type="button" class="studio-btn btn-outline" onclick="previewPost()">미리보기</button>
            <button type="button" class="studio-btn btn-primary" onclick="saveAll()">전체 저장</button>
            <button type="button" class="studio-btn btn-success" onclick="completePost()">작성 완료</button>
            <a href="<?php echo G5_ADMIN_URL; ?>/blog/post_form.php?w=u&post_id=<?php echo $post_id; ?>" class="studio-btn btn-dark">관리자로 이동</a>
        </div>
    </header>

    <!-- Main 2-column Layout -->
    <div class="blog-studio-layout">
        <!-- Left Column: Content Editor -->
        <main class="studio-main-content">
            <div id="builder_sections">
                <!-- Basic Info Section -->
                <section class="studio-section" id="sec_basic">
                    <div class="sec-header">
                        <h2>기본 정보</h2>
                        <div class="sec-status">
                            <span class="badge badge-gray">준비 중</span>
                        </div>
                    </div>
                    <div class="sec-body">
                        <!-- Content goes here -->
                        <p class="placeholder-text">기본 정보 입력폼이 위치할 영역입니다.</p>
                    </div>
                </section>

                <!-- Title Section -->
                <section class="studio-section" id="sec_title">
                    <div class="sec-header">
                        <h2>제목 작성</h2>
                        <div class="sec-status">
                            <span class="badge badge-gray">준비 중</span>
                        </div>
                    </div>
                    <div class="sec-body">
                        <!-- Content goes here -->
                        <p class="placeholder-text">제목 작성폼이 위치할 영역입니다.</p>
                    </div>
                </section>
                
                <!-- Body Section 1 -->
                <section class="studio-section" id="sec_body_1">
                    <div class="sec-header">
                        <h2>본문 1단락 - 도입부</h2>
                        <div class="sec-status">
                            <span class="badge badge-gray">준비 중</span>
                        </div>
                    </div>
                    <div class="sec-body">
                        <!-- Content goes here -->
                        <p class="placeholder-text">본문 1단락 작성 영역입니다.</p>
                    </div>
                </section>
            </div>
        </main>

        <!-- Right Column: AI Quick Tools -->
        <aside class="studio-quick-tools">
            <div class="quick-tools-inner">
                <h3>AI 퀵 도구</h3>
                <div class="tool-group">
                    <button type="button" class="tool-btn" onclick="openAIAgentSettings()">AI 에이전트 선택</button>
                    <button type="button" class="tool-btn" onclick="generatePromptAll()">전체 프롬프트 생성</button>
                    <button type="button" class="tool-btn" onclick="generateTitle()">제목 생성</button>
                    <button type="button" class="tool-btn" onclick="generateBody()">본문 생성</button>
                    <button type="button" class="tool-btn" onclick="generateImage()">이미지 생성</button>
                    <button type="button" class="tool-btn" onclick="generateKeywords()">키워드 자동 생성</button>
                    <button type="button" class="tool-btn" onclick="generateHashtags()">해시태그 자동 생성</button>
                </div>
                
                <h3>삽입 도구</h3>
                <div class="tool-group">
                    <button type="button" class="tool-btn outline">장소/지도 추가</button>
                    <button type="button" class="tool-btn outline">사이트 링크 추가</button>
                    <button type="button" class="tool-btn outline">동영상 추가</button>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php
include_once(G5_PATH.'/tail.sub.php');
?>
