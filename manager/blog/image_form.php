<?php
$sub_menu = '360800';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '이미지 업로드';

$tbl_projects = bp_table('content_projects');

// 프로젝트 목록 가져오기
$sql = " select id, title from {$tbl_projects} where deleted_at is null order by id desc ";
$result = sql_query($sql);
$projects = array();
while ($row = sql_fetch_array($result)) {
    $projects[] = $row;
}

include_once(__DIR__.'/../layout/header.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/admin_extend_sf_blog.css">', 0);
?>

<form name="fimage" id="fimage" action="./image_update.php" method="post" enctype="multipart/form-data" onsubmit="return fimage_submit(this);">
<input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>이미지 업로드</caption>
        <tbody>
        <tr>
            <th scope="row"><label for="project_id">연관 프로젝트</label></th>
            <td>
                <select name="project_id" id="project_id">
                    <option value="0">선택 없음 (공용 이미지)</option>
                    <?php foreach ($projects as $p) { ?>
                        <option value="<?php echo $p['id']; ?>"><?php echo get_text($p['title']); ?></option>
                    <?php } ?>
                </select>
                <div class="frm_info">특정 프로젝트에 종속된 이미지라면 선택하세요.</div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="upload_file">이미지 파일<strong class="sound_only">필수</strong></label></th>
            <td>
                <input type="file" name="upload_file" id="upload_file" accept="image/*" required>
                <div class="frm_info">JPG, PNG, GIF, WebP 형식의 이미지 파일만 업로드 가능합니다. (최대 10MB)</div>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="convert_to_webp">WebP 변환</label></th>
            <td>
                <input type="checkbox" name="convert_to_webp" id="convert_to_webp" value="1">
                <label for="convert_to_webp">업로드 시 WebP 형식으로 변환해 저장합니다(용량 최적화). 이미 WebP 파일이거나 서버가 변환을 지원하지 않으면 원본 형식이 그대로 저장됩니다.</label>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="is_ai_generated">AI 생성 여부</label></th>
            <td>
                <input type="checkbox" name="is_ai_generated" id="is_ai_generated" value="1">
                <label for="is_ai_generated">Midjourney/DALL-E 등 AI로 생성된 이미지입니다.</label>
            </td>
        </tr>
        <tr id="row_ai_prompt" style="display:none;">
            <th scope="row"><label for="ai_prompt">AI 프롬프트</label></th>
            <td>
                <textarea name="ai_prompt" id="ai_prompt" rows="3" style="width:100%;"></textarea>
                <div class="frm_info">이미지 생성에 사용된 프롬프트를 남겨두면 나중에 참고하기 좋습니다.</div>
            </td>
        </tr>
        </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./image_list.php" class="btn btn_02">목록</a>
    <input type="submit" value="업로드" class="btn_submit btn" accesskey="s">
</div>
</form>

<script>
$(function() {
    $('#is_ai_generated').on('change', function() {
        if ($(this).is(':checked')) {
            $('#row_ai_prompt').show();
        } else {
            $('#row_ai_prompt').hide();
        }
    });
});

function fimage_submit(f) {
    if (!f.upload_file.value) {
        alert("업로드할 이미지 파일을 선택해주세요.");
        f.upload_file.focus();
        return false;
    }
    
    // 파일 확장자 체크
    var ext = f.upload_file.value.split('.').pop().toLowerCase();
    if($.inArray(ext, ['gif','png','jpg','jpeg','webp']) == -1) {
        alert('gif, png, jpg, jpeg, webp 파일만 업로드 할수 있습니다.');
        f.upload_file.focus();
        return false;
    }

    return confirm("업로드 하시겠습니까?");
}
</script>

<?php
include_once(__DIR__.'/../layout/footer.php');
