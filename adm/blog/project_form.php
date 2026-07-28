<?php
include_once('./_common.php');

$sub_menu = '360400';
auth_check_menu($auth, $sub_menu, 'w');

$g5['title'] = '콘텐츠 프로젝트 등록';

$adv_table = bp_table('advertisers');
$sites_table = bp_table('sites');

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
    <p>새 콘텐츠 프로젝트를 등록합니다. 등록 직후 상태는 항상 '초안(draft)'이며, 외부 공개 발행 없이 검수·승인을 거쳐야 발행 단계로 진행됩니다.</p>
</div>

<form name="fprojectform" method="post" action="./project_update.php" onsubmit="return fprojectform_submit(this);">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>콘텐츠 프로젝트 등록</caption>
            <tbody>
                <tr><th scope="row"><label for="advertiser_id">사업체(광고주)</label></th>
                    <td>
                        <select name="advertiser_id" id="advertiser_id" required>
                            <option value="">선택</option>
                            <?php while ($a = sql_fetch_array($advertisers)) { ?>
                                <option value="<?php echo (int)$a['id']; ?>"><?php echo get_text($a['name']); ?></option>
                            <?php } ?>
                        </select>
                        <span class="help_txt">사업체가 없다면 먼저 <a href="./advertiser_form.php">광고주 등록</a>을 진행해 주세요.</span>
                    </td></tr>
                <tr><th scope="row">발행 사이트 (복수 선택)</th>
                    <td>
                        <?php if ($site_rows) { ?>
                            <?php foreach ($site_rows as $s) { ?>
                                <label style="display:inline-block;margin:2px 10px 2px 0;">
                                    <input type="checkbox" name="site_ids[]" value="<?php echo (int)$s['id']; ?>">
                                    <?php echo get_text($s['advertiser_name']) . ' - ' . get_text($s['name']) . ' (' . get_text($s['platform']) . ')'; ?>
                                </label>
                            <?php } ?>
                        <?php } else { ?>
                            <span class="help_txt">등록된 사이트가 없습니다. <a href="./site_form.php">사이트 등록</a>을 먼저 진행해 주세요.</span>
                        <?php } ?>
                    </td></tr>
                <tr><th scope="row"><label for="topic">핵심 주제</label></th>
                    <td><input type="text" name="topic" id="topic" class="frm_input" maxlength="255" required></td></tr>
                <tr><th scope="row"><label for="primary_keyword">대표 키워드</label></th>
                    <td><input type="text" name="primary_keyword" id="primary_keyword" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row">글 목적</th>
                    <td>
                        <select name="content_type">
                            <option value="info">정보형</option>
                            <option value="comparison">비교형</option>
                            <option value="case">사례형</option>
                            <option value="faq">FAQ형</option>
                            <option value="promo">홍보형</option>
                        </select>
                    </td></tr>
                <tr><th scope="row"><label for="target_audience">독자</label></th>
                    <td><input type="text" name="target_audience" id="target_audience" class="frm_input" maxlength="255" placeholder="예: 자영업자, 기업 담당자"></td></tr>
                <tr><th scope="row">글 길이</th>
                    <td>
                        <select name="content_length">
                            <option value="short">짧게</option>
                            <option value="normal" selected>보통</option>
                            <option value="detailed">상세</option>
                        </select>
                    </td></tr>
            </tbody>
        </table>
    </div>
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="등록" class="btn_submit btn">
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
