<?php
require_once './admin.lib.php';
require_once G5_PATH . '/head.php';
$sql = " select * from " . G5_TABLE_PREFIX . "landing_page order by id desc ";
$result = sql_query($sql);
?>
<div class="local_ov01 local_ov">
    <span class="btn_add"><a href="<?php echo G5_ADMIN_URL; ?>/landing_form.php">랜딩등록</a></span>
</div>
<section>
    <h2>랜딩목록</h2>
    <table>
        <thead>
            <tr><th>ID</th><th>랜딩제목</th><th>업체명</th><th>대표전화</th><th>관리</th></tr>
        </thead>
        <tbody>
        <?php while ($row = sql_fetch_array($result)) { ?>
            <tr>
                <td><?php echo (int) $row['id']; ?></td>
                <td><?php echo get_text($row['subject']); ?></td>
                <td><?php echo get_text($row['company']); ?></td>
                <td><?php echo get_text($row['tel']); ?></td>
                <td>
                    <a href="<?php echo G5_URL; ?>/page/landing.php?id=<?php echo (int) $row['id']; ?>" target="_blank">보기</a>
                    <a href="<?php echo G5_ADMIN_URL; ?>/landing_form.php?id=<?php echo (int) $row['id']; ?>">수정</a>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
</section>
<?php require_once G5_PATH . '/tail.php';
