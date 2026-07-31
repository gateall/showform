<?php
include_once('./_common.php');

$sub_menu = '360100';
auth_check_menu($auth, $sub_menu, 'w');

$table = bp_table('advertisers');
$sites_table = bp_table('sites');
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$row = array(
    'id' => 0, 'name' => '', 'ceo_name' => '', 'phone' => '', 'sub_phone' => '', 'email' => '', 'address' => '',
    'domain' => '', 'consult_url' => '', 'kakao_channel' => '', 'service_region' => '',
    'industry' => '', 'industry_detail' => '',
    'core_service' => '', 'intro_text' => '', 'forbidden_words' => '', 'mandatory_notice' => '', 'memo' => '',
    'status' => 'Y', 'contract_start_date' => '', 'contract_end_date' => '',
    'contract_status' => '운영 중', 'monthly_post_quota' => 0,
);
$sites = array();

if ($id > 0) {
    $row = sql_fetch(" select * from {$table} where id = '{$id}' ");
    if (!$row) {
        alert('광고주 정보를 찾을 수 없습니다.', G5_ADMIN_URL . '/blog/advertiser_list.php');
    }
    $g5['title'] = '광고주 수정';

    // post_builder.php에서 프로젝트를 등록할 때 "어느 사이트에 발행할지"를 결정하는
    // 실제 채널 목록 - 여기서 바로 보이고 등록/수정으로 이동할 수 있어야 한다.
    $sites_res = sql_query(" select id, name, platform, base_url, status from {$sites_table} where advertiser_id = '{$id}' order by id desc ");
    while ($s = sql_fetch_array($sites_res)) {
        $sites[] = $s;
    }
} else {
    $g5['title'] = '광고주 등록';
}

$industry_options = array('홈페이지 제작','광고·마케팅','통신·인터넷','음식점','숙박·펜션','이사·물류','교육·학원','병원·의료','법률·세무','부동산','인테리어','자동차','쇼핑몰','제조업','기업 서비스','기타');
$region_options = array('전국','서울','경기','인천','부산','대구','울산','대전','광주','세종','강원','충북','충남','전북','전남','경북','경남','제주');
$selected_industry = $row['industry'] !== '' ? explode(',', $row['industry']) : array();
$selected_region = $row['service_region'] !== '' ? explode(',', $row['service_region']) : array();
$platform_labels = array('wordpress' => '워드프레스', 'php' => '자체 PHP 사이트', 'naver' => '네이버(등록 패키지)', 'naver_blog' => '네이버 블로그(API)');

include_once(__DIR__ . '/../layout/header.php');
?>
<style>
.pb-check-scroll{max-height:180px;overflow-y:auto;border:1px solid #cbd5e1;border-radius:6px;padding:8px 12px;background:#fff;}
.pb-check-scroll label{display:inline-block;width:47%;font-weight:normal;font-size:0.9rem;padding:3px 0;}
</style>
<div class="local_desc01 local_desc">
    <p>콘텐츠 생성 시 상호·전화·주소·상담URL 등이 치환변수({{business_name}} 등)로 자동 삽입됩니다.</p>
</div>

<form name="fadvertiserform" method="post" action="<?php echo G5_ADMIN_URL; ?>/blog/advertiser_update.php" onsubmit="return fadvertiserform_submit(this);">
    <input type="hidden" name="id" value="<?php echo (int)$id; ?>">
    <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">

    <div class="tbl_frm01 tbl_wrap">
        <table>
            <caption>광고주 등록/수정</caption>
            <tbody>
                <tr><th scope="row"><label for="name">상호</label></th>
                    <td><input type="text" name="name" id="name" value="<?php echo get_text($row['name']); ?>" class="frm_input" maxlength="255" required></td></tr>
                <tr><th scope="row"><label for="ceo_name">대표자명</label></th>
                    <td><input type="text" name="ceo_name" id="ceo_name" value="<?php echo get_text($row['ceo_name']); ?>" class="frm_input" maxlength="100"></td></tr>
                <tr><th scope="row"><label for="phone">대표 전화</label></th>
                    <td><input type="text" name="phone" id="phone" value="<?php echo get_text($row['phone']); ?>" class="frm_input" maxlength="50"></td></tr>
                <tr><th scope="row"><label for="sub_phone">보조 전화</label></th>
                    <td><input type="text" name="sub_phone" id="sub_phone" value="<?php echo get_text($row['sub_phone']); ?>" class="frm_input" maxlength="50"></td></tr>
                <tr><th scope="row"><label for="email">이메일</label></th>
                    <td><input type="email" name="email" id="email" value="<?php echo get_text($row['email']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row"><label for="address">주소</label></th>
                    <td><input type="text" name="address" id="address" value="<?php echo get_text($row['address']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row"><label for="domain">도메인</label></th>
                    <td><input type="text" name="domain" id="domain" value="<?php echo get_text($row['domain']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row"><label for="consult_url">상담 URL</label></th>
                    <td><input type="text" name="consult_url" id="consult_url" value="<?php echo get_text($row['consult_url']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row"><label for="kakao_channel">카카오채널</label></th>
                    <td><input type="text" name="kakao_channel" id="kakao_channel" value="<?php echo get_text($row['kakao_channel']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row">업종 <span style="font-weight:normal; color:#888; font-size:0.85em;">(복수 선택)</span></th>
                    <td>
                        <div class="pb-check-scroll">
                            <?php foreach ($industry_options as $opt): ?>
                                <label><input type="checkbox" name="industry[]" value="<?php echo $opt; ?>" <?php echo in_array($opt, $selected_industry, true) ? 'checked' : ''; ?>> <?php echo $opt; ?></label>
                            <?php endforeach; ?>
                        </div>
                        <input type="text" name="industry_detail" value="<?php echo get_text($row['industry_detail']); ?>" class="frm_input" maxlength="255" placeholder="세부 업종 / 기타 직접입력" style="margin-top:6px;">
                    </td></tr>
                <tr><th scope="row">서비스 지역 <span style="font-weight:normal; color:#888; font-size:0.85em;">(복수 선택)</span></th>
                    <td>
                        <div class="pb-check-scroll">
                            <?php foreach ($region_options as $opt): ?>
                                <label><input type="checkbox" name="service_region[]" value="<?php echo $opt; ?>" <?php echo in_array($opt, $selected_region, true) ? 'checked' : ''; ?>> <?php echo $opt; ?></label>
                            <?php endforeach; ?>
                        </div>
                    </td></tr>
                <tr><th scope="row"><label for="core_service">핵심 서비스</label></th>
                    <td><input type="text" name="core_service" id="core_service" value="<?php echo get_text($row['core_service']); ?>" class="frm_input" maxlength="255"></td></tr>
                <tr><th scope="row"><label for="intro_text">기본 소개</label></th>
                    <td><textarea name="intro_text" id="intro_text" rows="3" style="width:100%;"><?php echo get_text($row['intro_text']); ?></textarea></td></tr>
                <tr><th scope="row"><label for="forbidden_words">금지 표현</label></th>
                    <td><textarea name="forbidden_words" id="forbidden_words" rows="2" style="width:100%;" placeholder="한 줄에 하나씩, 예: 업계 1위"><?php echo get_text($row['forbidden_words']); ?></textarea></td></tr>
                <tr><th scope="row"><label for="mandatory_notice">필수 고지</label></th>
                    <td><textarea name="mandatory_notice" id="mandatory_notice" rows="2" style="width:100%;" placeholder="예: 요금·조건은 현장에 따라 달라질 수 있음"><?php echo get_text($row['mandatory_notice']); ?></textarea></td></tr>
                <tr><th scope="row"><label for="memo">기타 참고사항</label></th>
                    <td><textarea name="memo" id="memo" rows="2" style="width:100%;"><?php echo get_text($row['memo']); ?></textarea></td></tr>
                <tr><th scope="row">시스템 활성 상태</th>
                    <td>
                        <label><input type="radio" name="status" value="Y" <?php echo $row['status'] === 'Y' ? 'checked' : ''; ?>> 사용</label>
                        <label style="margin-left:15px;"><input type="radio" name="status" value="N" <?php echo $row['status'] === 'N' ? 'checked' : ''; ?>> 중지</label>
                    </td></tr>
                <tr><th scope="row"><label for="contract_start_date">계약 시작일</label></th>
                    <td><input type="date" name="contract_start_date" id="contract_start_date" value="<?php echo $row['contract_start_date']; ?>" class="frm_input"></td></tr>
                <tr><th scope="row"><label for="contract_end_date">계약 종료일</label></th>
                    <td><input type="date" name="contract_end_date" id="contract_end_date" value="<?php echo $row['contract_end_date']; ?>" class="frm_input"></td></tr>
                <tr><th scope="row"><label for="monthly_post_quota">월간 발행 목표</label></th>
                    <td><input type="number" name="monthly_post_quota" id="monthly_post_quota" value="<?php echo (int)$row['monthly_post_quota']; ?>" class="frm_input" style="width:100px;"> 건</td></tr>
                <tr><th scope="row">계약 상태</th>
                    <td>
                        <?php 
                        $c_status = $row['contract_status']; 
                        $status_opts = array('계약 예정', '운영 중', '종료 예정', '일시 중지', '계약 종료');
                        foreach ($status_opts as $so) {
                            $checked = ($c_status === $so) ? 'checked' : '';
                            echo "<label style='margin-right:15px;'><input type='radio' name='contract_status' value='{$so}' {$checked}> {$so}</label>";
                        }
                        ?>
                    </td></tr>
            </tbody>
        </table>
    </div>

    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="저장" class="btn_submit btn">
        <a href="./advertiser_list.php" class="btn btn_02">목록</a>
    </div>
</form>

<?php if ($id > 0): ?>
<div class="tbl_frm01 tbl_wrap" style="margin-top:20px;">
    <table>
        <caption>등록 채널 <span style="font-weight:normal; color:#888; font-size:0.85em;">- 포스팅을 발행할 사이트</span></caption>
        <thead>
            <tr><th scope="col">사이트명</th><th scope="col">플랫폼</th><th scope="col">주소</th><th scope="col">상태</th><th scope="col">관리</th></tr>
        </thead>
        <tbody>
            <?php if (count($sites) > 0): ?>
                <?php foreach ($sites as $s): ?>
                <tr>
                    <td style="text-align:left;"><?php echo get_text($s['name']); ?></td>
                    <td><?php echo isset($platform_labels[$s['platform']]) ? $platform_labels[$s['platform']] : get_text($s['platform']); ?></td>
                    <td style="text-align:left;"><?php echo get_text($s['base_url']); ?></td>
                    <td><?php echo $s['status'] === 'Y' ? '사용' : '중지'; ?></td>
                    <td><a href="./site_form.php?id=<?php echo (int)$s['id']; ?>" class="btn btn_02">수정</a></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5" class="empty_table">등록된 채널이 없습니다. 채널을 등록하지 않으면 이 광고주의 포스팅을 발행할 곳이 없습니다.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <div class="btn_confirm01 btn_confirm">
        <a href="./site_form.php?advertiser_id=<?php echo (int)$id; ?>" class="btn btn_01">+ 새 채널 등록</a>
    </div>
</div>
<?php endif; ?>

<script>
function fadvertiserform_submit(f) {
    if (!f.name.value.trim()) { alert('상호를 입력해 주세요.'); f.name.focus(); return false; }
    return true;
}
</script>

<?php include_once(__DIR__ . '/../layout/footer.php');
