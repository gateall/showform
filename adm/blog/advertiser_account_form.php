<?php
include_once('./_common.php');

$sub_menu = '361100';
auth_check_menu($auth, $sub_menu, 'w');

$table = bp_table('advertiser_accounts');
$tbl_adv = bp_table('advertisers');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = array(
    'id' => 0, 'advertiser_id' => 0, 'login_id' => '', 'manager_name' => '', 'manager_email' => '', 'status' => 'active'
);

if ($id > 0) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' ");
    if (!$row) {
        alert('계정 정보를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/advertiser_account_list.php');
    }
    $g5['title'] = '광고주 로그인 계정 수정';
} else {
    $g5['title'] = '광고주 로그인 계정 등록';
}

$adv_res = sql_query(" select id, name from {$tbl_adv} order by name asc ");
$adv_opts = '';
while ($a = sql_fetch_array($adv_res)) {
    $sel = ($row['advertiser_id'] == $a['id']) ? 'selected' : '';
    $adv_opts .= "<option value='{$a['id']}' {$sel}>{$a['name']}</option>";
}

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>

<form name="faccountform" method="post" action="./advertiser_account_update.php" onsubmit="return faccountform_submit(this);">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo $g5['title']; ?></caption>
            <tbody>
                <tr><th scope="row"><label for="advertiser_id">연결할 광고주</label></th>
                    <td>
                        <select name="advertiser_id" id="advertiser_id" required>
                            <option value="">-- 광고주 선택 --</option>
                            <?php echo $adv_opts; ?>
                        </select>
                    </td></tr>
                <tr><th scope="row"><label for="login_id">로그인 아이디</label></th>
                    <td>
                        <input type="text" name="login_id" id="login_id" value="<?php echo get_text($row['login_id']); ?>" class="frm_input" maxlength="50" required <?php echo $id > 0 ? 'readonly style="background:#eee;"' : ''; ?>>
                        <?php if ($id == 0) echo '<span class="help_txt">영문/숫자 조합</span>'; ?>
                    </td></tr>
                <tr><th scope="row"><label for="login_password">비밀번호</label></th>
                    <td>
                        <input type="password" name="login_password" id="login_password" value="" class="frm_input" maxlength="50" <?php echo $id == 0 ? 'required' : ''; ?>>
                        <?php if ($id > 0) echo '<span class="help_txt">비밀번호를 변경할 때만 입력하세요.</span>'; ?>
                    </td></tr>
                <tr><th scope="row"><label for="manager_name">담당자명</label></th>
                    <td><input type="text" name="manager_name" id="manager_name" value="<?php echo get_text($row['manager_name']); ?>" class="frm_input" maxlength="50" required></td></tr>
                <tr><th scope="row"><label for="manager_email">이메일</label></th>
                    <td><input type="email" name="manager_email" id="manager_email" value="<?php echo get_text($row['manager_email']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row">상태</th>
                    <td>
                        <label><input type="radio" name="status" value="active" <?php echo $row['status'] === 'active' ? 'checked' : ''; ?>> 사용중 (로그인 허용)</label>
                        <label style="margin-left:15px;"><input type="radio" name="status" value="inactive" <?php echo $row['status'] === 'inactive' ? 'checked' : ''; ?>> 정지 (로그인 차단)</label>
                    </td></tr>
                <?php if ($id > 0) { ?>
                <tr><th scope="row">로그인 실패 초기화</th>
                    <td>
                        <label><input type="checkbox" name="reset_fail_count" value="1"> 초기화 (현재: <?php echo $row['login_fail_count']; ?>회)</label>
                    </td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="./advertiser_account_list.php" class="btn btn_02">목록</a>
    </div>
</form>

<script>
function faccountform_submit(f) {
    if (!f.advertiser_id.value) { alert('광고주를 선택하세요.'); f.advertiser_id.focus(); return false; }
    if (!f.login_id.value.trim()) { alert('아이디를 입력하세요.'); f.login_id.focus(); return false; }
    <?php if ($id == 0) { ?>
    if (!f.login_password.value.trim()) { alert('비밀번호를 입력하세요.'); f.login_password.focus(); return false; }
    <?php } ?>
    return true;
}
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
