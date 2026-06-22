<?php
if (!defined('_GNUBOARD_')) {
    include_once('../common.php');
}
include_once(G5_THEME_PATH.'/head.php');
?>

<div id="sub_content" class="location-page">
    <div class="location-inner">
        <h2 class="location-title">오시는길</h2>

        <div class="location-layout">
            <div class="location-map-box">
                <div class="location-map">
                    <!-- * 카카오맵 - 지도퍼가기 -->
                    <!-- 1. 지도 노드 -->
                    <div id="daumRoughmapContainer1776243529339" class="root_daum_roughmap root_daum_roughmap_landing"></div>
                </div>
            </div>

            <div class="location-info-box">
                <div class="location-info-item">
                    <div class="location-info-icon"><i class="fa fa-clock-o" aria-hidden="true"></i></div>
                    <div class="location-info-text">
                        <strong>운영시간</strong>
                        <p>매일 10:00 - 22:00</p>
                    </div>
                </div>

                <div class="location-info-item">
                    <div class="location-info-icon"><i class="fa fa-road" aria-hidden="true"></i></div>
                    <div class="location-info-text">
                        <strong>찾아오시는 길</strong>
                        <p><span class="location-address">대구 수성구 두산동 79-1, 3층</span></p>
                    </div>
                </div>

                <div class="location-phone">
                    <i class="fa fa-phone" aria-hidden="true"></i>
                    <span>010-2169-8148</span>
                </div>

                <div class="location-btn-wrap">
                    <a href="https://map.naver.com/p/search/%EB%8C%80%EA%B5%AC%20%EC%88%98%EC%84%B1%EA%B5%AC%20%EB%91%90%EC%82%B0%EB%8F%99%2079-1%203%EC%B8%B5" target="_blank" rel="noopener" class="location-naver-btn">
                        네이버지도 바로가기 <span>&gt;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.location-page{background:#f3f3f3;padding:70px 0 100px;}
.location-inner{max-width:1280px;margin:0 auto;padding:0 20px;}
.location-title{margin:0 0 40px;font-size:42px;font-weight:800;line-height:1.2;color:#111;letter-spacing:-0.03em;}
.location-layout{display:flex;gap:48px;align-items:center;}
.location-map-box{flex:1 1 62%;min-width:0;}
.location-info-box{flex:0 0 360px;}
.location-map{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.06);}
.location-map #daumRoughmapContainer1776243529339{width:100% !important;}
.location-map .root_daum_roughmap{width:100% !important;max-width:100% !important;}
.location-map .root_daum_roughmap .wrap_map{width:100% !important;}
.location-map .root_daum_roughmap .wrap_controllers{width:100% !important;}
.location-info-item{display:flex;align-items:flex-start;gap:16px;margin-bottom:36px;}
.location-info-icon{width:36px;flex:0 0 36px;font-size:30px;line-height:1;color:#111;text-align:center;padding-top:2px;}
.location-info-text strong{display:block;margin-bottom:10px;font-size:20px;font-weight:800;line-height:1.4;color:#111;letter-spacing:-0.02em;}
.location-info-text p{margin:0;font-size:18px;line-height:1.7;color:#666;letter-spacing:-0.02em;}
.location-address{display:inline-block;padding:2px 6px;background:#4e7fe8;color:#fff;line-height:1.5;}
.location-phone{display:flex;align-items:center;gap:14px;margin:22px 0 40px;font-size:24px;font-weight:800;line-height:1.4;color:#111;letter-spacing:-0.02em;}
.location-phone i{font-size:34px;}
.location-btn-wrap{margin-top:10px;}
.location-naver-btn{display:inline-flex;align-items:center;justify-content:center;gap:12px;min-width:230px;height:60px;padding:0 28px;background:#0b6b47;border-radius:8px;color:#fff !important;font-size:18px;font-weight:700;text-decoration:none;transition:all .2s ease;}
.location-naver-btn:hover{background:#09583b;transform:translateY(-2px);}
.location-naver-btn span{font-size:22px;line-height:1;}
@media (max-width:1024px){
.location-title{font-size:34px;}
.location-layout{flex-direction:column;align-items:stretch;gap:30px;}
.location-info-box{flex:1 1 auto;width:100%;}
.location-map-box{width:100%;}
}
@media (max-width:768px){
.location-page{padding:50px 0 70px;}
.location-inner{padding:0 16px;}
.location-title{margin-bottom:24px;font-size:28px;}
.location-info-item{gap:12px;margin-bottom:24px;}
.location-info-icon{width:28px;flex:0 0 28px;font-size:24px;}
.location-info-text strong{margin-bottom:6px;font-size:18px;}
.location-info-text p{font-size:16px;line-height:1.6;}
.location-phone{margin:10px 0 24px;font-size:18px;}
.location-phone i{font-size:26px;}
.location-naver-btn{width:100%;min-width:0;height:54px;font-size:16px;}
}
</style>

<!--
    2. 설치 스크립트
    * 지도 퍼가기 서비스를 2개 이상 넣을 경우, 설치 스크립트는 하나만 삽입합니다.
-->
<script charset="UTF-8" class="daum_roughmap_loader_script" src="https://ssl.daumcdn.net/dmaps/map_js_init/roughmapLoader.js"></script>

<!-- 3. 실행 스크립트 -->
<script charset="UTF-8">
new daum.roughmap.Lander({
    "timestamp" : "1776243529339",
    "key" : "2abaa2ju37fc",
    "mapWidth" : "100%",
    "mapHeight" : "420"
}).render();
</script>

<?php
include_once(G5_THEME_PATH.'/tail.php');
?>