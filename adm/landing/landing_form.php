<?php
include_once('./_common.php');

$g5['title'] = '랜딩 등록';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category_table = G5_TABLE_PREFIX . 'landing_category';
$category_list = sql_query(" select id, category_name from {$category_table} where is_display = 'Y' order by sort_order asc, id asc ");

$row = array();

if ($id) {
    $sql = " select * from landing_page where id = '{$id}' ";
    $row = sql_fetch($sql);
    $g5['title'] = '랜딩 수정';
}

$subject = isset($row['subject']) ? $row['subject'] : '';
$category_id = isset($row['category_id']) ? $row['category_id'] : '';
$industry = isset($row['industry']) ? $row['industry'] : '';
$use_yn = isset($row['use_yn']) ? $row['use_yn'] : 'Y';
$hero_title = isset($row['hero_title']) ? $row['hero_title'] : '';
$hero_text = isset($row['hero_text']) ? $row['hero_text'] : '';
$cta_text = isset($row['cta_text']) ? $row['cta_text'] : '무료 상담 신청하기';
$cta_link = isset($row['cta_link']) ? $row['cta_link'] : '#contact';
$problem_1 = isset($row['problem_1']) ? $row['problem_1'] : '';
$problem_2 = isset($row['problem_2']) ? $row['problem_2'] : '';
$problem_3 = isset($row['problem_3']) ? $row['problem_3'] : '';
$service_1 = isset($row['service_1']) ? $row['service_1'] : '';
$service_2 = isset($row['service_2']) ? $row['service_2'] : '';
$service_3 = isset($row['service_3']) ? $row['service_3'] : '';
$service_4 = isset($row['service_4']) ? $row['service_4'] : '';
$phone = isset($row['phone']) ? $row['phone'] : '';
$kakao_link = isset($row['kakao_link']) ? $row['kakao_link'] : '';
$privacy_text = isset($row['privacy_text']) ? $row['privacy_text'] : '상담을 위해 입력하신 정보 수집에 동의합니다.';

include_once(G5_ADMIN_PATH.'/admin.head.php');
?>

<form name="flandingform" method="post" action="./landing_update.php" enctype="multipart/form-data">
<input type="hidden" name="id" value="<?php echo $id; ?>">

<div class="tbl_frm01 tbl_wrap">
<table>
<caption>랜딩 등록</caption>
<tbody>

<tr>
<th scope="row">랜딩 제목</th>
<td><input type="text" name="subject" value="<?php echo get_text($subject); ?>" class="frm_input" size="80" required></td>
</tr>

<tr>
<th scope="row">카테고리</th>
<td>
<select name="category_id">
<option value="">카테고리 선택</option>
<?php while ($cat = sql_fetch_array($category_list)) { ?>
<option value="<?php echo (int)$cat['id']; ?>" <?php echo ((string)$category_id === (string)$cat['id']) ? 'selected' : ''; ?>><?php echo get_text($cat['category_name']); ?></option>
<?php } ?>
</select>
</td>
</tr>

<tr>
<th scope="row">업종</th>
<td><input type="text" name="industry" value="<?php echo get_text($industry); ?>" class="frm_input" size="40" placeholder="예: 누수, 방수, 병원"></td>
</tr>

<tr>
<th scope="row">사용여부</th>
<td>
<select name="use_yn">
<option value="Y" <?php echo $use_yn === 'Y' ? 'selected' : ''; ?>>사용</option>
<option value="N" <?php echo $use_yn === 'N' ? 'selected' : ''; ?>>미사용</option>
</select>
</td>
</tr>

<tr>
<th scope="row">메인 제목</th>
<td><input type="text" name="hero_title" value="<?php echo get_text($hero_title); ?>" class="frm_input" size="100"></td>
</tr>

<tr>
<th scope="row">서브 문구</th>
<td><textarea name="hero_text" rows="4" style="width:100%;"><?php echo get_text($hero_text); ?></textarea></td>
</tr>

<tr>
<th scope="row">대표 이미지</th>
<td><input type="file" name="hero_image"></td>
</tr>

<tr>
<th scope="row">CTA 문구</th>
<td><input type="text" name="cta_text" value="<?php echo get_text($cta_text); ?>" class="frm_input" size="60"></td>
</tr>

<tr>
<th scope="row">CTA 링크</th>
<td><input type="text" name="cta_link" value="<?php echo get_text($cta_link); ?>" class="frm_input" size="80"></td>
</tr>

<tr>
<th scope="row">문제 제기</th>
<td>
<input type="text" name="problem_1" value="<?php echo get_text($problem_1); ?>" class="frm_input" size="80"><br><br>
<input type="text" name="problem_2" value="<?php echo get_text($problem_2); ?>" class="frm_input" size="80"><br><br>
<input type="text" name="problem_3" value="<?php echo get_text($problem_3); ?>" class="frm_input" size="80">
</td>
</tr>

<tr>
<th scope="row">서비스 항목</th>
<td>
<input type="text" name="service_1" value="<?php echo get_text($service_1); ?>" class="frm_input" size="80"><br><br>
<input type="text" name="service_2" value="<?php echo get_text($service_2); ?>" class="frm_input" size="80"><br><br>
<input type="text" name="service_3" value="<?php echo get_text($service_3); ?>" class="frm_input" size="80"><br><br>
<input type="text" name="service_4" value="<?php echo get_text($service_4); ?>" class="frm_input" size="80">
</td>
</tr>

<tr>
<th scope="row">전화번호</th>
<td><input type="text" name="phone" value="<?php echo get_text($phone); ?>" class="frm_input" size="40"></td>
</tr>

<tr>
<th scope="row">카카오 링크</th>
<td><input type="text" name="kakao_link" value="<?php echo get_text($kakao_link); ?>" class="frm_input" size="80"></td>
</tr>

<tr>
<th scope="row">개인정보 동의 문구</th>
<td><textarea name="privacy_text" rows="3" style="width:100%;"><?php echo get_text($privacy_text); ?></textarea></td>
</tr>

</tbody>
</table>
</div>

<div class="btn_confirm01 btn_confirm">
<input type="submit" value="저장" class="btn_submit btn">
<a href="./landing_list.php" class="btn btn_02">목록</a>
</div>

</form>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');