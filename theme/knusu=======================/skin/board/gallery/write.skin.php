<?php
if (!defined('_GNUBOARD_')) exit; // 媛쒕퀎 ?섏씠吏 ?묎렐 遺덇?

// add_stylesheet('css 援щЦ', 異쒕젰?쒖꽌); ?レ옄媛 ?묒쓣 ?섎줉 癒쇱? 異쒕젰??
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
?>

<section id="bo_w">
    <h2 class="sound_only"><?php echo $g5['title'] ?></h2>

    <!-- 寃뚯떆臾??묒꽦/?섏젙 ?쒖옉 { -->
    <form name="fwrite" id="fwrite" action="<?php echo $action_url ?>" onsubmit="return fwrite_submit(this);" method="post" enctype="multipart/form-data" autocomplete="off" style="width:<?php echo $width; ?>">
    <input type="hidden" name="uid" value="<?php echo get_uniqid(); ?>">
    <input type="hidden" name="w" value="<?php echo $w ?>">
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="wr_id" value="<?php echo $wr_id ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <?php
    $option = '';
    $option_hidden = '';
    if ($is_notice || $is_html || $is_mail) { 
        $option = '';
        if ($is_notice) {
            $option .= PHP_EOL.'<li class="chk_box"><input type="checkbox" id="notice" name="notice"  class="selec_chk" value="1" '.$notice_checked.'>'.PHP_EOL.'<label for="notice"><span></span>怨듭?</label></li>';
        }
        if ($is_html) {
            if ($is_dhtml_editor) {
                $option_hidden .= '<input type="hidden" value="html1" name="html">';
            } else {
                $option .= PHP_EOL.'<li class="chk_box"><input type="checkbox" id="html" name="html" onclick="html_auto_br(this);" class="selec_chk" value="'.$html_value.'" '.$html_checked.'>'.PHP_EOL.'<label for="html"><span></span>html</label></li>';
            }
        }

        if ($is_mail) {
            $option .= PHP_EOL.'<li class="chk_box"><input type="checkbox" id="mail" name="mail"  class="selec_chk" value="mail" '.$recv_email_checked.'>'.PHP_EOL.'<label for="mail"><span></span>?듬?硫붿씪諛쏄린</label></li>';
        }
    }
    echo $option_hidden;
    ?>

    <?php if ($is_category) { ?>
    <div class="bo_w_select write_div">
        <label for="ca_name" class="sound_only">遺꾨쪟<strong>?꾩닔</strong></label>
        <select name="ca_name" id="ca_name" required>
            <option value="">遺꾨쪟瑜??좏깮?섏꽭??/option>
            <?php echo $category_option ?>
        </select>
    </div>
    <?php } ?>

    <div class="bo_w_info write_div">
	    <?php if ($is_name) { ?>
	        <label for="wr_name" class="sound_only">?대쫫<strong>?꾩닔</strong></label>
	        <input type="text" name="wr_name" value="<?php echo $name ?>" id="wr_name" required class="frm_input half_input required" placeholder="?대쫫">
	    <?php } ?>
	
	    <?php if ($is_password) { ?>
	        <label for="wr_password" class="sound_only">鍮꾨?踰덊샇<strong>?꾩닔</strong></label>
	        <input type="password" name="wr_password" id="wr_password" <?php echo $password_required ?> class="frm_input half_input <?php echo $password_required ?>" placeholder="鍮꾨?踰덊샇">
	    <?php } ?>
	
	    <?php if ($is_email) { ?>
			<label for="wr_email" class="sound_only">?대찓??/label>
			<input type="text" name="wr_email" value="<?php echo $email ?>" id="wr_email" class="frm_input half_input email " placeholder="?대찓??>
	    <?php } ?>
	    
	    <?php if ($is_homepage) { ?>
	        <label for="wr_homepage" class="sound_only">?덊럹?댁?</label>
	        <input type="text" name="wr_homepage" value="<?php echo $homepage ?>" id="wr_homepage" class="frm_input half_input" size="50" placeholder="?덊럹?댁?">
	    <?php } ?>
	</div>
	
    <?php if ($option) { ?>
    <div class="write_div">
        <span class="sound_only">?듭뀡</span>
        <ul class="bo_v_option">
        <?php echo $option ?>
        </ul>
    </div>
    <?php } ?>

    <div class="bo_w_tit write_div">
        <label for="wr_subject" class="sound_only">?쒕ぉ<strong>?꾩닔</strong></label>
        
        <div id="autosave_wrapper" class="write_div">
            <input type="text" name="wr_subject" value="<?php echo $subject ?>" id="wr_subject" required class="frm_input full_input required" size="50" maxlength="255" placeholder="?쒕ぉ">
            <?php if ($is_member) { // ?꾩떆 ??λ맂 湲 湲곕뒫 ?>
            <script src="<?php echo G5_JS_URL; ?>/autosave.js"></script>
            <?php if($editor_content_js) echo $editor_content_js; ?>
            <button type="button" id="btn_autosave" class="btn_frmline">?꾩떆 ??λ맂 湲 (<span id="autosave_count"><?php echo $autosave_count; ?></span>)</button>
            <div id="autosave_pop">
                <strong>?꾩떆 ??λ맂 湲 紐⑸줉</strong>
                <ul></ul>
                <div><button type="button" class="autosave_close">?リ린</button></div>
            </div>
            <?php } ?>
        </div>
    </div>

    <div class="write_div">
        <label for="wr_content" class="sound_only">?댁슜<strong>?꾩닔</strong></label>
        <div class="wr_content <?php echo $is_dhtml_editor ? $config['cf_editor'] : ''; ?>">
            <?php if($write_min || $write_max) { ?>
            <!-- 理쒖냼/理쒕? 湲?????ъ슜 ??-->
            <p id="char_count_desc">??寃뚯떆?먯? 理쒖냼 <strong><?php echo $write_min; ?></strong>湲???댁긽, 理쒕? <strong><?php echo $write_max; ?></strong>湲???댄븯源뚯? 湲???곗떎 ???덉뒿?덈떎.</p>
            <?php } ?>
            <?php echo $editor_html; // ?먮뵒???ъ슜?쒕뒗 ?먮뵒?곕줈, ?꾨땲硫?textarea 濡??몄텧 ?>
            <?php if($write_min || $write_max) { ?>
            <!-- 理쒖냼/理쒕? 湲?????ъ슜 ??-->
            <div id="char_count_wrap"><span id="char_count"></span>湲??/div>
            <?php } ?>
        </div>
        
    </div>

    <?php for ($i=1; $is_link && $i<=G5_LINK_COUNT; $i++) { ?>
    <div class="bo_w_link write_div">
        <label for="wr_link<?php echo $i ?>"><i class="fa fa-link" aria-hidden="true"></i><span class="sound_only"> 留곹겕  #<?php echo $i ?></span></label>
        <input type="text" name="wr_link<?php echo $i ?>" value="<?php if($w=="u"){ echo $write['wr_link'.$i]; } ?>" id="wr_link<?php echo $i ?>" class="frm_input full_input" size="50">
    </div>
    <?php } ?>

    <?php for ($i=0; $is_file && $i<$file_count; $i++) { ?>
    <div class="bo_w_flie write_div">
        <div class="file_wr write_div">
            <label for="bf_file_<?php echo $i+1 ?>" class="lb_icon"><i class="fa fa-folder-open" aria-hidden="true"></i><span class="sound_only"> ?뚯씪 #<?php echo $i+1 ?></span></label>
            <input type="file" name="bf_file[]" id="bf_file_<?php echo $i+1 ?>" title="?뚯씪泥⑤? <?php echo $i+1 ?> : ?⑸웾 <?php echo $upload_max_filesize ?> ?댄븯留??낅줈??媛?? class="frm_file ">
        </div>
        <?php if ($is_file_content) { ?>
        <input type="text" name="bf_content[]" value="<?php echo ($w == 'u') ? $file[$i]['bf_content'] : ''; ?>" title="?뚯씪 ?ㅻ챸???낅젰?댁＜?몄슂." class="full_input frm_input" size="50" placeholder="?뚯씪 ?ㅻ챸???낅젰?댁＜?몄슂.">
        <?php } ?>

        <?php if($w == 'u' && $file[$i]['file']) { ?>
        <span class="file_del">
            <input type="checkbox" id="bf_file_del<?php echo $i ?>" name="bf_file_del[<?php echo $i;  ?>]" value="1"> <label for="bf_file_del<?php echo $i ?>"><?php echo $file[$i]['source'].'('.$file[$i]['size'].')';  ?> ?뚯씪 ??젣</label>
        </span>
        <?php } ?>
        
    </div>
    <?php } ?>


    <?php if ($is_use_captcha) { //?먮룞?깅줉諛⑹?  ?>
    <div class="write_div">
        <?php echo $captcha_html ?>
    </div>
    <?php } ?>

    <div class="btn_confirm write_div">
        <a href="<?php echo get_pretty_url($bo_table); ?>" class="btn_cancel btn">痍⑥냼</a>
        <button type="submit" id="btn_submit" accesskey="s" class="btn_submit btn">?묒꽦?꾨즺</button>
    </div>
    </form>

    <script>
    <?php if($write_min || $write_max) { ?>
    // 湲?먯닔 ?쒗븳
    var char_min = parseInt(<?php echo $write_min; ?>); // 理쒖냼
    var char_max = parseInt(<?php echo $write_max; ?>); // 理쒕?
    check_byte("wr_content", "char_count");

    $(function() {
        $("#wr_content").on("keyup", function() {
            check_byte("wr_content", "char_count");
        });
    });

    <?php } ?>
    function html_auto_br(obj)
    {
        if (obj.checked) {
            result = confirm("?먮룞 以꾨컮轅덉쓣 ?섏떆寃좎뒿?덇퉴?\n\n?먮룞 以꾨컮轅덉? 寃뚯떆臾??댁슜以?以꾨컮??怨녹쓣<br>?쒓렇濡?蹂?섑븯??湲곕뒫?낅땲??");
            if (result)
                obj.value = "html2";
            else
                obj.value = "html1";
        }
        else
            obj.value = "";
    }

    function fwrite_submit(f)
    {
        <?php echo $editor_js; // ?먮뵒???ъ슜???먮컮?ㅽ겕由쏀듃?먯꽌 ?댁슜???쇳븘?쒕줈 ?ｌ뼱二쇰ŉ ?댁슜???낅젰?섏뿀?붿? 寃?ы븿   ?>

        var subject = "";
        var content = "";
        $.ajax({
            url: g5_bbs_url+"/ajax.filter.php",
            type: "POST",
            data: {
                "subject": f.wr_subject.value,
                "content": f.wr_content.value
            },
            dataType: "json",
            async: false,
            cache: false,
            success: function(data, textStatus) {
                subject = data.subject;
                content = data.content;
            }
        });

        if (subject) {
            alert("?쒕ぉ??湲덉??⑥뼱('"+subject+"')媛 ?ы븿?섏뼱?덉뒿?덈떎");
            f.wr_subject.focus();
            return false;
        }

        if (content) {
            alert("?댁슜??湲덉??⑥뼱('"+content+"')媛 ?ы븿?섏뼱?덉뒿?덈떎");
            if (typeof(ed_wr_content) != "undefined")
                ed_wr_content.returnFalse();
            else
                f.wr_content.focus();
            return false;
        }

        if (document.getElementById("char_count")) {
            if (char_min > 0 || char_max > 0) {
                var cnt = parseInt(check_byte("wr_content", "char_count"));
                if (char_min > 0 && char_min > cnt) {
                    alert("?댁슜? "+char_min+"湲???댁긽 ?곗뀛???⑸땲??");
                    return false;
                }
                else if (char_max > 0 && char_max < cnt) {
                    alert("?댁슜? "+char_max+"湲???댄븯濡??곗뀛???⑸땲??");
                    return false;
                }
            }
        }

        <?php echo $captcha_js; // 罹≪콬 ?ъ슜???먮컮?ㅽ겕由쏀듃?먯꽌 ?낅젰??罹≪콬瑜?寃?ы븿  ?>

        document.getElementById("btn_submit").disabled = "disabled";

        return true;
    }
    </script>
</section>
<!-- } 寃뚯떆臾??묒꽦/?섏젙 ??-->
