<?php
include_once('./_common.php');

$sub_menu = '360500';
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '콘텐츠 프로젝트';

$projects_table = bp_table('content_projects');
$adv_table = bp_table('advertisers');

$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$valid_status = array('draft', 'generated', 'review_required', 'approved', 'publish_pending', 'publishing', 'published', 'failed');

$where = array('1=1');
if (in_array($status, $valid_status, true)) {
    $where[] = "p.status = '" . sql_real_escape_string($status) . "'";
}
$where_sql = ' where ' . implode(' and ', $where);

$result = sql_query(" select p.*, a.name as advertiser_name
                      from {$projects_table} p
                      left join {$adv_table} a on a.id = p.advertiser_id
                      {$where_sql}
                      order by p.id desc limit 100 ");

$status_label = array(
    'draft' => '초안', 'generated' => 'AI생성완료', 'review_required' => '검수대기',
    'approved' => '승인됨', 'publish_pending' => '발행대기', 'publishing' => '발행중',
    'published' => '발행완료', 'failed' => '발행실패',
);

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>광고주·계약에 종속된 블로그 콘텐츠 프로젝트를 관리합니다. 승인(approved) 이전에는 발행할 수 없습니다.</p>
</div>

<form method="get" class="local_sch03 local_sch">
    <select name="status" onchange="this.form.submit();">
        <option value="">전체 상태</option>
        <?php foreach ($status_label as $code => $label) { ?>
            <option value="<?php echo $code; ?>" <?php echo $status === $code ? 'selected' : ''; ?>><?php echo get_text($label); ?></option>
        <?php } ?>
    </select>
    <a href="./project_form.php" class="btn btn_01">콘텐츠 프로젝트 등록</a>
</form>

<div class="tbl_head01 tbl_wrap" style="margin-top:10px;">
    <table>
        <caption>콘텐츠 프로젝트 목록</caption>
        <thead>
            <tr>
                <th scope="col">번호</th>
                <th scope="col">광고주</th>
                <th scope="col">주제</th>
                <th scope="col">대표 키워드</th>
                <th scope="col">상태</th>
                <th scope="col">등록일</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result && sql_num_rows($result) > 0) { ?>
                <?php while ($row = sql_fetch_array($result)) { ?>
                    <tr>
                        <td><?php echo (int)$row['id']; ?></td>
                        <td><?php echo get_text($row['advertiser_name']); ?></td>
                        <td style="text-align:left;"><a href="./project_view.php?id=<?php echo (int)$row['id']; ?>"><strong><?php echo get_text($row['topic']); ?></strong></a></td>
                        <td><?php echo get_text($row['primary_keyword']); ?></td>
                        <td><?php echo isset($status_label[$row['status']]) ? $status_label[$row['status']] : get_text($row['status']); ?></td>
                        <td><?php echo get_text($row['created_at']); ?></td>
                        <td><a href="./project_view.php?id=<?php echo (int)$row['id']; ?>" class="btn btn_02">열기</a></td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="7" class="empty_table">등록된 콘텐츠 프로젝트가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
