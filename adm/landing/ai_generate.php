<?php
$sub_menu = "900300";
include_once('./_common.php');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$g5['title'] = 'AI 랜딩페이지 자동 생성';

include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<style>
.ai_generate_wrap { max-width: 800px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); }
.ai_header { text-align: center; margin-bottom: 30px; }
.ai_header h2 { font-size: 24px; color: #1e293b; margin-bottom: 10px; }
.ai_header p { color: #64748b; font-size: 14px; }

.frm_grp { margin-bottom: 20px; }
.frm_grp label { display: block; font-weight: bold; color: #334155; margin-bottom: 8px; font-size: 14px; }
.frm_grp input[type="text"] { width: 100%; padding: 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 15px; outline: none; transition: border-color 0.2s; }
.frm_grp input[type="text"]:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.frm_grp .desc { display: block; color: #94a3b8; font-size: 12px; margin-top: 5px; }

.btn_generate { width: 100%; padding: 15px; background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); color: #fff; border: none; border-radius: 8px; font-size: 18px; font-weight: bold; cursor: pointer; transition: transform 0.2s, box-shadow 0.2s; margin-top: 20px; }
.btn_generate:hover { transform: translateY(-2px); box-shadow: 0 10px 15px rgba(37, 99, 235, 0.2); }
.btn_generate:disabled { background: #94a3b8; cursor: not-allowed; transform: none; box-shadow: none; }

/* 로딩 애니메이션 */
#loading_layer { display: none; text-align: center; margin-top: 20px; padding: 20px; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1; }
.spinner { width: 40px; height: 40px; border: 4px solid #e2e8f0; border-top: 4px solid #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 15px; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.loading_text { color: #1e293b; font-weight: bold; font-size: 15px; }
.loading_sub { color: #64748b; font-size: 13px; margin-top: 5px; }
</style>

<div class="ai_generate_wrap">
    <div class="ai_header">
        <h2>✨ AI 랜딩페이지 원클릭 자동 생성</h2>
        <p>단 4가지 키워드만 입력하면, 마케팅에 최적화된 카피라이팅이 적용된 템플릿이 즉시 생성됩니다.</p>
    </div>

    <form name="fgenerate" id="fgenerate" method="post" onsubmit="return false;">
        <div class="frm_grp">
            <label for="industry">업종 (분야)</label>
            <input type="text" name="industry" id="industry" placeholder="예: 누수탐지, 병원, 에어컨청소, 풀빌라" required>
            <span class="desc">타깃 고객이 검색할 메인 업종 키워드를 입력하세요.</span>
        </div>
        
        <div class="frm_grp">
            <label for="region">지역 (선택)</label>
            <input type="text" name="region" id="region" placeholder="예: 대구, 서울 강남구, 부산 전지역 (전국일 경우 생략 가능)">
            <span class="desc">특정 지역 기반의 서비스일 경우 입력하면 로컬 타겟팅 카피가 생성됩니다.</span>
        </div>

        <div class="frm_grp">
            <label for="service_name">서비스명 (상호명)</label>
            <input type="text" name="service_name" id="service_name" placeholder="예: 대구누수119, 연세바로치과, 훈골프투어" required>
            <span class="desc">랜딩페이지 전체에 노출될 브랜드/상호명을 입력하세요.</span>
        </div>

        <div class="frm_grp">
            <label for="phone">대표 전화번호</label>
            <input type="text" name="phone" id="phone" placeholder="예: 010-1234-5678, 1588-0000" required>
            <span class="desc">상담 및 CTA 버튼에 연동될 실제 전화번호를 입력하세요.</span>
        </div>

        <button type="button" id="btn_submit" class="btn_generate" onclick="generateLanding()">✨ 자동 생성 시작하기</button>
    </form>

    <div id="loading_layer">
        <div class="spinner"></div>
        <div class="loading_text">AI가 랜딩페이지 구조와 카피를 기획하고 있습니다...</div>
        <div class="loading_sub">이 작업은 약 10~20초 정도 소요됩니다. 창을 닫지 말고 잠시만 기다려 주세요.</div>
    </div>
</div>

<script>
function generateLanding() {
    var f = document.fgenerate;
    if (!f.industry.value.trim()) { alert("업종을 입력하세요."); f.industry.focus(); return; }
    if (!f.service_name.value.trim()) { alert("서비스/상호명을 입력하세요."); f.service_name.focus(); return; }
    if (!f.phone.value.trim()) { alert("전화번호를 입력하세요."); f.phone.focus(); return; }

    var btn = $('#btn_submit');
    var loading = $('#loading_layer');
    var form_data = $(f).serialize();

    btn.prop('disabled', true).text('생성 중입니다...');
    loading.slideDown();

    $.ajax({
        url: './ai_generate_action.php',
        type: 'POST',
        data: form_data,
        dataType: 'json',
        success: function(res) {
            if (res.success) {
                alert('🎉 성공적으로 AI 마스터 템플릿이 생성되었습니다!\n템플릿 목록 화면으로 이동합니다.');
                location.href = './template_list.php';
            } else {
                alert('오류가 발생했습니다: ' + res.error);
                btn.prop('disabled', false).text('✨ 자동 생성 시작하기');
                loading.slideUp();
            }
        },
        error: function(xhr, status, error) {
            alert('서버 통신 오류가 발생했습니다. 잠시 후 다시 시도해주세요.\n' + error);
            btn.prop('disabled', false).text('✨ 자동 생성 시작하기');
            loading.slideUp();
        }
    });
}
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
