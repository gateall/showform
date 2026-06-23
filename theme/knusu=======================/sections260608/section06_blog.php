<?php
if (!defined('_GNUBOARD_')) exit;

$blog_cards = array(
    array(
        'title' => '시공 전후 비교',
        'tag' => '대표 시공사례',
        'desc' => '문제 원인부터 탐지, 공사 전후 결과까지 한눈에 비교할 수 있습니다.',
        'link_text' => '사례 자세히 보기',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>'
    ),
    array(
        'title' => '누수 칼럼',
        'tag' => '전문가 가이드',
        'desc' => '누수 원인, 점검 방법, 유지관리 팁을 이해하기 쉽게 정리한 칼럼입니다.',
        'link_text' => '칼럼 더보기',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.5 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.5L14.5 2z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>'
    ),
    array(
        'title' => '현장 이야기',
        'tag' => '리얼 작업기',
        'desc' => '실제 현장 사진과 작업 스토리로 공사 흐름과 결과를 확인해보세요.',
        'link_text' => '현장 스토리 보기',
        'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"/></svg>'
    )
);

$blog_link = isset($knu_channels['blog']) ? $knu_channels['blog'] : 'https://blog.naver.com/qkrxogus_04';
?>

<style>
#knuBlog { position: relative; padding: 110px 0; background: linear-gradient(180deg, #fcfaf6 0%, #f7f8fb 100%); overflow: hidden; }
#knuBlog::before { content: ""; position: absolute; left: -50px; bottom: -50px; width: 250px; height: 250px; background: radial-gradient(circle, rgba(215, 179, 114, 0.04) 0%, rgba(255, 255, 255, 0) 70%); z-index: 1; }
#knuBlog .knu-sec-head { margin-bottom: 60px; text-align: center; position: relative; z-index: 5; }
#knuBlog .knu-badge { display: inline-block; margin-bottom: 16px; padding: 6px 14px; background: rgba(30,142,28,0.1); color: #2e7d32; border-radius: 4px; font-size: 13px; font-weight: 800; letter-spacing: .1em; border: 1px solid rgba(46,125,50,.2); }
#knuBlog .knu-sec-head h3 { font-size: 38px; font-weight: 900; color: #111; margin-bottom: 18px; letter-spacing: -0.02em; }
#knuBlog .knu-sec-head p { font-size: 17px; color: #667085; max-width: 650px; margin: 0 auto; line-height: 1.6; }
#knuBlog .knu-blog-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 26px; position: relative; z-index: 10; }
#knuBlog .knu-blog-card { position: relative; background: #fff; border: 1px solid #e7eaf0; border-radius: 24px; padding: 44px 34px; text-decoration: none; color: inherit; transition: all .4s cubic-bezier(.165,.84,.44,1); display: flex; flex-direction: column; height: 100%; }
#knuBlog .knu-blog-card:hover { transform: translateY(-10px); box-shadow: 0 24px 48px rgba(0,0,0,.06); border-color: #d9b06b; }
#knuBlog .knu-icon-box { width: 60px; height: 60px; background: #f8fafc; border-radius: 16px; display: flex; align-items: center; justify-content: center; margin-bottom: 28px; color: #153b6d; transition: all .3s ease; }
#knuBlog .knu-icon-box svg { width: 30px; height: 30px; }
#knuBlog .knu-blog-card:hover .knu-icon-box { background: #153b6d; color: #fff; transform: rotate(-3deg) scale(1.05); }
#knuBlog .knu-card-tag { display: inline-block; font-size: 12px; font-weight: 800; color: #d9b06b; margin-bottom: 10px; letter-spacing: .05em; text-transform: uppercase; }
#knuBlog .knu-blog-card h4 { font-size: 24px; font-weight: 850; color: #111; margin-bottom: 14px; line-height: 1.25; }
#knuBlog .knu-blog-card p { font-size: 15px; color: #667e98; line-height: 1.7; margin-bottom: 30px; flex-grow: 1; }
#knuBlog .knu-blog-link { display: inline-flex; align-items: center; gap: 10px; font-size: 15px; font-weight: 800; color: #153b6d; }
#knuBlog .knu-blog-link svg { width: 20px; height: 20px; color: #d9b06b; transition: transform .3s ease; }
#knuBlog .knu-blog-card:hover .knu-blog-link svg { transform: translateX(6px); }
#knuBlog .knu-card-bar { position: absolute; bottom: 0; left: 34px; width: 40px; height: 4px; background: #eee; transition: all .4s ease; border-radius: 4px 4px 0 0; }
#knuBlog .knu-blog-card:hover .knu-card-bar { width: 80px; background: #d9b06b; }
@media (max-width:1199px){ #knuBlog .knu-blog-grid { grid-template-columns: repeat(2,1fr); gap: 20px; } #knuBlog { padding: 90px 0; } }
@media (max-width:767px){ #knuBlog .knu-blog-grid { grid-template-columns: 1fr; gap: 16px; } #knuBlog { padding: 70px 0; } #knuBlog .knu-sec-head h3 { font-size: 30px; } #knuBlog .knu-blog-card { padding: 36px 30px; } }
</style>

<section class="knu-section" id="knuBlog">
    <div class="knu-container">
        <div class="knu-sec-head" data-knu-fade="data-knu-fade">
            <span class="knu-badge">06.BLOG CONTENT</span>
            <h3>블로그 사례 · 칼럼 · 현장 이야기</h3>
            <p>시공 전후 비교, 점검 포인트, 보험 처리 안내까지<br>실제 현장 기반 콘텐츠로 자세히 확인해보세요.</p>
        </div>

        <div class="knu-blog-grid">
            <?php foreach ($blog_cards as $item) { ?>
            <a href="<?php echo $blog_link; ?>" target="_blank" rel="noopener" class="knu-blog-card" data-knu-fade="data-knu-fade">
            <div class="knu-icon-box"><?php echo $item['icon']; ?></div>
                <span class="knu-card-tag"><?php echo $item['tag']; ?></span>
                <h4><?php echo $item['title']; ?></h4>
                <p><?php echo $item['desc']; ?></p>
                <div class="knu-blog-link">
                    <?php echo $item['link_text']; ?>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
                </div>
                <div class="knu-card-bar"></div>
            </a>
            <?php } ?>
        </div>
    </div>
</section>
