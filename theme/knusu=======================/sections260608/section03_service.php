<?php
if (!defined('_GNUBOARD_')) exit;

if (!function_exists('knu_service_icon_svg')) {
    function knu_service_icon_svg($type){
        switch($type){
            case 'detect':
                return '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="ksSvcDetect" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#e0b76d"/><stop offset="100%" stop-color="#b98633"/></linearGradient></defs><circle cx="27" cy="27" r="14" fill="#fff7ea" stroke="url(#ksSvcDetect)" stroke-width="3.2"/><path d="M37.5 37.5L50 50" fill="none" stroke="#163a6d" stroke-width="4" stroke-linecap="round"/><path d="M21.5 27.5c2.8-3 5.3-4.5 7.8-4.5 3.1 0 5.8 2 8.2 6" fill="none" stroke="#163a6d" stroke-width="3" stroke-linecap="round"/><path d="M19.5 31c2.7 2.7 5.7 4 8.8 4 2.9 0 5.8-1.5 8.6-4.7" fill="none" stroke="url(#ksSvcDetect)" stroke-width="3" stroke-linecap="round"/></svg>';
            case 'repair':
                return '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="ksSvcRepair" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#e0b76d"/><stop offset="100%" stop-color="#b98633"/></linearGradient></defs><rect x="10" y="24" width="18" height="16" rx="4" fill="#163a6d"/><rect x="36" y="24" width="18" height="16" rx="4" fill="#163a6d"/><rect x="24" y="20" width="16" height="24" rx="4" fill="url(#ksSvcRepair)"/><path d="M43 14l8 8-4 4-8-8 4-4z" fill="#fff7ea" stroke="#163a6d" stroke-width="2.4"/><path d="M15 47l9-9 4 4-9 9-4-4z" fill="#fff7ea" stroke="#163a6d" stroke-width="2.4"/></svg>';
            case 'insurance':
                return '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="ksSvcInsurance" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#e0b76d"/><stop offset="100%" stop-color="#b98633"/></linearGradient></defs><rect x="16" y="10" width="32" height="44" rx="6" fill="#fff7ea" stroke="#163a6d" stroke-width="3"/><path d="M24 22h16M24 30h16M24 38h10" fill="none" stroke="#163a6d" stroke-width="3" stroke-linecap="round"/><circle cx="43" cy="43" r="9" fill="url(#ksSvcInsurance)"/><path d="M39 43l3 3 6-7" fill="none" stroke="#fff" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
            case 'recovery':
                return '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="ksSvcRecovery" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#e0b76d"/><stop offset="100%" stop-color="#b98633"/></linearGradient></defs><path d="M14 18h36a4 4 0 0 1 4 4v24a4 4 0 0 1-4 4H14a4 4 0 0 1-4-4V22a4 4 0 0 1 4-4z" fill="#fff7ea" stroke="#163a6d" stroke-width="3"/><path d="M20 38l8-8 6 6 10-12" fill="none" stroke="url(#ksSvcRecovery)" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M18 46h28" fill="none" stroke="#163a6d" stroke-width="3" stroke-linecap="round"/></svg>';
            case 'waterproof':
            default:
                return '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="ksSvcWaterproof" x1="0" y1="0" x2="1" y2="1"><stop offset="0%" stop-color="#e0b76d"/><stop offset="100%" stop-color="#b98633"/></linearGradient></defs><path d="M32 11c8.3 9.4 15 16.5 15 25a15 15 0 1 1-30 0c0-8.5 6.7-15.6 15-25z" fill="url(#ksSvcWaterproof)"/><path d="M32 19c-4.7 5.6-8 10-8 15a8 8 0 1 0 16 0c0-5-3.3-9.4-8-15z" fill="#fff7ea"/><path d="M15 49c5.2-4 11-6 17-6 6.3 0 12.1 2 17 6" fill="none" stroke="#163a6d" stroke-width="3.4" stroke-linecap="round"/></svg>';
        }
    }
}

$knu_online_link = isset($knu_brand['online_link']) && $knu_brand['online_link'] ? $knu_brand['online_link'] : G5_URL.'/content/online.php';

$services = array(
    array(
        'icon'  => 'detect',
        'title' => '누수탐지',
        'tag'   => '정밀 장비 진단',
        'desc'  => '보이지 않는 누수 지점을 장비 기반으로 정확하게 탐지해 원인 파악의 시간을 줄입니다.',
        'img'   => G5_THEME_URL.'/img/service-detect.jpg'
    ),
    array(
        'icon'  => 'repair',
        'title' => '누수공사',
        'tag'   => '원인 구간 보수',
        'desc'  => '원인 배관 보수와 교체 공사를 통해 재누수 가능성을 낮추고 문제를 근본적으로 해결합니다.',
        'img'   => G5_THEME_URL.'/img/service-repair.jpg'
    ),
    array(
        'icon'  => 'insurance',
        'title' => '누수보험처리',
        'tag'   => '서류·증빙 안내',
        'desc'  => '보험 청구 흐름과 준비해야 할 사진, 내역, 증빙 자료를 단계별로 안내해드립니다.',
        'img'   => G5_THEME_URL.'/img/service-insurance.jpg'
    ),
    array(
        'icon'  => 'recovery',
        'title' => '누수피해복구공사',
        'tag'   => '마감 복구 대응',
        'desc'  => '젖은 벽체와 천장, 손상된 마감재를 현장 상태에 맞춰 복구해 생활 불편을 줄입니다.',
        'img'   => G5_THEME_URL.'/img/service-recovery.jpg'
    ),
    array(
        'icon'  => 'waterproof',
        'title' => '방수공사',
        'tag'   => '맞춤 공법 적용',
        'desc'  => '옥상, 외벽, 베란다 등 부위별 조건에 맞는 방수 솔루션으로 재누수 예방까지 고려합니다.',
        'img'   => G5_THEME_URL.'/img/service-waterproof.jpg'
    )
);
?>

<style>
#knuService{--ks-navy:#163a6d;--ks-navy-deep:#0d2a52;--ks-gold:#d9b06b;--ks-gold-deep:#b98633;--ks-text:#182230;--ks-muted:#667085;--ks-line:#e8ebf1;--ks-bg:#fffdf8;--ks-shadow:0 18px 40px rgba(13,42,82,.12);position:relative;padding:110px 0;background:linear-gradient(180deg,#fffdf8 0%,#fff 52%,#f8fafc 100%);overflow:hidden;}#knuService:before{content:"";position:absolute;left:-120px;top:-90px;width:320px;height:320px;border-radius:50%;background:radial-gradient(circle,rgba(217,176,107,.16) 0%,rgba(217,176,107,0) 72%);}#knuService:after{content:"";position:absolute;right:-150px;bottom:-140px;width:360px;height:360px;border-radius:50%;background:radial-gradient(circle,rgba(22,58,109,.08) 0%,rgba(22,58,109,0) 72%);}#knuService .knu-container{position:relative;z-index:1;max-width:1280px;margin:0 auto;padding:0 20px;box-sizing:border-box;}#knuService .knu-sec-head{text-align:center;max-width:840px;margin:0 auto 46px;}#knuService .knu-sec-badge{display:inline-flex;align-items:center;justify-content:center;height:38px;padding:0 16px;border-radius:999px;background:rgba(217,176,107,.13);border:1px solid rgba(217,176,107,.32);color:var(--ks-gold-deep);font-size:13px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;}#knuService .knu-sec-head h3{margin:16px 0 14px;color:var(--ks-text);font-size:clamp(32px,4vw,54px);line-height:1.08;font-weight:900;letter-spacing:-.04em;}#knuService .knu-sec-head p{margin:0;color:var(--ks-muted);font-size:clamp(15px,1.45vw,19px);line-height:1.8;word-break:keep-all;}#knuService .knu-service-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:24px;}#knuService .knu-service-item{position:relative;display:flex;flex-direction:column;border:1px solid var(--ks-line);border-radius:28px;background:rgba(255,255,255,.98);overflow:hidden;box-shadow:0 10px 28px rgba(16,24,40,.05);transition:transform .34s ease,box-shadow .34s ease,border-color .34s ease;}#knuService .knu-service-item:before{content:"";position:absolute;left:0;bottom:0;width:100%;height:4px;background:linear-gradient(90deg,var(--ks-gold) 0%,rgba(217,176,107,0) 100%);transform:scaleX(.22);transform-origin:left center;transition:transform .34s ease;}#knuService .knu-service-item:hover,#knuService .knu-service-item:focus-within{transform:translateY(-10px);border-color:rgba(217,176,107,.72);box-shadow:var(--ks-shadow);}#knuService .knu-service-item:hover:before,#knuService .knu-service-item:focus-within:before{transform:scaleX(1);}#knuService .knu-service-media{position:relative;height:238px;overflow:hidden;background:#eef2f7;}#knuService .knu-service-media:after{content:"";position:absolute;inset:0;background:linear-gradient(180deg,rgba(10,18,34,0) 20%,rgba(10,18,34,.14) 65%,rgba(10,18,34,.52) 100%);}#knuService .knu-service-media img{display:block;width:100%;height:100%;object-fit:cover;transition:transform .55s ease,filter .35s ease;}#knuService .knu-service-item:hover .knu-service-media img,#knuService .knu-service-item:focus-within .knu-service-media img{transform:scale(1.06);filter:saturate(1.03);}#knuService .knu-service-icon{position:absolute;left:18px;bottom:18px;z-index:2;display:inline-flex;align-items:center;justify-content:center;width:62px;height:62px;border-radius:18px;background:rgba(255,255,255,.96);border:1px solid rgba(217,176,107,.38);box-shadow:0 12px 24px rgba(13,42,82,.12);transition:transform .34s ease,box-shadow .34s ease;}#knuService .knu-service-item:hover .knu-service-icon,#knuService .knu-service-item:focus-within .knu-service-icon{transform:translateY(-3px) scale(1.08);box-shadow:0 18px 28px rgba(13,42,82,.16);}#knuService .knu-service-icon svg{display:block;width:34px;height:34px;}#knuService .knu-service-no{position:absolute;right:18px;top:18px;z-index:2;display:inline-flex;align-items:center;justify-content:center;min-width:54px;height:34px;padding:0 12px;border-radius:999px;background:rgba(255,255,255,.88);backdrop-filter:blur(8px);border:1px solid rgba(255,255,255,.65);color:var(--ks-navy);font-size:12px;font-weight:900;letter-spacing:.08em;}#knuService .knu-service-body{display:flex;flex-direction:column;flex:1 1 auto;padding:24px 24px 24px;}#knuService .knu-service-tag{display:inline-flex;align-items:center;align-self:flex-start;height:31px;padding:0 12px;margin:0 0 14px;border-radius:999px;background:rgba(22,58,109,.06);color:var(--ks-navy);font-size:12px;font-weight:800;letter-spacing:-.01em;}#knuService .knu-service-body h4{margin:0 0 12px;color:var(--ks-text);font-size:28px;line-height:1.22;font-weight:900;letter-spacing:-.04em;word-break:keep-all;}#knuService .knu-service-body p{margin:0 0 20px;color:var(--ks-muted);font-size:16px;line-height:1.8;word-break:keep-all;}#knuService .knu-service-actions{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:auto;padding-top:8px;}#knuService .knu-service-more{display:inline-flex;align-items:center;gap:8px;color:var(--ks-navy);font-size:14px;font-weight:800;letter-spacing:-.01em;}#knuService .knu-service-more svg{width:18px;height:18px;transition:transform .28s ease;}#knuService .knu-service-item:hover .knu-service-more svg,#knuService .knu-service-item:focus-within .knu-service-more svg{transform:translateX(4px);}

#knuService .knu-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 48px;
    padding: 0 18px;
    border-radius: 999px;
    background: linear-gradient(135deg, #66e109 0%, #5f6b7b 100%);
    color: #fff;
    text-decoration: none;
    font-size: 14px;
    font-weight: 800;
    letter-spacing: -.01em;
    box-shadow: 0 10px 20px rgba(13, 42, 82, .16);
    transition: transform .28s ease, box-shadow .28s ease, background .28s ease;
}



#knuService .knu-btn:hover{transform:translateY(-2px);box-shadow:0 14px 26px rgba(13,42,82,.22);background:linear-gradient(135deg,#20457b 0%,#0d2a52 100%);}#knuService .knu-btn:focus{outline:none;}@media (min-width:1400px){#knuService .knu-service-grid{grid-template-columns:repeat(5,minmax(0,1fr));}#knuService .knu-service-body h4{font-size:24px;}#knuService .knu-service-body p{font-size:15px;}}@media (max-width:1199px){#knuService{padding:96px 0;}#knuService .knu-service-grid{grid-template-columns:repeat(2,minmax(0,1fr));}#knuService .knu-service-media{height:230px;}}@media (max-width:767px){#knuService{padding:78px 0;}#knuService .knu-container{padding:0 16px;}#knuService .knu-sec-head{margin-bottom:34px;}#knuService .knu-sec-badge{height:34px;padding:0 13px;font-size:11px;letter-spacing:.12em;}#knuService .knu-sec-head h3{margin:12px 0 12px;line-height:1.14;}#knuService .knu-sec-head p{font-size:15px;line-height:1.72;}#knuService .knu-service-grid{grid-template-columns:1fr;gap:16px;}#knuService .knu-service-item{border-radius:22px;}#knuService .knu-service-media{height:214px;}#knuService .knu-service-icon{left:14px;bottom:14px;width:56px;height:56px;border-radius:16px;}#knuService .knu-service-icon svg{width:30px;height:30px;}#knuService .knu-service-no{right:14px;top:14px;min-width:48px;height:30px;padding:0 10px;font-size:11px;}#knuService .knu-service-body{padding:20px 18px 20px;}#knuService .knu-service-tag{height:29px;padding:0 10px;margin-bottom:12px;font-size:11px;}#knuService .knu-service-body h4{margin-bottom:10px;font-size:24px;}#knuService .knu-service-body p{margin-bottom:18px;font-size:15px;line-height:1.72;}#knuService .knu-service-actions{flex-direction:column;align-items:flex-start;gap:10px;}#knuService .knu-btn{width:100%;height:46px;}}@media (hover:none){#knuService .knu-service-item:active{transform:translateY(-4px);box-shadow:0 14px 28px rgba(13,42,82,.1);}}
</style>

<section class="knu-section" id="knuService">
    <div class="knu-container">
        <div class="knu-sec-head" data-knu-fade="data-knu-fade">
            <span class="knu-sec-badge">03.PROFESSIONAL SERVICE</span>
            <h3>누수 진단부터 공사, 보험, 복구까지<br>한 팀으로 일관되게 진행합니다</h3>
            <p>서비스별 현장 특성과 고객 상황을 함께 고려해 빠르게 진단하고, 필요한 공정만 정확하게 진행합니다.</p>
        </div>

        <div class="knu-service-grid">
            <?php foreach ($services as $idx => $item) { ?>
            <article class="knu-service-item" data-knu-fade="data-knu-fade">
                <div class="knu-service-media">
                    <img src="<?php echo $item['img']; ?>" alt="<?php echo $item['title']; ?>">
                    <span class="knu-service-icon"><?php echo knu_service_icon_svg($item['icon']); ?></span>
                    <span class="knu-service-no">0<?php echo $idx + 1; ?></span>
                </div>

                <div class="knu-service-body">
                    <span class="knu-service-tag"><?php echo $item['tag']; ?></span>
                    <h4><?php echo $item['title']; ?></h4>
                    <p><?php echo $item['desc']; ?></p>

                    <div class="knu-service-actions">
                        <span class="knu-service-more">
                            서비스 안내
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 5l7 7-7 7" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </span>
                        <a href="<?php echo $knu_online_link; ?>" class="knu-btn">상담 바로가기</a>
                    </div>
                </div>
            </article>
            <?php } ?>
        </div>
    </div>
</section>
