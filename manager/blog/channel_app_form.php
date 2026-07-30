<?php
include_once('./_common.php');

$sub_menu = '361110';
auth_check_menu($auth, $sub_menu, 'w');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$table = bp_table('channel_apps');

if ($id > 0) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' ");
    if (!$row) {
        alert('채널 앱을 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/channel_app_list.php');
    }
} else {
    $row = array(
        'channel_code' => 'naver_blog', 'display_name' => '네이버 블로그', 'client_id' => '',
        'masked_hint' => '', 'redirect_uri' => G5_ADMIN_URL . '/blog/naver_oauth_callback.php', 'is_active' => 'N',
    );
}

$g5['title'] = $id > 0 ? '채널 앱 수정' : '채널 앱 등록';

include_once(__DIR__ . '/../layout/header.php');
?>
<div class="local_desc01 local_desc">
    <p>client_secret은 저장 시 서버에서 암호화되며, 이 화면에는 새 값을 입력할 때만 반영됩니다(기존 값은 다시 표시되지 않습니다). redirect_uri는 네이버 개발자센터의 애플리케이션 설정에 등록된 Callback URL과 정확히 일치해야 합니다.</p>
</div>

<form name="fchannelappform" method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/channel_app_update.php">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <?php if ($id > 0) { ?>
    <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
    <?php } ?>
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo get_text($g5['title']); ?></caption>
            <tbody>
                <tr><th scope="row"><label for="channel_code">채널 코드</label></th>
                    <td>
                        <?php if ($id > 0) { ?>
                            <input type="text" value="<?php echo get_text($row['channel_code']); ?>" class="frm_input" readonly style="background:#f5f5f5;">
                            <input type="hidden" name="channel_code" value="<?php echo get_text($row['channel_code']); ?>">
                            <span class="help_txt">등록 후에는 코드를 변경할 수 없습니다.</span>
                        <?php } else { ?>
                            <input type="text" name="channel_code" id="channel_code" value="<?php echo get_text($row['channel_code']); ?>" class="frm_input" maxlength="30" placeholder="예: naver_blog" required>
                            <span class="help_txt">영문 소문자·숫자·밑줄만 사용하세요. 등록 후 변경 불가.</span>
                        <?php } ?>
                    </td></tr>
                <tr><th scope="row"><label for="display_name">표시명</label></th>
                    <td><input type="text" name="display_name" id="display_name" value="<?php echo get_text($row['display_name']); ?>" class="frm_input" maxlength="100" required></td></tr>
                <tr><th scope="row">사용 여부</th>
                    <td>
                        <label><input type="radio" name="is_active" value="Y" <?php echo $row['is_active'] === 'Y' ? 'checked' : ''; ?>> 사용함</label>
                        <label style="margin-left:15px;"><input type="radio" name="is_active" value="N" <?php echo $row['is_active'] === 'N' ? 'checked' : ''; ?>> 사용안함</label>
                    </td></tr>
                <tr><th scope="row"><label for="client_id">Client ID</label></th>
                    <td><input type="text" name="client_id" id="client_id" value="<?php echo get_text($row['client_id']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row"><label for="client_secret">Client Secret</label></th>
                    <td>
                        <input type="password" name="client_secret" id="client_secret" value="" class="frm_input" autocomplete="new-password" placeholder="새 값을 입력할 때만 교체됩니다">
                        <span class="help_txt"><?php echo $row['masked_hint'] ? '현재 저장된 값: ' . get_text($row['masked_hint']) : '저장된 값 없음'; ?></span>
                    </td></tr>
                <tr><th scope="row"><label for="redirect_uri">Redirect URI</label></th>
                    <td>
                        <input type="text" name="redirect_uri" id="redirect_uri" value="<?php echo get_text($row['redirect_uri']); ?>" class="frm_input" maxlength="255">
                        <span class="help_txt">네이버 개발자센터에 등록한 Callback URL과 동일해야 합니다.</span>
                    </td></tr>
            </tbody>
        </table>
    </div>
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="./channel_app_list.php" class="btn btn_02">목록</a>
    </div>
</form>

<script>
function fchannelappform_submit(f) {
    if (f.channel_code && !f.channel_code.readOnly && !f.channel_code.value.trim()) {
        alert('채널 코드를 입력해 주세요.');
        return false;
    }
    if (!f.display_name.value.trim()) {
        alert('표시명을 입력해 주세요.');
        return false;
    }
    return true;
}
document.forms['fchannelappform'].onsubmit = function() { return fchannelappform_submit(this); };
</script>

<?php include_once(__DIR__ . '/../layout/footer.php');
