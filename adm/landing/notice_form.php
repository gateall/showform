<?php
include_once('./_common.php');

auth_check_menu($auth, '900600', 'w');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$table = G5_TABLE_PREFIX . 'landing_notices';
$page_table = G5_TABLE_PREFIX . 'landing_pages';
$row = array();

if ($id) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' limit 1 ");
    if (!$row) {
        alert('공지 항목을 찾을 수 없습니다.', './notice_list.php');
    }
}

if (!isset($row['landing_id'])) $row['landing_id'] = 0;
if (!isset($row['title'])) $row['title'] = '';
if (!isset($row['content'])) $row['content'] = '';
if (!isset($row['is_active'])) $row['is_active'] = 'Y';

$g5['title'] = $id ? '공지 수정' : '공지 등록';
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

<form name="fnoticeform" method="post" action="./notice_update.php">
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
            <label>공지 제목</label>
            <input type="text" name="title" value="<?php echo get_text($row['title']); ?>" required>
        </div>
        <div class="sf-field" style="grid-column:1/-1;">
            <label>내용</label>
            <textarea name="content" rows="8" required><?php echo get_text($row['content']); ?></textarea>
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
        <a href="./notice_list.php" class="btn btn_02">목록</a>
    </div>
</div>
</form>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>