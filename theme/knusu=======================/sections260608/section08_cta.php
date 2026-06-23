<?php
if (!defined('_GNUBOARD_')) exit;

$phone_num = isset($knu_brand['phone']) ? $knu_brand['phone'] : '010-2169-8148';
$phone_link = isset($knu_brand['phone_link']) ? $knu_brand['phone_link'] : 'tel:01021698148';
$online_link = isset($knu_brand['online_link']) ? $knu_brand['online_link'] : '/content/online.php';
?>

<style>#knuCtaFinal{position:relative;padding:120px 0;background:linear-gradient(135deg,#1c8646 0%,#38b870 50%,#5cd18f 100%);overflow:hidden;color:#fff;text-align:center;}

/* 🔥 배경 글로우 강화 (핵심 수정) */
#knuCtaFinal::before{
content:"";position:absolute;left:-120px;top:-120px;width:420px;height:420px;
background:radial-gradient(circle,rgba(92,209,143,.35) 0%,rgba(28,134,70,.25) 40%,rgba(255,255,255,0) 70%);
z-index:1;pointer-events:none;
}

#knuCtaFinal::after{
content:"";position:absolute;right:-80px;bottom:-80px;width:320px;height:320px;
background:radial-gradient(circle,rgba(28,134,70,.45) 0%,rgba(92,209,143,.25) 40%,rgba(255,255,255,0) 70%);
z-index:1;pointer-events:none;
}

#knuCtaFinal .knu-cta-wrap{position:relative;z-index:10;}

#knuCtaFinal .knu-badge{
display:inline-block;margin-bottom:20px;padding:6px 16px;
background:rgba(255,255,255,.1);
border:1px solid rgba(255,255,255,.2);
color:#edda7d;border-radius:999px;
font-size:13px;font-weight:800;letter-spacing:.12em;
}

#knuCtaFinal h3{font-size:clamp(32px,5vw,56px);font-weight:900;color:#fff;margin-bottom:20px;letter-spacing:-.03em;line-height:1.2;}

#knuCtaFinal p{font-size:19px;color:rgba(255,255,255,.85);max-width:750px;margin:0 auto 46px;line-height:1.7;font-weight:500;}

#knuCtaFinal .knu-cta-tel-box{margin-bottom:60px;}

/* 🔥 전화번호 강조 수정 */
#knuCtaFinal .cta-tel{
display:inline-block;
font-size:clamp(48px,8vw,92px);
font-weight:900;
color:#000;
text-decoration:none;
line-height:1;
letter-spacing:-.02em;
transition:all .4s ease;
text-shadow:0 4px 20px rgba(0,0,0,.25);
}

#knuCtaFinal .cta-tel:hover{
color:#fff;
text-shadow:0 0 40px rgba(92,209,143,.6);
transform:scale(1.03);
}

#knuCtaFinal .knu-cta-note{
display:block;margin-top:18px;font-size:15px;font-weight:700;
color:rgba(255,255,255,.7);letter-spacing:.05em;
}

#knuCtaFinal .knu-cta-actions{
display:flex;gap:20px;justify-content:center;align-items:center;
}

/* 버튼 */
#knuCtaFinal .knu-btn-call{
display:inline-flex;align-items:center;gap:12px;
background:#edda7d;color:#0a182b !important;
padding:18px 42px;border-radius:999px;
text-decoration:none;font-size:18px;font-weight:900;
transition:.3s;box-shadow:0 10px 30px rgba(0,0,0,.2);
}

#knuCtaFinal .knu-btn-call:hover{
background:#fff;transform:translateY(-4px);
box-shadow:0 15px 40px rgba(92,209,143,.35);
}

#knuCtaFinal .knu-btn-inquiry{
display:inline-flex;align-items:center;gap:12px;
background:rgba(50,10,255,.5);color:#fff !important;


padding:18px 42px;border-radius:999px;
border:1px solid rgba(255,255,255,.2);
backdrop-filter:blur(8px);
font-size:18px;font-weight:800;
transition:.3s;
}

#knuCtaFinal .knu-btn-inquiry:hover{
background:rgba(255,255,255,.2);
border-color:#5cd18f;
transform:translateY(-4px);
}

/* 🔥 카드 디자인 고급화 */
#knuCtaFinal .knu-cta-proof{
display:grid;grid-template-columns:repeat(2,1fr);
gap:24px;max-width:900px;margin:0 auto 60px;
}

#knuCtaFinal .knu-proof-card{
display:flex;align-items:center;justify-content:space-between;
padding:30px;
background:rgba(20,40,30,.65);
border:1px solid rgba(0,0,0,.8);
border-radius:24px;
backdrop-filter:blur(12px);
transition:.4s cubic-bezier(.165,.84,.44,1);
text-align:left;overflow:hidden;position:relative;
}

#knuCtaFinal .knu-proof-card::before{
content:"";position:absolute;inset:0;
background:linear-gradient(135deg,rgba(92,209,143,.12) 0%,transparent 100%);
opacity:0;transition:.4s;
}

#knuCtaFinal .knu-proof-card:hover{
transform:translateY(-10px);
background:rgba(20,50,35,.8);
border-color:rgba(92,209,143,.5);
box-shadow:0 20px 40px rgba(0,0,0,.4),0 0 25px rgba(92,209,143,.25);
}

#knuCtaFinal .knu-proof-card:hover::before{opacity:1;}

#knuCtaFinal .knu-proof-card h4{font-size:20px;font-weight:800;color:#fff;margin-bottom:6px;}
#knuCtaFinal .knu-proof-card p{font-size:14px;color:rgba(255,255,255,.7);margin:0;}

#knuCtaFinal .knu-proof-card .card-img{
width:100px;height:100px;flex-shrink:0;margin-left:20px;
transition:.4s;
}

#knuCtaFinal .knu-proof-card:hover .card-img{
transform:scale(1.1) rotate(3deg);
}

/* 모바일 */
@media(max-width:767px){
#knuCtaFinal{padding:90px 0;}
#knuCtaFinal h3{font-size:32px;}
#knuCtaFinal p{font-size:16px;margin-bottom:34px;}
#knuCtaFinal .cta-tel{font-size:48px;}
#knuCtaFinal .knu-cta-actions{flex-direction:column;width:100%;max-width:320px;margin:0 auto;}
#knuCtaFinal .knu-cta-actions a{width:100%;justify-content:center;}
#knuCtaFinal .knu-cta-proof{grid-template-columns:1fr;margin-bottom:40px;}
}
#knuCtaFinal .knu-proof-card .card-img{display:block !important;width:100px;height:100px;min-width:100px;flex:0 0 100px;margin-left:20px;position:relative;z-index:3;overflow:visible;}
#knuCtaFinal .knu-proof-card .card-img img{display:block !important;width:100% !important;height:100% !important;object-fit:contain;opacity:1 !important;visibility:visible !important;max-width:100%;}

@media(max-width:767px){
#knuCtaFinal .knu-proof-card{display:flex;align-items:center;gap:16px;padding:22px;}
#knuCtaFinal .knu-proof-card .card-text{flex:1;min-width:0;}
#knuCtaFinal .knu-proof-card .card-img{width:78px;height:78px;min-width:78px;flex:0 0 78px;margin-left:0;}
#knuCtaFinal .knu-proof-card .card-img img{width:78px !important;height:78px !important;object-fit:contain;}
}

@media(max-width:420px){
#knuCtaFinal .knu-proof-card{padding:18px;gap:12px;}
#knuCtaFinal .knu-proof-card .card-img{width:64px;height:64px;min-width:64px;flex-basis:64px;}
#knuCtaFinal .knu-proof-card .card-img img{width:64px !important;height:64px !important;}
}


@media(max-width:768px){
#knuCtaFinal{padding:70px 16px;}
#knuCtaFinal h3{font-size:28px;line-height:1.3;}
#knuCtaFinal p{font-size:15px;line-height:1.6;}
#knuCtaFinal .cta-tel{display:block;font-size:42px;line-height:1.1;margin-bottom:10px;}
#knuCtaFinal .knu-cta-actions{display:flex;flex-direction:column;gap:14px;width:100%;max-width:360px;margin:0 auto;}
#knuCtaFinal .knu-cta-actions a{display:flex;align-items:center;justify-content:center;width:100%;height:58px;border-radius:14px;font-size:16px;font-weight:900;letter-spacing:-0.01em;}
#knuCtaFinal .knu-btn-call{background:#edda7d;color:#0a182b!important;box-shadow:0 8px 18px rgba(0,0,0,.18);}
#knuCtaFinal .knu-btn-inquiry{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.25);backdrop-filter:blur(6px);}
#knuCtaFinal .knu-btn-call:active{transform:scale(.97);opacity:.9;}
#knuCtaFinal .knu-btn-inquiry:active{transform:scale(.97);opacity:.9;}
#knuCtaFinal .knu-cta-actions svg{width:18px;height:18px;flex-shrink:0;}
}

/* hover 제거 (모바일 UX 안정화 핵심) */
@media(hover:none){
#knuCtaFinal .knu-btn-call:hover{background:#edda7d;transform:none;}
#knuCtaFinal .knu-btn-inquiry:hover{background:rgba(255,255,255,.12);transform:none;}
}  

#knuCtaFinal .knu-btn-call{min-width:200px;justify-content:center;white-space:nowrap;flex-shrink:0;}
#knuCtaFinal .knu-btn-inquiry{min-width:220px;justify-content:center;white-space:nowrap;flex-shrink:0;text-decoration:none;}
#knuCtaFinal .knu-cta-actions svg{width:24px;height:24px;flex-shrink:0;}

@media(max-width:768px){
#knuCtaFinal .knu-btn-call{min-width:0;width:100%;white-space:nowrap;}
#knuCtaFinal .knu-btn-inquiry{min-width:0;width:100%;white-space:nowrap;}
}

</style>

<section class="knu-section" id="knuCtaFinal">
    <div class="knu-container knu-cta-wrap" data-knu-fade="data-knu-fade">
        <span class="knu-badge">08.QUICK CONTACT</span>
        <h3>누수 문제, 미루지 말고 지금 상담하세요</h3>
        <p>전화 상담부터 방문 일정 안내까지 빠르게 도와드립니다.<br>전화상담과 온라인접수 모두 가능합니다.</p>

        <div class="knu-cta-tel-box">
            <a href="<?php echo $phone_link; ?>" class="cta-tel"><?php echo $phone_num; ?></a>
            <span class="knu-cta-note">연중무휴 · 긴급 출동 · 즉시 대응</span>
        </div>

        <div class="knu-cta-proof">
            <div class="knu-proof-card">
                <div class="card-text">
                    <span class="card-badge">CERTIFIED</span>
                    <h4>배관 용접 방수 국가기술자격증 보유</h4>
                    <p>정확한 탐지와 성실한 시공을 약속합니다</p>
                </div>
                <div class="card-img">
                    <img src="<?php echo G5_THEME_URL; ?>/img/single-img.png" alt="인증마크">
                </div>
            </div>
            <div class="knu-proof-card">
                <div class="card-text">
                    <span class="card-badge">AWARDS</span>
                    <h4>전국 기능 경기대회(배관) 3등</h4>
                    <p>최다 상장 및 수상 기록이 증명합니다</p>
                </div>
                <div class="card-img">
                    <img src="<?php echo G5_THEME_URL; ?>/img/single-img_01.png" alt="상장 이미지">
                </div>
            </div>
        </div>

        <div class="knu-cta-actions">
            <a href="<?php echo $phone_link; ?>" class="knu-btn-call">
                <svg viewBox="0 0 24 24" fill="currentColor"><path d="M6.62 10.79c1.44 2.81 3.76 5.14 6.59 6.59l2.2-2.2c.27-.27.67-.36 1.02-.24 1.12.37 2.33.57 3.57.57.55 0 1 .45 1 1V20c0 .55-.45 1-1 1-9.39 0-17-7.61-17-17 0-.55.45-1 1-1h3.5c.55 0 1 .45 1 1 0 1.25.2 2.45.57 3.57.11.35.03.74-.25 1.02l-2.2 2.2z"/></svg>
                전화상담 연결
            </a>
            <a href="<?php echo $online_link; ?>" class="knu-btn-inquiry">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                온라인상담 접수
            </a>
        </div>
    </div>
</section>
