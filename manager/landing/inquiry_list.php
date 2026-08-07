<?php
include_once('./_common.php');

auth_check_menu($auth, '900200', 'r');

$g5['title'] = '문의관리';
$inq_table = G5_TABLE_PREFIX . 'landing_inquiries';
$page_table = G5_TABLE_PREFIX . 'landing_pages';

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
$sfl = isset($_GET['sfl']) ? trim($_GET['sfl']) : 'all';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$rows = 20;
$offset = ($page - 1) * $rows;

$sql_search = ' where 1=1 ';
if (in_array($status, array('new', 'contacted', 'completed'), true)) {
    $sql_search .= " and a.status = '" . sql_real_escape_string($status) . "' ";
}
if ($stx !== '') {
    $safe = sql_real_escape_string($stx);
    if ($sfl === 'company_name') {
        $sql_search .= " and p.company_name like '%{$safe}%' ";
    } elseif ($sfl === 'phone') {
        $sql_search .= " and a.phone like '%{$safe}%' ";
    } else {
        $sql_search .= " and (p.company_name like '%{$safe}%' or a.phone like '%{$safe}%' or a.name like '%{$safe}%') ";
    }
}

$row_count = sql_fetch(" select count(*) as cnt from {$inq_table} a left join {$page_table} p on p.id = a.landing_id {$sql_search} ");
$total_count = isset($row_count['cnt']) ? (int)$row_count['cnt'] : 0;
$total_page = $rows > 0 ? ceil($total_count / $rows) : 1;

$sql = " select a.*, p.company_name, p.area_name from {$inq_table} a left join {$page_table} p on p.id = a.landing_id {$sql_search} order by a.id desc limit {$offset}, {$rows} ";
$result = sql_query($sql, false);

include_once(__DIR__ . '/../layout/header.php');
?>
<style>
/* 상태 색은 관리자 공통 배지(.mgr-badge)에 얹는 값만 남긴다. */
.inq-status-new       { background:#fef3c7; color:#92400e; }
.inq-status-contacted { background:#dbeafe; color:#1d4ed8; }
.inq-status-completed { background:#dcfce7; color:#166534; }

.inq-top { display:flex; justify-content:space-between; gap:12px; align-items:flex-end; flex-wrap:wrap; margin-bottom:16px; }
.inq-search { display:grid; grid-template-columns:1fr; gap:8px; width:100%; }
.inq-actions { display:flex; flex-wrap:wrap; gap:6px; }
.inq-actions .mgr-btn { min-height:44px; }

/* 전화 버튼은 목록에서 가장 자주 누르는 것이라 눈에 띄게 둔다. */
.inq-tel { display:inline-flex; align-items:center; gap:4px; min-height:44px; padding:0 12px;
           font-weight:700; color:#0f766e; text-decoration:none; }

@media (min-width: 768px) {
    .inq-search { grid-template-columns:auto auto 1fr auto; width:auto; align-items:center; }
    .inq-tel { min-height:0; padding:0; }
}
</style>

<div class="inq-top">
    <form method="get" class="inq-search">
        <select name="status" class="mgr-select">
            <option value="">전체 상태</option>
            <option value="new" <?php echo get_selected($status, 'new'); ?>>접수</option>
            <option value="contacted" <?php echo get_selected($status, 'contacted'); ?>>확인</option>
            <option value="completed" <?php echo get_selected($status, 'completed'); ?>>완료</option>
        </select>
        <select name="sfl" class="mgr-select">
            <option value="all" <?php echo get_selected($sfl, 'all'); ?>>통합 검색</option>
            <option value="company_name" <?php echo get_selected($sfl, 'company_name'); ?>>회사명</option>
            <option value="phone" <?php echo get_selected($sfl, 'phone'); ?>>연락처</option>
        </select>
        <input type="text" name="stx" class="mgr-input" value="<?php echo get_text($stx); ?>" placeholder="회사명 · 이름 · 연락처">
        <button type="submit" class="mgr-btn mgr-btn-primary" style="min-height:44px;">검색</button>
    </form>
    <div style="color:var(--mgr-text-muted);font-size:.875rem;white-space:nowrap;">
        총 문의 <strong style="color:var(--mgr-text);"><?php echo number_format($total_count); ?></strong>건
    </div>
</div>

<?php
// data-mgr-responsive-table은 responsive.css가 768px 이하에서 행을 카드로 바꾸고
// 각 칸 앞에 data-label을 붙여준다. PC 표를 그대로 축소하지 않기 위한 기존 장치라
// 새 CSS를 만들지 않고 그대로 쓴다.
?>
<div class="mgr-card" style="overflow-x:auto;">
    <table class="mgr-table" data-mgr-responsive-table>
        <thead>
            <tr>
                <th scope="col">ID</th>
                <th scope="col">랜딩회사명</th>
                <th scope="col">이름</th>
                <th scope="col">연락처</th>
                <th scope="col">문의내용</th>
                <th scope="col">상태</th>
                <th scope="col">등록일</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
        <?php
        $count = 0;
        $status_labels = array('new' => '접수', 'contacted' => '확인', 'completed' => '완료');
        while ($row = sql_fetch_array($result)) {
            $count++;
            $status_class = 'inq-status-new';
            if ($row['status'] === 'contacted') {
                $status_class = 'inq-status-contacted';
            } elseif ($row['status'] === 'completed') {
                $status_class = 'inq-status-completed';
            }
            // tel: 링크에 들어갈 번호에서 숫자와 +만 남긴다(하이픈·공백이 섞이면
            // 일부 기기에서 다이얼러가 열리지 않는다).
            $tel_raw = preg_replace('/[^0-9+]/', '', (string) $row['phone']);
        ?>
            <tr>
                <td data-label="ID"><?php echo (int)$row['id']; ?></td>
                <td data-label="랜딩회사명">
                    <a href="/page/landing.php?id=<?php echo (int)$row['landing_id']; ?>" target="_blank" rel="noopener">
                        <?php echo get_text($row['company_name'] ? $row['company_name'] : '랜딩 #' . (int)$row['landing_id']); ?>
                    </a>
                </td>
                <td data-label="이름"><?php echo get_text($row['name']); ?></td>
                <td data-label="연락처">
                    <?php if ($tel_raw !== '') { ?>
                        <a class="inq-tel" href="tel:<?php echo htmlspecialchars($tel_raw, ENT_QUOTES, 'UTF-8'); ?>">
                            📞 <?php echo get_text($row['phone']); ?>
                        </a>
                    <?php } else { ?>
                        <span style="color:var(--mgr-text-muted);">-</span>
                    <?php } ?>
                </td>
                <td data-label="문의내용"><?php echo nl2br(get_text(cut_str($row['message'], 80, '...'))); ?></td>
                <td data-label="상태">
                    <select class="mgr-select inq-status-select <?php echo $status_class; ?>" data-id="<?php echo (int)$row['id']; ?>" style="min-width:7rem;">
                        <?php foreach ($status_labels as $value => $label) { ?>
                            <option value="<?php echo $value; ?>" <?php echo get_selected($row['status'], $value); ?>><?php echo $label; ?></option>
                        <?php } ?>
                    </select>
                </td>
                <td data-label="등록일"><?php echo get_text($row['created_at']); ?></td>
                <td data-label="관리">
                    <div class="inq-actions">
                        <a class="mgr-btn" href="<?php echo G5_ADMIN_URL; ?>/landing/inquiry_view.php?id=<?php echo (int)$row['id']; ?>">상세</a>
                        <a class="mgr-btn mgr-btn-danger" href="<?php echo G5_ADMIN_URL; ?>/landing/inquiry_delete.php?id=<?php echo (int)$row['id']; ?>" onclick="return confirm('이 문의를 삭제하시겠습니까?\n삭제하면 되돌릴 수 없습니다.');">삭제</a>
                    </div>
                </td>
            </tr>
        <?php }
        if ($count === 0) {
            echo '<tr><td colspan="8" style="text-align:center;padding:2rem;color:var(--mgr-text-muted);">등록된 문의가 없습니다.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?status='.urlencode($status).'&sfl='.urlencode($sfl).'&stx='.urlencode($stx).'&page='); ?>

<script>
// 예전 코드는 JS 안에서 SF_MANAGER_URL . '...' 을 썼다. SF_MANAGER_URL은 PHP 상수이고
// '.'은 PHP 연결 연산자라, 브라우저에서는 ReferenceError가 나면서 상태 변경이 아예
// 동작하지 않았다. 엔드포인트도 /manager/ 가 아니라 /adm/landing/ 에만 있다.
var INQ_STATUS_URL = <?php echo json_encode(G5_ADMIN_URL . '/landing/inquiry_status_update.php', JSON_UNESCAPED_SLASHES); ?>;

$(function() {
    var STATUS_CLASS = {
        'new': 'inq-status-new',
        'contacted': 'inq-status-contacted',
        'completed': 'inq-status-completed'
    };

    $('.inq-status-select').on('change', function() {
        var $sel = $(this);
        var id = $sel.data('id');
        var status = $sel.val();
        var before = $sel.data('prev') || '';

        // 응답이 올 때까지 다시 못 바꾸게 막는다(연타로 요청이 겹치는 것 방지).
        $sel.prop('disabled', true);

        $.post(INQ_STATUS_URL, { id: id, status: status }, function(res) {
            if (res && res.success) {
                $sel.removeClass('inq-status-new inq-status-contacted inq-status-completed')
                    .addClass(STATUS_CLASS[status] || '');
                $sel.data('prev', status);
                return;
            }
            alert((res && res.error) ? res.error : '상태 변경에 실패했습니다.');
            if (before) $sel.val(before);
        }, 'json').fail(function(xhr) {
            // 세션이 풀리면 JSON 대신 로그인 HTML이 돌아온다 - 원인을 구분해 알린다.
            var ct = (xhr.getResponseHeader('content-type') || '').toLowerCase();
            if (ct.indexOf('json') === -1) {
                alert('로그인이 풀렸거나 권한이 없습니다. 새로고침 후 다시 시도해 주세요.');
            } else {
                alert('서버 통신 오류가 발생했습니다.');
            }
            if (before) $sel.val(before);
        }).always(function() {
            $sel.prop('disabled', false);
        });
    });

    // 되돌리기용으로 최초 값을 기억해 둔다.
    $('.inq-status-select').each(function() { $(this).data('prev', $(this).val()); });
});
</script>

<?php include_once(__DIR__ . '/../layout/footer.php'); ?>