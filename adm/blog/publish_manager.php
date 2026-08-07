<?php
$sub_menu = '360800'; // 새 메뉴 코드 (예시)
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$g5['title'] = '발행·포스팅 관리';

$tbl_targets = bp_table('post_targets');
$tbl_posts = bp_table('posts');
$tbl_sites = bp_table('sites');
$tbl_projects = bp_table('content_projects');
$tbl_versions = bp_table('post_versions');

$sql_search = " where 1=1 ";
$qstr = '';

$stx = isset($_GET['stx']) ? trim($_GET['stx']) : '';
if ($stx) {
    $stx_safe = sql_real_escape_string($stx);
    $sql_search .= " and p.title like '%{$stx_safe}%' ";
    $qstr .= '&stx='.urlencode($stx);
}

$sfl_status = isset($_GET['sfl_status']) ? trim($_GET['sfl_status']) : '';
if ($sfl_status) {
    $sfl_status_safe = sql_real_escape_string($sfl_status);
    $sql_search .= " and t.publish_status = '{$sfl_status_safe}' ";
    $qstr .= '&sfl_status='.urlencode($sfl_status);
}

$sfl_site = isset($_GET['sfl_site']) ? (int)$_GET['sfl_site'] : 0;
if ($sfl_site > 0) {
    $sql_search .= " and t.site_id = '{$sfl_site}' ";
    $qstr .= '&sfl_site='.$sfl_site;
}

$sql_common = " from {$tbl_targets} t 
                join {$tbl_posts} p on t.post_id = p.id 
                join {$tbl_sites} s on t.site_id = s.id 
                join {$tbl_projects} prj on p.project_id = prj.id 
                left join {$tbl_versions} v on t.published_version_id = v.id
                {$sql_search} ";

$row = sql_fetch(" select count(*) as cnt " . $sql_common);
$total_count = $row['cnt'];

$rows = isset($config['cf_page_rows']) ? $config['cf_page_rows'] : 20;
$total_page  = ceil($total_count / $rows);
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$from_record = ($page - 1) * $rows;

$sql = " select t.*, p.title as post_title, p.project_id, s.name as site_name, s.platform, prj.topic as project_topic,
                v.id as version_id, v.created_at as version_created_at 
         {$sql_common} 
         order by t.id desc 
         limit {$from_record}, {$rows} ";
$result = sql_query($sql);

$sites = sql_query(" select id, name from {$tbl_sites} order by id desc ");

include_once(G5_ADMIN_PATH . '/admin.head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/admin_extend_sf_blog.css">', 0);
?>
<div class="local_desc01 local_desc">
    <p>블로그 포스트의 채널별 발행 상태를 통합 관리합니다. 발행 실패 사유를 확인하고 재발행(초기화)을 요청할 수 있습니다.</p>
</div>

<details class="bp-filter-wrap" <?php echo ($sfl_status || $sfl_site || $stx) ? 'open' : ''; ?>>
    <summary class="bp-filter-summary">검색·필터</summary>
    <form name="fsearch" id="fsearch" class="bp-filter-form" method="get">
        <div class="bp-filter-row">
            <label>상태
                <select name="sfl_status" id="sfl_status">
                    <option value="">전체 상태</option>
                    <option value="draft" <?php echo $sfl_status=='draft'?'selected':'';?>>작성중(draft)</option>
                    <option value="ready" <?php echo $sfl_status=='ready'?'selected':'';?>>승인대기(ready)</option>
                    <option value="queued" <?php echo $sfl_status=='queued'?'selected':'';?>>발행대기(queued)</option>
                    <option value="publishing" <?php echo $sfl_status=='publishing'?'selected':'';?>>발행중(publishing)</option>
                    <option value="scheduled" <?php echo $sfl_status=='scheduled'?'selected':'';?>>예약됨(scheduled)</option>
                    <option value="published" <?php echo $sfl_status=='published'?'selected':'';?>>발행완료(published)</option>
                    <option value="partially_published" <?php echo $sfl_status=='partially_published'?'selected':'';?>>일부발행(partially_published)</option>
                    <option value="failed" <?php echo $sfl_status=='failed'?'selected':'';?>>실패(failed)</option>
                    <option value="cancelled" <?php echo $sfl_status=='cancelled'?'selected':'';?>>취소됨(cancelled)</option>
                </select>
            </label>
            <label>사이트
                <select name="sfl_site" id="sfl_site">
                    <option value="">전체 사이트</option>
                    <?php while($srow = sql_fetch_array($sites)){ ?>
                    <option value="<?php echo $srow['id'];?>" <?php echo $sfl_site==$srow['id']?'selected':'';?>><?php echo get_text($srow['name']);?></option>
                    <?php } ?>
                </select>
            </label>
            <label>검색어<input type="text" name="stx" value="<?php echo get_text($stx); ?>" id="stx" class="frm_input" size="25" placeholder="포스트 제목 검색"></label>
        </div>
        <div class="bp-filter-actions">
            <button type="submit" class="btn btn_submit btn">검색</button>
            <a href="./publish_manager.php" class="btn btn_02">초기화</a>
        </div>
    </form>
</details>

<div class="btn_fixed_top">
    <!-- 필요한 전역 액션이 있다면 추가 -->
</div>

<?php
$list = array();
if ($total_count > 0) {
    while ($row = sql_fetch_array($result)) {
        $status_color = '#666';
        $bg_color = '#fef3c7'; // default yellow-ish
        $s = $row['publish_status'];
        
        if ($s === 'published') {
            $status_color = '#065f46';
            $bg_color = '#d1fae5';
        } else if ($s === 'partially_published') {
            $status_color = '#b45309';
            $bg_color = '#fef3c7';
        } else if ($s === 'failed') {
            $status_color = '#991b1b';
            $bg_color = '#fee2e2';
        } else if ($s === 'publishing' || $s === 'queued') {
            $status_color = '#1e40af';
            $bg_color = '#e0e7ff';
        } else if ($s === 'draft' || $s === 'ready' || $s === 'cancelled') {
            $status_color = '#4b5563';
            $bg_color = '#f3f4f6';
        }

        $row['status_color'] = $status_color;
        $row['bg_color'] = $bg_color;
        $list[] = $row;
    }
}
?>

<div class="tbl_head01 tbl_wrap" id="pc_table_view" style="margin-top:15px;">
    <table>
        <thead>
            <tr>
                <th scope="col" style="width:60px;">Target ID</th>
                <th scope="col">포스트 제목</th>
                <th scope="col" style="width:120px;">대상 사이트</th>
                <th scope="col" style="width:100px;">플랫폼</th>
                <th scope="col" style="width:140px;">상태</th>
                <th scope="col" style="width:140px;">발행 버전/일시</th>
                <th scope="col" style="width:100px;">외부 링크</th>
                <th scope="col" style="width:150px;">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($list) > 0) { ?>
                <?php foreach ($list as $row) { ?>
                <tr>
                    <td style="text-align:center;"><?php echo $row['id']; ?></td>
                    <td>
                        <a href="<?php echo G5_URL ?>/manager/blog/post_builder.php?id=<?php echo $row['post_id']; ?>" target="_blank" style="font-weight:bold;">
                            <?php echo get_text(cut_str($row['post_title'], 40)); ?>
                        </a>
                    </td>
                    <td style="text-align:center;"><?php echo get_text($row['site_name']); ?></td>
                    <td style="text-align:center;"><?php echo get_text($row['platform']); ?></td>
                    <td style="text-align:center;">
                        <span style="display:inline-block; padding:3px 8px; border-radius:12px; color:<?php echo $row['status_color']; ?>; background-color:<?php echo $row['bg_color']; ?>; font-weight:bold; font-size:12px;">
                            <?php echo strtoupper($row['publish_status']); ?>
                        </span>
                    </td>
                    <td style="text-align:center; font-size:11px; color:#555;">
                        <?php if ($row['version_id']) { ?>
                            v.<?php echo $row['version_id']; ?><br>
                            <?php echo substr($row['version_created_at'], 0, 16); ?>
                        <?php } else { ?>
                            -
                        <?php } ?>
                    </td>
                    <td style="text-align:center;">
                        <?php if ($row['published_url']) { ?>
                            <a href="<?php echo get_text($row['published_url']); ?>" target="_blank" class="btn_03 btn" style="padding:2px 5px;">보기</a>
                        <?php } else { ?>
                            -
                        <?php } ?>
                    </td>
                    <td style="text-align:center;">
                        <?php if ($row['publish_status'] === 'failed' && $row['last_error']) { ?>
                            <button type="button" class="btn_02 btn" style="padding:2px 5px;" onclick="showErrorModal('<?php echo htmlspecialchars(addslashes($row['last_error'])); ?>')">에러확인</button>
                        <?php } ?>
                        
                        <?php if (in_array($row['publish_status'], ['failed', 'cancelled'])) { ?>
                            <!-- 재발행(초기화) 버튼 -->
                            <button type="button" class="btn_03 btn btn-reset-draft" data-id="<?php echo $row['id']; ?>" style="padding:2px 5px;">draft 초기화</button>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="8" class="empty_table">자료가 없습니다.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, "{$_SERVER['SCRIPT_NAME']}?$qstr&amp;page="); ?>

<!-- Error Modal -->
<div id="errorModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:10000; align-items:center; justify-content:center;">
    <div style="background:#fff; width:min(600px, 90vw); max-height:80vh; overflow-y:auto; border-radius:8px; box-shadow:0 10px 25px rgba(0,0,0,0.2); display:flex; flex-direction:column;">
        <div style="padding:15px 20px; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:16px; color:#e53e3e;">발행 실패 로그</h3>
            <button onclick="document.getElementById('errorModal').style.display='none'" style="border:none; background:none; font-size:20px; cursor:pointer;">&times;</button>
        </div>
        <div style="padding:20px; background:#f9fafb; font-family:monospace; font-size:13px; color:#333; white-space:pre-wrap; word-break:break-all;" id="errorModalContent">
        </div>
        <div style="padding:15px 20px; border-top:1px solid #eee; text-align:right;">
            <button class="btn btn_02" onclick="document.getElementById('errorModal').style.display='none'">닫기</button>
        </div>
    </div>
</div>

<script>
function showErrorModal(errorText) {
    document.getElementById('errorModalContent').textContent = errorText;
    document.getElementById('errorModal').style.display = 'flex';
}

$(function() {
    $('.btn-reset-draft').click(function() {
        if (!confirm('이 타겟의 상태를 draft로 초기화하시겠습니까?\n(초기화 후 포스트 작성 화면에서 다시 발행할 수 있습니다)')) return false;
        var id = $(this).data('id');
        
        $.post(SF_MANAGER_URL + '/blog/post_builder_ajax.php', {
            action: 'reset_target_status',
            target_id: id
        }, function(res) {
            if (res.ok) {
                alert('초기화 되었습니다.');
                location.reload();
            } else {
                alert('초기화 실패: ' + (res.error || '알 수 없는 오류'));
            }
        }, 'json').fail(function(xhr) {
            alert('서버 응답 오류');
        });
    });
});
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>
