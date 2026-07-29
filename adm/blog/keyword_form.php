<?php
$sub_menu = '360600';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$w = isset($_GET['w']) ? trim($_GET['w']) : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$keywords_table = bp_table('content_keywords');
$projects_table = bp_table('content_projects');

if ($w === 'u') {
    $html_title = '키워드 수정';
    $kw = sql_fetch(" select * from {$keywords_table} where id = '{$id}' ");
    if (!$kw) {
        alert('존재하지 않는 키워드입니다.');
    }
} else {
    $html_title = '키워드 신규 등록';
    $kw = array(
        'project_id' => 0,
        'keyword' => '',
        'status' => 'Y'
    );
}
$g5['title'] = $html_title;

// 검색 파라미터 유지
$qstr = '';
if (isset($_GET['project_id']) && (int)$_GET['project_id'] > 0) $qstr .= '&amp;project_id=' . (int)$_GET['project_id'];
if (isset($_GET['status']) && $_GET['status'] !== '') $qstr .= '&amp;status=' . urlencode($_GET['status']);
if (isset($_GET['stx']) && $_GET['stx'] !== '') $qstr .= '&amp;stx=' . urlencode($_GET['stx']);
if (isset($_GET['page']) && (int)$_GET['page'] > 0) $qstr .= '&amp;page=' . (int)$_GET['page'];

// 전체 프로젝트 목록 가져오기 (폼 셀렉트박스 용)
$sql_projects = " select id, topic from {$projects_table} order by id desc ";
$res_projects = sql_query($sql_projects);

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<style>
/* 입력 요소 최소 높이 48px 적용 (모바일 퍼스트) */
.mobile-form-wrap { background:#fff; border:1px solid #ddd; padding:20px; border-radius:5px; margin-top:15px; }
.mobile-form-wrap .form-group { margin-bottom: 20px; }
.mobile-form-wrap label.control-label { display: block; font-weight: bold; margin-bottom: 8px; font-size:16px; color:#333; }
.mobile-form-wrap select, .mobile-form-wrap input[type="text"] {
    width: 100%; min-height: 48px; font-size: 16px; padding: 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
}
.mobile-form-wrap .radio-group { display: flex; gap: 20px; align-items: center; min-height: 48px; }
.mobile-form-wrap .radio-group label { font-size: 16px; cursor: pointer; display: flex; align-items: center; }
.mobile-form-wrap .radio-group input[type="radio"] { width: 24px; height: 24px; margin-right: 8px; cursor: pointer; }
.btn-wrap { margin-top: 30px; text-align: center; display: flex; gap: 10px; justify-content: center; }
.btn-wrap .btn { min-height: 48px; line-height: 48px; font-size: 16px; min-width: 120px; padding: 0 20px; }
@media (max-width: 480px) {
    .btn-wrap { flex-direction: column; }
    .btn-wrap .btn { width: 100%; margin-left: 0 !important; margin-bottom: 10px; }
}
</style>

<div class="local_desc01 local_desc">
    <p>콘텐츠 프로젝트의 메인 키워드를 등록/수정합니다. 동일 프로젝트 내 중복 메인 키워드는 허용되지 않습니다.</p>
</div>

<form name="fkeyword" id="fkeyword" method="post" action="./keyword_update.php" onsubmit="return fkeyword_submit(this);" autocomplete="off">
    <input type="hidden" name="w" value="<?php echo get_text($w); ?>">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <input type="hidden" name="qstr" value="<?php echo $qstr; ?>">

    <div class="mobile-form-wrap">
        <div class="form-group">
            <label class="control-label" for="project_id">콘텐츠 프로젝트 <strong class="sound_only">필수</strong></label>
            <select name="project_id" id="project_id" required>
                <option value="">선택하세요</option>
                <?php while ($prow = sql_fetch_array($res_projects)) { ?>
                    <option value="<?php echo (int)$prow['id']; ?>" <?php echo (int)$kw['project_id'] === (int)$prow['id'] ? 'selected' : ''; ?>>
                        <?php echo get_text($prow['topic']); ?>
                    </option>
                <?php } ?>
            </select>
        </div>

        <div class="form-group">
            <label class="control-label" for="keyword">메인 키워드 <strong class="sound_only">필수</strong></label>
            <input type="text" name="keyword" id="keyword" value="<?php echo get_text($kw['keyword']); ?>" required maxlength="100" placeholder="예: 강남 피부과">
        </div>

        <div class="form-group">
            <label class="control-label">사용 상태</label>
            <div class="radio-group">
                <label><input type="radio" name="status" value="Y" <?php echo $kw['status'] === 'Y' ? 'checked' : ''; ?>> 활성 (사용)</label>
                <label><input type="radio" name="status" value="N" <?php echo $kw['status'] === 'N' ? 'checked' : ''; ?>> 비활성 (미사용)</label>
            </div>
            <p style="margin-top:5px; color:#666; font-size:14px;">비활성 상태인 키워드는 향후 자동화 파이프라인에서 무시됩니다.</p>
        </div>
    </div>

    <div class="btn-wrap">
        <button type="submit" class="btn_submit btn">저장</button>
        <a href="./keyword_list.php?<?php echo $qstr; ?>" class="btn btn_02">취소(목록)</a>
    </div>
</form>

<script>
function fkeyword_submit(f) {
    if (f.project_id.value === "") {
        alert("콘텐츠 프로젝트를 선택해 주세요.");
        f.project_id.focus();
        return false;
    }
    if (f.keyword.value.trim() === "") {
        alert("메인 키워드를 입력해 주세요.");
        f.keyword.focus();
        return false;
    }
    return true;
}
</script>

<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
