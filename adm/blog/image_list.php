<?php
$sub_menu = '360800';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '이미지 라이브러리';

$tbl_images = bp_table('images');
$tbl_projects = bp_table('content_projects');

$sql_search = " where 1=1 ";
$qstr = '';

$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
if ($stx) {
    $stx_safe = sql_real_escape_string($stx);
    $sql_search .= " and original_name like '%{$stx_safe}%' ";
    $qstr .= '&stx='.urlencode($stx);
}

$sql_common = " from {$tbl_images} {$sql_search} ";
$sql_order = " order by id desc ";

$sql = " select count(*) as cnt {$sql_common} ";
$row = sql_fetch($sql);
$total_count = $row['cnt'];

$rows = $config['cf_page_rows'];
$total_page  = ceil($total_count / $rows);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select * {$sql_common} {$sql_order} limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$list = array();
while ($row = sql_fetch_array($result)) {
    // 프로젝트명 조회 (필요 시)
    $project_name = '공용';
    if ($row['project_id']) {
        $p = sql_fetch(" select title from {$tbl_projects} where id = '{$row['project_id']}' ");
        if ($p) $project_name = $p['title'];
    }
    $row['project_name'] = $project_name;
    
    // 썸네일 URL (상대경로로 표시)
    $img_url = G5_DATA_URL . '/blog_images/' . $row['filename'];
    $row['img_url'] = $img_url;

    $list[] = $row;
}

include_once(G5_ADMIN_PATH.'/admin.head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/admin_extend_sf_blog.css">', 0);
?>

<div class="bp-list-toolbar">
    <a href="./image_form.php" class="bp-btn-primary-lg">새 이미지 업로드</a>
</div>

<div class="bp-filter-wrap">
    <details open>
        <summary class="bp-filter-summary">필터 및 검색</summary>
        <div class="bp-filter-form">
            <form name="fsearch" id="fsearch" method="get">
            <div class="bp-filter-row">
                <label>
                    <span>파일명 검색</span>
                    <input type="text" name="stx" value="<?php echo get_text($stx); ?>" placeholder="원본 파일명 검색">
                </label>
                <div class="bp-filter-actions">
                    <button type="submit" class="btn btn_submit">검색</button>
                    <a href="./image_list.php" class="btn">초기화</a>
                </div>
            </div>
            </form>
        </div>
    </details>
</div>

<!-- 모바일: 카드 뷰 -->
<div class="bp-project-cards" id="mobile_card_view">
    <?php if (count($list) > 0) { ?>
        <?php foreach ($list as $row) { ?>
        <div class="bp-project-card">
            <div class="bp-project-card-head">
                <a href="<?php echo $row['img_url']; ?>" target="_blank" class="bp-project-title"><?php echo get_text($row['original_name']); ?></a>
                <span class="bp-status-badge bp-status-badge-published"><?php echo number_format($row['file_size']/1024, 1); ?> KB</span>
            </div>
            <div style="margin-bottom:10px;">
                <img src="<?php echo $row['img_url']; ?>" alt="" style="max-width:100%; height:auto; border-radius:4px; max-height:200px; object-fit:contain;">
            </div>
            <div class="bp-project-meta">
                <span>프로젝트: <?php echo get_text($row['project_name']); ?></span>
                <span>형식: <?php echo get_text($row['mime_type']); ?></span>
                <span>업로드: <?php echo substr($row['created_at'], 0, 16); ?></span>
            </div>
            <div class="bp-project-actions">
                <button type="button" class="btn copy_url_btn" data-url="<?php echo $row['img_url']; ?>">URL 복사</button>
                <button type="button" class="bp-btn-danger" onclick="delete_image(<?php echo $row['id']; ?>)">삭제</button>
            </div>
        </div>
        <?php } ?>
    <?php } else { ?>
        <div class="bp-empty">등록된 이미지가 없습니다.</div>
    <?php } ?>
</div>

<!-- PC: 테이블 뷰 -->
<div class="tbl_head01 tbl_wrap" id="pc_table_view">
    <table>
        <caption>이미지 목록</caption>
        <thead>
            <tr>
                <th scope="col" style="width:100px;">미리보기</th>
                <th scope="col">원본 파일명</th>
                <th scope="col" style="width:150px;">프로젝트</th>
                <th scope="col" style="width:100px;">용량</th>
                <th scope="col" style="width:150px;">등록일시</th>
                <th scope="col" style="width:160px;">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($list) > 0) { ?>
                <?php foreach ($list as $row) { ?>
                <tr>
                    <td class="td_center">
                        <a href="<?php echo $row['img_url']; ?>" target="_blank">
                            <img src="<?php echo $row['img_url']; ?>" alt="" style="width:80px; height:60px; object-fit:cover; border-radius:4px;">
                        </a>
                    </td>
                    <td>
                        <strong><a href="<?php echo $row['img_url']; ?>" target="_blank"><?php echo get_text($row['original_name']); ?></a></strong>
                        <?php if ($row['is_ai_generated']) { ?>
                            <br><span style="color:#0046cc; font-size:11px;">AI Generated</span>
                        <?php } ?>
                    </td>
                    <td class="td_center"><?php echo get_text($row['project_name']); ?></td>
                    <td class="td_center"><?php echo number_format($row['file_size']/1024, 1); ?> KB</td>
                    <td class="td_center"><?php echo substr($row['created_at'], 0, 16); ?></td>
                    <td class="td_center">
                        <button type="button" class="btn copy_url_btn" data-url="<?php echo $row['img_url']; ?>">복사</button>
                        <button type="button" class="btn btn_02" onclick="delete_image(<?php echo $row['id']; ?>)">삭제</button>
                    </td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr>
                    <td colspan="6" class="empty_table">등록된 이미지가 없습니다.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, '?'.$qstr.'&page='); ?>

<style>
/* 반응형 분기: 768px */
@media (max-width: 768px) {
    #pc_table_view { display: none !important; }
}
@media (min-width: 769px) {
    #mobile_card_view { display: none !important; }
}
</style>

<script>
$(function(){
    $('.copy_url_btn').on('click', function() {
        var url = $(this).data('url');
        
        // Use modern clipboard API
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                alert('이미지 URL이 복사되었습니다.\n' + url);
            }).catch(function(err) {
                fallbackCopyTextToClipboard(url);
            });
        } else {
            fallbackCopyTextToClipboard(url);
        }
    });

    function fallbackCopyTextToClipboard(text) {
        var textArea = document.createElement("textarea");
        textArea.value = text;
        
        // Avoid scrolling to bottom
        textArea.style.top = "0";
        textArea.style.left = "0";
        textArea.style.position = "fixed";

        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();

        try {
            var successful = document.execCommand('copy');
            if (successful) {
                alert('이미지 URL이 복사되었습니다.\n' + text);
            } else {
                alert('URL 복사에 실패했습니다.');
            }
        } catch (err) {
            alert('URL 복사에 실패했습니다.');
        }

        document.body.removeChild(textArea);
    }
});

function delete_image(id) {
    if(confirm("정말 이 이미지를 삭제하시겠습니까?\n이미 본문에 삽입된 경우 엑스박스가 표시될 수 있습니다.")) {
        // CSRF 토큰을 동적으로 가져오는 방식 (GnuBoard standard)
        var token = g5_admin_csrf_token_key; 
        
        $.ajax({
            url: './image_delete.php',
            type: 'POST',
            data: { id: id, token: token },
            dataType: 'json',
            success: function(res) {
                if(res.error) {
                    alert(res.error);
                } else {
                    alert("삭제되었습니다.");
                    location.reload();
                }
            },
            error: function(xhr, status, error) {
                alert("삭제 중 오류가 발생했습니다: " + error);
            }
        });
    }
}
</script>

<?php
include_once(G5_ADMIN_PATH.'/admin.tail.php');
