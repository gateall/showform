<?php
if (!defined('_GNUBOARD_')) exit;

// 네이티브 관리자 헤더(#hd)와 완전히 분리된, 신규 업무 전용 상단 전환 메뉴.
// "쇼폼/블로그 자동화/랜딩페이지" 중 실제 화면이 있는 영역만 버튼으로 노출한다
// (없는 기능을 가짜 링크로 만들지 않는다 — 현재는 쇼폼 화면이 없어 자동 생략됨).
// $sf_content_verticals, $sf_current_vertical 은 admin.head.php가 넘긴다.
if (empty($sf_content_verticals)) {
    return;
}
?>
<nav class="sf-vswitch" aria-label="쇼폼·콘텐츠 운영 메뉴">
    <ul class="sf-vswitch-tabs">
        <?php $sf_v_id = 0; foreach ($sf_content_verticals as $sf_v_label => $sf_v_data): $sf_v_id++; $sf_panel_id = 'sf-vswitch-panel-' . $sf_v_id; $sf_is_current = ($sf_current_vertical === $sf_v_label); ?>
        <li class="sf-vswitch-tab-item">
            <button type="button" class="sf-vswitch-tab<?php echo $sf_is_current ? ' sf-current' : ''; ?>" aria-expanded="false" aria-controls="<?php echo $sf_panel_id; ?>">
                <?php echo get_text($sf_v_label); ?>
                <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
            </button>
            <div class="sf-vswitch-panel" id="<?php echo $sf_panel_id; ?>">
                <?php if ($sf_v_data['dashboard']): ?>
                <a href="<?php echo $sf_v_data['dashboard']['href']; ?>" class="sf-vswitch-dashboard-link">
                    <i class="fa-solid fa-gauge" aria-hidden="true"></i> <?php echo get_text($sf_v_data['dashboard']['title']); ?>
                </a>
                <?php endif; ?>
                <div class="sf-vswitch-sitemap">
                    <?php foreach ($sf_v_data['items'] as $sf_item): ?>
                    <a href="<?php echo $sf_item['href']; ?>" class="sf-vswitch-link"><?php echo get_text($sf_item['title']); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
        </li>
        <?php endforeach; ?>
    </ul>
</nav>
<script>
jQuery(function($) {
    var $tabs = $('.sf-vswitch-tab');

    $tabs.on('click', function(e) {
        e.stopPropagation();
        var $btn = $(this);
        var isExpanded = $btn.attr('aria-expanded') === 'true';

        $tabs.not($btn).attr('aria-expanded', 'false').removeClass('sf-open');
        $('.sf-vswitch-panel').not($btn.siblings('.sf-vswitch-panel')).removeClass('active');

        $btn.attr('aria-expanded', !isExpanded).toggleClass('sf-open', !isExpanded);
        $btn.siblings('.sf-vswitch-panel').toggleClass('active', !isExpanded);
    });

    $(document).on('click', function() {
        $tabs.attr('aria-expanded', 'false').removeClass('sf-open');
        $('.sf-vswitch-panel').removeClass('active');
    });

    $(document).on('keyup', function(e) {
        if (e.key === 'Escape') {
            $tabs.attr('aria-expanded', 'false').removeClass('sf-open');
            $('.sf-vswitch-panel').removeClass('active');
        }
    });
});
</script>
