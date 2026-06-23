<?php
if (!defined('_GNUBOARD_')) exit;
include_once(G5_PLUGIN_PATH.'/jquery-ui/datepicker.php');
?>
<style>
/* 상담신청 폼 공통 스타일 */
.mb_con { width: 80%; margin: 0 auto; padding-bottom: 24px; }
.mb_con .ms_inquiry ul { overflow: hidden; }
.mb_con .ms_inquiry li { width: 100%; float: left; margin-bottom: 2px; list-style: none; }
.ms_inquiry li.mi_name { position: relative; }
.ms_inquiry label { line-height: 27px; position: absolute; display: block; padding: 0px; background: url(/img/form_bar.png) no-repeat center left; color: #000; font-size: 1.02em; }
.ms_inquiry .two { letter-spacing: 0em; }
.ms_inquiry .three { letter-spacing: 0em; }
.ms_inquiry input[type="text"], .ms_inquiry input[type="tel"], .ms_inquiry textarea, .ms_inquiry .mi_personal, .ms_inquiry select {
    padding: 4px 6px; color: #222222; font-size: 1.02em !important;
    background: transparent; background-color: #fff; border: 1px solid #333;
    box-sizing: border-box; -moz-box-sizing: border-box; -webkit-box-sizing: border-box;
}
.ms_inquiry input[type="text"], .ms_inquiry input[type="tel"] { width: 372px; height: 28px; margin-left: 77px; }
.ms_inquiry select { width: 183px; height: 28px; margin-left: 77px; }
.ms_inquiry textarea { width: 372px; height: 72px; overflow-y: scroll; resize: none; margin-left: 77px; }
.ms_inquiry .mi_personal { width: 97%; height: 74px; overflow-y: scroll; line-height: 1.45em; margin: 0 auto; padding: 10px 12px; text-align: left; }
.ms_inquiry .mi_personal p { font-size: 1.0em; line-height: 1.5; text-align: left; }
.ms_inquiry .mi_check { margin-top: 6px; font-size: 0.92em; line-height: 1.15em; text-align: center; padding: 0; }
.ms_inquiry input[type="checkbox"] {
    -webkit-appearance: checkbox !important;
    -moz-appearance: checkbox !important;
    appearance: checkbox !important;
    width: 20px !important;
    height: 20px !important;
    vertical-align: middle;
    cursor: pointer;
    accent-color: #073567;
    display: inline-block !important;
    opacity: 1 !important;
    visibility: visible !important;
}
.ms_inquiry .mi_check input[type="checkbox"] { margin-right: 6px; }
.ms_inquiry .mi_submit { display: block; width: 100%; height: 48px; line-height: 48px; margin: 14px auto 28px; background: #073567; border: 1px solid #073567; font-size: 1.18em; color: #fff; -webkit-appearance: none; }
.ms_inquiry input[type="image"] { display: block; margin: 14px auto 26px; max-width: 100%; height: auto; }
.mi_tel img { padding: 0px 5px; }
.bbok { width: 100px; height: 29px; line-height: 29px; display: block; background-color: #767676; color: #fff !important; font-weight: 600; border-radius: 29px; text-align: center; font-size: 13px; }

/* 포함될가구 한 줄 정렬 및 하단 간격 */
.furniture-check-wrap { width: 420px; margin-left: 80px; margin-bottom: 20px; white-space: nowrap; font-size: 13px; line-height: 24px; height: auto; text-align: left; }
.furniture-check-wrap label { position: static !important; display: inline-block !important; background: none !important; padding: 0 !important; margin-right: 6px !important; letter-spacing: -0.5px; color: #222 !important; }
.furniture-check-wrap input[type="checkbox"] { -webkit-appearance: checkbox !important; -moz-appearance: checkbox !important; appearance: checkbox !important; width: 14px !important; height: 14px !important; margin: 0 2px 0 0 !important; vertical-align: middle !important; display: inline-block !important; opacity: 1 !important; visibility: visible !important; position: static !important; accent-color: #073567 !important; }

/* 출발지/도착지 엘리베이터 체크 */
.elevator-check-wrap { width: 372px; margin-left: 80px; margin-top: 7px; margin-bottom: 12px; text-align: left; font-size: 0.9em; line-height: 24px; clear: both; }
.elevator-check-wrap span { display: inline-block; margin-right: 10px; font-weight: 600; color: #333; }
.elevator-check-wrap label { position: static !important; display: inline-block !important; background: none !important; padding: 0 !important; margin-right: 12px !important; color: #222 !important; }
.elevator-check-wrap input[type="checkbox"] { -webkit-appearance: checkbox !important; -moz-appearance: checkbox !important; appearance: checkbox !important; width: 16px !important; height: 16px !important; display: inline-block !important; opacity: 1 !important; visibility: visible !important; position: static !important; vertical-align: middle !important; margin: 0 4px 0 0 !important; accent-color: #073567 !important; }

.mi_con { clear: both; margin-top: 20px !important; }

/* 개인정보처리방침 동의 스타일 */
.mi_agree { margin-top: 20px; width: 100%; box-sizing: border-box; }
.mi_personal { border: 1px solid #ccc; padding: 10px; font-size: 12px; line-height: 1.6; height: 100px; overflow-y: auto; background: #f9f9f9; text-align: left; box-sizing: border-box; }
.mi_check { margin-top: 10px; text-align: center; display: flex; align-items: center; justify-content: center; gap: 6px; flex-wrap: wrap; }
.mi_check input[type="checkbox"] { width: auto !important; margin: 0; }
.mi_check label { display: inline-block !important; position: static !important; background: none !important; padding: 0 !important; margin: 0 !important; font-weight: bold; cursor: pointer; line-height: 1.4; }

@media(max-width:768px){
    .mb_con { width: 100% !important; padding: 0 !important; box-sizing: border-box !important; }
    .mb_con table, .mb_con tbody, .mb_con tr, .mb_con td { display: block !important; width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; }
    .mb_con tr { height: auto !important; margin-bottom: 12px !important; padding: 13px !important; border: 1px solid #e5e7eb !important; border-radius: 12px !important; background: #fff !important; }
    .mb_con td { padding: 3px 0 !important; border: 0 !important; text-align: left !important; font-size: 13px !important; line-height: 1.45 !important; overflow: hidden !important; }
    .mb_con td:nth-child(3) { white-space: nowrap !important; text-overflow: ellipsis !important; }
    .mb_con .bbok { display: inline-block !important; margin-top: 8px !important; padding: 7px 18px !important; border-radius: 20px !important; font-size: 12px !important; }
    .ms_box input, .ms_box select, .ms_box textarea { width: 100% !important; max-width: 100% !important; box-sizing: border-box !important; }
    .ms_box label { display: block !important; margin: 5px 0 2px !important; text-align: left !important; }
    .ms_inquiry input[type="text"], .ms_inquiry input[type="tel"], .ms_inquiry textarea, .ms_inquiry select {
        width: 100% !important; margin-left: 0 !important; margin-top: 5px !important;
    }
    .ms_inquiry label { position: relative !important; margin-bottom: 4px; }
    .ms_inquiry .mi_tel img { display: none; }
    .ms_inquiry li.mi_tel > div { width: 100% !important; height: auto !important; margin-left: 0 !important; margin-top: 5px !important; }
    .furniture-check-wrap {
        width: 100% !important; margin-left: 0 !important; margin-top: 8px !important; margin-bottom: 5px !important;
        white-space: normal !important; display: flex !important; flex-wrap: wrap !important; gap: 6px 0 !important;
    }
    .ms_inquiry li.mi_tel .furniture-check-wrap label {
        width: 33.333% !important; display: inline-flex !important; align-items: center !important;
        box-sizing: border-box !important; margin: 0 !important; padding: 0 !important; position: static !important; background: none !important;
    }
    .furniture-check-wrap input[type="checkbox"] { margin-right: 4px !important; width: 14px !important; height: 14px !important; vertical-align: middle !important; }
    .elevator-check-wrap {
        width: 100% !important; margin-left: 0 !important; margin-top: 6px !important; margin-bottom: 12px !important;
        display: flex !important; align-items: center !important; flex-wrap: nowrap !important; gap: 8px !important;
    }
    .elevator-check-wrap span { display: inline-block !important; margin-bottom: 0 !important; margin-right: 8px !important; white-space: nowrap !important; font-weight: 600 !important; }
    .elevator-check-wrap label { display: inline-flex !important; align-items: center !important; margin: 0 4px 0 0 !important; white-space: nowrap !important; position: static !important; background: none !important; padding: 0 !important; width: auto !important; }
    .elevator-check-wrap input[type="checkbox"] { margin-right: 4px !important; width: 16px !important; height: 16px !important; vertical-align: middle !important; }
    .mi_con { margin-top: 5px !important; }
}
</style>

<form name="fquick_estimate" id="fquick_estimate" action="/bbs/form_insert.php" onsubmit="return fmain_estimate_submit(this);" method="post" autocomplete="off">
<input type="hidden" name="bo_table" value="inquiry">
<input type="hidden" name="mode" value="이사견적문의">
<input type="hidden" name="ret" value="<?php echo isset($estimate_ret_url) ? $estimate_ret_url : ''; ?>">

<div class="ms_box02 ms_box">
    <h2 style="font-size:30px; line-height:40px; font-weight:900; letter-spacing:-0.05em; margin-bottom:20px;"><i class="fa fa-home"></i> 실시간 견적문의</h2>
    <div class="mb_con">
        <div class="ms_inquiry ms_contents">
            <ul style="padding:0;">
                <li class="mi_name">
                    <label class="two">이사종류</label>
                    <select name="wr_9" id="wr_9" required>
                        <option value="">선택하세요.</option>
                        <option value="포장/반포장이사">포장/반포장이사</option>
                        <option value="일반이사">일반이사</option>
                        <option value="보관이사">보관이사</option>
                        <option value="용달화물">용달화물</option>
                        <option value="해외이사">해외이사</option>
                    </select>
                    <select name="wr_10" id="wr_10" style="margin-left:3px;" required>
                        <option value="아파트/빌라/다세대">아파트/빌라/다세대</option>
                        <option value="주택">주택</option>
                        <option value="오피스텔/원투룸">오피스텔/원투룸</option>
                        <option value="사무실">사무실</option>
                        <option value="공장">공장</option>
                        <option value="기타(학교,관공서,체육관종교시설 등 )">기타(학교,관공서,체육관종교시설 등)</option>
                    </select>
                </li>
                <li class="mi_name">
                    <label class="two">고객명</label>
                    <input type="text" name="wr_name" placeholder="이름" required="required">
                </li>
                <li class="mi_tel">
                    <label class="three">휴대폰</label>
                    <input type="tel" name="wr_1" placeholder="휴대폰번호" required="required">
                </li>
                <li class="mi_tel">
                    <label class="three">이사날짜</label>
                    <input type="text" name="wr_2" id="wr_2" placeholder="" required="required" style="width:338px;padding-right:10px;"> &nbsp;
                </li>
                <li class="mi_tel">
                    <label class="three">출발지정보</label>
                    <input type="text" name="wr_3" id="main_wr_3" placeholder="출발지주소" required="required" style="width:230px;" onclick="openDaumPostcode();" readonly>
                    <input type="text" name="wr_4" placeholder="평수" required="required" style="width:64px; margin-left:3px;">
                    <input type="text" name="wr_5" placeholder="층수" required="required" style="width:64px; margin-left:3px;">
                    <div class="elevator-check-wrap">
                        <span>출발지 엘리베이터</span>
                        <label><input type="checkbox" name="wr_6" value="있음" class="elevator-check">있음</label> <label><input type="checkbox" name="wr_6" value="없음" class="elevator-check">없음</label>
                    </div>
                </li>
                <li class="mi_tel">
                    <label class="three">도착지정보</label>
                    <input type="text" name="wr_7" id="main_wr_7" placeholder="도착지주소" required="required" style="width:230px;" onclick="openDaumPostcode1();" readonly>
                    <input type="text" name="wr_8" placeholder="평수" required="required" style="width:64px; margin-left:3px;">
                    <input type="text" name="wr_11" placeholder="층수" required="required" style="width:64px; margin-left:3px;">
                    <div class="elevator-check-wrap">
                        <span>도착지 엘리베이터</span>
                        <label><input type="checkbox" name="wr_12" value="있음" class="elevator-check">있음</label> <label><input type="checkbox" name="wr_12" value="없음" class="elevator-check">없음</label>
                    </div>
                </li>
                <li class="mi_tel">
                    <label class="three">포함될가구</label>
                    <div class="furniture-check-wrap">
                        <label><input type="checkbox" name="wr_13[]" value="에어컨">에어컨</label> <label><input type="checkbox" name="wr_13[]" value="피아노">피아노</label> <label><input type="checkbox" name="wr_13[]" value="돌침대">돌침대</label>
                        <label><input type="checkbox" name="wr_13[]" value="벽걸이TV">벽걸이TV</label> <label><input type="checkbox" name="wr_13[]" value="조립식장롱">조립식장롱</label>
                    </div>
                </li>
                <li class="mi_con" style="margin-top:10px;">
                    <label>추가사항</label>
                    <textarea type="text" name="wr_content" required="required" style="height:50px; padding:3px;"></textarea>
                </li>
            </ul>
            <div class="mi_agree notoSans" style="margin-top:20px;">
                <div class="mi_personal">
                    <p>
                    * 개인정보 수집/이용 목적 : 고객문의 상담요청에 대하여 회신을 하거나, 상담을 위한 서비스 이용기록조회<br>
                    * 수집하는 개인정보의 항목 : 성함, 연락처<br>
                    ※상담예약서비스 이용과정에서 아래와 같은 정보들이 생성되어 수집될 수 있습니다.<br>
                    - 서비스이용기록, 접속로그, 쿠키, 접속IP정보<br>
                    * 개인정보의 보유 및 이용기간<br>
                    -보존기간은 5년이며, 정보 제공자가 삭제를 요청할 경우 즉시 파기합니다.<br>
                    -고객님의 정보는 개인정보 보호법에 따라 보호되며 위의 사항 외에 별도로 사용하지 않을 것을 약속드립니다
                    </p>
                </div>
                <div class="mi_check">
                    <input type="checkbox" id="agree_chk_latest" name="agree" value="1" required="required">
                    <label for="agree_chk_latest">개인정보처리방침 동의</label>
                </div>
            </div>
            <br>
            <div style="text-align:center;">
                <button type="submit" style="background:#073567; color:#fff; border:none; padding:12px 40px; font-size:18px; font-weight:bold; cursor:pointer; border-radius:5px;">빠른 상담하기</button>
            </div>
        </div>
    </div>
</div>
</form>

<!-- 다음 우편번호 스크립트 -->
<script src="//t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>

<script>
function fmain_estimate_submit(f){
    if(!confirm('신청 하시겠습니까?')) return false;
    return true;
}

$(document).ready(function() {
    $('#wr_9').change(function(){
        var s1 = ["아파트/빌라/다세대","주택","오피스텔/원투룸","사무실","공장","기타(학교,관공서,체육관종교시설 등)"];
        var s2 = ["아파트/빌라/다세대","주택","오피스텔/원투룸","사무실","기타"];
        var s3 = ["오피스텔","원투룸","사무실","빌라","주택","아파트"]; 

        var selectItem = $("#wr_9").val();
        var changeItem;
          
        if(selectItem == "포장/반포장이사" || selectItem == "일반이사" || selectItem == "해외이사"){			
          changeItem = s1;			  
        }
        else if(selectItem == "보관이사"){
          changeItem = s2;
        }
        else if(selectItem == "용달화물"){
          changeItem = s3;			  
        }
         
        $('#wr_10').empty();

        $.each(changeItem, function( k, v ) {
          if(k == 0){
                var optiona = $("<option>"+"선택하세요"+"</option>");
                $('#wr_10').append(optiona);
            }
          var option = $("<option value='"+v+"'>"+v+"</option>");
          $('#wr_10').append(option);
        });	
    });

    $("#wr_2").datepicker({ 
         changeMonth: true, changeYear: true, dateFormat: "yy-mm-dd", showButtonPanel: true, yearRange: "c-99:c+99",		
        showOn: 'both',
        buttonImage: '/theme/knusu/img/btn001.gif',
        buttonImageOnly: true
    });
});

function openDaumPostcode() {	    
    daum.postcode.load(function(){
       new daum.Postcode({
          oncomplete: function(data) {					
             var fullAddr = data.address;
             if(data.buildingName) {
                 fullAddr += " " + data.buildingName;
             }
             var el = document.getElementById('main_wr_3');
             if(el) {
                 el.value = fullAddr;
                 el.focus();
             }
          }
       }).open();
    });
}
function openDaumPostcode1() {	    
    daum.postcode.load(function(){
       new daum.Postcode({
          oncomplete: function(data) {					
             var fullAddr = data.address;
             if(data.buildingName) {
                 fullAddr += " " + data.buildingName;
             }
             var el = document.getElementById('main_wr_7');
             if(el) {
                 el.value = fullAddr;
                 el.focus();
             }
          }
       }).open();
    });
}

// 엘리베이터 있음/없음 체크박스 중복 선택 방지
document.addEventListener('change', function(e){
    if(e.target.classList && e.target.classList.contains('elevator-check')){
        var name = e.target.getAttribute('name');
        var boxes = document.querySelectorAll('input.elevator-check[name="' + name + '"]');
        boxes.forEach(function(box){
            if(box !== e.target) box.checked = false;
        });
    }
});
</script>
