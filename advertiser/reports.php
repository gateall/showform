<?php
// /advertiser/reports.php
include_once('./_common.php');
adv_check_login();

$g5['title'] = '보고서 조회';
adv_head($g5['title']);

$adv_id = (int)$_SESSION['ss_adv_advertiser_id'];
?>
<div class="adv_section">
    <p>해당 광고주로 발행된 월간 및 콘텐츠 성과 보고서를 다운로드할 수 있습니다.</p>
    <div class="adv_grid">
        <div class="adv_grid_card">
            <div class="card_header">
                <strong>월간 광고주 보고서</strong>
            </div>
            <div class="card_body">
                <p>이번 달(<?php echo date('Y-m'); ?>) 발행 완료된 콘텐츠 요약본입니다.</p>
                <div class="adv_actions">
                    <a href="./report_download.php?type=monthly_adv&month=<?php echo date('Y-m'); ?>" class="btn_download">엑셀 다운로드</a>
                </div>
            </div>
        </div>
        
        <div class="adv_grid_card">
            <div class="card_header">
                <strong>콘텐츠 성과 보고서</strong>
            </div>
            <div class="card_body">
                <p>전체 누적 발행 글의 조회수, 공감수 등의 성과 지표입니다.</p>
                <div class="adv_actions">
                    <a href="./report_download.php?type=performance" class="btn_download">엑셀 다운로드</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php adv_tail(); ?>
