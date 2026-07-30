<?php
include_once('./_common.php');

$sub_menu = '361100'; // 새 메뉴 코드
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '광고주 로그인 계정 관리';
include_once(__DIR__ . '/../layout/header.php');

$tbl_acc = bp_table('advertiser_accounts');
$tbl_adv = bp_table('advertisers');

$sql_common = " from {$tbl_acc} a left join {$tbl_adv} ad on a.advertiser_id = ad.id ";
$sql_search = " where 1=1 ";

$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
if ($stx) {
    $sql_search .= " and (a.login_id like '%" . sql_real_escape_string($stx) . "%' or ad.name like '%" . sql_real_escape_string($stx) . "%') ";
}

$sql = " select count(*) as cnt {$sql_common} {$sql_search} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$sql = " select a.*, ad.name as advertiser_name {$sql_common} {$sql_search} order by a.id desc ";
$result = sql_query($sql);
?>

<div class="local_ov01 local_ov">
    <span class="btn_ov01"><span class="ov_txt">전체 </span><span class="ov_num"> <?php echo number_format($total_count); ?>건</span></span>
</div>

<form name="fsearch" id="fsearch" class="local_sch01 local_sch" method="get">
<label for="stx" class="sound_only">검색어<strong class="sound_only"> 필수</strong></label>
<input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input">
<input type="submit" class="btn_submit" value="검색">
</form>

<div class="btn_fixed_top">
    <a href="./advertiser_account_form.php" class="btn_01 btn">계정 생성</a>
</div>

<div class="tbl_head01 tbl_wrap">
    <table>
        <caption><?php echo $g5['title']; ?> 목록</caption>
        <thead>
        <tr>
            <th scope="col">계정 ID</th>
            <th scope="col">연결된 광고주</th>
            <th scope="col">로그인 아이디</th>
            <th scope="col">담당자명</th>
            <th scope="col">이메일</th>
            <th scope="col">상태</th>
            <th scope="col">최근 로그인</th>
            <th scope="col">실패</th>
            <th scope="col">관리</th>
        </tr>
        </thead>
        <tbody>
        <?php
        for ($i=0; $row=sql_fetch_array($result); $i++) {
            $status_str = $row['status'] === 'active' ? '사용중' : '<span style="color:#c00">정지</span>';
        ?>
        <tr>
            <td class="td_num"><?php echo $row['id']; ?></td>
            <td><?php echo get_text($row['advertiser_name']); ?></td>
            <td class="td_left"><b><?php echo get_text($row['login_id']); ?></b></td>
            <td><?php echo get_text($row['manager_name']); ?></td>
            <td><?php echo get_text($row['manager_email']); ?></td>
            <td class="td_chk"><?php echo $status_str; ?></td>
            <td class="td_datetime"><?php echo $row['last_login_at'] ? $row['last_login_at'] : '-'; ?></td>
            <td class="td_num"><?php echo $row['login_fail_count']; ?></td>
            <td class="td_mng">
                <a href="./advertiser_account_form.php?id=<?php echo $row['id']; ?>" class="btn btn_03">수정</a>
            </td>
        </tr>
        <?php }
        if ($total_count == 0) {
            echo '<tr><td colspan="9" class="empty_table">자료가 없습니다.</td></tr>';
        }
        ?>
        </tbody>
    </table>
</div>

<?php include_once(__DIR__ . '/../layout/footer.php');
