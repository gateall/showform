<?php
$sub_menu = '360000';
include_once('./_common.php');

$g5['title'] = '블로그 통합 대시보드';
include_once(G5_ADMIN_PATH.'/admin.head.php');

// 탭 구성
$tabs = [
    ['id' => 'tab-proj', 'title' => '포스팅 프로젝트 관리', 'url' => './project_list.php'],
    ['id' => 'tab-adv', 'title' => '광고주 관리', 'url' => './advertiser_list.php'],
    ['id' => 'tab-acc', 'title' => '광고주 계정', 'url' => './advertiser_account_list.php'],
    ['id' => 'tab-site', 'title' => '사이트 관리', 'url' => './site_list.php'],
    ['id' => 'tab-ai', 'title' => 'AI 세팅', 'url' => './ai_provider_list.php'],
    ['id' => 'tab-pub', 'title' => '예약 발행 관리', 'url' => './publish_job_list.php'],
    ['id' => 'tab-img', 'title' => '이미지 라이브러리', 'url' => './image_list.php'],
    ['id' => 'tab-report', 'title' => '통계 보고서', 'url' => './report_dashboard.php']
];
?>

<style>
/* 통합 대시보드 레이아웃 */
.ud-wrap {
    display: flex;
    height: calc(100vh - 150px);
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
}

/* 좌측 탭 메뉴 */
.ud-sidebar {
    width: 250px;
    background: #f8f9fa;
    border-right: 1px solid #ddd;
    overflow-y: auto;
}

.ud-tab {
    display: block;
    padding: 15px 20px;
    color: #333;
    text-decoration: none;
    font-size: 15px;
    font-weight: 500;
    border-bottom: 1px solid #eee;
    transition: all 0.2s;
    cursor: pointer;
}

.ud-tab:hover {
    background: #e9ecef;
}

.ud-tab.active {
    background: #4a5568;
    color: #fff;
    border-left: 4px solid #3182ce;
}

/* 우측 콘텐츠(아이프레임) */
.ud-content {
    flex: 1;
    position: relative;
}

#ud_iframe {
    width: 100%;
    height: 100%;
    border: none;
    background: #fff;
}
</style>

<div class="ud-wrap">
    <div class="ud-sidebar">
        <div style="padding: 20px;">
            <button type="button" class="btn_submit btn" style="width: 100%; font-size: 15px; padding: 12px; background: #3182ce;" onclick="createNewPost()">+ 새 포스팅 작성</button>
            <script>
            function createNewPost() {
                if(confirm('새 포스팅 작성을 시작하시겠습니까? (초안이 생성되고 넓은 작성 화면으로 이동합니다)')) {
                    // Create draft via AJAX
                    fetch('<?php echo G5_URL; ?>/blog-studio/ajax/create_draft.php', { method: 'POST' })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            window.location.href = '<?php echo G5_URL; ?>/blog-studio/write.php?post_id=' + data.post_id;
                        } else {
                            alert('초안 생성 실패: ' + data.message);
                        }
                    });
                }
            }
            </script>
        </div>
        <?php foreach ($tabs as $i => $tab) { ?>
            <a class="ud-tab <?php echo $i === 0 ? 'active' : ''; ?>" data-url="<?php echo $tab['url']; ?>" onclick="changeTab(this)">
                <?php echo $tab['title']; ?>
            </a>
        <?php } ?>
    </div>
    <div class="ud-content">
        <!-- 첫 번째 탭의 주소로 초기화 -->
        <iframe id="ud_iframe" src="<?php echo $tabs[0]['url']; ?>"></iframe>
    </div>
</div>

<script>
function changeTab(el) {
    // 탭 활성화 변경
    document.querySelectorAll('.ud-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    
    // Iframe URL 변경
    const url = el.getAttribute('data-url');
    document.getElementById('ud_iframe').src = url;
}

// Iframe 내부 페이지의 헤더/사이드바 등 불필요 요소 숨김 처리
document.getElementById('ud_iframe').addEventListener('load', function() {
    try {
        const doc = this.contentWindow.document;
        
        // CSS 주입을 통해 레이아웃 간소화
        const style = doc.createElement('style');
        style.textContent = `
            /* 그누보드 기본 헤더/사이드바 숨김 */
            #hd, #left_menu, .sf-admin-header, #sf-admin-sidebar, .sf-sidebar-overlay, #sf-admin-sitemap, #container_title, .local_desc {
                display: none !important;
            }
            /* 컨테이너 여백/위치 초기화 */
            #wrapper { padding: 0 !important; margin: 0 !important; }
            #container { padding: 20px !important; margin: 0 !important; width: 100% !important; min-width: 100% !important; }
            body { background: #fff !important; }
        `;
        doc.head.appendChild(style);
    } catch(e) {
        console.error("Iframe style injection blocked by cross-origin policy.", e);
    }
});
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
