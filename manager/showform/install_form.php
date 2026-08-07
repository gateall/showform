<?php
$sub_menu = '700900';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$sql_file = __DIR__ . '/sql/showform_v1.sql';
if (!is_file($sql_file)) {
    alert('SQL 파일을 찾을 수 없습니다: ' . $sql_file);
}

$table_prefix = G5_TABLE_PREFIX;
$table_name = $table_prefix . 'sf_portfolio';
$like = sql_real_escape_string($table_name);
$result = sql_query(" show tables like '{$like}' ", false);
$installed = $result && sql_num_rows($result) > 0;

$install_result = get_session('sf_install_result');
set_session('sf_install_result', '');
$did_install = is_array($install_result);
$install_error = $did_install && isset($install_result['error']) ? $install_result['error'] : '';

$g5['title'] = '쇼폼 — 테이블 설치';

include_once(__DIR__ . '/../layout/header.php');
?>
<div class="local_desc01 local_desc">
    <h2>쇼폼(제작 사례) — 테이블 설치</h2>
    <p>제작 사례(포트폴리오) 관리: <?php echo $installed ? '설치됨' : '<span style="color:#c00;">미설치</span>'; ?></p>
</div>

<?php if ($install_error !== '') { ?>
    <div class="local_desc01 local_desc" style="margin-top:15px; border:1px solid #c00;">
        <p style="color:#c00;">설치 중 오류가 발생했습니다: <?php echo get_text($install_error); ?></p>
    </div>
<?php } elseif ($did_install) { ?>
    <div class="local_desc01 local_desc" style="margin-top:15px;">
        <p>설치가 완료되었습니다.</p>
        <p><a href="./portfolio_list.php" class="btn btn_submit btn">제작 사례 목록으로 이동</a></p>
    </div>
<?php } ?>

<form method="post" action="./install.php">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="<?php echo $installed ? '재설치(변경 없음, 안전)' : '설치 진행'; ?>" class="btn_submit btn" onclick="return confirm('쇼폼 제작 사례 테이블을 설치하시겠습니까?');">
    </div>
</form>

<?php
include_once(__DIR__ . '/../layout/footer.php');
