<?php
// 미니웹 빌더 저장 처리.
//
// 화면에서 버튼을 감추는 것으로 권한을 처리했다고 보지 않는다. 모든 요청은 여기서
// 관리자 여부와 토큰을 다시 확인한다.
$sub_menu = '900100';
include_once('./_common.php');

header('Content-Type: application/json; charset=utf-8');

function mw_json($data)
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// _common.php가 미로그인/비관리자를 이미 막지만, AJAX는 HTML 로그인 화면을 받으면
// JSON 파싱이 깨지므로 여기서 한 번 더 확인해 JSON으로 답한다.
if (!$is_admin) {
    mw_json(array('success' => false, 'error' => '관리자 권한이 필요합니다.'));
}
$auth_error = auth_check_menu($auth, $sub_menu, 'w', true);
if ($auth_error) {
    mw_json(array('success' => false, 'error' => str_replace('\n', ' ', $auth_error)));
}
if (!check_token()) {
    mw_json(array('success' => false, 'error' => '요청이 만료되었습니다. 새로고침 후 다시 시도해 주세요.'));
}

$prefix = G5_TABLE_PREFIX;
$prj_table = $prefix . 'miniweb_project';
$sec_table = $prefix . 'miniweb_section';
$blk_table = $prefix . 'miniweb_block';

$action = isset($_POST['action']) ? $_POST['action'] : '';
$pid = isset($_POST['pid']) ? (int) $_POST['pid'] : 0;
$section_type = isset($_POST['section_type']) ? preg_replace('/[^a-z_]/', '', $_POST['section_type']) : '';
$now = G5_TIME_YMDHIS;

// 프로젝트가 없으면 만들어 준다. 빌더에 처음 들어왔을 때 저장이 실패하지 않도록.
if ($action === 'ensure_project') {
    if ($pid > 0) {
        $row = sql_fetch(" select id from {$prj_table} where id = '{$pid}' limit 1 ", false);
        if ($row) {
            mw_json(array('success' => true, 'pid' => $pid, 'created' => false));
        }
    }
    $name = isset($_POST['project_name']) ? trim($_POST['project_name']) : '새 미니웹';
    sql_query(" insert into {$prj_table}
                set project_name = '" . sql_real_escape_string($name) . "',
                    status = 'draft',
                    created_at = '{$now}' ");
    mw_json(array('success' => true, 'pid' => (int) sql_insert_id(), 'created' => true));
}

if ($pid < 1 || $section_type === '') {
    mw_json(array('success' => false, 'error' => '프로젝트 또는 섹션 정보가 없습니다.'));
}

// 디자인 교체. content_json 은 건드리지 않는다 — 이게 이 기능의 핵심이다.
if ($action === 'change_block') {
    $block_id = isset($_POST['block_id']) ? (int) $_POST['block_id'] : 0;

    $blk = sql_fetch(" select id from {$blk_table}
                       where id = '{$block_id}' and section_type = '" . sql_real_escape_string($section_type) . "'
                       and is_active = 1 limit 1 ", false);
    if (!$blk) {
        mw_json(array('success' => false, 'error' => '선택한 디자인을 찾을 수 없습니다.'));
    }

    $sec = sql_fetch(" select id from {$sec_table}
                       where project_id = '{$pid}' and section_type = '" . sql_real_escape_string($section_type) . "'
                       limit 1 ", false);
    if ($sec) {
        // update 대상에 content_json 이 없다는 점이 중요하다.
        sql_query(" update {$sec_table}
                    set block_id = '{$block_id}', updated_at = '{$now}'
                    where id = '" . (int) $sec['id'] . "' ");
    } else {
        sql_query(" insert into {$sec_table}
                    set project_id = '{$pid}',
                        section_type = '" . sql_real_escape_string($section_type) . "',
                        block_id = '{$block_id}',
                        sort_order = 1,
                        created_at = '{$now}' ");
    }
    mw_json(array('success' => true, 'block_id' => $block_id));
}

// 콘텐츠 저장. block_id 는 건드리지 않는다.
if ($action === 'save_content') {
    $raw = isset($_POST['content']) ? $_POST['content'] : '';
    $content = json_decode($raw, true);
    if (!is_array($content)) {
        mw_json(array('success' => false, 'error' => '콘텐츠 형식이 올바르지 않습니다.'));
    }

    // 키 이름을 제한한다. 스키마에 없는 키가 섞여 들어와 쌓이는 것을 막는다.
    $clean = array();
    foreach ($content as $k => $v) {
        if (!preg_match('/^[a-z0-9_]{1,40}$/', $k)) continue;
        if (is_array($v) || is_object($v)) continue;
        $clean[$k] = mb_substr((string) $v, 0, 2000);
    }
    $json = json_encode($clean, JSON_UNESCAPED_UNICODE);

    $sec = sql_fetch(" select id from {$sec_table}
                       where project_id = '{$pid}' and section_type = '" . sql_real_escape_string($section_type) . "'
                       limit 1 ", false);
    if ($sec) {
        sql_query(" update {$sec_table}
                    set content_json = '" . sql_real_escape_string($json) . "', updated_at = '{$now}'
                    where id = '" . (int) $sec['id'] . "' ");
    } else {
        sql_query(" insert into {$sec_table}
                    set project_id = '{$pid}',
                        section_type = '" . sql_real_escape_string($section_type) . "',
                        content_json = '" . sql_real_escape_string($json) . "',
                        sort_order = 1,
                        created_at = '{$now}' ");
    }
    mw_json(array('success' => true, 'saved_at' => date('H:i:s')));
}

mw_json(array('success' => false, 'error' => '알 수 없는 요청입니다: ' . $action));
