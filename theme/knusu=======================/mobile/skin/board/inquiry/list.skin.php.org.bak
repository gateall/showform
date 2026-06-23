<?php
if (!defined('_GNUBOARD_')) exit; // 媛쒕퀎 ?섏씠吏 ?묎렐 遺덇?

// ?좏깮?듭뀡?쇰줈 ?명빐 ??⑹튂湲곌? 媛蹂?곸쑝濡?蹂??
$colspan = 5;

if ($is_checkbox) $colspan++;
if ($is_good) $colspan++;
if ($is_nogood) $colspan++;

// add_stylesheet('css 援щЦ', 異쒕젰?쒖꽌); ?レ옄媛 ?묒쓣 ?섎줉 癒쇱? 異쒕젰??
add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
?>

<!-- 寃뚯떆??紐⑸줉 ?쒖옉 { -->
<div id="bo_list" style="width:<?php echo $width; ?>">

    <!-- 寃뚯떆??移댄뀒怨좊━ ?쒖옉 { -->
    <?php if ($is_category) { ?>
    <nav id="bo_cate">
        <h2><?php echo $board['bo_subject'] ?> 移댄뀒怨좊━</h2>
        <ul id="bo_cate_ul">
            <?php echo $category_option ?>
        </ul>
    </nav>
    <?php } ?>
    <!-- } 寃뚯떆??移댄뀒怨좊━ ??-->
    
    <form name="fboardlist" id="fboardlist" action="<?php echo G5_BBS_URL; ?>/board_list_update.php" onsubmit="return fboardlist_submit(this);" method="post">
    
    <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
    <input type="hidden" name="sfl" value="<?php echo $sfl ?>">
    <input type="hidden" name="stx" value="<?php echo $stx ?>">
    <input type="hidden" name="spt" value="<?php echo $spt ?>">
    <input type="hidden" name="sca" value="<?php echo $sca ?>">
    <input type="hidden" name="sst" value="<?php echo $sst ?>">
    <input type="hidden" name="sod" value="<?php echo $sod ?>">
    <input type="hidden" name="page" value="<?php echo $page ?>">
    <input type="hidden" name="sw" value="">

    <!-- 寃뚯떆???섏씠吏 ?뺣낫 諛?踰꾪듉 ?쒖옉 { -->
    <div id="bo_btn_top">
        <div id="bo_list_total">
            <span>Total <?php echo number_format($total_count) ?>嫄?/span>
            <?php echo $page ?> ?섏씠吏
        </div>

        <ul class="btn_bo_user">
        	<?php if ($admin_href) { ?><li><a href="<?php echo $admin_href ?>" class="btn_admin btn" title="愿由ъ옄"><i class="fa fa-cog fa-spin fa-fw"></i><span class="sound_only">愿由ъ옄</span></a></li><?php } ?>
            <?php if ($rss_href) { ?><li><a href="<?php echo $rss_href ?>" class="btn_b01 btn" title="RSS"><i class="fa fa-rss" aria-hidden="true"></i><span class="sound_only">RSS</span></a></li><?php } ?>
            <li>
            	<button type="button" class="btn_bo_sch btn_b01 btn" title="寃뚯떆??寃??><i class="fa fa-search" aria-hidden="true"></i><span class="sound_only">寃뚯떆??寃??/span></button>
            </li>
            <?php if ($write_href) { ?><li><a href="<?php echo $write_href ?>" class="btn_b01 btn" title="湲?곌린"><i class="fa fa-pencil" aria-hidden="true"></i><span class="sound_only">湲?곌린</span></a></li><?php } ?>
        	<?php if ($is_admin == 'super' || $is_auth) {  ?>
        	<li>
        		<button type="button" class="btn_more_opt is_list_btn btn_b01 btn" title="寃뚯떆??由ъ뒪???듭뀡"><i class="fa fa-ellipsis-v" aria-hidden="true"></i><span class="sound_only">寃뚯떆??由ъ뒪???듭뀡</span></button>
        		<?php if ($is_checkbox) { ?>	
		        <ul class="more_opt is_list_btn">  
		            <li><button type="submit" name="btn_submit" value="?좏깮??젣" onclick="document.pressed=this.value"><i class="fa fa-trash-o" aria-hidden="true"></i> ?좏깮??젣</button></li>
		            <li><button type="submit" name="btn_submit" value="?좏깮蹂듭궗" onclick="document.pressed=this.value"><i class="fa fa-files-o" aria-hidden="true"></i> ?좏깮蹂듭궗</button></li>
		            <li><button type="submit" name="btn_submit" value="?좏깮?대룞" onclick="document.pressed=this.value"><i class="fa fa-arrows" aria-hidden="true"></i> ?좏깮?대룞</button></li>
		        </ul>
		        <?php } ?>
        	</li>
        	<?php }  ?>
        </ul>
    </div>
    <!-- } 寃뚯떆???섏씠吏 ?뺣낫 諛?踰꾪듉 ??-->
        	
    <div class="tbl_head01 tbl_wrap">
        <table>
        <caption><?php echo $board['bo_subject'] ?> 紐⑸줉</caption>
        <thead>
        <tr>
            <?php if ($is_checkbox) { ?>
            <th scope="col" class="all_chk chk_box">
            	<input type="checkbox" id="chkall" onclick="if (this.checked) all_checked(true); else all_checked(false);" class="selec_chk">
                <label for="chkall">
                	<span></span>
                	<b class="sound_only">?꾩옱 ?섏씠吏 寃뚯떆臾? ?꾩껜?좏깮</b>
				</label>
            </th>
            <?php } ?>
            <th scope="col">踰덊샇</th>
            <th scope="col">?쒕ぉ</th>
            <th scope="col">湲?댁씠</th>
            <th scope="col"><?php echo subject_sort_link('wr_hit', $qstr2, 1) ?>議고쉶 </a></th>
            <?php if ($is_good) { ?><th scope="col"><?php echo subject_sort_link('wr_good', $qstr2, 1) ?>異붿쿇 </a></th><?php } ?>
            <?php if ($is_nogood) { ?><th scope="col"><?php echo subject_sort_link('wr_nogood', $qstr2, 1) ?>鍮꾩텛泥?</a></th><?php } ?>
            <th scope="col"><?php echo subject_sort_link('wr_datetime', $qstr2, 1) ?>?좎쭨  </a></th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $i<count($list); $i++) {
        	if ($i%2==0) $lt_class = "even";
        	else $lt_class = "";
		?>
        <tr class="<?php if ($list[$i]['is_notice']) echo "bo_notice"; ?> <?php echo $lt_class ?>">
            <?php if ($is_checkbox) { ?>
            <td class="td_chk chk_box">
				<input type="checkbox" name="chk_wr_id[]" value="<?php echo $list[$i]['wr_id'] ?>" id="chk_wr_id_<?php echo $i ?>" class="selec_chk">
            	<label for="chk_wr_id_<?php echo $i ?>">
            		<span></span>
            		<b class="sound_only"><?php echo $list[$i]['subject'] ?></b>
            	</label>
            </td>
            <?php } ?>
            <td class="td_num2">
            <?php
            if ($list[$i]['is_notice']) // 怨듭??ы빆
                echo '<strong class="notice_icon">怨듭?</strong>';
            else if ($wr_id == $list[$i]['wr_id'])
                echo "<span class=\"bo_current\">?대엺以?/span>";
            else
                echo $list[$i]['num'];
             ?>
            </td>

            <td class="td_subject" style="padding-left:<?php echo $list[$i]['reply'] ? (strlen($list[$i]['wr_reply'])*10) : '0'; ?>px">
                <?php
                if ($is_category && $list[$i]['ca_name']) {
				?>
                <a href="<?php echo $list[$i]['ca_name_href'] ?>" class="bo_cate_link"><?php echo $list[$i]['ca_name'] ?></a>
                <?php } ?>
                <div class="bo_tit">
                    <a href="<?php echo $list[$i]['href'] ?>"><?php
                if ($bo_table == 'inquiry') {
                    $status_text = isset($list[$i]['wr_8']) && trim((string)$list[$i]['wr_8']) !== '' ? trim((string)$list[$i]['wr_8']) : '견적접수';
                    $status_color = '#01b6eb';
                    if ($status_text === '견적확인') $status_color = '#f39c12';
                    elseif ($status_text === '견적제출') $status_color = '#073567';
                    echo '<span style="display:inline-block; padding:2px 6px; font-size:11px; color:#fff; background-color:'.$status_color.'; border-radius:3px; vertical-align:middle; margin-right:5px;">'.$status_text.'</span>';
                }
                ?>
                        <?php echo $list[$i]['icon_reply'] ?>
                        <?php
                            if (isset($list[$i]['icon_secret'])) echo rtrim($list[$i]['icon_secret']);
                         ?>
                        <?php echo $list[$i]['subject'] ?>
                    </a>
                    <?php
                    if ($list[$i]['icon_new']) echo "<span class=\"new_icon\">N<span class=\"sound_only\">?덇?</span></span>";
                    // if ($list[$i]['file']['count']) { echo '<'.$list[$i]['file']['count'].'>'; }
                    if (isset($list[$i]['icon_hot'])) echo rtrim($list[$i]['icon_hot']);
                    if (isset($list[$i]['icon_file'])) echo rtrim($list[$i]['icon_file']);
                    if (isset($list[$i]['icon_link'])) echo rtrim($list[$i]['icon_link']);
                    ?>
                    <?php if ($list[$i]['comment_cnt']) { ?><span class="sound_only">?볤?</span><span class="cnt_cmt"><?php echo $list[$i]['wr_comment']; ?></span><span class="sound_only">媛?/span><?php } ?>
                </div>
            </td>
            <td class="td_name sv_use"><?php echo $list[$i]['name'] ?></td>
            <td class="td_num"><?php echo $list[$i]['wr_hit'] ?></td>
            <?php if ($is_good) { ?><td class="td_num"><?php echo $list[$i]['wr_good'] ?></td><?php } ?>
            <?php if ($is_nogood) { ?><td class="td_num"><?php echo $list[$i]['wr_nogood'] ?></td><?php } ?>
            <td class="td_datetime"><?php echo $list[$i]['datetime2'] ?></td>

        </tr>
        <?php } ?>
        <?php if (count($list) == 0) { echo '<tr><td colspan="'.$colspan.'" class="empty_table">寃뚯떆臾쇱씠 ?놁뒿?덈떎.</td></tr>'; } ?>
        </tbody>
        </table>
    </div>
	<!-- ?섏씠吏 -->
	<?php echo $write_pages; ?>
	<!-- ?섏씠吏 -->
	
    <?php if ($list_href || $is_checkbox || $write_href) { ?>
    <div class="bo_fx">
        <?php if ($list_href || $write_href) { ?>
        <ul class="btn_bo_user">
        	<?php if ($admin_href) { ?><li><a href="<?php echo $admin_href ?>" class="btn_admin btn" title="愿由ъ옄"><i class="fa fa-cog fa-spin fa-fw"></i><span class="sound_only">愿由ъ옄</span></a></li><?php } ?>
            <?php if ($rss_href) { ?><li><a href="<?php echo $rss_href ?>" class="btn_b01 btn" title="RSS"><i class="fa fa-rss" aria-hidden="true"></i><span class="sound_only">RSS</span></a></li><?php } ?>
            <?php if ($write_href) { ?><li><a href="<?php echo $write_href ?>" class="btn_b01 btn" title="湲?곌린"><i class="fa fa-pencil" aria-hidden="true"></i><span class="sound_only">湲?곌린</span></a></li><?php } ?>
        </ul>	
        <?php } ?>
    </div>
    <?php } ?>   
    </form>

    <!-- 寃뚯떆??寃???쒖옉 { -->
    <div class="bo_sch_wrap">
        <fieldset class="bo_sch">
            <h3>寃??/h3>
            <form name="fsearch" method="get">
            <input type="hidden" name="bo_table" value="<?php echo $bo_table ?>">
            <input type="hidden" name="sca" value="<?php echo $sca ?>">
            <input type="hidden" name="sop" value="and">
            <label for="sfl" class="sound_only">寃?됰???/label>
            <select name="sfl" id="sfl">
                <?php echo get_board_sfl_select_options($sfl); ?>
            </select>
            <label for="stx" class="sound_only">寃?됱뼱<strong class="sound_only"> ?꾩닔</strong></label>
            <div class="sch_bar">
                <input type="text" name="stx" value="<?php echo stripslashes($stx) ?>" required id="stx" class="sch_input" size="25" maxlength="20" placeholder=" 寃?됱뼱瑜??낅젰?댁＜?몄슂">
                <button type="submit" value="寃?? class="sch_btn"><i class="fa fa-search" aria-hidden="true"></i><span class="sound_only">寃??/span></button>
            </div>
            <button type="button" class="bo_sch_cls" title="?リ린"><i class="fa fa-times" aria-hidden="true"></i><span class="sound_only">?リ린</span></button>
            </form>
        </fieldset>
        <div class="bo_sch_bg"></div>
    </div>
    <script>
    jQuery(function($){
        // 寃뚯떆??寃??
        $(".btn_bo_sch").on("click", function() {
            $(".bo_sch_wrap").toggle();
        })
        $('.bo_sch_bg, .bo_sch_cls').click(function(){
            $('.bo_sch_wrap').hide();
        });
    });
    </script>
    <!-- } 寃뚯떆??寃????--> 
</div>

<?php if($is_checkbox) { ?>
<noscript>
<p>?먮컮?ㅽ겕由쏀듃瑜??ъ슜?섏? ?딅뒗 寃쎌슦<br>蹂꾨룄???뺤씤 ?덉감 ?놁씠 諛붾줈 ?좏깮??젣 泥섎━?섎?濡?二쇱쓽?섏떆湲?諛붾엻?덈떎.</p>
</noscript>
<?php } ?>

<?php if ($is_checkbox) { ?>
<script>
function all_checked(sw) {
    var f = document.fboardlist;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_wr_id[]")
            f.elements[i].checked = sw;
    }
}

function fboardlist_submit(f) {
    var chk_count = 0;

    for (var i=0; i<f.length; i++) {
        if (f.elements[i].name == "chk_wr_id[]" && f.elements[i].checked)
            chk_count++;
    }

    if (!chk_count) {
        alert(document.pressed + "??寃뚯떆臾쇱쓣 ?섎굹 ?댁긽 ?좏깮?섏꽭??");
        return false;
    }

    if(document.pressed == "?좏깮蹂듭궗") {
        select_copy("copy");
        return;
    }

    if(document.pressed == "?좏깮?대룞") {
        select_copy("move");
        return;
    }

    if(document.pressed == "?좏깮??젣") {
        if (!confirm("?좏깮??寃뚯떆臾쇱쓣 ?뺣쭚 ??젣?섏떆寃좎뒿?덇퉴?\n\n?쒕쾲 ??젣???먮즺??蹂듦뎄?????놁뒿?덈떎\n\n?듬?湲???덈뒗 寃뚯떆湲???좏깮?섏떊 寃쎌슦\n?듬?湲???좏깮?섏뀛??寃뚯떆湲????젣?⑸땲??"))
            return false;

        f.removeAttribute("target");
        f.action = g5_bbs_url+"/board_list_update.php";
    }

    return true;
}

// ?좏깮??寃뚯떆臾?蹂듭궗 諛??대룞
function select_copy(sw) {
    var f = document.fboardlist;

    if (sw == "copy")
        str = "蹂듭궗";
    else
        str = "?대룞";

    var sub_win = window.open("", "move", "left=50, top=50, width=500, height=550, scrollbars=1");

    f.sw.value = sw;
    f.target = "move";
    f.action = g5_bbs_url+"/move.php";
    f.submit();
}

// 寃뚯떆??由ъ뒪??愿由ъ옄 ?듭뀡
jQuery(function($){
    $(".btn_more_opt.is_list_btn").on("click", function(e) {
        e.stopPropagation();
        $(".more_opt.is_list_btn").toggle();
    });
    $(document).on("click", function (e) {
        if(!$(e.target).closest('.is_list_btn').length) {
            $(".more_opt.is_list_btn").hide();
        }
    });
});
</script>
<?php } ?>
<!-- } 寃뚯떆??紐⑸줉 ??-->

