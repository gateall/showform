<?php
include_once(__DIR__ . '/../_common.php');
auth_check_menu($auth, '700100', 'w');

$table = G5_TABLE_PREFIX . 'sf_portfolio';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($id > 0) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' and deleted_at is null ");
    if (!$row) {
        alert('제작 사례를 찾을 수 없습니다.', SF_MANAGER_URL . '/showform/list.php');
    }
} else {
    $row = array(
        'title' => '', 'slug' => '', 'industry' => '', 'summary' => '', 'thumbnail' => '',
        'body' => '', 'site_url' => '', 'build_type' => '', 'price_note' => '',
        'is_featured' => 'N', 'is_display' => 'Y', 'sort_order' => 0,
    );
}

$page_title = $id > 0 ? '쇼폼 수정' : '쇼폼 신규 등록';
include __DIR__ . '/../layout/header.php';
?>

<div class="mgr-card" style="padding:1.25rem;margin-bottom:1rem;color:var(--mgr-text-muted);font-size:.875rem;">
    실제 사이트가 아직 없는 사례는 사이트 URL을 비워두면 목록에 "사이트 보기" 버튼이 노출되지 않습니다.
    가격/기간은 확정형 문구 대신 상담 유도형 문구를 권장합니다.
</div>

<form name="fmgrportfolio" method="post" action="<?php echo G5_ADMIN_URL ?>/showform/portfolio_update.php" onsubmit="return fmgrportfolio_submit(this);">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <input type="hidden" name="return" value="manager">
    <?php if ($id > 0): ?>
    <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
    <?php endif; ?>

    <div class="mgr-card" style="padding:1.5rem;">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="mgr-field" style="grid-column:1/-1;">
                <label for="title">프로젝트명 *</label>
                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($row['title']) ?>" class="mgr-input" maxlength="200" required>
            </div>
            <div class="mgr-field">
                <label for="slug">슬러그</label>
                <input type="text" name="slug" id="slug" value="<?php echo htmlspecialchars($row['slug']) ?>" class="mgr-input" maxlength="200" placeholder="예: cafe-modern-2026">
            </div>
            <div class="mgr-field">
                <label for="industry">업종</label>
                <input type="text" name="industry" id="industry" value="<?php echo htmlspecialchars($row['industry']) ?>" class="mgr-input" maxlength="100">
            </div>
            <div class="mgr-field" style="grid-column:1/-1;">
                <label for="summary">한 줄 소개</label>
                <input type="text" name="summary" id="summary" value="<?php echo htmlspecialchars($row['summary']) ?>" class="mgr-input" maxlength="500">
            </div>
            <div class="mgr-field" style="grid-column:1/-1;">
                <label for="thumbnail">대표 이미지 URL</label>
                <input type="text" name="thumbnail" id="thumbnail" value="<?php echo htmlspecialchars($row['thumbnail']) ?>" class="mgr-input" maxlength="255" placeholder="/data/showform/xxx.jpg">
            </div>
            <div class="mgr-field" style="grid-column:1/-1;">
                <label for="body">상세 본문</label>
                <textarea name="body" id="body" class="mgr-input" rows="8" style="height:auto;padding:.625rem .875rem;"><?php echo htmlspecialchars($row['body']) ?></textarea>
            </div>
            <div class="mgr-field" style="grid-column:1/-1;">
                <label for="site_url">실제 사이트 URL</label>
                <input type="text" name="site_url" id="site_url" value="<?php echo htmlspecialchars($row['site_url']) ?>" class="mgr-input" maxlength="255" placeholder="https://example.com">
            </div>
            <div class="mgr-field">
                <label for="build_type">제작 유형</label>
                <input type="text" name="build_type" id="build_type" value="<?php echo htmlspecialchars($row['build_type']) ?>" class="mgr-input" maxlength="50" placeholder="예: 랜딩페이지, 홈페이지">
            </div>
            <div class="mgr-field">
                <label for="sort_order">정렬 순서</label>
                <input type="number" name="sort_order" id="sort_order" value="<?php echo (int) $row['sort_order'] ?>" class="mgr-input">
            </div>
            <div class="mgr-field" style="grid-column:1/-1;">
                <label for="price_note">가격/기간 안내 문구</label>
                <textarea name="price_note" id="price_note" class="mgr-input" rows="3" style="height:auto;padding:.625rem .875rem;" placeholder="업종·페이지 분량·기능에 따라 견적이 달라집니다. 상담 후 정확한 비용을 안내합니다."><?php echo htmlspecialchars($row['price_note']) ?></textarea>
            </div>
            <div class="mgr-field">
                <label>추천 여부</label>
                <div>
                    <label style="font-weight:400;"><input type="radio" name="is_featured" value="Y" <?php echo $row['is_featured'] === 'Y' ? 'checked' : ''; ?>> 추천</label>
                    <label style="font-weight:400;margin-left:1rem;"><input type="radio" name="is_featured" value="N" <?php echo $row['is_featured'] === 'N' ? 'checked' : ''; ?>> 일반</label>
                </div>
            </div>
            <div class="mgr-field">
                <label>공개 여부</label>
                <div>
                    <label style="font-weight:400;"><input type="radio" name="is_display" value="Y" <?php echo $row['is_display'] === 'Y' ? 'checked' : ''; ?>> 공개</label>
                    <label style="font-weight:400;margin-left:1rem;"><input type="radio" name="is_display" value="N" <?php echo $row['is_display'] === 'N' ? 'checked' : ''; ?>> 비공개</label>
                </div>
            </div>
        </div>
    </div>

    <div style="display:flex;gap:.5rem;margin-top:1rem;">
        <button type="submit" class="mgr-btn mgr-btn-primary">저장</button>
        <a href="<?php echo SF_MANAGER_URL ?>/showform/list.php" class="mgr-btn">목록</a>
    </div>
</form>

<script>
function fmgrportfolio_submit(f) {
    if (!f.title.value.trim()) { alert('프로젝트명을 입력해 주세요.'); f.title.focus(); return false; }
    var url = f.site_url.value.trim();
    if (url && !/^https?:\/\//i.test(url)) {
        alert('사이트 URL은 http:// 또는 https:// 로 시작해야 합니다.');
        f.site_url.focus();
        return false;
    }
    return true;
}
</script>

<?php include __DIR__ . '/../layout/footer.php'; ?>
