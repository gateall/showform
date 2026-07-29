<?php
include_once('./_common.php');

$sub_menu = '360500';
auth_check_menu($auth, $sub_menu, 'w');

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$project = null;
$linked_site_ids = array();

$adv_table = bp_table('advertisers');
$sites_table = bp_table('sites');

if ($id > 0) {
    $projects_table = bp_table('content_projects');
    $project = sql_fetch(" select * from {$projects_table} where id = '{$id}' ");
    if (!$project) {
        alert('콘텐츠 프로젝트를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/project_list.php');
    }
    if (!in_array($project['status'], array('draft', 'pending_approval'), true)) {
        alert('승인 이후에는 기본정보를 수정할 수 없습니다. 상세 화면에서 확인해 주세요.', G5_ADMIN_URL . '/blog/project_view.php?id=' . $id);
    }

    $posts_table = bp_table('posts');
    $targets_table = bp_table('post_targets');
    $post = sql_fetch(" select id from {$posts_table} where project_id = '{$id}' order by id desc limit 1 ");
    if ($post) {
        $tresult = sql_query(" select site_id from {$targets_table} where post_id = '" . (int) $post['id'] . "' ");
        while ($t = sql_fetch_array($tresult)) {
            $linked_site_ids[] = (int) $t['site_id'];
        }
    }
}

$g5['title'] = $id > 0 ? '콘텐츠 프로젝트 수정' : '콘텐츠 프로젝트 등록';

$advertisers = sql_query(" select id, name from {$adv_table} where status = 'Y' order by name asc ");
$sites = sql_query(" select s.id, s.name, s.platform, s.advertiser_id, a.name as advertiser_name
                     from {$sites_table} s left join {$adv_table} a on a.id = s.advertiser_id
                     where s.status = 'Y' order by a.name asc, s.name asc ");
$site_rows = array();
while ($s = sql_fetch_array($sites)) {
    $site_rows[] = $s;
}

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p><?php echo $id > 0
        ? '콘텐츠 프로젝트의 기본정보를 수정합니다. 승인 이전(초안·승인대기) 상태에서만 수정할 수 있습니다.'
        : "새 콘텐츠 프로젝트를 등록합니다. 등록 직후 상태는 항상 '초안(draft)'이며, 외부 공개 발행 없이 검수·승인을 거쳐야 발행 단계로 진행됩니다."; ?></p>
</div>

<form name="fprojectform" method="post" action="./project_update.php" onsubmit="return fprojectform_submit(this);">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <?php if ($id > 0) { ?>
    <input type="hidden" name="id" value="<?php echo (int) $id; ?>">
    <?php } ?>
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption><?php echo get_text($g5['title']); ?></caption>
            <tbody>
                <tr><th scope="row"><label for="advertiser_id">사업체(광고주)</label></th>
                    <td>
                        <select name="advertiser_id" id="advertiser_id" required>
                            <option value="">선택</option>
                            <?php while ($a = sql_fetch_array($advertisers)) { ?>
                                <option value="<?php echo (int) $a['id']; ?>" <?php echo ($project && (int) $project['advertiser_id'] === (int) $a['id']) ? 'selected' : ''; ?>><?php echo get_text($a['name']); ?></option>
                            <?php } ?>
                        </select>
                        <span class="help_txt">사업체가 없다면 먼저 <a href="./advertiser_form.php">광고주 등록</a>을 진행해 주세요.</span>
                    </td></tr>
                <tr><th scope="row">발행 사이트 (복수 선택)</th>
                    <td>
                        <?php if ($site_rows) { ?>
                            <?php foreach ($site_rows as $s) { ?>
                                <label style="display:inline-block;margin:2px 10px 2px 0;">
                                    <input type="checkbox" name="site_ids[]" value="<?php echo (int) $s['id']; ?>" <?php echo in_array((int) $s['id'], $linked_site_ids, true) ? 'checked' : ''; ?>>
                                    <?php echo get_text($s['advertiser_name']) . ' - ' . get_text($s['name']) . ' (' . get_text($s['platform']) . ')'; ?>
                                </label>
                            <?php } ?>
                        <?php } else { ?>
                            <span class="help_txt">등록된 사이트가 없습니다. <a href="./site_form.php">사이트 등록</a>을 먼저 진행해 주세요.</span>
                        <?php } ?>
                    </td></tr>
                <tr><th scope="row"><label for="topic">핵심 주제</label></th>
                    <td><input type="text" name="topic" id="topic" class="frm_input" maxlength="255" required value="<?php echo $project ? get_text($project['topic']) : ''; ?>"></td></tr>
                <tr><th scope="row"><label for="primary_keyword">대표 키워드</label></th>
                    <td><input type="text" name="primary_keyword" id="primary_keyword" class="frm_input" maxlength="255" value="<?php echo $project ? get_text($project['primary_keyword']) : ''; ?>"></td></tr>
                <tr><th scope="row">글 목적</th>
                    <td>
                        <select name="content_type">
                            <?php
                            $content_types = array('info' => '정보형', 'comparison' => '비교형', 'case' => '사례형', 'faq' => 'FAQ형', 'promo' => '홍보형');
                            $cur_type = $project ? $project['content_type'] : 'info';
                            foreach ($content_types as $code => $label) {
                            ?>
                                <option value="<?php echo $code; ?>" <?php echo $cur_type === $code ? 'selected' : ''; ?>><?php echo get_text($label); ?></option>
                            <?php } ?>
                        </select>
                    </td></tr>
                <tr><th scope="row"><label for="target_audience">독자</label></th>
                    <td><input type="text" name="target_audience" id="target_audience" class="frm_input" maxlength="255" placeholder="예: 자영업자, 기업 담당자" value="<?php echo $project ? get_text($project['target_audience']) : ''; ?>"></td></tr>
                <tr><th scope="row">글 길이</th>
                    <td>
                        <select name="content_length">
                            <?php
                            $content_lengths = array('short' => '짧게', 'normal' => '보통', 'detailed' => '상세');
                            $cur_length = $project ? $project['content_length'] : 'normal';
                            foreach ($content_lengths as $code => $label) {
                            ?>
                                <option value="<?php echo $code; ?>" <?php echo $cur_length === $code ? 'selected' : ''; ?>><?php echo get_text($label); ?></option>
                            <?php } ?>
                        </select>
                    </td></tr>
            </tbody>
        </table>
    </div>
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="<?php echo $id > 0 ? '수정 저장' : '등록'; ?>" class="btn_submit btn">
        <a href="./project_list.php" class="btn btn_02">목록</a>
    </div>
</form>

<script>
function fprojectform_submit(f) {
    if (!f.advertiser_id.value) { alert('사업체를 선택해 주세요.'); return false; }
    if (!f.topic.value.trim()) { alert('핵심 주제를 입력해 주세요.'); f.topic.focus(); return false; }
    return true;
}
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
