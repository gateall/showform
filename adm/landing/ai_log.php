<?php
$sub_menu = "900350"; // AI 로그 내역 서브메뉴
include_once('./_common.php');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$g5['title'] = 'AI 서비스 이용 로그 내역';

$table = G5_TABLE_PREFIX . 'landing_ai_log';

// 기간, 모델, 기능, 상태, 검색어
$fr_date = isset($_GET['fr_date']) ? $_GET['fr_date'] : date('Y-m-d', strtotime('-6 days'));
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
$model = isset($_GET['model']) ? trim($_GET['model']) : '';
$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';

$sql_search = " WHERE DATE(created_at) between '{$fr_date}' and '{$to_date}' ";
if ($model) $sql_search .= " and model_name = '{$model}' ";
if ($action) $sql_search .= " and action_type = '{$action}' ";
if ($status) $sql_search .= " and status = '{$status}' ";
if ($stx) {
    $sql_search .= " and (mb_id like '%{$stx}%' or prompt like '%{$stx}%' or error_msg like '%{$stx}%') ";
}

// 페이징
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rows = 20;
$offset = ($page - 1) * $rows;

$sql_cnt = " select count(*) as cnt from {$table} {$sql_search} ";
$row_cnt = sql_fetch($sql_cnt);
$total_count = $row_cnt['cnt'];
$total_page  = ceil($total_count / $rows);

$sql = " select * from {$table} {$sql_search} order by id desc limit {$offset}, {$rows} ";
$result = sql_query($sql);

include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<style>
.search_box { background:#f8fafc; padding:15px; border-radius:8px; margin-bottom:20px; border:1px solid #e2e8f0; }
.search_row { display:flex; gap:10px; align-items:center; margin-bottom:10px; flex-wrap:wrap; }
.search_row:last-child { margin-bottom:0; }
.btn-quick { padding:6px 12px; background:#e2e8f0; color:#475569; border:none; border-radius:4px; cursor:pointer; font-size:13px; }
.btn-quick:hover { background:#cbd5e1; }
.btn-submit { padding:8px 20px; background:#3b82f6; color:#fff; border:none; border-radius:4px; cursor:pointer; font-weight:bold; }

.status_badge { padding:3px 8px; border-radius:4px; font-size:12px; font-weight:bold; }
.status_success { background:#dcfce7; color:#16a34a; }
.status_fail { background:#fee2e2; color:#dc2626; }

.btn_detail { padding:4px 8px; background:#475569; color:#fff; border-radius:4px; font-size:12px; text-decoration:none; cursor:pointer; border:none; }
.btn_detail:hover { background:#334155; }

/* 모달 스타일 */
.modal_overlay { display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:9999; justify-content:center; align-items:center; }
.modal_content { background:#fff; width:90%; max-width:800px; max-height:90vh; border-radius:8px; overflow-y:auto; display:flex; flex-direction:column; }
.modal_header { padding:15px 20px; background:#f1f5f9; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; align-items:center; }
.modal_header h2 { margin:0; font-size:18px; color:#1e293b; }
.btn_close { background:none; border:none; font-size:24px; cursor:pointer; color:#64748b; }
.modal_body { padding:20px; }
.log_sec { margin-bottom:20px; }
.log_sec h3 { margin:0 0 10px 0; font-size:15px; color:#10b981; }
.log_sec pre { background:#1e293b; color:#a5b4fc; padding:15px; border-radius:6px; font-family:Consolas, monospace; font-size:13px; overflow-x:auto; white-space:pre-wrap; }
.log_sec.error pre { color:#fca5a5; }

.list_actions { display:flex; justify-content:space-between; margin-bottom:10px; align-items:center; }
</style>

<form name="fsearch" method="get" class="search_box">
    <div class="search_row">
        <strong>호출일시</strong>
        <button type="button" class="btn-quick" onclick="setDate('<?php echo date('Y-m-d'); ?>','<?php echo date('Y-m-d'); ?>')">오늘</button>
        <button type="button" class="btn-quick" onclick="setDate('<?php echo date('Y-m-d', strtotime('-6 days')); ?>','<?php echo date('Y-m-d'); ?>')">7일</button>
        <button type="button" class="btn-quick" onclick="setDate('<?php echo date('Y-m-d', strtotime('-29 days')); ?>','<?php echo date('Y-m-d'); ?>')">30일</button>
        <input type="date" name="fr_date" id="fr_date" value="<?php echo $fr_date; ?>" class="frm_input"> ~ 
        <input type="date" name="to_date" id="to_date" value="<?php echo $to_date; ?>" class="frm_input">
    </div>
    <div class="search_row">
        <strong>기능분류</strong>
        <select name="action">
            <option value="">전체</option>
            <option value="카피라이팅 생성" <?php echo $action=='카피라이팅 생성'?'selected':'';?>>카피라이팅 생성</option>
            <option value="연동 테스트" <?php echo $action=='연동 테스트'?'selected':'';?>>연동 테스트</option>
        </select>
        <strong style="margin-left:15px;">모델</strong>
        <input type="text" name="model" value="<?php echo get_text($model); ?>" class="frm_input" size="15" placeholder="gpt-4o 등">
        <strong style="margin-left:15px;">상태</strong>
        <select name="status">
            <option value="">전체</option>
            <option value="success" <?php echo $status=='success'?'selected':'';?>>성공</option>
            <option value="fail" <?php echo $status=='fail'?'selected':'';?>>실패</option>
        </select>
    </div>
    <div class="search_row">
        <strong>통합검색</strong>
        <input type="text" name="stx" value="<?php echo get_text($stx); ?>" class="frm_input" size="40" placeholder="요청자ID, 프롬프트, 에러메시지 검색">
        <button type="submit" class="btn-submit">로그 검색</button>
    </div>
</form>

<form name="floglist" id="floglist" method="post" action="./ai_log_delete.php">
<div class="list_actions">
    <div>* 검색된 로그: 총 <?php echo number_format($total_count); ?>건</div>
    <div>
        <button type="button" class="btn btn_02" onclick="deleteLogs();">선택 삭제</button>
        <button type="button" class="btn btn_01" style="background:#10b981; color:#fff; border:none;" onclick="location.href='./ai_log_excel.php?<?php echo $_SERVER['QUERY_STRING']; ?>'">엑셀 다운로드</button>
    </div>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <thead>
            <tr>
                <th scope="col"><input type="checkbox" id="chkall" onclick="if(this.checked) $('.chk_id').prop('checked',true); else $('.chk_id').prop('checked',false);"></th>
                <th scope="col">번호</th>
                <th scope="col">호출일시</th>
                <th scope="col">요청자</th>
                <th scope="col">사용 모델</th>
                <th scope="col">프롬프트(요약)</th>
                <th scope="col">토큰</th>
                <th scope="col">상태</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php
            for ($i=0; $row=sql_fetch_array($result); $i++) {
                $status_class = $row['status'] == 'success' ? 'status_success' : 'status_fail';
                $status_txt = $row['status'] == 'success' ? '성공' : '실패';
                $prompt_short = cut_str($row['prompt'], 30, '...');
                
                // 모달 렌더링용 JSON 인코딩 보관
                $detail_json = htmlspecialchars(json_encode(array(
                    'model' => $row['model_name'],
                    'action' => $row['action_type'],
                    'status' => $row['status'],
                    'prompt' => $row['prompt'],
                    'response' => $row['response'],
                    'error' => $row['error_msg']
                ), JSON_UNESCAPED_UNICODE));
            ?>
            <tr>
                <td class="td_chk"><input type="checkbox" name="chk[]" value="<?php echo $row['id']; ?>" class="chk_id"></td>
                <td class="td_num"><?php echo $row['id']; ?></td>
                <td class="td_datetime"><?php echo $row['created_at']; ?></td>
                <td class="td_name"><?php echo get_text($row['mb_id']); ?></td>
                <td><?php echo get_text($row['model_name']); ?></td>
                <td style="text-align:left;"><?php echo get_text($prompt_short); ?></td>
                <td class="td_num"><?php echo number_format($row['tokens']); ?> T</td>
                <td><span class="status_badge <?php echo $status_class; ?>"><?php echo $status_txt; ?></span></td>
                <td>
                    <button type="button" class="btn_detail" onclick="openModal(<?php echo $row['id']; ?>)">상세</button>
                    <!-- 숨겨진 데이터 -->
                    <textarea id="log_data_<?php echo $row['id']; ?>" style="display:none;"><?php echo $detail_json; ?></textarea>
                </td>
            </tr>
            <?php } 
            if ($i == 0) echo '<tr><td colspan="9" class="empty_table">조회된 로그 내역이 없습니다.</td></tr>';
            ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&page='); ?>
</form>

<!-- 상세 모달창 -->
<div id="logModal" class="modal_overlay">
    <div class="modal_content">
        <div class="modal_header">
            <h2 id="modalTitle">AI API 호출 상세</h2>
            <button class="btn_close" onclick="closeModal()">×</button>
        </div>
        <div class="modal_body">
            <div class="log_sec">
                <h3>Request (입력 프롬프트)</h3>
                <pre id="modalReq"></pre>
            </div>
            <div class="log_sec" id="secRes">
                <h3>Response (서버 응답 원본)</h3>
                <pre id="modalRes"></pre>
            </div>
            <div class="log_sec error" id="secErr" style="display:none;">
                <h3 style="color:#ef4444;">Error Message</h3>
                <pre id="modalErr"></pre>
            </div>
        </div>
    </div>
</div>

<script>
function setDate(fr, to) {
    $('#fr_date').val(fr);
    $('#to_date').val(to);
    document.fsearch.submit();
}

function deleteLogs() {
    if ($('.chk_id:checked').length == 0) {
        alert('삭제할 로그를 1개 이상 선택하세요.');
        return;
    }
    if (confirm('선택한 AI 호출 로그를 정말 삭제하시겠습니까? (삭제 후 복구 불가)')) {
        document.getElementById('floglist').submit();
    }
}

function openModal(id) {
    try {
        var dataStr = $('#log_data_' + id).val();
        var data = JSON.parse(dataStr);
        
        $('#modalTitle').text('상세 로그: ' + data.action + ' (' + data.model + ')');
        $('#modalReq').text(data.prompt);
        
        if (data.status === 'success') {
            $('#secRes').show();
            $('#secErr').hide();
            // JSON 응답을 이쁘게 포맷팅
            try {
                var resJson = JSON.parse(data.response);
                $('#modalRes').text(JSON.stringify(resJson, null, 2));
            } catch(e) {
                $('#modalRes').text(data.response);
            }
        } else {
            $('#secRes').show();
            $('#modalRes').text(data.response || '응답 없음');
            $('#secErr').show();
            $('#modalErr').text(data.error);
        }
        
        $('#logModal').css('display', 'flex');
    } catch(e) {
        alert('데이터 파싱 오류');
    }
}

function closeModal() {
    $('#logModal').hide();
}

// 배경 클릭 시 닫기
$(window).on('click', function(e) {
    if($(e.target).is('#logModal')) closeModal();
});
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
?>
