<?php
require_once './admin.lib.php';
require_once G5_PATH . '/head.php';

$id = isset($_GET['id']) ? (int) $id : 0;
$row = array(
    'subject' => '',
    'company' => '',
    'tel' => '',
    'category' => '',
    'area' => '',
    'hero_text' => '',
    'intro_text' => ''
);

if ($id > 0) {
    $sql = " select * from " . G5_TABLE_PREFIX . "landing_page where id = '" . $id . "' ";
    $data = sql_fetch($sql);
    if ($data) {
        $row = array_merge($row, $data);
    }
}
?>
<section class="landing-admin-form">
    <h2>랜딩등록</h2>
    <p>관리자 입력값을 기반으로 랜딩페이지를 자동 출력하는 입력 화면입니다.</p>
    <form name="flanding" method="post" enctype="multipart/form-data" action="<?php echo G5_ADMIN_URL; ?>/landing_form_update.php">
        <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
        <table>
            <tr><th>랜딩제목</th><td><input type="text" name="subject" value="<?php echo get_text($row['subject']); ?>"></td></tr>
            <tr><th>업체명</th><td><input type="text" name="company" value="<?php echo get_text($row['company']); ?>"></td></tr>
            <tr><th>대표전화</th><td><input type="text" name="tel" value="<?php echo get_text($row['tel']); ?>"></td></tr>
            <tr><th>업종</th><td><input type="text" name="category" value="<?php echo get_text($row['category']); ?>"></td></tr>
            <tr><th>서비스지역</th><td><input type="text" name="area" value="<?php echo get_text($row['area']); ?>"></td></tr>
            <tr><th>대표문구</th><td><input type="text" name="hero_text" value="<?php echo get_text($row['hero_text']); ?>"></td></tr>
            <tr><th>소개문구</th><td><textarea name="intro_text" rows="4"><?php echo get_text($row['intro_text']); ?></textarea></td></tr>
            <tr><th>대표이미지</th><td><input type="file" name="hero_image"></td></tr>
        </table>
        <p><button type="submit">저장</button></p>
    </form>
</section>
<?php require_once G5_PATH . '/tail.php';
