<?php
if (!defined('_GNUBOARD_')) exit;

// 상단 고정 바: 로고, "기본 관리자"/"쇼폼·콘텐츠 운영" 영역 전환, 우측 유틸리티.
// $sf_current_area, $sf_content_dashboard_url 은 admin_menu_build.php 결과를 호출부에서 넘긴다.
?>
<header class="sf-admin-header sf-area-<?php echo $sf_current_area; ?>">
    <div class="sf-header-left">
        <button type="button" class="sf-btn-gnb" id="sf-btn-gnb" aria-expanded="false" aria-controls="sf-admin-sidebar" aria-label="메뉴 열기">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <a class="sf-header-logo" href="<?php echo correct_goto_url(G5_ADMIN_URL); ?>">
            <img src="<?php echo G5_ADMIN_URL ?>/img/logo.png" alt="SHOWFORM ADMIN">
        </a>
    </div>

    <nav class="sf-area-switch" aria-label="운영 영역 전환">
        <a href="<?php echo correct_goto_url(G5_ADMIN_URL); ?>"
           class="sf-area-btn<?php echo $sf_current_area === 'core' ? ' active' : ''; ?>"
           <?php echo $sf_current_area === 'core' ? 'aria-current="page"' : ''; ?>>기본 관리자</a>
        <a href="<?php echo $sf_content_dashboard_url; ?>"
           class="sf-area-btn<?php echo $sf_current_area === 'content' ? ' active' : ''; ?>"
           <?php echo $sf_current_area === 'content' ? 'aria-current="page"' : ''; ?>>쇼폼·콘텐츠 운영</a>
    </nav>

    <div class="sf-header-right">
        <span class="sf-admin-whoami">
            <?php
            $sf_admin_label = ($is_admin === 'super') ? '최고관리자' : '관리자';
            $sf_admin_name = isset($member['mb_nick']) && $member['mb_nick'] !== '' ? $member['mb_nick'] : (isset($member['mb_id']) ? $member['mb_id'] : '');
            echo get_text($sf_admin_label . '(' . $sf_admin_name . ') 로그인 중');
            ?>
        </span>
        <a href="<?php echo correct_goto_url(G5_ADMIN_URL); ?>" title="관리자 메인" aria-label="관리자 메인"><i class="fa-solid fa-gauge" aria-hidden="true"></i></a>
        <a href="<?php echo G5_URL ?>/" target="_blank" rel="noopener noreferrer" title="홈페이지" aria-label="홈페이지"><i class="fa-solid fa-house" aria-hidden="true"></i></a>
        <a href="<?php echo G5_BBS_URL ?>/logout.php" title="로그아웃" aria-label="로그아웃"><i class="fa-solid fa-right-from-bracket" aria-hidden="true"></i></a>
    </div>
</header>
