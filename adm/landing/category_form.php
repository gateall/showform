<?php
include_once('./_common.php');

$sub_menu = '990051';
auth_check_menu($auth, $sub_menu, 'w');

$table = G5_TABLE_PREFIX . 'landing_category';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = array(
    'id' => 0,
    'category_code' => '',
    'category_name' => '',
    'category_desc' => '',
    'sort_order' => 0,
    'is_display' => 'Y',
);

if ($id > 0) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' ");
    if (!$row) {
        alert('카테고리 정보를 찾을 수 없습니다.', G5_ADMIN_URL . '/landing/category_list.php');
    }
    $g5['title'] = '카테고리 수정';
} else {
    $g5['title'] = '카테고리 등록';
}

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>

<div class="local_desc01 local_desc">
    <p>랜딩페이지 업종 카테고리를 등록하거나 수정합니다.</p>
</div>

<form name="fcategoryform" method="post" action="./category_update.php" onsubmit="return fcategoryform_submit(this);">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>카테고리 등록/수정</caption>
            <tbody>
                <tr>
                    <th scope="row"><label for="category_name">카테고리명</label></th>
                    <td><input type="text" name="category_name" id="category_name" value="<?php echo get_text($row['category_name']); ?>" class="frm_input" maxlength="50" required></td>
                </tr>
                <tr>
                    <th scope="row"><label for="category_code">카테고리 코드</label></th>
                    <td><input type="text" name="category_code" id="category_code" value="<?php echo get_text($row['category_code']); ?>" class="frm_input" maxlength="50" required placeholder="예: nusu, bangsu, interior"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="category_desc">카테고리 설명</label></th>
                    <td><textarea name="category_desc" id="category_desc" rows="4" style="width:100%;"><?php echo get_text($row['category_desc']); ?></textarea></td>
                </tr>
                <tr>
                    <th scope="row"><label for="sort_order">정렬 순서</label></th>
                    <td><input type="number" name="sort_order" id="sort_order" value="<?php echo (int)$row['sort_order']; ?>" class="frm_input" min="0" step="1" required></td>
                </tr>
                <tr>
                    <th scope="row">노출 여부</th>
                    <td>
                        <label><input type="radio" name="is_display" value="Y" <?php echo $row['is_display'] === 'Y' ? 'checked' : ''; ?>> 노출함</label>
                        <label style="margin-left:15px;"><input type="radio" name="is_display" value="N" <?php echo $row['is_display'] === 'N' ? 'checked' : ''; ?>> 노출안함</label>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="./category_list.php" class="btn btn_02">목록</a>
        <?php if ($id > 0) { ?>
            <a href="./category_delete.php?id=<?php echo (int)$id; ?>&amp;token=<?php echo get_admin_token(); ?>" class="btn btn_01" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
        <?php } ?>
    </div>
</form>

<script>
function fcategoryform_submit(f) {
    if (!f.category_name.value.trim()) {
        alert('카테고리명을 입력해 주세요.');
        f.category_name.focus();
        return false;
    }
    if (!f.category_code.value.trim()) {
        alert('카테고리 코드를 입력해 주세요.');
        f.category_code.focus();
        return false;
    }
    if (isNaN(parseInt(f.sort_order.value, 10))) {
        alert('정렬 순서는 숫자만 입력해 주세요.');
        f.sort_order.focus();
        return false;
    }
    return true;
}
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
