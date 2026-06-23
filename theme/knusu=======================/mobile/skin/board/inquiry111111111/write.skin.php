<?php
if (!defined('_GNUBOARD_')) exit; // 媛쒕퀎 ?섏씠吏 ?묎렐 遺덇?

add_stylesheet('<link rel="stylesheet" href="'.$board_skin_url.'/style.css">', 0);
?>

<section id="bo_w">
    <h2 class="sound_only"><?php echo $g5['title'] ?></h2>

    <div class="estimate-write-wrap">
        <?php
        include_once(G5_PATH.'/skin/board/inquiry/estimate_form.inc.php');
        ?>
    </div>
</section>

<style>
.estimate-write-wrap{width:100%;max-width:900px;margin:0 auto;padding:30px 20px;box-sizing:border-box;}
.estimate-write-wrap *{box-sizing:border-box;}
.estimate-write-wrap form{width:100%;max-width:720px;margin:0 auto;}

/* ???대? ?덉씠?꾩썐 諛??곗륫 怨듬갚 ?닿껐???꾪븳 ?ㅽ???蹂댁셿 */
.estimate-write-wrap .ms_inquiry{position:relative;}
.estimate-write-wrap .ms_inquiry ul{padding:0;margin:0;list-style:none;}
.estimate-write-wrap .ms_inquiry li{position:relative;margin-bottom:2px;min-height:36px;}

/* ?쇰꺼 absolute 諛곗튂 ?댁젣 諛??뺣젹 媛뺤젣 */
.estimate-write-wrap .ms_inquiry ul > li > label{
    position:absolute !important;
    left:0;
    top:0;
    width:90px !important;
    height:36px;
    line-height:36px !important;
    background:url(/img/form_bar.png) no-repeat center left !important;
    padding:0 0 0 12px !important;
    margin:0 !important;
    display:inline-block !important;
    letter-spacing:0 !important;
}

/* ?낅젰 ?꾨뱶 ?덈퉬 ?뺤옣?섏뿬 ?곗륫 怨듬갚 ?쒓굅 */
.estimate-write-wrap .ms_inquiry input[type="text"], 
.estimate-write-wrap .ms_inquiry input[type="tel"], 
.estimate-write-wrap .ms_inquiry textarea {
    width: calc(100% - 100px) !important;
    max-width: 520px !important;
    margin-left: 100px !important;
    height: 36px !important;
}
.estimate-write-wrap .ms_inquiry textarea{
    height:80px !important;
}
.estimate-write-wrap .ms_inquiry select {
    width: calc(50% - 53px) !important;
    max-width: 258px !important;
    margin-left: 100px !important;
    height: 36px !important;
    padding:4px 6px;
}
.estimate-write-wrap .ms_inquiry select + select {
    margin-left: 5px !important;
}

/* ?댁궗?좎쭨 datepicker ?뺣젹 */
.estimate-write-wrap .ms_inquiry #wr_2_datepicker {
    width: calc(100% - 130px) !important;
    max-width: 485px !important;
    margin-left: 100px !important;
    display: inline-block !important;
    vertical-align: middle;
}
.estimate-write-wrap .ui-datepicker-trigger {
    vertical-align: middle;
    margin-left: 6px;
    cursor: pointer;
    height: 28px;
}

/* 異쒕컻吏/?꾩갑吏 二쇱냼 ?뺣낫 ?뺣젹 */
.estimate-write-wrap .ms_inquiry input[name="addr_out"],
.estimate-write-wrap .ms_inquiry input[name="addr_in"] {
    width: calc(100% - 310px) !important;
    max-width: 310px !important;
    margin-left: 100px !important;
    display: inline-block !important;
    vertical-align: middle;
}
.estimate-write-wrap .ms_inquiry input[name="pyung_out"],
.estimate-write-wrap .ms_inquiry input[name="pyung_in"],
.estimate-write-wrap .ms_inquiry input[name="floor_out"],
.estimate-write-wrap .ms_inquiry input[name="floor_in"],
.estimate-write-wrap .ms_inquiry input[name="ho_out"],
.estimate-write-wrap .ms_inquiry input[name="ho_in"] {
    width: 58px !important;
    margin-left: 4px !important;
    display: inline-block !important;
    vertical-align: middle;
    text-align: center;
}
.estimate-write-wrap .elevator-check-wrap {
    width: calc(100% - 100px) !important;
    max-width: 520px !important;
    margin-left: 100px !important;
    margin-top: 6px;
    margin-bottom: 6px;
    padding: 0;
}
.estimate-write-wrap .furniture-check-wrap {
    width: calc(100% - 100px) !important;
    max-width: 520px !important;
    margin-left: 100px !important;
    margin-bottom: 15px !important;
    padding: 0;
    white-space: normal !important;
}

/* 吏꾪뻾?곹깭 愿由ъ옄 ?꾩슜 select ?곸뿭 湲??寃뱀묠 諛⑹? 諛??대┃ ?닿껐 */
.estimate-admin-status{
    width:100%;
    max-width:520px;
    margin:30px auto 0;
    padding:20px;
    border-top:1px dashed #ccc;
    text-align:center;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:12px;
    position:relative;
    z-index:999;
}
.estimate-admin-status label{
    position:static !important;
    display:inline-block !important;
    background:none !important;
    padding:0 !important;
    font-weight:bold !important;
    color:#073567 !important;
    margin:0 !important;
    line-height:40px !important;
    white-space:nowrap !important;
    letter-spacing:0 !important;
}
.estimate-admin-status select{
    width:180px;
    height:40px;
    padding:0 12px;
    font-size:14px;
    border:1px solid #073567;
    background:#fff;
    color:#222;
    position:relative;
    z-index:1000;
    pointer-events:auto;
    cursor:pointer;
}

/* 痍⑥냼 諛??섏젙?꾨즺 踰꾪듉 ?뺣젹 */
.btn_confirm.write_div{width:100%;display:flex;align-items:center;justify-content:center;gap:10px;margin:30px auto 0;text-align:center;}
.btn_confirm.write_div .btn,.btn_confirm.write_div button,.btn_confirm.write_div a{display:inline-flex!important;align-items:center;justify-content:center;min-width:130px;height:48px;padding:0 34px;border:0;border-radius:5px;font-size:16px;font-weight:bold;line-height:1;text-decoration:none;cursor:pointer;box-sizing:border-box;vertical-align:middle;}
.btn_confirm.write_div .btn_cancel{background:#767676;color:#fff;}
.btn_confirm.write_div .btn_submit{background:#073567;color:#fff;}
.btn_confirm.write_div .btn:hover{opacity:.9;}

@media (max-width:768px){
    .estimate-write-wrap{padding:20px 12px;}
    .estimate-write-wrap form{max-width:100%;}
    
    .estimate-write-wrap .ms_inquiry ul > li > label {
        position: relative !important;
        width: 100% !important;
        margin-bottom: 5px !important;
        line-height: 1.4 !important;
        background: none !important;
        padding-left: 0 !important;
        display: block !important;
    }
    .estimate-write-wrap .ms_inquiry input[type="text"], 
    .estimate-write-wrap .ms_inquiry input[type="tel"], 
    .estimate-write-wrap .ms_inquiry textarea,
    .estimate-write-wrap .ms_inquiry select {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-top: 5px !important;
    }
    .estimate-write-wrap .ms_inquiry select + select {
        margin-top: 6px !important;
        margin-left: 0 !important;
    }
    .estimate-write-wrap .ms_inquiry #wr_2_datepicker {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
    }
    .estimate-write-wrap .ms_inquiry input[name="addr_out"],
    .estimate-write-wrap .ms_inquiry input[name="addr_in"] {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-bottom: 6px !important;
    }
    .estimate-write-wrap .ms_inquiry input[name="pyung_out"],
    .estimate-write-wrap .ms_inquiry input[name="pyung_in"],
    .estimate-write-wrap .ms_inquiry input[name="floor_out"],
    .estimate-write-wrap .ms_inquiry input[name="floor_in"],
    .estimate-write-wrap .ms_inquiry input[name="ho_out"],
    .estimate-write-wrap .ms_inquiry input[name="ho_in"] {
        width: calc(33.3% - 4px) !important;
        margin-left: 0 !important;
        margin-bottom: 6px !important;
        display: inline-block !important;
    }
    .estimate-write-wrap .ms_inquiry input[name="pyung_out"] + input,
    .estimate-write-wrap .ms_inquiry input[name="pyung_in"] + input,
    .estimate-write-wrap .ms_inquiry input[name="floor_out"] + input,
    .estimate-write-wrap .ms_inquiry input[name="floor_in"] + input {
        margin-left: 6px !important;
    }

    /* 媛援?媛濡?3媛?諛곗튂 諛섏쓳??*/
    .estimate-write-wrap .furniture-check-wrap {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-bottom: 25px !important;
        display: flex !important;
        flex-wrap: wrap !important;
        gap: 6px 0 !important;
    }
    .estimate-write-wrap .ms_inquiry li.mi_tel .furniture-check-wrap label {
        width: 33.333% !important;
        display: inline-flex !important;
        align-items: center !important;
        box-sizing: border-box !important;
        margin: 0 !important;
        margin-right: 0px !important;
        margin-left: 0px !important;
        padding: 0 !important;
        position: static !important;
        background: none !important;
    }
    .estimate-write-wrap .furniture-check-wrap input[type="checkbox"] {
        margin-right: 4px !important;
        width: 14px !important;
        height: 14px !important;
        vertical-align: middle !important;
    }

    /* ?섎━踰좎씠????以??뺣젹 諛섏쓳??*/
    .estimate-write-wrap .elevator-check-wrap {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-top: 6px !important;
        margin-bottom: 12px !important;
        display: flex !important;
        align-items: center !important;
        flex-wrap: nowrap !important;
        gap: 8px !important;
    }
    .estimate-write-wrap .elevator-check-wrap span {
        display: inline-block !important;
        margin-bottom: 0 !important;
        margin-right: 8px !important;
        white-space: nowrap !important;
        font-weight: 600 !important;
    }
    .estimate-write-wrap .elevator-check-wrap label {
        display: inline-flex !important;
        align-items: center !important;
        margin: 0 4px 0 0 !important;
        white-space: nowrap !important;
        position: static !important;
        background: none !important;
        padding: 0 !important;
        width: auto !important;
    }
    .estimate-write-wrap .elevator-check-wrap input[type="checkbox"] {
        margin-right: 4px !important;
        width: 16px !important;
        height: 16px !important;
        vertical-align: middle !important;
    }
    
    .estimate-admin-status{max-width:100%;padding:16px 12px;flex-direction:column;gap:8px;}
    .estimate-admin-status label{line-height:1.4 !important;text-align:center;}
    .estimate-admin-status select{width:100%;max-width:260px;}
    .btn_confirm.write_div{flex-direction:column;gap:8px;}
    .btn_confirm.write_div .btn,.btn_confirm.write_div button,.btn_confirm.write_div a{width:100%;max-width:260px;margin:0;}
}
</style>