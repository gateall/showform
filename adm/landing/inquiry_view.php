<?php
$sub_menu = "990200";
include_once('../../common.php');
include_once(G5_ADMIN_PATH.'/admin.lib.php');
if (!$is_admin) alert('관리자만 접근 가능합니다.', G5_URL);
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id) alert('잘못된 접근입니다.');
$t = G5_TABLE_PREFIX.'landing_inquiry';
$inquiry = sql_fetch(" select * from {$t} where id = '{$id}' ");
if (!$inquiry) alert('존재하지 않는 문의 내역입니다.');
$g5['title'] = '문의 상세';
include_once(G5_ADMIN_PATH.'/admin.head.php');
?>
<div class="landing-admin-wrap">
    <div class="landing-admin-header"><div><h1>문의관리 상세</h1><p>상담 문의 내용을 확인합니다.</p></div><a href="./inquiry_list.php" class="landing-btn landing-btn-primary">목록으로</a></div>
    <div class="landing-admin-card">
        <table class="landing-table">
            <tr><th>이름</th><td><?php echo get_text($inquiry['name']); ?></td></tr>
            <tr><th>연락처</th><td><?php echo get_text($inquiry['tel']); ?></td></tr>
            <tr><th>이메일</th><td><?php echo get_text($inquiry['email']); ?></td></tr>
            <tr><th>문의유형</th><td><?php echo get_text($inquiry['category']); ?></td></tr>
            <tr><th>문의내용</th><td><?php echo nl2br(get_text($inquiry['content'])); ?></td></tr>
            <tr><th>IP</th><td><?php echo get_text($inquiry['ip']); ?></td></tr>
            <tr><th>등록일</th><td><?php echo $inquiry['created_at']; ?></td></tr>
        </table>
    </div>
</div>
<?php include_once(G5_ADMIN_PATH.'/admin.tail.php');
