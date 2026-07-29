<?php
include_once('./_common.php');

$sub_menu = '360200';
auth_check_menu($auth, $sub_menu, 'w');

$table = bp_table('advertisers');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = array(
    'id' => 0, 'name' => '', 'phone' => '', 'sub_phone' => '', 'address' => '',
    'domain' => '', 'consult_url' => '', 'kakao_channel' => '', 'service_region' => '',
    'core_service' => '', 'intro_text' => '', 'forbidden_words' => '', 'mandatory_notice' => '',
    'status' => 'Y',
);

if ($id > 0) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' ");
    if (!$row) {
        alert('광고주 정보를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/advertiser_list.php');
    }
    $g5['title'] = '광고주 수정';
} else {
    $g5['title'] = '광고주 등록';
}

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>콘텐츠 생성 시 상호·전화·주소·상담URL 등이 치환변수({{business_name}} 등)로 자동 삽입됩니다.</p>
</div>

<form name="fadvertiserform" method="post" action="./advertiser_update.php" onsubmit="return fadvertiserform_submit(this);">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>광고주 등록/수정</caption>
            <tbody>
                <tr><th scope="row"><label for="name">상호</label></th>
                    <td><input type="text" name="name" id="name" value="<?php echo get_text($row['name']); ?>" class="frm_input" maxlength="255" required></td></tr>
                <tr><th scope="row"><label for="phone">대표 전화</label></th>
                    <td><input type="text" name="phone" id="phone" value="<?php echo get_text($row['phone']); ?>" class="frm_input" maxlength="50"></td></tr>
                <tr><th scope="row"><label for="sub_phone">보조 전화</label></th>
                    <td><input type="text" name="sub_phone" id="sub_phone" value="<?php echo get_text($row['sub_phone']); ?>" class="frm_input" maxlength="50"></td></tr>
                <tr><th scope="row"><label for="address">주소</label></th>
                    <td><input type="text" name="address" id="address" value="<?php echo get_text($row['address']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row"><label for="domain">도메인</label></th>
                    <td><input type="text" name="domain" id="domain" value="<?php echo get_text($row['domain']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row"><label for="consult_url">상담 URL</label></th>
                    <td><input type="text" name="consult_url" id="consult_url" value="<?php echo get_text($row['consult_url']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row"><label for="kakao_channel">카카오채널</label></th>
                    <td><input type="text" name="kakao_channel" id="kakao_channel" value="<?php echo get_text($row['kakao_channel']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row"><label for="service_region">서비스 지역</label></th>
                    <td><input type="text" name="service_region" id="service_region" value="<?php echo get_text($row['service_region']); ?>" class="frm_input" maxlength="255" placeholder="예: 울산,부산"></td></tr>
                <tr><th scope="row"><label for="core_service">핵심 서비스</label></th>
                    <td><input type="text" name="core_service" id="core_service" value="<?php echo get_text($row['core_service']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row"><label for="intro_text">기본 소개</label></th>
                    <td><textarea name="intro_text" id="intro_text" rows="3" style="width:100%;"><?php echo get_text($row['intro_text']); ?></textarea></td></tr>
                <tr><th scope="row"><label for="forbidden_words">금지 표현</label></th>
                    <td><textarea name="forbidden_words" id="forbidden_words" rows="2" style="width:100%;" placeholder="한 줄에 하나씩, 예: 업계 1위"><?php echo get_text($row['forbidden_words']); ?></textarea></td></tr>
                <tr><th scope="row"><label for="mandatory_notice">필수 고지</label></th>
                    <td><textarea name="mandatory_notice" id="mandatory_notice" rows="2" style="width:100%;" placeholder="예: 요금·조건은 현장에 따라 달라질 수 있음"><?php echo get_text($row['mandatory_notice']); ?></textarea></td></tr>
                <tr><th scope="row">상태</th>
                    <td>
                        <label><input type="radio" name="status" value="Y" <?php echo $row['status'] === 'Y' ? 'checked' : ''; ?>> 사용</label>
                        <label style="margin-left:15px;"><input type="radio" name="status" value="N" <?php echo $row['status'] === 'N' ? 'checked' : ''; ?>> 중지</label>
                    </td></tr>
            </tbody>
        </table>
    </div>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="./advertiser_list.php" class="btn btn_02">목록</a>
    </div>
</form>

<script>
function fadvertiserform_submit(f) {
    if (!f.name.value.trim()) { alert('상호를 입력해 주세요.'); f.name.focus(); return false; }
    return true;
}
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
