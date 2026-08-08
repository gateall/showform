<?php
// 미니웹 설치 상태 점검 / SQL 실행 도우미.
//
// 이 파일의 함수들은 두 부류로 엄격히 나뉜다.
//   - mw_install_status_*  : SELECT / SHOW 만 한다. 화면을 열기만 해도 실행되므로
//                            여기서는 절대 CREATE / ALTER 를 하지 않는다.
//   - mw_install_run_*     : 관리자가 설치 버튼을 눌렀을 때만 호출된다.
//
// template_list.php:23처럼 화면 로딩 중 ALTER TABLE을 던지는 방식은 쓰지 않는다.
// 그 방식은 실패해도 조용히 넘어가고, 로컬과 운영의 스키마가 언제 갈렸는지 알 수 없다.
if (!defined('_GNUBOARD_')) exit;

define('MW_SQL_DIR', __DIR__ . '/../sql');

// 이 저장소의 lib/common.lib.php:2322 check_token()은 본문이 `return true` 하나뿐이라
// POST 토큰과 세션 토큰을 비교하지 않는다(원본 그누보드5는 비교한다). 그대로 쓰면
// CSRF 방어가 없는 것과 같으므로, 미니웹은 여기서 직접 비교한다.
// 코어를 고치면 토큰을 보내지 않는 기존 폼이 전부 막힐 수 있어 손대지 않았다.
// $consume=true  : 한 번 쓰면 무효화한다. 설치처럼 되돌리기 어려운 단발 요청용.
// $consume=false : 검증만 한다. 빌더는 한 번 연 화면에서 디자인 교체·저장을 여러 번
//                  호출하므로, 첫 요청에서 토큰을 없애면 두 번째부터 전부 실패한다.
function mw_verify_token($consume = true)
{
    $posted = isset($_POST['token']) ? trim((string) $_POST['token']) : '';
    $saved = (string) get_session('ss_token');

    if ($consume) {
        set_session('ss_token', '');
    }

    if ($posted === '' || $saved === '') {
        return false;
    }
    return hash_equals($saved, $posted);
}

// 미니웹이 쓰는 테이블 목록. 접두어를 붙이기 전의 이름이다.
function mw_install_tables()
{
    return array(
        'miniweb_project' => '프로젝트',
        'miniweb_block'   => '블록 라이브러리',
        'miniweb_section' => '섹션(고객 콘텐츠)',
    );
}

// SHOW TABLES 로 존재 여부만 본다.
function mw_table_exists($prefix, $table)
{
    $like = sql_real_escape_string($prefix . $table);
    $res = sql_query(" show tables like '{$like}' ", false);
    return $res && sql_num_rows($res) > 0;
}

// ─── 시드 자동 탐색 ────────────────────────────────────────────────────────
// 시드를 하나 늘릴 때마다 이 파일과 설치 화면을 고치는 구조를 만들지 않는다.
// sql/ 에 miniweb_seed_*.sql 을 두면 설치 목록에 저절로 나타난다.
//
// 관리자가 보낸 문자열을 경로로 쓰지 않는다. 실행 대상은 아래에서 찾아낸 목록뿐이다.
// basename() 은 경로를 다듬을 뿐 보안 검증이 아니므로, 형식 검사와 폴더 확인을 함께 한다.
function mw_seed_files()
{
    $dir = realpath(MW_SQL_DIR);
    if ($dir === false) {
        return array();
    }

    $out = array();
    foreach ((array) glob($dir . '/miniweb_seed_*.sql') as $path) {
        if (!preg_match('/^miniweb_seed_[a-zA-Z0-9_-]+\.sql$/', basename($path))) continue;
        $real = realpath($path);
        if ($real === false || !is_file($real)) continue;
        if (strpos($real, $dir . DIRECTORY_SEPARATOR) !== 0) continue; // 폴더 밖은 제외
        $out[] = $real;
    }
    sort($out);
    return $out;
}

// 파일 앞머리의 "-- MINIWEB-키: 값" 줄을 읽는다. 없으면 파일명에서 유추한다.
function mw_seed_meta($path)
{
    $meta = array('file' => basename($path), 'path' => $path, 'id' => '', 'label' => '',
                  'section_type' => '', 'version' => '', 'blocks' => 0);

    $fp = @fopen($path, 'r');
    if ($fp) {
        $read = 0;
        while ($read < 40 && ($line = fgets($fp)) !== false) {
            $read++;
            if (!preg_match('/^\s*--\s*MINIWEB-([A-Za-z-]+)\s*:\s*(.+?)\s*$/', $line, $m)) continue;
            switch (strtoupper($m[1])) {
                case 'SEED-ID':      $meta['id'] = $m[2]; break;
                case 'SEED-LABEL':   $meta['label'] = $m[2]; break;
                case 'SECTION-TYPE': $meta['section_type'] = $m[2]; break;
                case 'SEED-VERSION': $meta['version'] = $m[2]; break;
                case 'SEED-BLOCKS':  $meta['blocks'] = (int) $m[2]; break;
            }
        }
        fclose($fp);
    }

    // metadata 가 없거나 id 형식이 틀린 파일도 목록에서 조용히 사라지지 않게 파일명으로 채운다.
    // id 는 화면에 뿌리고 POST 로 돌아오는 값이라 형식을 좁게 제한한다.
    $from_file = preg_match('/^miniweb_seed_(.+)\.sql$/', $meta['file'], $m) ? $m[1] : '';
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', (string) $meta['id'])) {
        $meta['id'] = $from_file;
    }
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', (string) $meta['id'])) {
        $meta['id'] = '';
    }
    if ($meta['label'] === '') {
        $meta['label'] = $meta['id'];
    }
    return $meta;
}

// 섹션 타입별 블록 수. 설치 여부는 "파일이 있으니 설치된 것"이 아니라 DB 로만 판단한다.
function mw_section_block_count($prefix, $section_type)
{
    $section_type = trim((string) $section_type);
    if ($section_type === '') {
        return -1; // 셀 기준이 없다
    }
    $row = sql_fetch(" select count(*) as cnt from {$prefix}miniweb_block
                       where section_type = '" . sql_real_escape_string($section_type) . "' ", false);
    return $row ? (int) $row['cnt'] : 0;
}

// 성공 메시지에 무엇이 들어갔는지 보여주기 위한 목록.
function mw_section_block_names($prefix, $section_type, $limit = 10)
{
    $section_type = trim((string) $section_type);
    if ($section_type === '') {
        return array();
    }
    $limit = (int) $limit;
    $res = sql_query(" select block_name from {$prefix}miniweb_block
                       where section_type = '" . sql_real_escape_string($section_type) . "'
                       order by sort_order asc, id asc limit {$limit} ", false);
    $out = array();
    if ($res) {
        while ($row = sql_fetch_array($res)) { $out[] = $row['block_name']; }
    }
    return $out;
}

// 기대 개수를 모르면 '일부 설치'라고 단정하지 않는다. 0개면 미설치, 있으면 설치됨.
function mw_seed_state($count, $expected)
{
    if ($count < 0)  return 'unknown';
    if ($count === 0) return 'none';
    if ($expected > 0 && $count < $expected) return 'partial';
    return 'installed';
}

function mw_seed_list($prefix, $block_table_ready)
{
    $list = array();
    foreach (mw_seed_files() as $path) {
        $meta = mw_seed_meta($path);
        if ($meta['id'] === '') continue;
        $meta['count'] = $block_table_ready ? mw_section_block_count($prefix, $meta['section_type']) : -1;
        $meta['state'] = mw_seed_state($meta['count'], (int) $meta['blocks']);
        $list[$meta['id']] = $meta;
    }
    return $list;
}

// POST 로 온 id 로 파일을 고른다. 경로 문자열은 받지 않는다.
function mw_seed_find($id)
{
    $id = (string) $id;
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $id)) {
        return null;
    }
    foreach (mw_seed_files() as $path) {
        $meta = mw_seed_meta($path);
        if ($meta['id'] === $id) {
            return $meta;
        }
    }
    return null;
}

// 화면이 보여줄 상태. 조회만 한다.
function mw_install_status($prefix)
{
    $status = array('tables' => array(), 'all_tables' => true, 'seeds' => array());

    foreach (mw_install_tables() as $table => $label) {
        $exists = mw_table_exists($prefix, $table);
        $status['tables'][$table] = array('label' => $label, 'installed' => $exists);
        if (!$exists) {
            $status['all_tables'] = false;
        }
    }

    // 블록 테이블이 있을 때만 DB 를 센다.
    $status['seeds'] = mw_seed_list($prefix, $status['tables']['miniweb_block']['installed']);

    return $status;
}

// SQL 파일을 문장 단위로 실행한다. 버튼을 눌렀을 때만 호출된다.
// 반환: array('ok' => bool, 'ran' => int, 'errors' => array)
function mw_install_run_sql_file($path, $prefix)
{
    $result = array('ok' => false, 'ran' => 0, 'errors' => array());

    if (!is_file($path)) {
        $result['errors'][] = 'SQL 파일을 찾을 수 없습니다: ' . basename($path);
        return $result;
    }

    $sql = file_get_contents($path);
    if ($sql === false || trim($sql) === '') {
        $result['errors'][] = 'SQL 파일을 읽지 못했습니다: ' . basename($path);
        return $result;
    }

    $sql = str_replace('{prefix}', $prefix, $sql);

    // 주석 줄(--)을 걷어낸다. 세미콜론이 주석 안에 있으면 문장 분리가 어긋난다.
    $lines = array();
    foreach (preg_split('/\r\n|\r|\n/', $sql) as $line) {
        if (preg_match('/^\s*--/', $line)) continue;
        $lines[] = $line;
    }
    $clean = implode("\n", $lines);

    foreach (mw_install_split_statements($clean) as $statement) {
        // 실패해도 다음 문장을 시도하되, 무엇이 실패했는지는 반드시 남긴다.
        $ok = sql_query($statement, false);
        if ($ok === false) {
            $result['errors'][] = mw_install_first_line($statement) . ' — ' . sql_error();
        } else {
            $result['ran']++;
        }
    }

    $result['ok'] = empty($result['errors']);
    return $result;
}

// 세미콜론으로 문장을 나누되 따옴표 안은 건드리지 않는다.
// 단순히 explode(';')로 자르면 블록 시드의 CSS('.mw-hero { text-align:left; }')처럼
// 문자열 리터럴 안에 세미콜론이 들어 있을 때 INSERT가 중간에서 잘려나간다.
// 실제로 그렇게 잘려서 시드 설치가 문법 오류로 실패했다.
function mw_install_split_statements($sql)
{
    $out = array();
    $buf = '';
    $in_string = false;
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
        $ch = $sql[$i];

        if ($in_string) {
            $buf .= $ch;
            // 백슬래시 이스케이프( \' 등 )는 다음 글자까지 통째로 넘긴다.
            if ($ch === '\\' && $i + 1 < $len) {
                $buf .= $sql[$i + 1];
                $i++;
                continue;
            }
            if ($ch === "'") {
                $in_string = false;
            }
            continue;
        }

        if ($ch === "'") {
            $in_string = true;
            $buf .= $ch;
            continue;
        }

        if ($ch === ';') {
            $out[] = $buf;
            $buf = '';
            continue;
        }

        $buf .= $ch;
    }

    if (trim($buf) !== '') {
        $out[] = $buf;
    }

    return array_values(array_filter(array_map('trim', $out), 'strlen'));
}

// 오류 메시지에 SQL 전문을 쏟지 않고 무엇을 실행하다 실패했는지만 짧게 남긴다.
function mw_install_first_line($statement)
{
    $line = trim(strtok($statement, "\n"));
    return mb_strimwidth($line, 0, 80, '…');
}

function mw_install_sql_path($file)
{
    return MW_SQL_DIR . '/' . $file;
}
