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

// 화면이 보여줄 상태. 조회만 한다.
function mw_install_status($prefix)
{
    $status = array('tables' => array(), 'all_tables' => true, 'hero_seed' => 0, 'hero_expected' => 3);

    foreach (mw_install_tables() as $table => $label) {
        $exists = mw_table_exists($prefix, $table);
        $status['tables'][$table] = array('label' => $label, 'installed' => $exists);
        if (!$exists) {
            $status['all_tables'] = false;
        }
    }

    // 블록 테이블이 있을 때만 시드 개수를 센다.
    if ($status['tables']['miniweb_block']['installed']) {
        $row = sql_fetch(" select count(*) as cnt from {$prefix}miniweb_block where section_type = 'hero' ", false);
        $status['hero_seed'] = $row ? (int) $row['cnt'] : 0;
    }

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
