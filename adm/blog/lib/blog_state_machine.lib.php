<?php
if (!defined('_GNUBOARD_')) exit;

// draft -> generated -> review_required -> approved -> publish_pending -> publishing -> published|failed
// review_required -> draft 는 반려(재작성 요청), failed -> publish_pending 은 재시도 경로로 최소 추가한다.
function bp_valid_transitions()
{
    return array(
        'draft'            => array('generated'),
        'generated'        => array('review_required'),
        'review_required'  => array('approved', 'draft'),
        'approved'         => array('publish_pending'),
        'publish_pending'  => array('publishing'),
        'publishing'       => array('published', 'failed'),
        'failed'           => array('publish_pending'),
        'published'        => array(),
    );
}

function bp_can_transition($from, $to)
{
    $map = bp_valid_transitions();
    if (!isset($map[$from])) {
        return false;
    }
    return in_array($to, $map[$from], true);
}

// 상태 전이를 검증 후 적용한다. 실패 시 false와 오류 메시지를 반환하고 DB는 변경하지 않는다.
function bp_transition_project($project_id, $to_status, $actor, $detail = '')
{
    $table = bp_table('content_projects');
    $project_id = (int) $project_id;

    $row = sql_fetch(" select id, status from {$table} where id = '{$project_id}' ");
    if (!$row) {
        return array('ok' => false, 'error' => '프로젝트를 찾을 수 없습니다.');
    }

    $from = $row['status'];
    if (!bp_can_transition($from, $to_status)) {
        return array('ok' => false, 'error' => "'{$from}' 상태에서 '{$to_status}'(으)로 전이할 수 없습니다.");
    }

    $extra = '';
    if ($to_status === 'approved') {
        $extra = ", reviewed_by = '" . sql_real_escape_string($actor) . "', approved_at = '" . G5_TIME_YMDHIS . "'";
    }

    sql_query(" update {$table}
                    set status = '" . sql_real_escape_string($to_status) . "',
                        updated_at = '" . G5_TIME_YMDHIS . "'
                        {$extra}
                    where id = '{$project_id}' ");

    bp_log_activity($project_id, "status_change:{$from}->{$to_status}", $actor, $detail);

    return array('ok' => true, 'from' => $from, 'to' => $to_status);
}

// 발행(퍼블리시) 자체를 막는 최종 게이트 — approved 이전 상태에서는 어떤 경로로도 발행 호출이 성공하지 못하게 한다.
function bp_is_publish_allowed($project_status)
{
    return in_array($project_status, array('approved', 'publish_pending', 'publishing', 'failed'), true);
}
