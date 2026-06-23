<?php
include_once('./_common.php');
if (!defined('_GNUBOARD_')) exit;
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) alert('잘못된 접근입니다.');
$table = G5_TABLE_PREFIX . 'landing_page';
$row = sql_fetch(" select * from {$table} where id = '{$id}' ");
if (!$row) {
    if ($is_admin) {
        alert('해당 랜딩페이지를 찾을 수 없습니다.', G5_ADMIN_URL . '/landing/landing_form.php');
    }
    alert('해당 랜딩페이지를 찾을 수 없습니다.');
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $tel = isset($_POST['tel']) ? trim($_POST['tel']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $category = isset($_POST['category']) ? trim($_POST['category']) : '';
    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
    if ($name !== '' && $tel !== '' && $content !== '') {
        $inq = G5_TABLE_PREFIX . 'landing_inquiry';
        sql_query(" insert into {$inq} set landing_id='{$id}', name='".sql_real_escape_string($name)."', tel='".sql_real_escape_string($tel)."', email='".sql_real_escape_string($email)."', category='".sql_real_escape_string($category)."', content='".sql_real_escape_string($content)."', ip='".sql_real_escape_string($_SERVER['REMOTE_ADDR'])."', created_at='".G5_TIME_YMDHIS."' ");
        alert('상담 문의가 접수되었습니다.', G5_URL . '/page/landing.php?id=' . $id);
    }
}
header('Content-Type:text/html; charset=UTF-8');
add_stylesheet('<link rel="stylesheet" href="'.G5_THEME_URL.'/landing/landing.css?ver='.G5_CSS_VER.'">', 20);
include_once(G5_THEME_PATH.'/head.php');
?>
<main class="knu-main page-landing">
    <section class="landing-view">
        <div class="landing-view__hero">
            <h1><?php echo get_text($row['subject']); ?></h1>
            <p><?php echo nl2br(get_text($row['hero_text'])); ?></p>
            <p><?php echo nl2br(get_text($row['intro_text'])); ?></p>
        </div>
        <div class="landing-view__hero" style="margin-top:20px;background:#fff;color:#111;">
            <h2>상담폼</h2>
            <form method="post">
                <p><input type="text" name="name" placeholder="이름" style="width:100%;padding:12px;margin-bottom:10px;"></p>
                <p><input type="text" name="tel" placeholder="연락처" style="width:100%;padding:12px;margin-bottom:10px;"></p>
                <p><input type="text" name="email" placeholder="이메일" style="width:100%;padding:12px;margin-bottom:10px;"></p>
                <p><input type="text" name="category" placeholder="문의유형" style="width:100%;padding:12px;margin-bottom:10px;"></p>
                <p><textarea name="content" placeholder="문의내용" rows="5" style="width:100%;padding:12px;"></textarea></p>
                <p><button type="submit" style="padding:12px 18px;">문의 저장</button></p>
            </form>
        </div>
    </section>
</main>
<?php include_once(G5_THEME_PATH.'/tail.php');
