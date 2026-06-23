<?php
$sub_menu = "900200"; // 임시 서브메뉴 번호 (라이브 페이지 목록)
include_once('./_common.php');

$g5['title'] = '라이브 페이지 관리';

$table = G5_TABLE_PREFIX . 'landing_page';
$inq_table = G5_TABLE_PREFIX . 'landing_inquiry';

// 라이브 페이지(is_template = 'N')만 필터
$sql_search = " WHERE is_template = 'N' ";
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
if ($stx) {
    $sql_search .= " and (subject like '%{$stx}%' or hero_title like '%{$stx}%') ";
}

$sql_cnt = " select count(*) as cnt from {$table} {$sql_search} ";
$row_cnt = sql_fetch($sql_cnt);
$total_count = $row_cnt['cnt'];

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rows = 20;
$offset = ($page - 1) * $rows;
$total_page  = ceil($total_count / $rows);

$sql = " select a.*, 
            (select count(*) from {$inq_table} where landing_id = a.id) as inq_cnt,
            (select subject from {$table} where id = a.parent_id) as parent_name 
         from {$table} a 
         {$sql_search} 
         order by a.id desc 
         limit {$offset}, {$rows} ";
$result = @sql_query($sql, false);

include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<style>
.search_box { background:#f8fafc; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center;}
.btn-submit { padding:8px 20px; background:#475569; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold; }
.btn-link-template { padding:8px 20px; background:#10b981; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold; text-decoration:none; }

.status_switch { position:relative; display:inline-block; width:40px; height:22px; }
.status_switch input { opacity:0; width:0; height:0; }
.slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#cbd5e1; transition:.4s; border-radius:34px; }
.slider:before { position:absolute; content:""; height:16px; width:16px; left:3px; bottom:3px; background-color:white; transition:.4s; border-radius:50%; }
input:checked + .slider { background-color:#3b82f6; }
input:checked + .slider:before { transform:translateX(18px); }

.live_link { color:#3b82f6; text-decoration:none; font-weight:bold; }
.live_link:hover { text-decoration:underline; }

.action_btns a { padding:4px 8px; font-size:12px; border:1px solid #cbd5e1; background:#fff; color:#475569; border-radius:4px; text-decoration:none; margin-right:4px;}
.action_btns a:hover { background:#f1f5f9; }
</style>

<div class="search_box">
    <form name="fsearch" method="get" style="margin:0;">
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" class="frm_input" size="30" placeholder="타이틀 검색">
        <button type="submit" class="btn-submit">검색</button>
    </form>
    <div>
        <a href="./template_list.php" class="btn-link-template">⬅ 마스터 템플릿 목록으로 돌아가기</a>
    </div>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <thead>
            <tr>
                <th scope="col">번호</th>
                <th scope="col">라이브 타이틀</th>
                <th scope="col">복사 원본 템플릿</th>
                <th scope="col">방문자(PV)</th>
                <th scope="col">접수 문의</th>
                <th scope="col">배포 스위치 (ON/OFF)</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $i = 0;
            if ($result) {
                for ($i=0; $row=sql_fetch_array($result); $i++) {
                    $live_url = G5_URL . "/page/landing.php?id=" . $row['id'];
            ?>
            <tr>
                <td class="td_num"><?php echo $row['id']; ?></td>
                <td style="text-align:left;">
                    <a href="<?php echo $live_url; ?>" target="_blank" class="live_link"><?php echo get_text($row['subject']); ?> ↗</a>
                </td>
                <td style="color:#64748b;"><?php echo $row['parent_name'] ? get_text($row['parent_name']) : '원본 없음'; ?></td>
                <td class="td_num"><?php echo number_format($row['view_count']); ?></td>
                <td class="td_num" style="color:#10b981; font-weight:bold;"><?php echo number_format($row['inq_cnt']); ?>건</td>
                <td style="text-align:center;">
                    <label class="status_switch">
                        <input type="checkbox" onchange="toggleStatus(<?php echo $row['id']; ?>, this.checked)" <?php echo $row['is_display'] == 'Y' ? 'checked' : ''; ?>>
                        <span class="slider"></span>
                    </label>
                </td>
                <td class="action_btns" style="text-align:center;">
                    <a href="./template_edit.php?id=<?php echo $row['id']; ?>">에디터</a>
                    <a href="javascript:;" onclick="deleteLivePage(<?php echo $row['id']; ?>)" style="color:#ef4444; border-color:#fca5a5;">삭제</a>
                </td>
            </tr>
            <?php 
                } 
            }
            if ($i == 0) echo '<tr><td colspan="7" class="empty_table">생성된 라이브 페이지가 없습니다. 템플릿 목록에서 새로 생성해주세요.</td></tr>';
            ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?stx='.urlencode($stx).'&page='); ?>

<script>
// 배포 상태 토글
function toggleStatus(id, is_checked) {
    const status = is_checked ? 'Y' : 'N';
    $.post('./template_status.php', { id: id, is_display: status }, function(res) {
        if(!res.success) {
            alert('오류가 발생했습니다: ' + res.error);
            location.reload();
        }
    }, 'json').fail(function() {
        alert('서버 통신 오류');
        location.reload();
    });
}

// 라이브 페이지 삭제
function deleteLivePage(id) {
    if(confirm('이 라이브 페이지를 삭제하시겠습니까?\n이 페이지로 수집된 통계 및 문의 데이터 접근이 불가능해집니다.')) {
        $.post('./template_delete.php', { id: id }, function(res) {
            if(res.success) {
                location.reload();
            } else {
                alert('삭제 실패: ' + res.error);
            }
        }, 'json').fail(function() {
            alert('서버 통신 오류');
        });
    }
}
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>