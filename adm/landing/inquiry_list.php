<?php
$sub_menu = "900200"; // 임의의 서브메뉴 코드
include_once('./_common.php');

$g5['title'] = '상담 문의 내역 관리';

// 메모 테이블 자동 생성
$table_memo = G5_TABLE_PREFIX . 'landing_inquiry_memo';
$sql_memo_create = "
CREATE TABLE IF NOT EXISTS `{$table_memo}` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `inquiry_id` int(11) NOT NULL DEFAULT '0',
  `mb_id` varchar(20) NOT NULL DEFAULT '',
  `memo` text,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `inquiry_id` (`inquiry_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
";
@sql_query($sql_memo_create, false);

$table = G5_TABLE_PREFIX . 'landing_inquiry';
$landing_table = G5_TABLE_PREFIX . 'landing_page';

// 필터 및 검색 파라미터
$sfl = isset($_GET['sfl']) ? trim($_GET['sfl']) : '';
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
$sdt = isset($_GET['sdt']) ? trim($_GET['sdt']) : ''; // 시작일
$edt = isset($_GET['edt']) ? trim($_GET['edt']) : ''; // 종료일
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$landing_id = isset($_GET['landing_id']) ? (int)$_GET['landing_id'] : 0;

$sql_search = " WHERE 1=1 ";

if ($sdt) $sql_search .= " and DATE(created_at) >= '{$sdt}' ";
if ($edt) $sql_search .= " and DATE(created_at) <= '{$edt}' ";
if ($status) $sql_search .= " and status = '{$status}' ";
if ($landing_id) $sql_search .= " and landing_id = '{$landing_id}' ";

if ($stx) {
    if ($sfl == 'name') $sql_search .= " and name like '%{$stx}%' ";
    else if ($sfl == 'tel') $sql_search .= " and tel like '%{$stx}%' ";
    else if ($sfl == 'content') $sql_search .= " and content like '%{$stx}%' ";
    else $sql_search .= " and (name like '%{$stx}%' or tel like '%{$stx}%' or content like '%{$stx}%') ";
}

$sql_common = " from {$table} {$sql_search} ";

// 전체 레코드 수
$sql = " select count(*) as cnt {$sql_common} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = isset($_GET['rows']) ? (int)$_GET['rows'] : 20;
if ($rows < 20) $rows = 20;

$total_page  = ceil($total_count / $rows);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select * {$sql_common} order by id desc limit {$from_record}, {$rows} ";
$result = sql_query($sql);

include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<div class="local_ov01 local_ov">
    <a href="./inquiry_list.php" class="ov_listall">전체목록</a>
    <a href="./inquiry_excel.php?<?php echo $qstr; ?>" class="btn_ov01" style="margin-left:10px; background:#10b981; color:#fff;">엑셀 다운로드</a>
    <span class="btn_ov01"><span class="ov_txt">총 문의 건수</span><span class="ov_num"> <?php echo number_format($total_count); ?>건</span></span>
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
<div class="sch_last">
    <label for="sdt" class="sound_only">시작일</label>
    <input type="date" name="sdt" value="<?php echo $sdt; ?>" id="sdt" class="frm_input" size="10" placeholder="시작일"> ~
    <label for="edt" class="sound_only">종료일</label>
    <input type="date" name="edt" value="<?php echo $edt; ?>" id="edt" class="frm_input" size="10" placeholder="종료일">
    
    <label for="status" class="sound_only">처리상태</label>
    <select name="status" id="status">
        <option value="">전체 상태</option>
        <option value="접수대기" <?php echo get_selected($status, '접수대기'); ?>>접수대기</option>
        <option value="상담중" <?php echo get_selected($status, '상담중'); ?>>상담중</option>
        <option value="예약확정" <?php echo get_selected($status, '예약확정'); ?>>예약확정</option>
        <option value="취소" <?php echo get_selected($status, '취소'); ?>>취소</option>
    </select>

    <label for="sfl" class="sound_only">검색대상</label>
    <select name="sfl" id="sfl">
        <option value="">통합 검색</option>
        <option value="name" <?php echo get_selected($sfl, 'name'); ?>>이름</option>
        <option value="tel" <?php echo get_selected($sfl, 'tel'); ?>>연락처</option>
        <option value="content" <?php echo get_selected($sfl, 'content'); ?>>상담내용</option>
    </select>
    <label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
    <input type="text" name="stx" value="<?php echo $stx; ?>" id="stx" class="frm_input" size="20" placeholder="검색어">
    <input type="submit" class="btn_submit" value="검색">
</div>
</form>

<div class="tbl_head01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?> 목록</caption>
    <thead>
    <tr>
        <th scope="col" width="60">번호</th>
        <th scope="col" width="120">처리 상태</th>
        <th scope="col" width="100">이름</th>
        <th scope="col" width="140">연락처</th>
        <th scope="col" width="140">희망 일정</th>
        <th scope="col">상담 요청 내용</th>
        <th scope="col" width="160">등록일시</th>
        <th scope="col" width="80">관리</th>
    </tr>
    </thead>
    <tbody>
    <?php
    $list_num = $total_count - ($page - 1) * $rows;
    for ($i=0; $row=sql_fetch_array($result); $i++) {
        
        // 뱃지 색상
        $badge_color = '#6b7280'; // gray
        $badge_bg = '#f3f4f6';
        if ($row['status'] == '접수대기') { $badge_color = '#ef4444'; $badge_bg = '#fef2f2'; } // red
        else if ($row['status'] == '상담중') { $badge_color = '#eab308'; $badge_bg = '#fefce8'; } // yellow
        else if ($row['status'] == '예약확정') { $badge_color = '#22c55e'; $badge_bg = '#f0fdf4'; } // green
        
        $content_snippet = cut_str(strip_tags($row['content']), 40, '...');
    ?>
    <tr class="<?php echo $bg; ?>">
        <td class="td_num"><?php echo $list_num; ?></td>
        <td class="td_center">
            <select class="status_select" data-id="<?php echo $row['id']; ?>" style="border:1px solid <?php echo $badge_color; ?>; color:<?php echo $badge_color; ?>; background:<?php echo $badge_bg; ?>; padding:4px 8px; border-radius:4px; font-weight:bold;">
                <option value="접수대기" <?php echo get_selected($row['status'], '접수대기'); ?>>접수대기</option>
                <option value="상담중" <?php echo get_selected($row['status'], '상담중'); ?>>상담중</option>
                <option value="예약확정" <?php echo get_selected($row['status'], '예약확정'); ?>>예약확정</option>
                <option value="취소" <?php echo get_selected($row['status'], '취소'); ?>>취소</option>
            </select>
        </td>
        <td class="td_center"><strong><?php echo get_text($row['name']); ?></strong></td>
        <td class="td_center"><?php echo get_text($row['tel']); ?></td>
        <td class="td_center"><?php echo get_text($row['schedule']); ?></td>
        <td class="td_left" title="<?php echo get_text($row['content']); ?>"><?php echo $content_snippet; ?></td>
        <td class="td_datetime"><?php echo $row['created_at']; ?></td>
        <td class="td_mng td_mng_s">
            <a href="./inquiry_form.php?id=<?php echo $row['id']; ?>" class="btn btn_03">상세/메모</a>
        </td>
    </tr>
    <?php
        $list_num--;
    }
    if ($i == 0)
        echo '<tr><td colspan="8" class="empty_table">자료가 없습니다.</td></tr>';
    ?>
    </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, $_SERVER['SCRIPT_NAME'].'?'.$qstr.'&amp;page='); ?>

<script>
$(function(){
    // Ajax 상태 변경 처리
    $('.status_select').on('change', function(){
        var id = $(this).data('id');
        var status = $(this).val();
        var $select = $(this);
        
        $.post('./inquiry_status_update.php', {id: id, status: status}, function(res){
            if(res.error) {
                alert(res.error);
                return;
            }
            // 색상 업데이트
            var color = '#6b7280', bg = '#f3f4f6';
            if (status == '접수대기') { color = '#ef4444'; bg = '#fef2f2'; }
            else if (status == '상담중') { color = '#eab308'; bg = '#fefce8'; }
            else if (status == '예약확정') { color = '#22c55e'; bg = '#f0fdf4'; }
            
            $select.css({ 'color': color, 'border-color': color, 'background-color': bg });
        }, 'json');
    });
});
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
