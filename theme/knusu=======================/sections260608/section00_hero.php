<?php
if (!defined('_GNUBOARD_')) exit;
?>


<style>

/* =========================
   HERO BASE
========================= */
#knuTop{position:relative;background:#000;}

#knuTop .knu-hero-swiper,
#knuTop .knu-hero-slide{
min-height:clamp(620px,90vh,920px);
}

#knuTop .knu-hero-slide{
position:relative;
display:flex;
align-items:center;
background-size:cover;
background-position:center;
}

#knuTop .knu-hero-overlay{
position:absolute;
inset:0;
z-index:1;
background:linear-gradient(110deg,rgba(8,24,20,.32),rgba(12,22,32,.24),rgba(5,10,18,.18));
}

#knuTop .knu-hero-content{
position:relative;
z-index:10;
color:#fff;
max-width:900px;
padding:108px 0 140px;
}

/* =========================
   HERO COPY (레이어 구조)
========================= */
#knuTop .knu-hero-copy{
position:relative;
width:min(1200px,calc(100% - 48px));
min-height:520px;
margin:0 auto;
padding:70px 64px;
overflow:hidden;
isolation:isolate;
border-radius:28px;
background:linear-gradient(100deg,rgba(0,35,38,.82),rgba(0,70,65,.48));
box-shadow:0 30px 80px rgba(0,0,0,.35);
}

/* 어두운 오버레이 */
#knuTop .knu-hero-copy::before{
content:"";
position:absolute;
inset:0;
z-index:1;
background:linear-gradient(90deg,rgba(0,20,24,.86) 0%,rgba(0,40,44,.72) 42%,rgba(0,80,70,.22) 100%);
}

/* 인물 사진 레이어 */
#knuTop .knu-hero-person{
position:absolute;
right:2%;
bottom:0;
z-index:2;
width:min(42%,470px);
pointer-events:none;
}

#knuTop .knu-hero-person picture,
#knuTop .knu-hero-person img{
display:block;
width:100%;
height:auto;
object-fit:contain;
}

/* 텍스트 영역 */
#knuTop .knu-hero-text{
position:relative;
z-index:4;
width:min(560px,52%);
color:#fff;
}

/* =========================
   PC 조정
========================= */
@media(min-width:1280px){
#knuTop .knu-hero-copy{
width:min(1200px,calc(100% - 80px));
}
}

/* =========================
   모바일
========================= */
@media (max-width:768px){
#knuTop .knu-hero-swiper,
#knuTop .knu-hero-slide{min-height:520px;height:520px;}

#knuTop .knu-hero-copy{width:calc(100% - 24px);min-height:auto;height:470px;padding:26px 18px 150px;border-radius:20px;display:flex;align-items:flex-start;justify-content:center;overflow:hidden;background:linear-gradient(180deg,rgba(0,35,38,.88),rgba(0,55,50,.58));}

#knuTop .knu-hero-copy::before{background:linear-gradient(180deg,rgba(0,20,24,.9) 0%,rgba(0,35,38,.75) 48%,rgba(0,55,50,.28) 100%);}

#knuTop .knu-hero-text{width:100%;max-width:100%;text-align:center;z-index:5;}

#knuTop .knu-eyebrow{margin-bottom:8px;padding:6px 13px;font-size:12px;line-height:1.2;}

#knuTop .knu-hero-text h2{margin:0 0 10px;font-size:23px;line-height:1.22;letter-spacing:-.04em;}

#knuTop .knu-hero-text p{margin:0 auto 14px;max-width:92%;font-size:13.5px;line-height:1.5;}

#knuTop .knu-hero-cta{display:flex;justify-content:center;gap:8px;margin-top:0;flex-wrap:nowrap;}

#knuTop .knu-btn{min-width:auto;height:40px;padding:0 15px;font-size:13px;border-radius:12px;}

#knuTop .knu-hero-person{right:50%;bottom:-18px;transform:translateX(50%);width:min(68vw,260px);opacity:.55;z-index:2;}

#knuTop .pc-title{display:none;}
#knuTop .mo-title{display:block;}
}

@media (max-width:480px){
#knuTop .knu-hero-swiper,
#knuTop .knu-hero-slide{min-height:500px;height:500px;}

#knuTop .knu-hero-copy{height:450px;padding:24px 16px 135px;}
#knuTop .knu-hero-text h2{font-size:clamp(24px,3vw,36px)!important;margin-bottom:8px}
#knuTop .knu-hero-text p{font-size:13px;line-height:1;margin-bottom:12px;}
#knuTop .knu-btn{height:38px;padding:0 13px;font-size:12.5px;}
#knuTop .knu-hero-person{width:min(70vw,240px);bottom:-20px;}
}


@media (max-width:360px){
#knuTop .knu-hero-swiper,
#knuTop .knu-hero-slide{min-height:480px;height:480px;}
#knuTop .knu-hero-copy{height:430px;padding:22px 14px 120px;}
#knuTop .knu-hero-text p{font-size:12.5px;line-height:1;}

#knuTop .knu-btn{height:36px;padding:0 11px;font-size:12px;}

#knuTop .knu-hero-person{width:min(68vw,220px);bottom:-24px;}
}

/* =========================
   TEXT
========================= */
#knuTop .knu-eyebrow{
display:inline-flex;
margin-bottom:18px;
padding:9px 18px;
border:1px solid rgba(255,255,255,.32);
border-radius:999px;
background:rgba(255,255,255,.16);
color:#fff;
font-weight:700;
}

#knuTop .knu-hero-text h2{
margin:0 0 22px;
color:#fff;
font-size:clamp(38px,3.2vw,58px);
line-height:1.16;
font-weight:800;
letter-spacing:-.04em;
text-shadow:0 4px 24px rgba(0,0,0,.65);
}

#knuTop .knu-hero-text p{
color:rgba(255,255,255,.92);
font-size:18px;
line-height:1.75;
text-shadow:0 3px 16px rgba(0,0,0,.55);
}

#knuTop .pc-title{
display:block;
}

#knuTop .mo-title{
display:none;
}

#knuTop .knu-hero-cta{
margin-top:42px;
display:flex;
gap:14px;
}

/* =========================
   NAV
========================= */
#knuTop .swiper-button-prev,
#knuTop .swiper-button-next{
width:52px;
height:52px;
border-radius:50%;
border:1px solid rgba(255,255,255,.3);
background:rgba(255,255,255,.14);
color:#fff;
}

#knuTop .knu-hero-pagination{
bottom:28px;
}

/* 기본 오버레이 (1,2번 슬라이드 유지) */
#knuTop .knu-hero-overlay{
position:absolute;inset:0;z-index:1;
background:linear-gradient(110deg,rgba(8,24,20,.32),rgba(12,22,32,.24),rgba(5,10,18,.18));
}

/* ✅ 3번째 슬라이드만 완전 흰색 */
#knuTop .knu-hero-slide:nth-child(3) .knu-hero-overlay{
background:#ffffff !important;
}

</style>

<section class="knu-hero" id="knuTop">
    <div class="knu-hero-swiper swiper">
        <div class="swiper-wrapper">

            <div class="swiper-slide">
                <div class="knu-hero-slide" style="background-image:url('<?php echo G5_THEME_URL; ?>/img/hero1.jpg');">
                    <div class="knu-hero-overlay"></div>
                    <div class="knu-container knu-hero-content" data-knu-fade="data-knu-fade">
                        <div class="knu-hero-copy">
                            <div class="knu-hero-person" aria-hidden="true">
                                <picture>
                                    <source media="(max-width: 768px)" srcset="/img/photo02.png">
                                    <img src="/img/photo01.png" alt="">
                                </picture>
                            </div>
                            
                            <div class="knu-hero-text">
                                <p class="knu-eyebrow">코리아누수</p>
                                <h2 class="pc-title">대구 및 전지역 누수탐지 · 배관 · 방수 · 보험처리</h2>
                                <h2 class="mo-title">대구 및 전지역<br>누수탐지·방수</h2>
                                <p>원인부터 정확하게 찾고, 공사와 복구까지 한 번에 해결하는 코리아누수의 원스톱 서비스</p>
                                <div class="knu-hero-cta">
                                    <a href="<?php echo $knu_brand['phone_link']; ?>" class="knu-btn knu-btn-gold"><i class="fa-solid fa-phone-volume"></i> 전화상담</a>
                                    <a href="<?php echo $knu_brand['online_link']; ?>" class="knu-btn knu-btn-glass">온라인상담</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="knu-hero-slide" style="background-image:url('<?php echo G5_THEME_URL; ?>/img/hero2.jpg');">
                    <div class="knu-hero-overlay"></div>
                    <div class="knu-container knu-hero-content" data-knu-fade="data-knu-fade">
                        <div class="knu-hero-copy">
                            <div class="knu-hero-person" aria-hidden="true">
                                <picture>
                                    <source media="(max-width: 768px)" srcset="/img/photo02.png">
                                    <img src="/img/photo01.png" alt="">
                                </picture>
                            </div>
                            
                            <div class="knu-hero-text">
                                <p class="knu-eyebrow">최신기술팀</p>
                                <h2 class="pc-title">최첨단 장비와 풍부한 현장 경험으로<br>해결되지 않던 누수까지 찾아냅니다</h2>
                                <h2 class="mo-title">최첨단 장비로<br>누수탐지</h2>
                                <p>초기부터 정확한 진단과 고성능 장비를 사용하여 미세 누수 지점까지 오차 없이 찾아냅니다.</p>
                                <div class="knu-hero-cta">
                                    <a href="<?php echo $knu_brand['gallery_link']; ?>" class="knu-btn knu-btn-gold">장비 및 시공사례</a>
                                    <a href="tel:<?php echo $knu_brand['phone']; ?>" class="knu-btn knu-btn-glass">실시간 누수 문의</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="knu-hero-slide" style="background-image:url('<?php echo G5_THEME_URL; ?>/img/hero3.jpg');">
                    <div class="knu-hero-overlay"></div>
                    <div class="knu-container knu-hero-content" data-knu-fade="data-knu-fade">
                        <div class="knu-hero-copy">
                            <div class="knu-hero-person" aria-hidden="true">
                                <picture>
                                    <source media="(max-width: 768px)" srcset="/img/photo02.png">
                                    <img src="/img/photo01.png" alt="">
                                </picture>
                            </div>
                            
                            <div class="knu-hero-text">
                                <p class="knu-eyebrow">누수전문가</p>
                                <h2 class="pc-title">누수탐지부터 정확 진단<br>공사 이후까지 책임 시공</h2>
                                <h2 class="mo-title">누수탐지부터<br>책임 시공까지</h2>
                                <p>누수탐지, 누수공사, 보험처리, 피해복구공사까지 현장 상황에 맞춘 전문 솔루션을 제공합니다.</p>
                                <div class="knu-hero-cta">
                                    <a href="<?php echo $knu_brand['gallery_link']; ?>" class="knu-btn knu-btn-gold">솔루션</a>
                                    <a href="#knuCtaFinal" class="knu-btn knu-btn-glass">즉시 문의하기</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <div class="knu-hero-pagination swiper-pagination"></div>
        <div class="knu-hero-prev swiper-button-prev"></div>
        <div class="knu-hero-next swiper-button-next"></div>
        <a href="#knuStrength" class="knu-scroll-cue" aria-label="다음 섹션으로 이동"><span></span></a>
    </div>
</section>