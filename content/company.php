<?php
include_once('../common.php');
$g5['title'] = "회사소개";
$g5['meta_keywords'] = "인터넷2424, 포장이사, 일반이사, 사무실이사, 보관이사, 원룸이사, 기업이사, 이사업체, 이사전문업체, 전국이사";
include_once(G5_PATH.'/head.php');
?>
<style>
.content_wrap { padding: 40px 15px; max-width: 1200px; margin: 0 auto; line-height: 1.6; word-break: keep-all; }
.content_wrap h2 { font-size: 28px; font-weight: bold; margin-bottom: 20px; color: #333; text-align: center; }
.content_wrap h3 { font-size: 22px; font-weight: bold; margin-top: 30px; margin-bottom: 15px; color: #1a5c9a; border-bottom: 2px solid #1a5c9a; padding-bottom: 10px; }
.content_wrap p { font-size: 16px; color: #555; margin-bottom: 15px; }
.value_list { display: flex; flex-wrap: wrap; gap: 20px; margin-top: 20px; }
.value_item { flex: 1 1 calc(50% - 20px); background: #f8f9fa; padding: 20px; border-radius: 8px; box-sizing: border-box; }
.value_item h4 { font-size: 18px; color: #333; margin-top: 0; margin-bottom: 10px; }
.company_info { background: #eef2f5; padding: 20px; border-radius: 8px; margin-top: 40px; text-align: center; }
.company_info p { margin: 5px 0; font-size: 18px; font-weight: bold; color: #333; }
@media (max-width: 768px) {
    .value_item { flex: 1 1 100%; }
    .content_wrap h2 { font-size: 24px; }
}
</style>

<div class="content_wrap">
    <h2>회사소개</h2>
    <p><strong>인터넷2424</strong>는 고객 중심의 서비스를 실천하는 이사 전문기업입니다. 다년간 축적된 현장 경험과 노하우를 바탕으로 포장이사, 일반이사, 사무실이사, 보관이사 등 다양한 분야의 이사 서비스를 제공하고 있습니다.</p>

    <h3>경영이념 및 핵심가치</h3>
    <div class="value_list">
        <div class="value_item">
            <h4>고객만족</h4>
            <p>고객 중심 서비스로 고객의 입장에서 생각하고 행동합니다.</p>
        </div>
        <div class="value_item">
            <h4>안전운송</h4>
            <p>소중한 물품을 파손 최소화하여 안전하게 운송합니다.</p>
        </div>
        <div class="value_item">
            <h4>책임서비스</h4>
            <p>이사 전 과정에 대한 책임감 있는 작업을 통해 서비스를 제공합니다.</p>
        </div>
        <div class="value_item">
            <h4>정직경영</h4>
            <p>투명한 견적과 정직, 신뢰를 바탕으로 고객과 함께 성장합니다.</p>
        </div>
    </div>

    <h3>서비스 특징 및 고객과의 약속</h3>
    <ul>
        <li>체계적인 이사 시스템 및 전문 인력 운영</li>
        <li>친절한 고객 상담 및 합리적인 비용</li>
        <li>안전한 포장, 운송 및 철저한 사후 관리 서비스</li>
    </ul>
    <p>고객님의 소중한 이삿짐을 내 가족의 물건처럼 생각하며 안전하게 운반하겠습니다.</p>

    <div class="company_info">
        <p>상호명 : 인터넷2424</p>
        <p>대표번호 : 1844-****</p>
        <p>주소 : 서울시 **구 **동</p>
    </div>
</div>

<?php
include_once(G5_PATH.'/tail.php');
?>
