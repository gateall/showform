<?php
if (!defined('_GNUBOARD_')) exit; // 媛쒕퀎 ?섏씠吏 ?묎렐 遺덇?

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
$status_val = '寃ъ쟻?묒닔';
$memo = '';

if (isset($w) && $w == 'u' && isset($write)) {
    $cust_name = isset($write['wr_name']) ? trim($write['wr_name']) : (isset($write['wr_1']) ? trim($write['wr_1']) : '');
    $phone = isset($write['wr_2']) ? trim($write['wr_2']) : '';
    $move_type = isset($write['wr_3']) ? trim($write['wr_3']) : '';
    $housing_type = isset($write['wr_10']) ? trim($write['wr_10']) : '';
    $move_date = isset($write['wr_6']) ? trim($write['wr_6']) : '';
    $status_val = isset($write['wr_8']) && trim($write['wr_8']) !== '' ? trim($write['wr_8']) : '寃ъ쟻?묒닔';
    $memo = isset($write['wr_9']) ? trim($write['wr_9']) : '';

    // wr_4 (異쒕컻吏二쇱냼 / ?됱닔 / 痢듭닔 / ?몄닔 / ?섎━踰좎씠???щ?) ?뚯떛
    if (isset($write['wr_4']) && $write['wr_4']) {
        $out_arr = explode(' / ', $write['wr_4']);
        $addr_out = isset($out_arr[0]) ? trim($out_arr[0]) : '';
        $pyung_out = isset($out_arr[1]) ? trim(str_replace('??, '', $out_arr[1])) : '';
        $floor_out = isset($out_arr[2]) ? trim(str_replace('痢?, '', $out_arr[2])) : '';
        if (count($out_arr) >= 5) {
            $ho_out = isset($out_arr[3]) ? trim(str_replace('??, '', $out_arr[3])) : '';
            $elevator_out = isset($out_arr[4]) ? trim(str_replace('?섎━踰좎씠??, '', $out_arr[4])) : '';
        } else {
            $ho_out = '';
            $elevator_out = isset($out_arr[3]) ? trim(str_replace('?섎━踰좎씠??, '', $out_arr[3])) : '';
        }
    }

    // wr_5 (?꾩갑吏二쇱냼 / ?됱닔 / 痢듭닔 / ?몄닔 / ?섎━踰좎씠???щ?) ?뚯떛
    if (isset($write['wr_5']) && $write['wr_5']) {
        $in_arr = explode(' / ', $write['wr_5']);
        $addr_in = isset($in_arr[0]) ? trim($in_arr[0]) : '';
        $pyung_in = isset($in_arr[1]) ? trim(str_replace('??, '', $in_arr[1])) : '';
        $floor_in = isset($in_arr[2]) ? trim(str_replace('痢?, '', $in_arr[2])) : '';
        if (count($in_arr) >= 5) {
            $ho_in = isset($in_arr[3]) ? trim(str_replace('??, '', $in_arr[3])) : '';
            $elevator_in = isset($in_arr[4]) ? trim(str_replace('?섎━踰좎씠??, '', $in_arr[4])) : '';
        } else {
            $ho_in = '';
            $elevator_in = isset($in_arr[3]) ? trim(str_replace('?섎━踰좎씠??, '', $in_arr[3])) : '';
        }
    }

    // wr_7 (媛援?由ъ뒪?? ?뚯떛
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

<input type="hidden" name="wr_subject" value="<?php echo $cust_name ? get_text($cust_name) : '?ㅼ떆媛?寃ъ쟻臾몄쓽'; ?>">

<div class="mb_con">
    <div class="ms_inquiry ms_contents">
        <ul>
            <li class="mi_name">
                <label class="two">?댁궗醫낅쪟</label>
                <select name="move_type" id="wr_9_select" required>
                    <option value="">?좏깮?섏꽭??</option>
                    <option value="?ъ옣/諛섑룷?μ씠?? <?php echo $move_type == '?ъ옣/諛섑룷?μ씠?? ? 'selected' : ''; ?>>?ъ옣/諛섑룷?μ씠??/option>
                    <option value="?쇰컲?댁궗" <?php echo $move_type == '?쇰컲?댁궗' ? 'selected' : ''; ?>>?쇰컲?댁궗</option>
                    <option value="蹂닿??댁궗" <?php echo $move_type == '蹂닿??댁궗' ? 'selected' : ''; ?>>蹂닿??댁궗</option>
                    <option value="?⑸떖?붾Ъ" <?php echo $move_type == '?⑸떖?붾Ъ' ? 'selected' : ''; ?>>?⑸떖?붾Ъ</option>
                    <option value="?댁쇅?댁궗" <?php echo $move_type == '?댁쇅?댁궗' ? 'selected' : ''; ?>>?댁쇅?댁궗</option>
                </select>
                <select name="housing_type" id="wr_10_select" style="margin-left:3px;" required>
                    <option value="">?좏깮?섏꽭??/option>
                    <?php if ($housing_type) { ?>
                        <option value="<?php echo get_text($housing_type); ?>" selected><?php echo get_text($housing_type); ?></option>
                    <?php } ?>
                </select>
            </li>
            <li class="mi_name">
                <label class="two">怨좉컼紐?/label>
                <input type="text" name="wr_name" value="<?php echo get_text($cust_name); ?>" placeholder="?대쫫" required="required">
            </li>
            <li class="mi_tel">
                <label class="three">?대???/label>
                <input type="tel" name="phone" value="<?php echo get_text($phone); ?>" placeholder="?대??곕쾲?? required="required">
            </li>
            <li class="mi_tel">
                <label class="three">?댁궗?좎쭨</label>
                <input type="text" name="move_date" id="wr_2_datepicker" value="<?php echo get_text($move_date); ?>" required="required" style="width:calc(100% - 100px); max-width:338px;">
            </li>
            <li class="mi_tel">
                <label class="three">異쒕컻吏?뺣낫</label>
                <input type="text" name="addr_out" value="<?php echo get_text($addr_out); ?>" placeholder="異쒕컻吏二쇱냼" required="required" style="width:calc(100% - 220px); max-width:170px;" onclick="openDaumPostcode();">
                <input type="text" name="pyung_out" value="<?php echo get_text($pyung_out); ?>" placeholder="?됱닔" required="required" style="width:48px; margin-left:3px;">
                <input type="text" name="floor_out" value="<?php echo get_text($floor_out); ?>" placeholder="痢듭닔" required="required" style="width:48px; margin-left:3px;">
                <input type="text" name="ho_out" value="<?php echo get_text($ho_out); ?>" placeholder="?? style="width:48px; margin-left:3px;">
                <div class="elevator-check-wrap">
                    <span>異쒕컻吏 ?섎━踰좎씠??/span>
                    <label><input type="checkbox" name="elevator_out" value="?덉쓬" class="elevator-check" <?php echo $elevator_out == '?덉쓬' ? 'checked' : ''; ?>> ?덉쓬</label> <label><input type="checkbox" name="elevator_out" value="?놁쓬" class="elevator-check" <?php echo $elevator_out == '?놁쓬' ? 'checked' : ''; ?>> ?놁쓬</label>
                </div>
            </li>
            <li class="mi_tel">
                <label class="three">?꾩갑吏?뺣낫</label>
                <input type="text" name="addr_in" value="<?php echo get_text($addr_in); ?>" placeholder="?꾩갑吏二쇱냼" required="required" style="width:calc(100% - 220px); max-width:170px;" onclick="openDaumPostcode1();">
                <input type="text" name="pyung_in" value="<?php echo get_text($pyung_in); ?>" placeholder="?됱닔" required="required" style="width:48px; margin-left:3px;">
                <input type="text" name="floor_in" value="<?php echo get_text($floor_in); ?>" placeholder="痢듭닔" required="required" style="width:48px; margin-left:3px;">
                <input type="text" name="ho_in" value="<?php echo get_text($ho_in); ?>" placeholder="?? style="width:48px; margin-left:3px;">
                <div class="elevator-check-wrap">
                    <span>?꾩갑吏 ?섎━踰좎씠??/span>
                    <label><input type="checkbox" name="elevator_in" value="?덉쓬" class="elevator-check" <?php echo $elevator_in == '?덉쓬' ? 'checked' : ''; ?>> ?덉쓬</label> <label><input type="checkbox" name="elevator_in" value="?놁쓬" class="elevator-check" <?php echo $elevator_in == '?놁쓬' ? 'checked' : ''; ?>> ?놁쓬</label>
                </div>
            </li>
            <li class="mi_tel">
                <label class="three">?ы븿?좉?援?/label>
                <div class="furniture-check-wrap">
                    <label><input type="checkbox" name="furniture[]" value="?먯뼱而? <?php echo in_array('?먯뼱而?, $furniture_arr) ? 'checked' : ''; ?>>?먯뼱而?/label> <label><input type="checkbox" name="furniture[]" value="?쇱븘?? <?php echo in_array('?쇱븘??, $furniture_arr) ? 'checked' : ''; ?>>?쇱븘??/label> <label><input type="checkbox" name="furniture[]" value="?뚯묠?" <?php echo in_array('?뚯묠?', $furniture_arr) ? 'checked' : ''; ?>>?뚯묠?</label>
                    <label><input type="checkbox" name="furniture[]" value="踰쎄구?큈V" <?php echo in_array('踰쎄구?큈V', $furniture_arr) ? 'checked' : ''; ?>>踰쎄구?큈V</label> <label><input type="checkbox" name="furniture[]" value="議곕┰?앹옣濡? <?php echo in_array('議곕┰?앹옣濡?, $furniture_arr) ? 'checked' : ''; ?>>議곕┰?앹옣濡?/label>
                </div>
            </li>
            <li class="mi_con">
                <label>異붽??ы빆</label>
                <textarea name="memo" required="required" style="height:72px; padding:6px; box-sizing:border-box; width:100%; max-width:372px; margin-left:77px;"><?php echo get_text($memo); ?></textarea>
            </li>
        </ul>

        <?php if (isset($is_admin) && $is_admin) { ?>
        <div class="estimate-admin-status">
            <label for="status_val">吏꾪뻾?곹깭 (愿由ъ옄 ?꾩슜):</label>
            <select name="status_val" id="status_val">
                <option value="寃ъ쟻?묒닔" <?php echo get_selected($status_val, '寃ъ쟻?묒닔'); ?>>寃ъ쟻?묒닔</option>
                <option value="寃ъ쟻?뺤씤" <?php echo get_selected($status_val, '寃ъ쟻?뺤씤'); ?>>寃ъ쟻?뺤씤</option>
                <option value="寃ъ쟻?쒖텧" <?php echo get_selected($status_val, '寃ъ쟻?쒖텧'); ?>>寃ъ쟻?쒖텧</option>
            </select>
        </div>
        <?php } ?>

        <?php if ((!isset($w) || $w != 'u')) { ?>
        <div class="mi_agree notoSans">
            <div class="mi_personal">
                <p>
                  * 媛쒖씤?뺣낫 ?섏쭛/?댁슜 紐⑹쟻 : 怨좉컼臾몄쓽 ?곷떞?붿껌????섏뿬 ?뚯떊???섍굅?? ?곷떞???꾪븳 ?쒕퉬???댁슜湲곕줉議고쉶<br>
                  * ?섏쭛?섎뒗 媛쒖씤?뺣낫????ぉ : ?깊븿, ?곕씫泥?br>
                  ?살긽?댁삁?쎌꽌鍮꾩뒪 ?댁슜怨쇱젙?먯꽌 ?꾨옒? 媛숈? ?뺣낫?ㅼ씠 ?앹꽦?섏뼱 ?섏쭛?????덉뒿?덈떎.<br>
                  - ?쒕퉬?ㅼ씠?⑷린濡? ?묒냽濡쒓렇, 荑좏궎, ?묒냽IP?뺣낫<br>
                  * 媛쒖씤?뺣낫??蹂댁쑀 諛??댁슜湲곌컙<br>
                  -蹂댁〈湲곌컙? 5?꾩씠硫? ?뺣낫 ?쒓났?먭? ??젣瑜??붿껌??寃쎌슦 利됱떆 ?뚭린?⑸땲??<br>
                  -怨좉컼?섏쓽 ?뺣낫??媛쒖씤?뺣낫 蹂댄샇踰뺤뿉 ?곕씪 蹂댄샇?섎ŉ ?꾩쓽 ?ы빆 ?몄뿉 蹂꾨룄濡??ъ슜?섏? ?딆쓣 寃껋쓣 ?쎌냽?쒕┰?덈떎
                </p>
            </div>
            <div class="mi_check">
                <input type="checkbox" id="agree_chk" required="required">
                <label for="agree_chk">媛쒖씤?뺣낫泥섎━諛⑹묠 ?숈쓽</label>
            </div>
        </div>
        <?php } ?>

        <div class="btn_confirm write_div">
            <a href="<?php echo G5_BBS_URL; ?>/board.php?bo_table=<?php echo $bo_table; ?>" class="btn_cancel btn">痍⑥냼</a>
            <button type="submit" id="btn_submit" accesskey="s" class="btn_submit btn"><?php echo (isset($w) && $w == 'u') ? '?섏젙?꾨즺' : '鍮좊Ⅸ ?곷떞?섍린'; ?></button>
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
        var s1 = ["?꾪뙆??鍮뚮씪/?ㅼ꽭?","二쇳깮","?ㅽ뵾?ㅽ뀛/?먰닾猷?,"?щТ??,"怨듭옣","湲고?(?숆탳,愿怨듭꽌,泥댁쑁愿醫낃탳?쒖꽕 ??"];
        var s2 = ["?꾪뙆??鍮뚮씪/?ㅼ꽭?","二쇳깮","?ㅽ뵾?ㅽ뀛/?먰닾猷?,"?щТ??,"湲고?"];
        var s3 = ["?ㅽ뵾?ㅽ뀛","?먰닾猷?,"?щТ??,"鍮뚮씪","二쇳깮","?꾪뙆??]; 

        var selectItem = $("#wr_9_select").val();
        var changeItem;
          
        if(selectItem == "?ъ옣/諛섑룷?μ씠?? || selectItem == "?쇰컲?댁궗" || selectItem == "?댁쇅?댁궗"){            
          changeItem = s1;            
        }
        else if(selectItem == "蹂닿??댁궗"){
          changeItem = s2;
        }
        else if(selectItem == "?⑸떖?붾Ъ"){
          changeItem = s3;            
        } else {
          changeItem = [];
        }
         
        $('#wr_10_select').empty();
        if (changeItem.length > 0) {
            var optiona = $("<option value=''>?좏깮?섏꽭??/option>");
            $('#wr_10_select').append(optiona);
            $.each(changeItem, function( k, v ) {
              var option = $("<option value='"+v+"'>"+v+"</option>");
              $('#wr_10_select').append(option);
            }); 
        } else {
            var optiona = $("<option value=''>?댁궗醫낅쪟瑜??좏깮?섏꽭??/option>");
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
        alert("?댁궗醫낅쪟瑜??좏깮??二쇱꽭??");
        f.move_type.focus();
        return false;
    }
    if (!f.housing_type.value) {
        alert("二쇨굅?뺥깭瑜??좏깮??二쇱꽭??");
        f.housing_type.focus();
        return false;
    }
    if (!f.wr_name.value) {
        alert("怨좉컼紐낆쓣 ?낅젰??二쇱꽭??");
        f.wr_name.focus();
        return false;
    }
    if (!f.phone.value) {
        alert("?대???踰덊샇瑜??낅젰??二쇱꽭??");
        f.phone.focus();
        return false;
    }
    if (!f.move_date.value) {
        alert("?댁궗?좎쭨瑜??좏깮??二쇱꽭??");
        f.move_date.focus();
        return false;
    }
    if (!f.addr_out.value) {
        alert("異쒕컻吏 二쇱냼瑜??낅젰??二쇱꽭??");
        f.addr_out.focus();
        return false;
    }
    if (!f.addr_in.value) {
        alert("?꾩갑吏 二쇱냼瑜??낅젰??二쇱꽭??");
        f.addr_in.focus();
        return false;
    }
    if (!f.memo.value) {
        alert("異붽??ы빆???낅젰??二쇱꽭??");
        f.memo.focus();
        return false;
    }

    <?php if (!isset($w) || $w != 'u') { ?>
    if (!f.agree_chk.checked) {
        alert("媛쒖씤?뺣낫泥섎━諛⑹묠???숈쓽?섏뀛???좎껌??媛?ν빀?덈떎.");
        f.agree_chk.focus();
        return false;
    }
    <?php } ?>

    document.getElementById("btn_submit").disabled = "disabled";
    return true;
}
</script>
