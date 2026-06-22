<?php
if (!defined('_GNUBOARD_')) exit;

$vdo_id = '0ak9oqrR25Y'; // 요청하신 대표 영상 ID
$ch_link = 'https://www.youtube.com/channel/UCWhd1CR-VCcs8wn4sd9aJ-g';
?>

<style>
/* Section Styles: YouTube Focus */
#knuYoutube { 
    position: relative; 
    padding: 100px 0; 
    background: linear-gradient(180deg, #0a182b 0%, #0d2a52 100%); 
    overflow: hidden;
    color: #fff;
}

/* Background Decor */
#knuYoutube::before {
    content: ""; position: absolute; left: -10%; top: -10%; width: 40%; height: 60%;
    background: radial-gradient(circle, rgba(215, 176, 107, 0.08) 0%, rgba(10, 24, 43, 0) 70%);
    z-index: 1; pointer-events: none;
}

#knuYoutube .knu-sec-head { margin-bottom: 54px; text-align: center; position: relative; z-index: 5; }
#knuYoutube .knu-badge { 
    display: inline-block; margin-bottom: 16px; padding: 6px 14px; 
    background: rgba(215, 176, 107, 0.2); border: 1px solid rgba(215, 176, 107, 0.4);
    color: #edda7d; border-radius: 4px; font-size: 13px; font-weight: 800; letter-spacing: 0.1em; 
}
#knuYoutube .knu-sec-head h3 { font-size: clamp(30px, 4.5vw, 48px); font-weight: 900; color: #fff; margin-bottom: 18px; letter-spacing: -0.03em; }
#knuYoutube .knu-sec-head p { font-size: 18px; color: rgba(255,255,255,0.7); max-width: 650px; margin: 0 auto; line-height: 1.6; }

/* Video Interaction Card */
#knuYoutube .knu-vdo-container { 
    position: relative; max-width: 1000px; margin: 0 auto; z-index: 10;
}
#knuYoutube .knu-vdo-card {
    position: relative; width: 100%; aspect-ratio: 16 / 9;
    border-radius: 20px; overflow: hidden; background: #000;
    box-shadow: 0 30px 60px rgba(0,0,0,0.5), 0 0 0 1px rgba(255,255,255,0.1);
    cursor: pointer; transition: transform 0.4s ease, box-shadow 0.4s ease;
}
#knuYoutube .knu-vdo-card:hover { transform: scale(1.02); box-shadow: 0 40px 80px rgba(0,0,0,0.6); }

/* Thumbnail Overlay */
#knuYoutube .knu-vdo-poster {
    position: absolute; inset: 0; 
    background: url('<?php echo G5_THEME_URL; ?>/img/youtube-main-thumb.jpg') center/cover no-repeat;
    transition: transform 0.6s ease, filter 0.4s ease;
}
@media (max-width: 767px) {
    #knuYoutube .knu-vdo-poster { background-image: url('<?php echo G5_THEME_URL; ?>/img/youtube-main-thumb-mobile.jpg'); }
    #knuYoutube .knu-vdo-card { aspect-ratio: 4 / 5; } /* 모바일에서는 세로형 대응 */
}
#knuYoutube .knu-vdo-card:hover .knu-vdo-poster { transform: scale(1.06); filter: brightness(0.85); }

/* Play Button Decor */
#knuYoutube .knu-vdo-overlay {
    position: absolute; inset: 0; background: rgba(0,0,0,0.25);
    display: flex; align-items: center; justify-content: center;
    transition: background 0.4s ease; z-index: 2;
}
#knuYoutube .knu-vdo-card:hover .knu-vdo-overlay { background: rgba(0,0,0,0.4); }

#knuYoutube .knu-play-btn {
    width: 84px; height: 84px; background: #fff; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.4), 0 0 0 8px rgba(255,255,255,0.2);
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}
#knuYoutube .knu-play-btn svg { width: 32px; height: 32px; fill: #db2b2b; margin-left: 4px; }
#knuYoutube .knu-vdo-card:hover .knu-play-btn { transform: scale(1.15); box-shadow: 0 15px 40px rgba(0,0,0,0.5), 0 0 0 12px rgba(255,255,255,0.3); }

/* YouTube Logo Badge */
#knuYoutube .knu-yt-badge {
    position: absolute; top: 24px; left: 24px;
    background: rgba(0,0,0,0.6); backdrop-filter: blur(8px);
    padding: 8px 16px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.2);
    display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 700;
}
#knuYoutube .knu-yt-badge svg { width: 24px; height: 18px; fill: #fff; }

/* Bottom Content */
#knuYoutube .knu-vdo-footer { margin-top: 42px; text-align: center; }
#knuYoutube .knu-vdo-footer p { font-size: 19px; font-weight: 700; color: #edda7d; margin-bottom: 24px; }
#knuYoutube .knu-channel-btn {
    display: inline-flex; align-items: center; gap: 12px;
    background: #fff; color: #0a182b; font-weight: 800;
    padding: 16px 36px; border-radius: 999px; text-decoration: none;
    transition: all 0.3s ease; box-shadow: 0 10px 24px rgba(0,0,0,0.2);
}
#knuYoutube .knu-channel-btn svg { width: 24px; height: 24px; fill: #db2b2b; }
#knuYoutube .knu-channel-btn:hover { background: #edda7d; transform: translateY(-3px); box-shadow: 0 15px 32px rgba(0,0,0,0.3); }

/* Iframe Layer */
#knuYoutube #knuVdoIframe { 
    position: absolute; inset: 0; width: 100%; height: 100%; border: 0; z-index: 5;
    background: #000; display: none;
}
</style>

<section class="knu-section" id="knuYoutube" data-knu-fade>
    <div class="knu-container">
        <!-- Section Header -->
        <div class="knu-sec-head">
            <span class="knu-badge">04.REAL WORK VIDEO</span>
            <h3>실제 현장 작업 영상</h3>
            <p>누수 원인 진단부터 공사 흐름까지,<br>코리아누수의 정밀한 작업 과정을 실제 영상으로 확인해보세요.</p>
        </div>

        <!-- Video Card -->
        <div class="knu-vdo-container">
            <div class="knu-vdo-card" id="knuVdoTrigger" onclick="knuLoadVideo('<?php echo $vdo_id; ?>')">
                <div class="knu-vdo-poster"></div>
                <div class="knu-vdo-overlay">
                    <div class="knu-play-btn">
                        <svg viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                    </div>
                </div>
                <div class="knu-yt-badge">
                    <svg viewBox="0 0 576 512"><path d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.503 48.284 47.824C117.219 448 288 448 288 448s170.781 0 213.371-11.486c23.497-6.321 42.003-24.174 48.284-47.824 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.201-142.739 81.205z"/></svg>
                    YOUTUBE
                </div>
                <iframe id="knuVdoIframe" src="" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>

            <!-- Footer -->
            <div class="knu-vdo-footer">
                <p>실제 작업 현장과 더 많은 시공 사례를 확인하세요</p>
                <a href="<?php echo $ch_link; ?>" target="_blank" rel="noopener" class="knu-channel-btn">
                    <svg viewBox="0 0 576 512"><path d="M549.655 124.083c-6.281-23.65-24.787-42.276-48.284-48.597C458.781 64 288 64 288 64S117.22 64 74.629 75.486c-23.497 6.322-42.003 24.947-48.284 48.597-11.412 42.867-11.412 132.305-11.412 132.305s0 89.438 11.412 132.305c6.281 23.65 24.787 41.503 48.284 47.824C117.219 448 288 448 288 448s170.781 0 213.371-11.486c23.497-6.321 42.003-24.174 48.284-47.824 11.412-42.867 11.412-132.305 11.412-132.305s0-89.438-11.412-132.305zm-317.51 213.508V175.185l142.739 81.201-142.739 81.205z"/></svg>
                    공식 유튜브 채널 바로가기
                </a>
            </div>
        </div>
    </div>
</section>

<script>
function knuLoadVideo(vdoId) {
    const iframe = document.getElementById('knuVdoIframe');
    const trigger = document.getElementById('knuVdoTrigger');
    if (!iframe || !vdoId) return;
    
    // iframe src 설정 및 노출
    iframe.src = 'https://www.youtube.com/embed/' + vdoId + '?autoplay=1&rel=0';
    iframe.style.display = 'block';
    
    // 포스터 및 오버레이 숨김 처리 (선택사항, iframe이 z-index로 덮음)
}
</script>
