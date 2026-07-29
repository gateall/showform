<?php
include_once('./_common.php');

$sub_menu = '360200';
auth_check_menu($auth, $sub_menu, 'w');

$table = bp_table('sites');
$adv_table = bp_table('advertisers');
$cred_table = bp_table('site_credentials');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = array('id' => 0, 'advertiser_id' => 0, 'name' => '', 'platform' => 'wordpress', 'base_url' => '', 'status' => 'Y');
$cred = array('cred_username' => '', 'masked_hint' => '');

if ($id > 0) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' ");
    if (!$row) {
        alert('사이트 정보를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/site_list.php');
    }
    $found_cred = sql_fetch(" select * from {$cred_table} where site_id = '{$id}' and cred_type = 'wp_app_password' ");
    if ($found_cred) {
        $cred = $found_cred;
    }
    $g5['title'] = '발행 사이트 수정';
} else {
    $g5['title'] = '발행 사이트 등록';
}

$advertisers = sql_query(" select id, name from {$adv_table} where status = 'Y' order by name asc ");

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>워드프레스 Application Password는 저장 시 서버에서 암호화되며, 이 화면에는 새 값을 입력할 때만 반영됩니다(기존 값은 다시 표시되지 않습니다).</p>
</div>

<form name="fsiteform" method="post" action="./site_update.php" onsubmit="return fsiteform_submit(this);">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>사이트 등록/수정</caption>
            <tbody>
                <tr><th scope="row"><label for="advertiser_id">광고주</label></th>
                    <td>
                        <select name="advertiser_id" id="advertiser_id" required>
                            <option value="">선택</option>
                            <?php while ($a = sql_fetch_array($advertisers)) { ?>
                                <option value="<?php echo (int)$a['id']; ?>" <?php echo (int)$row['advertiser_id'] === (int)$a['id'] ? 'selected' : ''; ?>><?php echo get_text($a['name']); ?></option>
                            <?php } ?>
                        </select>
                    </td></tr>
                <tr><th scope="row"><label for="name">사이트명</label></th>
                    <td><input type="text" name="name" id="name" value="<?php echo get_text($row['name']); ?>" class="frm_input" maxlength="255" required></td></tr>
                <tr><th scope="row">플랫폼</th>
                    <td>
                        <select name="platform" id="platform">
                            <option value="wordpress" <?php echo $row['platform'] === 'wordpress' ? 'selected' : ''; ?>>워드프레스</option>
                            <option value="php" <?php echo $row['platform'] === 'php' ? 'selected' : ''; ?>>자체 PHP 사이트</option>
                            <option value="naver" <?php echo $row['platform'] === 'naver' ? 'selected' : ''; ?>>네이버 (등록 패키지)</option>
                        </select>
                    </td></tr>
                <tr><th scope="row"><label for="base_url">사이트 주소</label></th>
                    <td><input type="text" name="base_url" id="base_url" value="<?php echo get_text($row['base_url']); ?>" class="frm_input" maxlength="255" placeholder="https://example.com"></td></tr>
                <tr><th scope="row">상태</th>
                    <td>
                        <label><input type="radio" name="status" value="Y" <?php echo $row['status'] === 'Y' ? 'checked' : ''; ?>> 사용</label>
                        <label style="margin-left:15px;"><input type="radio" name="status" value="N" <?php echo $row['status'] === 'N' ? 'checked' : ''; ?>> 중지</label>
                    </td></tr>
            </tbody>
        </table>
    </div>

    <div class="tbl_frm01 tbl_wrap" style="margin-top:15px;">
        <table>
            <caption>워드프레스 채널 인증 (Application Password)</caption>
            <tbody>
                <tr><th scope="row"><label for="wp_username">WP 사용자명</label></th>
                    <td><input type="text" name="wp_username" id="wp_username" value="<?php echo get_text($cred['cred_username']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row"><label for="wp_app_password">Application Password</label></th>
                    <td>
                        <input type="password" name="wp_app_password" id="wp_app_password" value="" class="frm_input" autocomplete="new-password" placeholder="새 값을 입력할 때만 교체됩니다">
                        <span class="help_txt"><?php echo $cred['masked_hint'] ? '현재 저장된 값: ' . get_text($cred['masked_hint']) : '저장된 값 없음'; ?></span>
                    </td></tr>
                <tr><th scope="row">연결 확인</th>
                    <td>
                        <button type="button" id="btn_wp_test" class="btn btn_02">연결 테스트</button>
                        <span id="wp_test_result" class="help_txt"></span>
                    </td></tr>
            </tbody>
        </table>
    </div>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="./site_list.php" class="btn btn_02">목록</a>
    </div>
</form>

<script>
function fsiteform_submit(f) {
    if (!f.advertiser_id.value) { alert('광고주를 선택해 주세요.'); return false; }
    if (!f.name.value.trim()) { alert('사이트명을 입력해 주세요.'); f.name.focus(); return false; }
    return true;
}

document.getElementById('btn_wp_test').addEventListener('click', function () {
    var btn = this;
    var resultEl = document.getElementById('wp_test_result');
    btn.disabled = true;
    btn.textContent = '테스트 중…';
    resultEl.textContent = '';

    $.post('./site_test_connection.php', {
        token: document.getElementsByName('token')[0].value,
        site_id: document.getElementsByName('id')[0].value,
        base_url: document.getElementById('base_url').value,
        wp_username: document.getElementById('wp_username').value,
        wp_app_password: document.getElementById('wp_app_password').value
    }, function (res) {
        btn.disabled = false;
        btn.textContent = '연결 테스트';
        if (res.success) {
            resultEl.style.color = '#0a0';
            resultEl.textContent = '연결 성공';
        } else {
            resultEl.style.color = '#c00';
            resultEl.textContent = '연결 실패: ' + res.error;
        }
    }, 'json').fail(function () {
        btn.disabled = false;
        btn.textContent = '연결 테스트';
        resultEl.style.color = '#c00';
        resultEl.textContent = '서버 통신 오류';
    });
});
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
