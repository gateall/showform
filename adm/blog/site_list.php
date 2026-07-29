<?php
include_once('./_common.php');

$sub_menu = '360300';
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '발행 사이트 관리';

$table = bp_table('sites');
$adv_table = bp_table('advertisers');
$cred_table = bp_table('site_credentials');

$result = sql_query(" select s.*, a.name as advertiser_name,
                        (select masked_hint from {$cred_table} c where c.site_id = s.id and c.cred_type = 'wp_app_password' limit 1) as wp_masked_hint,
                        (select masked_hint from {$cred_table} c where c.site_id = s.id and c.cred_type = 'php_api_key' limit 1) as php_masked_hint
                      from {$table} s
                      left join {$adv_table} a on a.id = s.advertiser_id
                      order by s.id desc ");

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>광고주별 발행 사이트(워드프레스/자체 PHP/네이버)를 관리합니다. 워드프레스는 Application Password로 연결하며 값은 화면에 다시 표시되지 않습니다.</p>
</div>

<a href="./site_form.php" class="btn btn_01">사이트 등록</a>

<?php
$list = array();
if ($result && sql_num_rows($result) > 0) {
    while ($row = sql_fetch_array($result)) {
        $list[] = $row;
    }
}
?>

<!-- 모바일: 카드 뷰 -->
<div class="bp-project-cards" id="mobile_card_view" style="margin-top:15px;">
    <?php if (count($list) > 0) { ?>
        <?php foreach ($list as $row) { ?>
        <div class="bp-project-card">
            <div class="bp-project-card-head">
                <a href="./site_form.php?id=<?php echo (int) $row['id']; ?>" class="bp-project-title">
                    <?php echo get_text($row['name']); ?>
                </a>
                <span class="bp-status-badge bp-status-badge-<?php echo $row['status'] === 'Y' ? 'published' : 'failed'; ?>">
                    <?php echo $row['status'] === 'Y' ? '사용' : '중지'; ?>
                </span>
            </div>
            <div class="bp-project-meta">
                <span>번호: <?php echo (int) $row['id']; ?></span>
                <span>광고주: <?php echo get_text($row['advertiser_name']); ?></span>
                <span>플랫폼: <?php echo get_text($row['platform']); ?></span>
                <span>WP 인증: <?php echo $row['wp_masked_hint'] ? get_text($row['wp_masked_hint']) : '미설정'; ?></span>
                <span>PHP API: <?php echo $row['php_masked_hint'] ? get_text($row['php_masked_hint']) : '미설정'; ?></span>
            </div>
            <div class="bp-project-actions">
                <a href="./site_category_mapping.php?site_id=<?php echo (int) $row['id']; ?>" class="btn btn_03">카테고리 매핑</a>
                <a href="./site_form.php?id=<?php echo (int) $row['id']; ?>" class="btn btn_02">수정</a>
                <a href="./site_delete.php?id=<?php echo (int) $row['id']; ?>&amp;token=<?php echo get_admin_token(); ?>" class="btn btn_01" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
            </div>
        </div>
        <?php } ?>
    <?php } else { ?>
        <div class="bp-empty">등록된 사이트가 없습니다.</div>
    <?php } ?>
</div>

<!-- PC: 테이블 뷰 -->
<div class="tbl_head01 tbl_wrap" id="pc_table_view" style="margin-top:10px;">
    <table>
        <caption>발행 사이트 목록</caption>
        <thead>
            <tr>
                <th scope="col">번호</th>
                <th scope="col">광고주</th>
                <th scope="col">사이트명</th>
                <th scope="col">플랫폼</th>
                <th scope="col">WP 인증정보</th>
                <th scope="col">상태</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($list) > 0) { ?>
                <?php foreach ($list as $row) { ?>
                    <tr>
                        <td><?php echo (int)$row['id']; ?></td>
                        <td><?php echo get_text($row['advertiser_name']); ?></td>
                        <td style="text-align:left;"><a href="./site_form.php?id=<?php echo (int)$row['id']; ?>"><strong><?php echo get_text($row['name']); ?></strong></a></td>
                        <td><?php echo get_text($row['platform']); ?></td>
                        <td><?php echo $row['wp_masked_hint'] ? get_text($row['wp_masked_hint']) : '<span style="color:#999;">미설정</span>'; ?><br>
                            <span style="font-size:0.9em; color:#666;">PHP: <?php echo $row['php_masked_hint'] ? get_text($row['php_masked_hint']) : '미설정'; ?></span>
                        </td>
                        <td><?php echo $row['status'] === 'Y' ? '사용' : '중지'; ?></td>
                        <td>
                            <a href="./site_category_mapping.php?site_id=<?php echo (int)$row['id']; ?>" class="btn btn_03">카테고리 매핑</a>
                            <a href="./site_form.php?id=<?php echo (int)$row['id']; ?>" class="btn btn_02">수정</a>
                            <a href="./site_delete.php?id=<?php echo (int)$row['id']; ?>&amp;token=<?php echo get_admin_token(); ?>" class="btn btn_01" onclick="return confirm('정말 삭제하시겠습니까?');">삭제</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="7" class="empty_table">등록된 사이트가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
