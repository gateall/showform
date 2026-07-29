<?php
$sub_menu = '360700';
include_once('./_common.php');

$w = isset($_GET['w']) ? $_GET['w'] : '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

auth_check_menu($auth, $sub_menu, $w ? 'w' : 'r');

$tbl_jobs = bp_table('publish_jobs');
$tbl_targets = bp_table('post_targets');
$tbl_posts = bp_table('posts');
$tbl_sites = bp_table('sites');

$html_title = '예약 발행 등록';
$job = array(
    'schedule_type' => 'fixed',
    'scheduled_at' => date('Y-m-d H:i:00', strtotime('+1 hour')),
    'schedule_start_at' => date('Y-m-d 09:00:00', strtotime('+1 day')),
    'schedule_end_at' => date('Y-m-d 18:00:00', strtotime('+1 day')),
    'priority' => 0,
    'internal_memo' => ''
);

if ($w == 'u') {
    $html_title = '예약 발행 수정';
    $job = sql_fetch(" select j.*, p.title as post_title, s.name as site_name 
                       from {$tbl_jobs} j 
                       join {$tbl_targets} t on j.post_target_id = t.id 
                       join {$tbl_posts} p on t.post_id = p.id 
                       join {$tbl_sites} s on t.site_id = s.id 
                       where j.id = '{$id}' ");
    if (!$job) {
        alert('존재하지 않는 작업입니다.');
    }
    if (!in_array($job['status'], array('scheduled', 'pending'))) {
        alert('수정 가능한 상태(예약됨, 대기중)가 아닙니다.');
    }
}

// 등록 시 승인된 포스트 목록만 가져온다 — 승인되지 않은 글은 예약 자체를 만들 수 없어야 한다
// (publish_job_update.php의 서버측 검증이 최종 방어선이며, 이 목록은 그 정책과 일치시키기 위한 UI 필터).
$approved_posts = array();
if ($w == '') {
    $sql_posts = " select p.id, p.title, prj.topic
                   from {$tbl_posts} p
                   join " . bp_table('content_projects') . " prj on p.project_id = prj.id
                   where prj.status = 'approved' and prj.deleted_at is null
                   order by p.id desc limit 100 ";
    $res_posts = sql_query($sql_posts);
    while($row = sql_fetch_array($res_posts)){
        $approved_posts[] = $row;
    }
}

$g5['title'] = $html_title;
include_once(G5_ADMIN_PATH . '/admin.head.php');
add_stylesheet('<link rel="stylesheet" href="'.G5_ADMIN_URL.'/css/admin_extend_sf_blog.css">', 0);
?>

<form name="fpublishjob" id="fpublishjob" action="./publish_job_update.php" onsubmit="return fpublishjob_submit(this);" method="post">
<input type="hidden" name="w" value="<?php echo $w; ?>">
<input type="hidden" name="id" value="<?php echo $id; ?>">
<input type="hidden" name="token" value="">

<div class="tbl_frm01 tbl_wrap">
    <table>
    <caption><?php echo $g5['title']; ?></caption>
    <tbody>
    
    <?php if ($w == '') { ?>
    <tr>
        <th scope="row"><label for="post_id">대상 포스트</label></th>
        <td>
            <select name="post_id" id="post_id" required onchange="load_targets(this.value)">
                <option value="">포스트 선택 (최근 100건)</option>
                <?php foreach($approved_posts as $p) { ?>
                <option value="<?php echo $p['id']; ?>">
                    [<?php echo cut_str(get_text($p['topic']), 15); ?>] <?php echo cut_str(get_text($p['title']), 50); ?>
                </option>
                <?php } ?>
            </select>
            <span class="frm_info">승인된 포스트만 발행 예약을 할 수 있습니다.</span>
        </td>
    </tr>
    <tr>
        <th scope="row">발행 대상 (사이트)</th>
        <td id="target_checkboxes">
            <span class="frm_info" style="color:#f00;">포스트를 먼저 선택해주세요.</span>
        </td>
    </tr>
    <?php } else { ?>
    <tr>
        <th scope="row">포스트 및 사이트</th>
        <td>
            <strong><?php echo get_text($job['post_title']); ?></strong><br>
            <span style="color:#0066cc;">대상: <?php echo get_text($job['site_name']); ?></span>
        </td>
    </tr>
    <tr>
        <th scope="row">상태</th>
        <td>
            <strong><?php echo strtoupper($job['status']); ?></strong>
        </td>
    </tr>
    <?php } ?>
    
    <tr>
        <th scope="row"><label for="schedule_type">예약 방식</label></th>
        <td>
            <select name="schedule_type" id="schedule_type" onchange="toggle_schedule_fields()">
                <option value="fixed" <?php echo $job['schedule_type']=='fixed'?'selected':'';?>>고정 시각</option>
                <option value="random" <?php echo $job['schedule_type']=='random'?'selected':'';?>>기간 내 랜덤 시각</option>
            </select>
        </td>
    </tr>
    
    <tr class="tr_fixed">
        <th scope="row"><label for="scheduled_at">예약 발행 시각</label></th>
        <td>
            <input type="text" name="scheduled_at" id="scheduled_at" value="<?php echo substr($job['scheduled_at'],0,16); ?>" class="frm_input" size="20" placeholder="YYYY-MM-DD HH:MM">
            <span class="frm_info">과거 시각은 설정할 수 없습니다. (예: 2026-08-01 13:00)</span>
        </td>
    </tr>
    
    <tr class="tr_random">
        <th scope="row"><label for="schedule_start_at">랜덤 시작 시각</label></th>
        <td>
            <input type="text" name="schedule_start_at" id="schedule_start_at" value="<?php echo substr($job['schedule_start_at'],0,16); ?>" class="frm_input" size="20" placeholder="YYYY-MM-DD HH:MM">
        </td>
    </tr>
    <tr class="tr_random">
        <th scope="row"><label for="schedule_end_at">랜덤 종료 시각</label></th>
        <td>
            <input type="text" name="schedule_end_at" id="schedule_end_at" value="<?php echo substr($job['schedule_end_at'],0,16); ?>" class="frm_input" size="20" placeholder="YYYY-MM-DD HH:MM">
            <span class="frm_info">시작 시각과 종료 시각 사이의 무작위 시각이 저장 시 확정됩니다.</span>
        </td>
    </tr>
    
    <tr>
        <th scope="row"><label for="priority">우선순위</label></th>
        <td>
            <input type="number" name="priority" id="priority" value="<?php echo (int)$job['priority']; ?>" class="frm_input" size="5">
            <span class="frm_info">기본값 0. 숫자가 높을수록 먼저 처리됩니다.</span>
        </td>
    </tr>
    
    <tr>
        <th scope="row"><label for="internal_memo">내부 메모</label></th>
        <td>
            <textarea name="internal_memo" id="internal_memo" rows="3" style="width:100%;"><?php echo get_text($job['internal_memo']); ?></textarea>
        </td>
    </tr>
    </tbody>
    </table>
</div>

<div class="btn_fixed_top">
    <a href="./publish_job_list.php" class="btn_02 btn">목록</a>
    <input type="submit" value="저장" class="btn_submit btn" accesskey="s">
</div>
</form>

<script>
function toggle_schedule_fields() {
    var type = $('#schedule_type').val();
    if (type === 'fixed') {
        $('.tr_fixed').show();
        $('.tr_random').hide();
    } else {
        $('.tr_fixed').hide();
        $('.tr_random').show();
    }
}
toggle_schedule_fields();

function load_targets(post_id) {
    if (!post_id) {
        $('#target_checkboxes').html('<span style="color:#f00;">포스트를 먼저 선택해주세요.</span>');
        return;
    }
    
    // 타겟 로딩을 위한 ajax 호출 (간단히 자기 자신 호출 후 json 반환 구조 대신 별도 endpoint 구성이 좋으나, 여기선 간단히 ajax_targets)
    $.post('./publish_job_update.php', { w: 'ajax_targets', post_id: post_id }, function(data) {
        if (data.error) {
            $('#target_checkboxes').html('<span style="color:red;">'+data.error+'</span>');
        } else {
            var html = '';
            for(var i=0; i<data.targets.length; i++) {
                var t = data.targets[i];
                html += '<label><input type="checkbox" name="post_target_ids[]" value="'+t.id+'" '+(t.has_job ? 'disabled' : 'checked')+'> ';
                html += t.site_name + ' (ID: ' + t.id + ')';
                if (t.has_job) html += ' <span style="color:#999;">[이미 예약됨]</span>';
                html += '</label><br>';
            }
            if (data.targets.length === 0) {
                html = '<span style="color:#f00;">선택한 포스트에 연결된 사이트(Target)가 없습니다.</span>';
            }
            $('#target_checkboxes').html(html);
        }
    }, 'json');
}

function fpublishjob_submit(f) {
    var type = $('#schedule_type').val();
    if (f.w.value == '') {
        var len = $("input[name='post_target_ids[]']:checked").length;
        if (len < 1) {
            alert('최소 하나 이상의 발행 대상을 선택해주세요.');
            return false;
        }
    }
    
    if (type === 'fixed') {
        if (!f.scheduled_at.value) {
            alert('고정 예약 시각을 입력해주세요.');
            f.scheduled_at.focus();
            return false;
        }
    } else {
        if (!f.schedule_start_at.value) {
            alert('랜덤 시작 시각을 입력해주세요.');
            f.schedule_start_at.focus();
            return false;
        }
        if (!f.schedule_end_at.value) {
            alert('랜덤 종료 시각을 입력해주세요.');
            f.schedule_end_at.focus();
            return false;
        }
        if (f.schedule_start_at.value >= f.schedule_end_at.value) {
            alert('종료 시각은 시작 시각보다 이후여야 합니다.');
            f.schedule_end_at.focus();
            return false;
        }
    }
    
    get_ajax_token();
    return true;
}
</script>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>
