<?php
include_once('./_common.php');
$sub_menu = '360300'; // 발행사이트 관리에 속함
auth_check_menu($auth, $sub_menu, 'w');

$site_id = isset($_GET['site_id']) ? (int) $_GET['site_id'] : 0;
if ($site_id < 1) {
    alert('잘못된 접근입니다.');
}

$site_table = bp_table('sites');
$cat_table = bp_table('category_mappings');

$site = sql_fetch(" select * from {$site_table} where id = '{$site_id}' ");
if (!$site) {
    alert('사이트가 존재하지 않습니다.');
}

$g5['title'] = '카테고리 매핑 (' . get_text($site['name']) . ')';
include_once(G5_ADMIN_PATH . '/admin.head.php');

$mappings = sql_query(" select * from {$cat_table} where site_id = '{$site_id}' order by id asc ");
?>

<div class="local_desc01 local_desc">
    <p>우리 시스템의 포스트 카테고리와 타겟 사이트의 카테고리 ID를 매핑합니다.</p>
</div>

<form name="fcategory" id="fcategory" action="./site_category_update.php" method="post">
    <input type="hidden" name="site_id" value="<?php echo $site_id; ?>">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

    <div class="tbl_head01 tbl_wrap">
        <table>
            <caption>카테고리 매핑 목록</caption>
            <thead>
                <tr>
                    <th scope="col">내부 카테고리명</th>
                    <th scope="col">원격 카테고리 ID</th>
                    <th scope="col">원격 카테고리명</th>
                    <th scope="col">삭제</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = sql_fetch_array($mappings)) { ?>
                <tr>
                    <td>
                        <input type="hidden" name="map_id[]" value="<?php echo $row['id']; ?>">
                        <input type="text" name="internal_category[]" value="<?php echo get_text($row['internal_category_name']); ?>" required class="frm_input">
                    </td>
                    <td><input type="text" name="remote_id[]" value="<?php echo get_text($row['remote_category_id']); ?>" required class="frm_input"></td>
                    <td><input type="text" name="remote_name[]" value="<?php echo get_text($row['remote_category_name']); ?>" class="frm_input"></td>
                    <td><input type="checkbox" name="del_map[]" value="<?php echo $row['id']; ?>"> 삭제</td>
                </tr>
                <?php } ?>
                <tr>
                    <td>
                        <input type="hidden" name="map_id[]" value="0">
                        <input type="text" name="internal_category[]" value="" placeholder="새 내부 카테고리" class="frm_input">
                    </td>
                    <td><input type="text" name="remote_id[]" value="" placeholder="새 원격 ID" class="frm_input"></td>
                    <td><input type="text" name="remote_name[]" value="" placeholder="새 원격 카테고리명" class="frm_input"></td>
                    <td>신규</td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="./site_list.php" class="btn btn_02">목록</a>
    </div>
</form>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>
