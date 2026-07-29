<?php
include_once('./_common.php');

$sub_menu = '360600';
auth_check_menu($auth, $sub_menu, 'r');

$projects_table = bp_table('content_projects');
$keywords_table = bp_table('content_keywords');

$project_id = isset($_GET['project_id']) ? (int) $_GET['project_id'] : 0;
$project = sql_fetch(" select * from {$projects_table} where id = '{$project_id}' ");
if (!$project) {
    alert('콘텐츠 프로젝트를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/project_list.php');
}

$g5['title'] = '키워드 등록 — ' . $project['topic'];

$groups = array(
    'primary' => '대표 키워드', 'secondary' => '보조 키워드', 'question' => '고객 질문 키워드',
    'region' => '지역 키워드', 'service' => '서비스 키워드', 'comparison' => '비교 키워드',
    'intent' => '구매·상담 의도 키워드', 'exclude' => '제외 키워드',
);

$result = sql_query(" select * from {$keywords_table} where project_id = '{$project_id}' order by keyword_group asc, id asc ");
$by_group = array();
while ($row = sql_fetch_array($result)) {
    $by_group[$row['keyword_group']][] = $row;
}

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>프로젝트 "<?php echo get_text($project['topic']); ?>"의 키워드를 그룹별로 관리합니다. 잠긴(🔒) 키워드는 향후 AI 재생성 시에도 유지됩니다.</p>
    <p><a href="./project_form.php?id=<?php echo (int)$project_id; ?>" class="btn btn_02">프로젝트로 돌아가기</a></p>
</div>

<form name="fkeywordadd" method="post" action="./keyword_update.php" onsubmit="return fkeywordadd_submit(this);">
    <input type="hidden" name="mode" value="add">
    <input type="hidden" name="project_id" value="<?php echo (int)$project_id; ?>">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>키워드 추가</caption>
            <tbody>
                <tr>
                    <th scope="row">그룹</th>
                    <td>
                        <select name="keyword_group">
                            <?php foreach ($groups as $code => $label) { ?>
                                <option value="<?php echo $code; ?>"><?php echo get_text($label); ?></option>
                            <?php } ?>
                        </select>
                    </td>
                    <th scope="row">키워드</th>
                    <td><input type="text" name="keyword" class="frm_input" maxlength="100" required></td>
                    <td><label><input type="checkbox" name="is_locked" value="Y"> 잠금</label></td>
                    <td><button type="submit" class="btn_submit btn">추가</button></td>
                </tr>
            </tbody>
        </table>
    </div>
</form>

<?php foreach ($groups as $code => $label) { ?>
    <div class="tbl_head01 tbl_wrap" style="margin-top:15px;">
        <table>
            <caption><?php echo get_text($label); ?></caption>
            <thead><tr><th scope="col">키워드</th><th scope="col">잠금</th><th scope="col">관리</th></tr></thead>
            <tbody>
                <?php if (!empty($by_group[$code])) { ?>
                    <?php foreach ($by_group[$code] as $kw) { ?>
                        <tr>
                            <td style="text-align:left;"><?php echo get_text($kw['keyword']); ?></td>
                            <td><?php echo $kw['is_locked'] === 'Y' ? '🔒' : ''; ?></td>
                            <td>
                                <a href="./keyword_update.php?mode=delete&amp;id=<?php echo (int)$kw['id']; ?>&amp;project_id=<?php echo (int)$project_id; ?>&amp;token=<?php echo get_admin_token(); ?>" class="btn btn_01" onclick="return confirm('삭제하시겠습니까?');">삭제</a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr><td colspan="3" class="empty_table">등록된 키워드가 없습니다.</td></tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
<?php } ?>

<script>
function fkeywordadd_submit(f) {
    if (!f.keyword.value.trim()) { alert('키워드를 입력해 주세요.'); return false; }
    return true;
}
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
