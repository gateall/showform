<?php
$sub_menu = "900200";
include_once('./_common.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) alert('잘못된 접근입니다.');

$table = G5_TABLE_PREFIX . 'landing_inquiry';
$row = sql_fetch(" select * from {$table} where id = '{$id}' ");
if (!$row) alert('존재하지 않는 문의입니다.');

$g5['title'] = '문의 상세 및 관리자 메모';
include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption>고객 문의 내용</caption>
    <tbody>
    <tr>
        <th scope="row" width="150">이름</th>
        <td><strong><?php echo get_text($row['name']); ?></strong></td>
        <th scope="row" width="150">연락처</th>
        <td><?php echo get_text($row['tel']); ?></td>
    </tr>
    <tr>
        <th scope="row">희망 일정</th>
        <td><?php echo get_text($row['schedule']); ?></td>
        <th scope="row">참여 인원</th>
        <td><?php echo get_text($row['people']); ?></td>
    </tr>
    <tr>
        <th scope="row">처리 상태</th>
        <td>
            <strong style="color:#1d4ed8; font-size:15px;"><?php echo get_text($row['status']); ?></strong>
        </td>
        <th scope="row">등록일시</th>
        <td><?php echo $row['created_at']; ?> (IP: <?php echo $row['remote_ip']; ?>)</td>
    </tr>
    <tr>
        <th scope="row">요청 및 문의사항</th>
        <td colspan="3">
            <div style="padding:15px; background:#f9fafb; border:1px solid #e5e7eb; border-radius:8px; line-height:1.6; min-height:100px;">
                <?php echo nl2br(get_text($row['content'])); ?>
            </div>
        </td>
    </tr>
    </tbody>
    </table>
</div>

<div class="btn_confirm01 btn_confirm">
    <a href="./inquiry_list.php?<?php echo $qstr; ?>" class="btn_02 btn">목록으로</a>
</div>

<h2 class="h2_frm" style="margin-top:40px; font-size:18px;">관리자 타임라인 메모</h2>
<div class="memo_wrap" style="max-width:800px;">
    <!-- 메모 입력 폼 -->
    <form name="fmemo" method="post" action="./inquiry_memo_update.php" style="margin-bottom:20px; padding:20px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px;">
        <input type="hidden" name="inquiry_id" value="<?php echo $id; ?>">
        <div style="display:flex; gap:10px;">
            <textarea name="memo" required style="flex:1; height:60px; padding:10px; border:1px solid #cbd5e1; border-radius:4px;" placeholder="상담 진행 상황이나 특이사항을 기록해 주세요."></textarea>
            <input type="submit" value="메모 등록" class="btn_submit btn" style="width:100px;">
        </div>
    </form>

    <!-- 메모 목록 -->
    <ul class="memo_list" style="list-style:none; padding:0; margin:0;">
        <?php
        $table_memo = G5_TABLE_PREFIX . 'landing_inquiry_memo';
        $sql_memo = " select * from {$table_memo} where inquiry_id = '{$id}' order by id desc ";
        $res_memo = sql_query($sql_memo);
        
        for ($i=0; $m=sql_fetch_array($res_memo); $i++) {
        ?>
        <li style="padding:15px; background:#fff; border:1px solid #e5e7eb; border-radius:8px; margin-bottom:10px;">
            <div style="display:flex; justify-content:space-between; margin-bottom:8px; font-size:13px; color:#6b7280;">
                <span><strong><?php echo $m['mb_id']; ?></strong> 작성</span>
                <span><?php echo $m['created_at']; ?></span>
            </div>
            <div style="font-size:14px; line-height:1.6; color:#111827;">
                <?php echo nl2br(get_text($m['memo'])); ?>
            </div>
        </li>
        <?php }
        if ($i == 0) {
            echo '<li style="padding:20px; text-align:center; color:#9ca3af;">등록된 메모가 없습니다.</li>';
        }
        ?>
    </ul>
</div>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
