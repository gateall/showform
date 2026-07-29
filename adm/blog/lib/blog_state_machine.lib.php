<?php
if (!defined('_GNUBOARD_')) exit;

// draft -> pending_approval -> approved -> publish_pending -> publishing -> published|failed
// pending_approval -> draft 는 반려(재작성 요청), approved -> pending_approval 은 승인 취소.
// failed -> publish_pending 은 재시도 경로.
//
// 콘텐츠 파이프라인(Stage 2) 범위에서는 draft/pending_approval/approved만 실제로 쓰인다 —
// 제목 생성·선택·본문 생성은 전부 draft 상태에서 이루어지고(별도 "생성 완료" 상태를 두지
// 않는다), 별도 상태 값이 아니라 제목 후보·선택 여부로 진행 단계를 판단한다
// (project_action.php의 select_title/generate_body 가드 참조).
// publish_pending 이후(워드프레스 발행 파이프라인)는 이전 단계에서 이미 구현·검증되었고
// 이번 단계에서는 건드리지 않는다 — 상태만 그대로 유지해 앞으로도 동작하게 남겨둔다.
function bp_valid_transitions(): array
{
    return array(
        'draft'            => array('pending_approval'),
        'pending_approval' => array('approved', 'draft'),
        'approved'         => array('pending_approval', 'publish_pending'),
        'publish_pending'  => array('publishing'),
        'publishing'       => array('published', 'failed'),
        'failed'           => array('publish_pending'),
        'published'        => array(),
    );
}

function bp_can_transition(string $from, string $to): bool
{
    $map = bp_valid_transitions();
    if (!isset($map[$from])) {
        return false;
    }
    return in_array($to, $map[$from], true);
}

// 상태 전이를 검증 후 적용한다. 실패 시 false와 오류 메시지를 반환하고 DB는 변경하지 않는다.
function bp_transition_project(int $project_id, string $to_status, string $actor, string $detail = ''): array
{
    $table = bp_table('content_projects');

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
    if ($to_status === 'pending_approval' && $from === 'approved') {
        // 승인 취소 — approved_at을 지워 "현재 승인된 상태가 아님"을 명확히 한다.
        $extra = ", approved_at = NULL";
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
function bp_is_publish_allowed(string $project_status): bool
{
    return in_array($project_status, array('approved', 'publish_pending', 'publishing', 'failed'), true);
}
