<?php
$sub_menu = '700100';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$table = G5_TABLE_PREFIX . 'sf_portfolio';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' and deleted_at is null ");
    if (!$row) {
        alert('제작 사례를 찾을 수 없습니다.', G5_ADMIN_URL . '/showform/portfolio_list.php');
    }
} else {
    $row = array(
        'title' => '', 'slug' => '', 'industry' => '', 'summary' => '', 'thumbnail' => '',
        'body' => '', 'site_url' => '', 'build_type' => '', 'price_note' => '',
        'is_featured' => 'N', 'is_display' => 'Y', 'sort_order' => 0,
    );
}

$g5['title'] = $id > 0 ? '제작 사례 수정' : '제작 사례 등록';

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>실제 사이트가 아직 없는 사례는 사이트 URL을 비워두면 목록에 "사이트 보기" 버튼이 노출되지 않습니다. 가격/기간은 확정형 문구 대신 상담 유도형 문구를 권장합니다.</p>
</div>

<form name="fportfolioform" method="post" action="./portfolio_update.php" onsubmit="return fportfolioform_submit(this);">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <?php if ($id > 0) { ?>
    <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
    <?php } ?>
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo get_text($g5['title']); ?></caption>
            <tbody>
                <tr><th scope="row"><label for="title">프로젝트명</label></th>
                    <td><input type="text" name="title" id="title" value="<?php echo get_text($row['title']); ?>" class="frm_input" maxlength="200" required></td></tr>
                <tr><th scope="row"><label for="slug">슬러그</label></th>
                    <td><input type="text" name="slug" id="slug" value="<?php echo get_text($row['slug']); ?>" class="frm_input" maxlength="200" placeholder="예: cafe-modern-2026">
                        <span class="help_txt">영문 소문자·숫자·하이픈 권장, 비워두면 자동 생성됩니다.</span></td></tr>
                <tr><th scope="row"><label for="industry">업종</label></th>
                    <td><input type="text" name="industry" id="industry" value="<?php echo get_text($row['industry']); ?>" class="frm_input" maxlength="100"></td></tr>
                <tr><th scope="row"><label for="summary">한 줄 소개</label></th>
                    <td><input type="text" name="summary" id="summary" value="<?php echo get_text($row['summary']); ?>" class="frm_input" maxlength="500"></td></tr>
                <tr><th scope="row"><label for="thumbnail">대표 이미지 URL</label></th>
                    <td><input type="text" name="thumbnail" id="thumbnail" value="<?php echo get_text($row['thumbnail']); ?>" class="frm_input" maxlength="255" placeholder="/data/showform/xxx.jpg"></td></tr>
                <tr><th scope="row"><label for="body">상세 본문</label></th>
                    <td><textarea name="body" id="body" class="frm_input" rows="10" style="width:100%;"><?php echo get_text($row['body']); ?></textarea></td></tr>
                <tr><th scope="row"><label for="site_url">실제 사이트 URL</label></th>
                    <td><input type="text" name="site_url" id="site_url" value="<?php echo get_text($row['site_url']); ?>" class="frm_input" maxlength="255" placeholder="https://example.com">
                        <span class="help_txt">http:// 또는 https:// 로 시작하는 주소만 허용됩니다. 비워두면 "사이트 보기" 버튼이 숨겨집니다.</span></td></tr>
                <tr><th scope="row"><label for="build_type">제작 유형</label></th>
                    <td><input type="text" name="build_type" id="build_type" value="<?php echo get_text($row['build_type']); ?>" class="frm_input" maxlength="50" placeholder="예: 랜딩페이지, 홈페이지"></td></tr>
                <tr><th scope="row"><label for="price_note">가격/기간 안내 문구</label></th>
                    <td><textarea name="price_note" id="price_note" class="frm_input" rows="3" style="width:100%;" placeholder="업종·페이지 분량·기능에 따라 견적이 달라집니다. 상담 후 정확한 비용을 안내합니다."><?php echo get_text($row['price_note']); ?></textarea></td></tr>
                <tr><th scope="row">추천 여부</th>
                    <td>
                        <label><input type="radio" name="is_featured" value="Y" <?php echo $row['is_featured'] === 'Y' ? 'checked' : ''; ?>> 추천</label>
                        <label style="margin-left:15px;"><input type="radio" name="is_featured" value="N" <?php echo $row['is_featured'] === 'N' ? 'checked' : ''; ?>> 일반</label>
                    </td></tr>
                <tr><th scope="row">공개 여부</th>
                    <td>
                        <label><input type="radio" name="is_display" value="Y" <?php echo $row['is_display'] === 'Y' ? 'checked' : ''; ?>> 공개</label>
                        <label style="margin-left:15px;"><input type="radio" name="is_display" value="N" <?php echo $row['is_display'] === 'N' ? 'checked' : ''; ?>> 비공개</label>
                    </td></tr>
                <tr><th scope="row"><label for="sort_order">정렬 순서</label></th>
                    <td><input type="number" name="sort_order" id="sort_order" value="<?php echo (int) $row['sort_order']; ?>" class="frm_input" style="width:100px;">
                        <span class="help_txt">숫자가 작을수록 먼저 노출됩니다.</span></td></tr>
            </tbody>
        </table>
    </div>
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="./portfolio_list.php" class="btn btn_02">목록</a>
    </div>
</form>

<script>
function fportfolioform_submit(f) {
    if (!f.title.value.trim()) { alert('프로젝트명을 입력해 주세요.'); f.title.focus(); return false; }
    var url = f.site_url.value.trim();
    if (url && !/^https?:\/\//i.test(url)) {
        alert('사이트 URL은 http:// 또는 https:// 로 시작해야 합니다.');
        f.site_url.focus();
        return false;
    }
    return true;
}
document.forms['fportfolioform'].onsubmit = function() { return fportfolioform_submit(this); };
</script>

<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
