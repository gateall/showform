<style>
.bo_v_nb{max-width:900px;margin:15px auto 0;padding:0;list-style:none;border:1px solid #ddd;box-sizing:border-box;}
.bo_v_nb li{display:flex;align-items:center;gap:10px;padding:12px 15px;border-bottom:1px solid #eee;font-size:13px;color:#555;}
.bo_v_nb li:last-child{border-bottom:0;}
.bo_v_nb .nb_tit{color:#376fc7;font-weight:700;}
.bo_v_nb a{color:#333;text-decoration:none;flex:1;}
.bo_v_nb .nb_date{margin-left:auto;color:#999;font-size:12px;}

.estimate-view-table{width:100%;max-width:900px;margin:0 auto 15px;border-collapse:collapse;border-top:3px solid #111;}
.estimate-view-table th{width:120px;background:#f5f5f5;border-bottom:1px solid #ddd;padding:14px 10px;text-align:left;font-size:15px;font-weight:700;color:#111;}
.estimate-view-table td{border-bottom:1px solid #ddd;padding:14px 10px;text-align:left;font-size:15px;color:#555;line-height:1.6;}
.estimate-view-table .point-red{color:#f00;font-weight:700;}
.estimate-status-box{max-width:900px;margin:15px auto 70px;padding:12px;border:1px solid #ddd;border-radius:6px;text-align:left;box-sizing:border-box;background:#fff;}
.estimate-status-btn{display:inline-block;margin:3px 4px 3px 0;padding:8px 13px;background:#4b4d68;color:#fff!important;text-decoration:none!important;font-size:13px;font-weight:700;border-radius:0;}
.estimate-status-btn.active{background:#8cc63f;color:#111!important;}
.estimate-view-btns{max-width:900px;margin:25px auto 15px;display:flex;justify-content:space-between;align-items:center;gap:10px;box-sizing:border-box;}
.estimate-action-btn{display:inline-block;padding:8px 12px;background:#4b4d68;color:#fff!important;text-decoration:none!important;font-size:13px;font-weight:700;margin:2px;}
.estimate-action-btn.danger{background:#d94141!important;}

@media(max-width:768px){
.estimate-view-table,.estimate-view-table tbody,.estimate-view-table tr,.estimate-view-table th,.estimate-view-table td{display:block;width:100%!important;box-sizing:border-box;}
.estimate-view-table tr{border-bottom:1px solid #ddd;}
.estimate-view-table th{border-bottom:0;padding:10px;background:#f5f5f5;}
.estimate-view-table td{border-bottom:0;padding:10px;}
.estimate-status-box{margin:12px 10px 30px;}
.estimate-status-btn{font-size:12px;padding:7px 10px;}
.estimate-view-btns{margin:20px 10px;flex-wrap:wrap;}
.estimate-view-btns-left,.estimate-view-btns-right{width:100%;display:flex;flex-wrap:wrap;gap:4px;}
.bo_v_nb{margin:15px 10px;}
.bo_v_nb li{display:block;line-height:1.6;}
.bo_v_nb .nb_date{display:block;margin-left:0;}
}
</style>
<!-- INQUIRY VIEW TEST: basic -->
<?php

if (!defined("_GNUBOARD_"))
    exit; // 개별 페이지 접근 불가
include_once(G5_LIB_PATH . '/thumbnail.lib.php');

if (!function_exists('clean_est_text')) {
    function clean_est_text($str)
    {
        $str = html_entity_decode((string) $str, ENT_QUOTES, 'UTF-8');
        $str = str_replace(array('&nbsp;', "\xC2\xA0"), ' ', $str);
        return trim($str);
    }
}

if (!function_exists('get_pure_memo')) {
    function get_pure_memo($content) {
        $content = str_replace(array("\r\n", "\r"), "\n", $content);
        $patterns = array(
            '/^Inquiry type:.*$/mi',
            '/^Name:.*$/mi',
            '/^Phone:.*$/mi',
            '/^Move-out date:.*$/mi',
            '/^Move-out address:.*$/mi',
            '/^Move-in address:.*$/mi',
            '/^Items:.*$/mi',
            '/^\(contact type:.*\)$/mi'
        );
        $content = preg_replace($patterns, '', $content);
        return trim($content);
    }
}

add_stylesheet('<link rel="stylesheet" href="' . $board_skin_url . '/style.css">', 0);
add_stylesheet('<link rel="stylesheet" href="' . G5_THEME_URL . '/css/inquiry-view.css?ver=20260609">', 0);
?>

<script src="<?php echo G5_JS_URL; ?>/viewimageresize.js"></script>

<!-- 게시물 읽기 시작 { -->

<article id="bo_v" style="width:<?php echo $width; ?>">
    <header>
        <h2 id="bo_v_title" class="estimate-view-title">
            <span class="bo_v_tit">
                <?php echo cut_str(get_text($view['wr_subject']), 70); ?>
            </span>
        </h2>
    </header>

    <?php if (false) { ?>
    <section id="bo_v_info">
        <h2>페이지 정보</h2>
        <div class="profile_info">
            <div class="pf_img">
                <?php echo get_member_profile_img($view['mb_id']) ?>
            </div>
            <div class="profile_info_ct">
                <span class="sound_only">작성자</span> <strong>
                    <?php echo $view['name'] ?>
                    <?php if ($is_ip_view) {
                        echo "&nbsp;($ip)";
                    } ?>
                </strong><br>
                <span class="sound_only">댓글</span><strong><a href="#bo_vc"> <i class="fa fa-commenting-o"
                            aria-hidden="true"></i>
                        <?php echo number_format($view['wr_comment']) ?>건</a></strong>
                <span class="sound_only">조회</span><strong><i class="fa fa-eye" aria-hidden="true"></i>
                    <?php echo number_format($view['wr_hit']) ?>회</strong>
                <strong class="if_date"><span class="sound_only">작성자</span><i class="fa fa-clock-o"
                        aria-hidden="true"></i>
                    <?php echo date("y-m-d H:i", strtotime($view['wr_datetime'])) ?></strong>
            </div>
        </div>
    </section>
    <?php } ?>

    <!-- 게시물 상단 버튼 시작 { -->
    <?php if ($bo_table != 'inquiry') { ?>
    <div id="bo_v_top">
        <ul class="btn_bo_user bo_v_com">
            <li><a href="<?php echo $list_href ?>" class="btn_b01 btn" title="목록"><i class="fa fa-list"
                        aria-hidden="true"></i><span class="sound_only">목록</span></a></li>
            <?php if ($copy_href || $move_href || $search_href) { ?>
                <li>
                    <button type="button" class="btn_more_opt is_view_btn btn_b01 btn"><i class="fa fa-ellipsis-v"
                            aria-hidden="true"></i><span class="sound_only">게시판 리스트 옵션</span></button>
                    <ul class="more_opt is_view_btn">
                        <?php if ($copy_href) { ?>
                            <li><a href="<?php echo $copy_href ?>" onclick="board_move(this.href); return false;">복사<i
                                        class="fa fa-files-o" aria-hidden="true"></i></a></li>
                        <?php } ?>
                        <?php if ($move_href) { ?>
                            <li><a href="<?php echo $move_href ?>" onclick="board_move(this.href); return false;">이동<i
                                        class="fa fa-arrows" aria-hidden="true"></i></a></li>
                        <?php } ?>
                        <?php if ($search_href) { ?>
                            <li><a href="<?php echo $search_href ?>">검색<i class="fa fa-search" aria-hidden="true"></i></a>
                            </li>
                        <?php } ?>
                    </ul>
                </li>
            <?php } ?>
        </ul>
        <script>
            jQuery(function ($) {
                $(".btn_more_opt.is_view_btn").on("click", function (e) {
                    e.stopPropagation();
                    $(".more_opt.is_view_btn").toggle();
                });
                $(document).on("click", function (e) {
                    if (!$(e.target).closest('.is_view_btn').length) {
                        $(".more_opt.is_view_btn").hide();
                    }
                });
            });
        </script>
        <?php
        $link_buttons = ob_get_contents();
        ob_end_flush();
        ?>
    </div>
    <?php } ?>
    <!-- } 게시물 상단 버튼 끝-->

    <section id="bo_v_atc">
        <h2 id="bo_v_atc_title">본문</h2>
        <?php /* 스크랩 버튼 영역 제거
        <div id="bo_v_share">
            <?php include_once(G5_SNS_PATH . "/view.sns.skin.php"); ?>
            <?php if ($scrap_href) { ?><a href="<?php echo $scrap_href; ?>" target="_blank" class="btn btn_b03"
                    onclick="win_scrap(this.href); return false;"><i class="fa fa-bookmark" aria-hidden="true"></i> 스크랩</a>
            <?php } ?>
        </div>
        */ ?>

        <?php
        // 파일 출력
        $v_img_count = count($view['file']);
        if ($v_img_count) {
            echo "<div id=\"bo_v_img\">\n";
            foreach ($view['file'] as $view_file) {
                echo get_file_thumbnail($view_file);
            }
            echo "</div>\n";
        }
        ?>

        <!-- 본문 내용 시작 { -->
        <?php
        $status_text = clean_est_text($view['wr_8']);
        if (!$status_text) $status_text = '견적접수';

        $move_type = clean_est_text($view['wr_3']);
        if (clean_est_text($view['wr_10'])) {
            $move_type .= ' / ' . clean_est_text($view['wr_10']);
        }
        $start_info = clean_est_text($view['wr_4']);
        $end_info = clean_est_text($view['wr_5']);
        $cust_name = isset($view['wr_1']) && trim($view['wr_1']) !== '' ? clean_est_text($view['wr_1']) : clean_est_text($view['wr_name']);
        $phone = clean_est_text($view['wr_2']);
        $move_date = clean_est_text($view['wr_6']);
        $furniture = clean_est_text($view['wr_7']);
        $memo = clean_est_text($view['wr_9']);
        ?>

        <table class="estimate-view-table">
            <tbody>
                <tr>
                    <th>고객명</th>
                    <td><?php echo $cust_name; ?></td>
                    <th>연락처</th>
                    <td class="point-red"><?php echo $phone; ?></td>
                </tr>
                <tr>
                    <th>이사 종류</th>
                    <td colspan="3"><?php echo $move_type; ?></td>
                </tr>
                <tr>
                    <th>출발지</th>
                    <td colspan="3"><?php echo $start_info; ?></td>
                </tr>
                <tr>
                    <th>도착지</th>
                    <td colspan="3"><?php echo $end_info; ?></td>
                </tr>
                <tr>
                    <th>이사일</th>
                    <td colspan="3" class="point-red"><?php echo $move_date; ?></td>
                </tr>
                <tr>
                    <th>선택품목</th>
                    <td colspan="3"><?php echo $furniture; ?></td>
                </tr>
                <tr>
                    <th>기타 메모</th>
                    <td colspan="3">
                        <?php 
                        if ($memo) {
                            echo nl2br($memo);
                        } else {
                            echo '<span style="color:#999; font-style:italic;">입력된 메모가 없습니다.</span>';
                        }
                        ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <?php if ($bo_table == 'inquiry' && $is_admin) { ?>
        <div class="estimate-status-box">
            <a href="<?php echo G5_BBS_URL; ?>/inquiry_status_update.php?bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $wr_id; ?>&status=<?php echo urlencode('견적접수'); ?>" class="estimate-status-btn <?php echo $status_text == '견적접수' ? 'active' : ''; ?>">견적접수</a>

            <a href="<?php echo G5_BBS_URL; ?>/inquiry_status_update.php?bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $wr_id; ?>&status=<?php echo urlencode('견적확인'); ?>" class="estimate-status-btn <?php echo $status_text == '견적확인' ? 'active' : ''; ?>">견적확인</a>

            <a href="<?php echo G5_BBS_URL; ?>/inquiry_status_update.php?bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $wr_id; ?>&status=<?php echo urlencode('견적제출'); ?>" class="estimate-status-btn <?php echo $status_text == '견적제출' ? 'active' : ''; ?>">견적제출</a>
        </div>
        <?php } ?>

        <div id="bo_v_con" style="display:none;"><?php echo get_view_thumbnail($view['content']); ?></div>
        <!-- } 본문 내용 끝 -->

        <?php if ($is_signature) { ?>
            <p>
                <?php echo $signature ?>
            </p>
        <?php } ?>

        <!-- 추천 비추천 시작 { -->
        <?php if ($good_href || $nogood_href) { ?>
            <div id="bo_v_act">
                <?php if ($good_href) { ?>
                    <span class="bo_v_act_gng">
                        <a href="<?php echo $good_href . '&amp;' . $qstr ?>" id="good_button" class="bo_v_good"><i
                                class="fa fa-thumbs-o-up" aria-hidden="true"></i><span class="sound_only">추천</span><strong>
                                <?php echo number_format($view['wr_good']) ?>
                            </strong></a>
                        <b id="bo_v_act_good"></b>
                    </span>
                <?php } ?>
                <?php if ($nogood_href) { ?>
                    <span class="bo_v_act_gng">
                        <a href="<?php echo $nogood_href . '&amp;' . $qstr ?>" id="nogood_button" class="bo_v_nogood"><i
                                class="fa fa-thumbs-o-down" aria-hidden="true"></i><span class="sound_only">비추천</span><strong>
                                    <?php echo number_format($view['wr_nogood']) ?>
                                </strong></a>
                        <b id="bo_v_act_nogood"></b>
                    </span>
                <?php } ?>
            </div>
        <?php } else {
            if ($board['bo_use_good'] || $board['bo_use_nogood']) {
                ?>
                <div id="bo_v_act">
                    <?php if ($board['bo_use_good']) { ?><span class="bo_v_good"><i class="fa fa-thumbs-o-up"
                                aria-hidden="true"></i><span class="sound_only">추천</span><strong>
                                <?php echo number_format($view['wr_good']) ?>
                            </strong></span>
                    <?php } ?>
                    <?php if ($board['bo_use_nogood']) { ?><span class="bo_v_nogood"><i class="fa fa-thumbs-o-down"
                                aria-hidden="true"></i><span class="sound_only">비추천</span><strong>
                                    <?php echo number_format($view['wr_nogood']) ?>
                                </strong></span>
                        <?php } ?>
                </div>
                <?php
            }
        }
        ?>
        <!-- } 추천 비추천 끝 -->
    </section>

    <?php
    $cnt = 0;
    if ($view['file']['count']) {
        for ($i = 0; $i < count($view['file']); $i++) {
            if (isset($view['file'][$i]['source']) && $view['file'][$i]['source'] && !$view['file'][$i]['view'])
                $cnt++;
        }
    }
    ?>

    <?php if ($cnt) { ?>
        <!-- 첨부파일 시작 { -->
        <section id="bo_v_file">
            <h2>첨부파일</h2>
            <ul>
                <?php
                for ($i = 0; $i < count($view['file']); $i++) {
                    if (isset($view['file'][$i]['source']) && $view['file'][$i]['source'] && !$view['file'][$i]['view']) {
                        ?>
                        <li>
                            <i class="fa fa-folder-open" aria-hidden="true"></i>
                            <a href="<?php echo $view['file'][$i]['href']; ?>" class="view_file_download">
                                <strong>
                                    <?php echo $view['file'][$i]['source'] ?>
                                </strong>
                                <?php echo $view['file'][$i]['content'] ?> (
                                <?php echo $view['file'][$i]['size'] ?>)
                            </a>
                            <br>
                            <span class="bo_v_file_cnt">
                                <?php echo $view['file'][$i]['download'] ?>회 다운로드 | DATE :
                                <?php echo $view['file'][$i]['datetime'] ?>
                            </span>
                        </li>
                        <?php
                    }
                }
                ?>
            </ul>
        </section>
        <!-- } 첨부파일 끝 -->
    <?php } ?>

    <?php if (isset($view['link']) && array_filter($view['link'])) { ?>
        <!-- 관련링크 시작 { -->
        <section id="bo_v_link">
            <h2>관련링크</h2>
            <ul>
                <?php
                for ($i = 1; $i <= count($view['link']); $i++) {
                    if ($view['link'][$i]) {
                        $link = cut_str($view['link'][$i], 70);
                        ?>
                        <li>
                            <i class="fa fa-link" aria-hidden="true"></i>
                            <a href="<?php echo $view['link_href'][$i] ?>" target="_blank">
                                <strong>
                                    <?php echo $link ?>
                                </strong>
                            </a>
                            <br>
                            <span class="bo_v_link_cnt">
                                <?php echo $view['link_hit'][$i] ?>회 연결
                            </span>
                        </li>
                        <?php
                    }
                }
                ?>
            </ul>
        </section>
        <!-- } 관련링크 끝 -->
    <?php } ?>

    <?php if ($prev_href || $next_href) { ?>
        <ul class="bo_v_nb">
            <?php if ($prev_href) { ?>
                <li class="btn_prv"><span class="nb_tit"><i class="fa fa-chevron-up" aria-hidden="true"></i> 이전글</span><a
                        href="<?php echo $prev_href ?>">
                        <?php echo $prev_wr_subject; ?>
                    </a> <span class="nb_date">
                        <?php echo str_replace('-', '.', substr($prev_wr_date, '2', '8')); ?>
                    </span></li>
            <?php } ?>
            <?php if ($next_href) { ?>
                <li class="btn_next"><span class="nb_tit"><i class="fa fa-chevron-down" aria-hidden="true"></i> 다음글</span><a
                        href="<?php echo $next_href ?>">
                        <?php echo $next_wr_subject; ?>
                    </a> <span class="nb_date">
                        <?php echo str_replace('-', '.', substr($next_wr_date, '2', '8')); ?>
                    </span></li>
            <?php } ?>
        </ul>
    <?php } ?>

    <?php
    // 코멘트 입출력
    include_once(G5_BBS_PATH . '/view_comment.php');
    ?>

    <div class="estimate-view-btns">
        <div class="estimate-view-btns-left">
            <?php if ($update_href) { ?>
                <a href="<?php echo G5_BBS_URL; ?>/write.php?w=u&bo_table=<?php echo $bo_table; ?>&wr_id=<?php echo $wr_id; ?>" class="estimate-action-btn">수정</a>
            <?php } ?>

            <?php if ($delete_href) { ?>
                <a href="<?php echo $delete_href; ?>" onclick="del(this.href); return false;" class="estimate-action-btn danger">삭제</a>
            <?php } ?>
        </div>

        <div class="estimate-view-btns-right">
            <a href="<?php echo G5_BBS_URL; ?>/board.php?bo_table=<?php echo $bo_table; ?>" class="estimate-action-btn">목록</a>
        </div>
    </div>

</article>
<!-- } 게시글 읽기 끝 -->

<script>
    <?php if ($board['bo_download_point'] < 0) { ?>
        $(function () {
            $("a.view_file_download").click(function () {
                if (!g5_is_member) {
                    alert("다운로드 권한이 없습니다.\n회원이시라면 로그인 후 이용해 주세요.");
                    return false;
                }

                var msg = "이 파일을 다운로드 하시면 포인트가 차감됩니다.\n\n정말 다운로드 하시겠습니까?";

                if (confirm(msg)) {
                    var href = $(this).attr("href") + "&js=on";
                    $(this).attr("href", href);

                    return true;
                } else {
                    return false;
                }
            });
        });
    <?php } ?>

    function board_move(href) {
        window.open(href, "boardmove", "left=50, top=50, width=500, height=550, scrollbars=1");
    }
</script>