<?php
include_once('./_common.php');

auth_check_menu($auth, '900300', 'w');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$table = G5_TABLE_PREFIX . 'landing_reviews';
$page_table = G5_TABLE_PREFIX . 'landing_pages';
$row = array();

if ($id) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' limit 1 ");
    if (!$row) {
        alert('후기를 찾을 수 없습니다.', './review_list.php');
    }
}

if (!isset($row['landing_id'])) $row['landing_id'] = 0;
if (!isset($row['customer_name'])) $row['customer_name'] = '';
if (!isset($row['rating'])) $row['rating'] = 5;
if (!isset($row['content'])) $row['content'] = '';
if (!isset($row['sort_order'])) $row['sort_order'] = 0;
if (!isset($row['is_active'])) $row['is_active'] = 'Y';

$g5['title'] = $id ? '후기 수정' : '후기 등록';
$landing_list = sql_query(" select id, company_name from {$page_table} order by id desc ");

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<style>
.sf-card { background:#fff; border:1px solid #e5e7eb; border-radius:14px; padding:20px; }
.sf-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:16px; }
@media (max-width: 768px) { .sf-grid { grid-template-columns:1fr; } }
.sf-field label { display:block; margin-bottom:6px; font-weight:700; color:#334155; }
.sf-field input, .sf-field select, .sf-field textarea { width:100%; box-sizing:border-box; }
</style>

<form name="freviewform" method="post" action="./review_update.php">
<input type="hidden" name="id" value="<?php echo (int)$id; ?>">
<div class="sf-card">
    <div class="sf-grid">
        <div class="sf-field">
            <label>랜딩 선택</label>
            <select name="landing_id" required>
                <option value="">선택</option>
                <?php while ($p = sql_fetch_array($landing_list)) { ?>
                <option value="<?php echo (int)$p['id']; ?>" <?php echo get_selected($row['landing_id'], $p['id']); ?>><?php echo get_text($p['company_name']); ?></option>
                <?php } ?>
            </select>
        </div>
        <div class="sf-field">
            <label>고객명</label>
            <input type="text" name="customer_name" value="<?php echo get_text($row['customer_name']); ?>" required>
        </div>
        <div class="sf-field">
            <label>평점</label>
            <select name="rating">
                <?php for ($i=1; $i<=5; $i++) { ?>
                <option value="<?php echo $i; ?>" <?php echo get_selected($row['rating'], $i); ?>><?php echo $i; ?>점</option>
                <?php } ?>
            </select>
        </div>
        <div class="sf-field">
            <label>정렬 순서</label>
            <input type="number" name="sort_order" value="<?php echo (int)$row['sort_order']; ?>" min="0">
        </div>
        <div class="sf-field" style="grid-column:1/-1;">
            <label>후기내용</label>
            <textarea name="content" rows="6" required><?php echo get_text($row['content']); ?></textarea>
        </div>
        <div class="sf-field">
            <label>노출 여부</label>
            <select name="is_active">
                <option value="Y" <?php echo $row['is_active'] === 'Y' ? 'selected' : ''; ?>>노출</option>
                <option value="N" <?php echo $row['is_active'] === 'N' ? 'selected' : ''; ?>>숨김</option>
            </select>
        </div>
    </div>
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="./review_list.php" class="btn btn_02">목록</a>
    </div>
</div>
</form>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>