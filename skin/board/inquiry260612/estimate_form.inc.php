<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

echo G5_POSTCODE_JS;
include_once(G5_PLUGIN_PATH.'/jquery-ui/datepicker.php');

$cust_name = '';
$phone = '';
$move_type = '';
$housing_type = '';
$addr_out = '';
$pyung_out = '';
$floor_out = '';
$ho_out = '';
$elevator_out = '';
$addr_in = '';
$pyung_in = '';
$floor_in = '';
$ho_in = '';
$elevator_in = '';
$move_date = '';
$furniture_arr = array();
$status_val = '견적접수';
$memo = '';

if (isset($w) && $w == 'u' && isset($write)) {
    $cust_name = isset($write['wr_name']) ? trim($write['wr_name']) : (isset($write['wr_1']) ? trim($write['wr_1']) : '');
    $phone = isset($write['wr_2']) ? trim($write['wr_2']) : '';
    $move_type = isset($write['wr_3']) ? trim($write['wr_3']) : '';
    $housing_type = isset($write['wr_10']) ? trim($write['wr_10']) : '';
    $move_date = isset($write['wr_6']) ? trim($write['wr_6']) : '';
    $status_val = isset($write['wr_8']) && trim($write['wr_8']) !== '' ? trim($write['wr_8']) : '견적접수';
    $memo = isset($write['wr_9']) ? trim($write['wr_9']) : '';

    // wr_4 (출발지주소 / 평수 / 층수 / 호수 / 엘리베이터 여부) 파싱
    if (isset($write['wr_4']) && $write['wr_4']) {
        $out_arr = explode(' / ', $write['wr_4']);
        $addr_out = isset($out_arr[0]) ? trim($out_arr[0]) : '';
        $pyung_out = isset($out_arr[1]) ? trim(str_replace('평', '', $out_arr[1])) : '';
        $floor_out = isset($out_arr[2]) ? trim(str_replace('층', '', $out_arr[2])) : '';
        if (count($out_arr) >= 5) {
            $ho_out = isset($out_arr[3]) ? trim(str_replace('호', '', $out_arr[3])) : '';
            $elevator_out = isset($out_arr[4]) ? trim(str_replace('엘리베이터', '', $out_arr[4])) : '';
        } else {
            $ho_out = '';
            $elevator_out = isset($out_arr[3]) ? trim(str_replace('엘리베이터', '', $out_arr[3])) : '';
        }
    }

    // wr_5 (도착지주소 / 평수 / 층수 / 호수 / 엘리베이터 여부) 파싱
    if (isset($write['wr_5']) && $write['wr_5']) {
        $in_arr = explode(' / ', $write['wr_5']);
        $addr_in = isset($in_arr[0]) ? trim($in_arr[0]) : '';
        $pyung_in = isset($in_arr[1]) ? trim(str_replace('평', '', $in_arr[1])) : '';
        $floor_in = isset($in_arr[2]) ? trim(str_replace('층', '', $in_arr[2])) : '';
        if (count($in_arr) >= 5) {
            $ho_in = isset($in_arr[3]) ? trim(str_replace('호', '', $in_arr[3])) : '';
            $elevator_in = isset($in_arr[4]) ? trim(str_replace('엘리베이터', '', $in_arr[4])) : '';
        } else {
            $ho_in = '';
            $elevator_in = isset($in_arr[3]) ? trim(str_replace('엘리베이터', '', $in_arr[3])) : '';
        }
    }

    // wr_7 (가구 리스트) 파싱
    if (isset($write['wr_7']) && $write['wr_7']) {
        $furniture_arr = explode(',', $write['wr_7']);
    }
}

if (!isset($action_url) || !$action_url) {
    $action_url = G5_BBS_URL . '/write_update.php';
}
?>

<form name="fwrite" id="fwrite" action="<?php echo $action_url; ?>" method="post" enctype="multipart/form-data" autocomplete="off" onsubmit="return fwrite_submit(this);">
<input type="hidden" name="uid" value="<?php echo isset($uid) ? $uid : get_uniqid(); ?>">
<input type="hidden" name="w" value="<?php echo isset($w) ? $w : ''; ?>">
<input type="hidden" name="bo_table" value="<?php echo isset($bo_table) ? $bo_table : 'inquiry'; ?>">
<input type="hidden" name="wr_id" value="<?php echo isset($wr_id) ? $wr_id : ''; ?>">
<input type="hidden" name="sca" value="<?php echo isset($sca) ? $sca : ''; ?>">
<input type="hidden" name="sfl" value="<?php echo isset($sfl) ? $sfl : ''; ?>">
<input type="hidden" name="stx" value="<?php echo isset($stx) ? $stx : ''; ?>">
<input type="hidden" name="spt" value="<?php echo isset($spt) ? $spt : ''; ?>">
<input type="hidden" name="sst" value="<?php echo isset($sst) ? $sst : ''; ?>">
<input type="hidden" name="sod" value="<?php echo isset($sod) ? $sod : ''; ?>">
<input type="hidden" name="page" value="<?php echo isset($page) ? $page : ''; ?>">

<input type="hidden" name="wr_subject" value="<?php echo $cust_name ? get_text($cust_name) : '실시간 견적문의'; ?>">

<div class="mb_con">
    <div class="ms_inquiry ms_contents">
        <ul>
            <li class="mi_name">
                <label class="two">이사종류</label>
                <select name="move_type" id="wr_9_select" required>
                    <option value="">선택하세요.</option>
                    <option value="포장/반포장이사" <?php echo $move_type == '포장/반포장이사' ? 'selected' : ''; ?>>포장/반포장이사</option>
                    <option value="일반이사" <?php echo $move_type == '일반이사' ? 'selected' : ''; ?>>일반이사</option>
                    <option value="보관이사" <?php echo $move_type == '보관이사' ? 'selected' : ''; ?>>보관이사</option>
                    <option value="용달화물" <?php echo $move_type == '용달화물' ? 'selected' : ''; ?>>용달화물</option>
                    <option value="해외이사" <?php echo $move_type == '해외이사' ? 'selected' : ''; ?>>해외이사</option>
                </select>
                <select name="housing_type" id="wr_10_select" style="margin-left:3px;" required>
                    <option value="">선택하세요</option>
                    <?php if ($housing_type) { ?>
                        <option value="<?php echo get_text($housing_type); ?>" selected><?php echo get_text($housing_type); ?></option>
                    <?php } ?>
                </select>
            </li>
            <li class="mi_name">
                <label class="two">고객명</label>
                <input type="text" name="wr_name" value="<?php echo get_text($cust_name); ?>" placeholder="이름" required="required">
            </li>
            <li class="mi_tel">
                <label class="three">휴대폰</label>
                <input type="tel" name="phone" value="<?php echo get_text($phone); ?>" placeholder="휴대폰번호" required="required">
            </li>
            <li class="mi_tel">
                <label class="three">이사날짜</label>
                <input type="text" name="move_date" id="wr_2_datepicker" value="<?php echo get_text($move_date); ?>" required="required" style="width:calc(100% - 100px); max-width:338px;">
            </li>
            <li class="mi_tel">
                <label class="three">출발지정보</label>
                <input type="text" name="addr_out" value="<?php echo get_text($addr_out); ?>" placeholder="출발지주소" required="required" style="width:calc(100% - 220px); max-width:170px;" onclick="openDaumPostcode();">
                <input type="text" name="pyung_out" value="<?php echo get_text($pyung_out); ?>" placeholder="평수" required="required" style="width:48px; margin-left:3px;">
                <input type="text" name="floor_out" value="<?php echo get_text($floor_out); ?>" placeholder="층수" required="required" style="width:48px; margin-left:3px;">
                <input type="text" name="ho_out" value="<?php echo get_text($ho_out); ?>" placeholder="호" style="width:48px; margin-left:3px;">
                <div class="elevator-check-wrap">
                    <span>출발지 엘리베이터</span>
                    <label><input type="checkbox" name="elevator_out" value="있음" class="elevator-check" <?php echo $elevator_out == '있음' ? 'checked' : ''; ?>> 있음</label> <label><input type="checkbox" name="elevator_out" value="없음" class="elevator-check" <?php echo $elevator_out == '없음' ? 'checked' : ''; ?>> 없음</label>
                </div>
            </li>
            <li class="mi_tel">
                <label class="three">도착지정보</label>
                <input type="text" name="addr_in" value="<?php echo get_text($addr_in); ?>" placeholder="도착지주소" required="required" style="width:calc(100% - 220px); max-width:170px;" onclick="openDaumPostcode1();">
                <input type="text" name="pyung_in" value="<?php echo get_text($pyung_in); ?>" placeholder="평수" required="required" style="width:48px; margin-left:3px;">
                <input type="text" name="floor_in" value="<?php echo get_text($floor_in); ?>" placeholder="층수" required="required" style="width:48px; margin-left:3px;">
                <input type="text" name="ho_in" value="<?php echo get_text($ho_in); ?>" placeholder="호" style="width:48px; margin-left:3px;">
                <div class="elevator-check-wrap">
                    <span>도착지 엘리베이터</span>
                    <label><input type="checkbox" name="elevator_in" value="있음" class="elevator-check" <?php echo $elevator_in == '있음' ? 'checked' : ''; ?>> 있음</label> <label><input type="checkbox" name="elevator_in" value="없음" class="elevator-check" <?php echo $elevator_in == '없음' ? 'checked' : ''; ?>> 없음</label>
                </div>
            </li>
            <li class="mi_tel">
                <label class="three">포함될가구</label>
                <div class="furniture-check-wrap">
                    <label><input type="checkbox" name="furniture[]" value="에어컨" <?php echo in_array('에어컨', $furniture_arr) ? 'checked' : ''; ?>>에어컨</label> <label><input type="checkbox" name="furniture[]" value="피아노" <?php echo in_array('피아노', $furniture_arr) ? 'checked' : ''; ?>>피아노</label> <label><input type="checkbox" name="furniture[]" value="돌침대" <?php echo in_array('돌침대', $furniture_arr) ? 'checked' : ''; ?>>돌침대</label>
                    <label><input type="checkbox" name="furniture[]" value="벽걸이TV" <?php echo in_array('벽걸이TV', $furniture_arr) ? 'checked' : ''; ?>>벽걸이TV</label> <label><input type="checkbox" name="furniture[]" value="조립식장롱" <?php echo in_array('조립식장롱', $furniture_arr) ? 'checked' : ''; ?>>조립식장롱</label>
                </div>
            </li>
            <li class="mi_con">
                <label>추가사항</label>
                <textarea name="memo" required="required" style="height:72px; padding:6px; box-sizing:border-box; width:100%; max-width:372px; margin-left:77px;"><?php echo get_text($memo); ?></textarea>
            </li>
        </ul>

        <?php if (isset($is_admin) && $is_admin) { ?>
        <div class="estimate-admin-status">
            <label for="status_val">진행상태 (관리자 전용):</label>
            <select name="status_val" id="status_val">
                <option value="견적접수" <?php echo get_selected($status_val, '견적접수'); ?>>견적접수</option>
                <option value="견적확인" <?php echo get_selected($status_val, '견적확인'); ?>>견적확인</option>
                <option value="견적제출" <?php echo get_selected($status_val, '견적제출'); ?>>견적제출</option>
            </select>
        </div>
        <?php } ?>

        <?php if ((!isset($w) || $w != 'u')) { ?>
        <div class="mi_agree notoSans">
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
                <input type="checkbox" id="agree_chk" required="required">
                <label for="agree_chk">개인정보처리방침 동의</label>
            </div>
        </div>
        <?php } ?>

        <div class="btn_confirm write_div">
            <a href="<?php echo G5_BBS_URL; ?>/board.php?bo_table=<?php echo $bo_table; ?>" class="btn_cancel btn">취소</a>
            <button type="submit" id="btn_submit" accesskey="s" class="btn_submit btn"><?php echo (isset($w) && $w == 'u') ? '수정완료' : '빠른 상담하기'; ?></button>
        </div>
    </div>
</div>
</form>

<script>
document.addEventListener('change', function(e){
    if(e.target.classList && e.target.classList.contains('elevator-check')){
        var name = e.target.getAttribute('name');
        var boxes = document.querySelectorAll('input.elevator-check[name="'+name+'"]');
        boxes.forEach(function(box){
            if(box !== e.target) box.checked = false;
        });
    }
});

$(document).ready(function() {
    $('#wr_9_select').change(function(){
        var s1 = ["아파트/빌라/다세대","주택","오피스텔/원투룸","사무실","공장","기타(학교,관공서,체육관종교시설 등)"];
        var s2 = ["아파트/빌라/다세대","주택","오피스텔/원투룸","사무실","기타"];
        var s3 = ["오피스텔","원투룸","사무실","빌라","주택","아파트"]; 

        var selectItem = $("#wr_9_select").val();
        var changeItem;
          
        if(selectItem == "포장/반포장이사" || selectItem == "일반이사" || selectItem == "해외이사"){            
          changeItem = s1;            
        }
        else if(selectItem == "보관이사"){
          changeItem = s2;
        }
        else if(selectItem == "용달화물"){
          changeItem = s3;            
        } else {
          changeItem = [];
        }
         
        $('#wr_10_select').empty();
        if (changeItem.length > 0) {
            var optiona = $("<option value=''>선택하세요</option>");
            $('#wr_10_select').append(optiona);
            $.each(changeItem, function( k, v ) {
              var option = $("<option value='"+v+"'>"+v+"</option>");
              $('#wr_10_select').append(option);
            }); 
        } else {
            var optiona = $("<option value=''>이사종류를 선택하세요</option>");
            $('#wr_10_select').append(optiona);
        }
    });

    $("#wr_2_datepicker").datepicker({ 
         changeMonth: true, 
         changeYear: true, 
         dateFormat: "yy-mm-dd", 
         showButtonPanel: true, 
         yearRange: "c-99:c+99",        
         showOn: 'both',
         buttonImage: '/theme/knusu/img/btn001.gif',
         buttonImageOnly: true
    });
});

function openDaumPostcode() {        
    daum.postcode.load(function(){
       new daum.Postcode({
          oncomplete: function(data) {                  
             document.fwrite.addr_out.value = data.address + " " + data.buildingName;
             document.fwrite.addr_out.focus();
          }
       }).open();
    });
}

function openDaumPostcode1() {        
    daum.postcode.load(function(){
       new daum.Postcode({
          oncomplete: function(data) {                  
             document.fwrite.addr_in.value = data.address + " " + data.buildingName;
             document.fwrite.addr_in.focus();
          }
       }).open();
    });
}

function fwrite_submit(f) {
    if (!f.move_type.value) {
        alert("이사종류를 선택해 주세요.");
        f.move_type.focus();
        return false;
    }
    if (!f.housing_type.value) {
        alert("주거형태를 선택해 주세요.");
        f.housing_type.focus();
        return false;
    }
    if (!f.wr_name.value) {
        alert("고객명을 입력해 주세요.");
        f.wr_name.focus();
        return false;
    }
    if (!f.phone.value) {
        alert("휴대폰 번호를 입력해 주세요.");
        f.phone.focus();
        return false;
    }
    if (!f.move_date.value) {
        alert("이사날짜를 선택해 주세요.");
        f.move_date.focus();
        return false;
    }
    if (!f.addr_out.value) {
        alert("출발지 주소를 입력해 주세요.");
        f.addr_out.focus();
        return false;
    }
    if (!f.addr_in.value) {
        alert("도착지 주소를 입력해 주세요.");
        f.addr_in.focus();
        return false;
    }
    if (!f.memo.value) {
        alert("추가사항을 입력해 주세요.");
        f.memo.focus();
        return false;
    }

    <?php if (!isset($w) || $w != 'u') { ?>
    if (!f.agree_chk.checked) {
        alert("개인정보처리방침에 동의하셔야 신청이 가능합니다.");
        f.agree_chk.focus();
        return false;
    }
    <?php } ?>

    document.getElementById("btn_submit").disabled = "disabled";
    return true;
}
</script>
