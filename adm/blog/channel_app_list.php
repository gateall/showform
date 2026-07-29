<?php
include_once('./_common.php');

$sub_menu = '361110';
auth_check_menu($auth, $sub_menu, 'r');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$g5['title'] = 'SNS/채널 앱 설정';

$table = bp_table('channel_apps');
$result = sql_query(" select * from {$table} order by id asc ");

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>SNS/외부 블로그 채널의 앱 레벨 OAuth 설정(client_id/client_secret)입니다. 채널별 사용자(사이트) 연동은 발행사이트관리 &gt; 사이트 수정 화면에서 진행합니다.</p>
</div>

<a href="./channel_app_form.php" class="btn btn_01">채널 앱 등록</a>

<?php
$list = array();
if ($result && sql_num_rows($result) > 0) {
    while ($row = sql_fetch_array($result)) {
        $list[] = $row;
    }
}
?>

<div class="tbl_head01 tbl_wrap" style="margin-top:10px;">
    <table>
        <caption>채널 앱 목록</caption>
        <thead>
            <tr>
                <th scope="col">번호</th>
                <th scope="col">채널 코드</th>
                <th scope="col">표시명</th>
                <th scope="col">redirect_uri</th>
                <th scope="col">사용 여부</th>
                <th scope="col">수정일</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($list) > 0) { ?>
                <?php foreach ($list as $row) { ?>
                    <tr>
                        <td><?php echo (int) $row['id']; ?></td>
                        <td><code><?php echo get_text($row['channel_code']); ?></code></td>
                        <td style="text-align:left;"><a href="./channel_app_form.php?id=<?php echo (int) $row['id']; ?>"><strong><?php echo get_text($row['display_name']); ?></strong></a></td>
                        <td style="word-break:break-all;"><?php echo get_text($row['redirect_uri']); ?></td>
                        <td><?php echo $row['is_active'] === 'Y' ? '사용' : '중지'; ?></td>
                        <td><?php echo $row['updated_at'] ? get_text($row['updated_at']) : get_text($row['created_at']); ?></td>
                        <td><a href="./channel_app_form.php?id=<?php echo (int) $row['id']; ?>" class="btn btn_02">수정</a></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="7" class="empty_table">등록된 채널 앱이 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
