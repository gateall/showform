<?php
include_once('../common.php');
$g5['title'] = "이사준비사항";
$g5['meta_keywords'] = "인터넷2424, 포장이사, 일반이사, 사무실이사, 보관이사, 원룸이사, 기업이사, 이사업체, 이사전문업체, 전국이사";
include_once(G5_PATH.'/head.php');
?>
<style>
.content_wrap { padding: 40px 15px; max-width: 1200px; margin: 0 auto; line-height: 1.6; word-break: keep-all; }
.content_wrap h2 { font-size: 28px; font-weight: bold; margin-bottom: 20px; color: #333; text-align: center; }
.step_list { display: flex; flex-direction: column; gap: 20px; margin-top: 30px; }
.step_item { background: #f8f9fa; border-left: 5px solid #1a5c9a; padding: 20px; border-radius: 4px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); }
.step_item h3 { margin-top: 0; color: #1a5c9a; font-size: 22px; border-bottom: 1px dashed #ccc; padding-bottom: 10px; margin-bottom: 15px; }
ul.check_list { list-style: none; padding: 0; margin: 0; }
ul.check_list li { position: relative; padding-left: 20px; margin-bottom: 8px; font-size: 16px; color: #444; }
ul.check_list li::before { content: "✓"; position: absolute; left: 0; color: #e63946; font-weight: bold; }
.company_info { background: #eef2f5; padding: 20px; border-radius: 8px; margin-top: 40px; text-align: center; }
.company_info p { margin: 5px 0; font-size: 18px; font-weight: bold; color: #333; }
</style>

<div class="content_wrap">
    <h2>이사준비사항</h2>
    <p style="text-align:center;">체계적인 준비가 성공적인 이사를 만듭니다. <strong>인터넷2424</strong>가 알려드리는 이사 준비 체크리스트입니다.</p>

    <div class="step_list">
        <div class="step_item">
            <h3>이사 30일 전</h3>
            <ul class="check_list">
                <li>이사업체 선정</li>
                <li>방문견적 신청</li>
                <li>계약 체결</li>
            </ul>
        </div>

        <div class="step_item">
            <h3>이사 14일 전</h3>
            <ul class="check_list">
                <li>불필요한 물건 정리</li>
                <li>각종 주소변경 준비</li>
                <li>인터넷 및 통신이전 신청</li>
            </ul>
        </div>

        <div class="step_item">
            <h3>이사 7일 전</h3>
            <ul class="check_list">
                <li>냉장고 정리</li>
                <li>세탁기 배수 및 정리</li>
                <li>귀중품 별도 보관</li>
            </ul>
        </div>

        <div class="step_item">
            <h3>이사 전날</h3>
            <ul class="check_list">
                <li>냉장고 전원 차단</li>
                <li>귀중품 및 중요 서류 정리</li>
                <li>최종 점검</li>
            </ul>
        </div>

        <div class="step_item">
            <h3>이사 당일</h3>
            <ul class="check_list">
                <li>가스 차단 확인</li>
                <li>전기 및 수도 점검</li>
                <li>열쇠 인계</li>
            </ul>
        </div>
    </div>

    <div class="company_info">
        <p>이사 상담안내</p>
        <p>대표번호 : 1844-****</p>
        <p>주소 : 서울시 **구 **동 (인터넷2424)</p>
    </div>
</div>

<?php
include_once(G5_PATH.'/tail.php');
?>
