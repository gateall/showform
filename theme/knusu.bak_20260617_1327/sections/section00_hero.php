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
min-height:clamp(400px,50vh,540px);
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
min-height:380px;
margin:0 auto;
padding:40px 64px;
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
#knuTop .knu-hero-slide{min-height:360px;height:360px;}

#knuTop .knu-hero-copy{width:calc(100% - 24px);min-height:auto;height:320px;padding:26px 18px 80px;border-radius:20px;display:flex;align-items:flex-start;justify-content:center;overflow:hidden;background:linear-gradient(180deg,rgba(0,35,38,.88),rgba(0,55,50,.58));}

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
#knuTop .knu-hero-slide{min-height:320px;height:320px;}

#knuTop .knu-hero-copy{height:300px;padding:24px 16px 80px;}
#knuTop .knu-hero-text h2{font-size:clamp(20px,3vw,28px)!important;margin-bottom:8px}
#knuTop .knu-hero-text p{font-size:13px;line-height:1.3;margin-bottom:12px;}
#knuTop .knu-btn{height:38px;padding:0 13px;font-size:12.5px;}
#knuTop .knu-hero-person{width:min(70vw,240px);bottom:-20px;}
}


@media (max-width:360px){
#knuTop .knu-hero-swiper,
#knuTop .knu-hero-slide{min-height:300px;height:300px;}
#knuTop .knu-hero-copy{height:280px;padding:22px 14px 70px;}
#knuTop .knu-hero-text p{font-size:12px;line-height:1.3;}

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
                <div class="knu-hero-slide" style="background-image:url('<?php echo G5_THEME_URL; ?>/img/hero1.png');">
                    <div class="knu-hero-overlay"></div>
                    <div class="knu-container knu-hero-content" data-knu-fade="data-knu-fade">
                        <div class="knu-hero-copy">
                            <div class="knu-hero-person" aria-hidden="true">
                                <picture>
                                    <source media="(max-width: 768px)" srcset="/theme/knusu/img/photo01.png">
                                    <img src="/theme/knusu/img/photo01.png" alt="이사 견적 상담 이미지">
                                </picture>
                            </div>
                            
                            <div class="knu-hero-text">
                                <p class="knu-eyebrow">인터넷2424</p>
                                <h2 class="pc-title">내 짐처럼 소중하게<br>완벽한 포장이사 서비스</h2>
                                <h2 class="mo-title">내 짐처럼 소중하게<br>완벽한 포장이사</h2>
                                <p>숙련된 전문가의 손길로 포장부터 정리정돈까지 완벽하게 책임지는 프리미엄 이사</p>
                                <div class="knu-hero-cta">
                                    <a href="<?php echo $knu_brand['phone_link']; ?>" class="knu-btn knu-btn-gold"><i class="fa-solid fa-phone-volume"></i> 견적문의</a>
                                    <a href="<?php echo $knu_brand['online_link']; ?>" class="knu-btn knu-btn-glass">온라인상담</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="knu-hero-slide" style="background-image:url('<?php echo G5_THEME_URL; ?>/img/hero2.png');">
                    <div class="knu-hero-overlay"></div>
                    <div class="knu-container knu-hero-content" data-knu-fade="data-knu-fade">
                        <div class="knu-hero-copy">
                            <div class="knu-hero-person" aria-hidden="true">
                                <picture>
                                    <source media="(max-width: 768px)" srcset="/theme/knusu/img/photo01.png">
                                    <img src="/theme/knusu/img/photo01.png" alt="이사 견적 상담 이미지">
                                </picture>
                            </div>
                            
                            <div class="knu-hero-text">
                                <p class="knu-eyebrow">안전운송</p>
                                <h2 class="pc-title">다년간의 노하우로 완성된<br>체계적인 이사 시스템</h2>
                                <h2 class="mo-title">체계적인<br>이사 시스템</h2>
                                <p>정확한 물량 파악과 체계적인 계획으로 파손 없이 안전하고 신속하게 운송합니다.</p>
                                <div class="knu-hero-cta">
                                    <a href="<?php echo $knu_brand['gallery_link']; ?>" class="knu-btn knu-btn-gold">이사 현장 갤러리</a>
                                    <a href="tel:<?php echo $knu_brand['phone']; ?>" class="knu-btn knu-btn-glass">실시간 이사 문의</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="swiper-slide">
                <div class="knu-hero-slide" style="background-image:url('<?php echo G5_THEME_URL; ?>/img/hero3.png');">
                    <div class="knu-hero-overlay"></div>
                    <div class="knu-container knu-hero-content" data-knu-fade="data-knu-fade">
                        <div class="knu-hero-copy">
                            <div class="knu-hero-person" aria-hidden="true">
                                <picture>
                                    <source media="(max-width: 768px)" srcset="/theme/knusu/img/photo01.png">
                                    <img src="/theme/knusu/img/photo01.png" alt="이사 견적 상담 이미지">
                                </picture>
                            </div>
                            
                            <div class="knu-hero-text">
                                <p class="knu-eyebrow">책임서비스</p>
                                <h2 class="pc-title">처음부터 끝까지<br>믿을 수 있는 책임 이사</h2>
                                <h2 class="mo-title">처음부터 끝까지<br>책임 이사</h2>
                                <p>합리적인 비용과 친절한 서비스로 고객님의 새로운 시작을 기분 좋게 도와드립니다.</p>
                                <div class="knu-hero-cta">
                                    <a href="<?php echo $knu_brand['online_link']; ?>" class="knu-btn knu-btn-gold">온라인 견적신청</a>
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