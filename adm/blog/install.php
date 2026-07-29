<?php
$sub_menu = '360900';
include_once('./_common.php');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$sql_file_v1 = __DIR__ . '/sql/blog_automation_v1.sql';
$sql_file_v2 = __DIR__ . '/sql/blog_automation_v2.sql';
$sql_file_v3 = __DIR__ . '/sql/blog_automation_v3.sql';
$sql_file_v4 = __DIR__ . '/sql/blog_automation_v4.sql';
$sql_file_v5 = __DIR__ . '/sql/blog_automation_v5.sql';
$sql_file_v6 = __DIR__ . '/sql/blog_automation_v6.sql';
$sql_file_v7 = __DIR__ . '/sql/blog_automation_v7.sql';
$sql_file_v8 = __DIR__ . '/sql/blog_automation_v8.sql';
$sql_file_v9 = __DIR__ . '/sql/blog_automation_v9.sql';

if (
    !is_file($sql_file_v1) || !is_file($sql_file_v2) || !is_file($sql_file_v3) ||
    !is_file($sql_file_v4) || !is_file($sql_file_v5) || !is_file($sql_file_v6) ||
    !is_file($sql_file_v7) || !is_file($sql_file_v8) || !is_file($sql_file_v9)
) {
    alert('SQL 파일을 찾을 수 없습니다.');
}

global $g5;

function bp_install_check_extensions(): array
{
    $required = array('mysqli', 'openssl', 'json', 'mbstring', 'curl');
    $status = array();
    foreach ($required as $ext) {
        $status[$ext] = extension_loaded($ext);
    }
    return $status;
}

function bp_install_table_exists(string $prefix, string $table): bool
{
    $like = sql_real_escape_string($prefix . 'blog_' . $table);
    $result = sql_query(" show tables like '{$like}' ", false);
    return $result && sql_num_rows($result) > 0;
}

function bp_install_column_exists(string $prefix, string $table, string $column): bool
{
    $table_name = sql_real_escape_string($prefix . 'blog_' . $table);
    $col_name = sql_real_escape_string($column);
    $result = sql_query(" show columns from `{$table_name}` like '{$col_name}' ", false);
    return $result && sql_num_rows($result) > 0;
}

function bp_install_run_sql_file(string $path, string $prefix): array
{
    $sql_content = file_get_contents($path);
    $sql_content = str_replace('{prefix}', $prefix, $sql_content);

    $lines = explode("\n", $sql_content);
    $lines = array_filter($lines, function ($line) {
        return strpos(trim($line), '--') !== 0;
    });
    $clean_sql = implode("\n", $lines);
    $statements = array_filter(array_map('trim', explode(';', $clean_sql)));

    $created = array();
    foreach ($statements as $stmt) {
        if ($stmt === '') {
            continue;
        }
        sql_query($stmt, false);
        if (preg_match('/CREATE TABLE IF NOT EXISTS `([a-zA-Z0-9_]+)`/i', $stmt, $m)) {
            $created[] = $m[1];
        }
    }
    return $created;
}

function bp_install_diagnostics(): array
{
    global $g5;
    $link = isset($g5['connect_db']) ? $g5['connect_db'] : null;

    $db_version = '';
    $db_charset = '';
    $db_collation = '';
    if ($link) {
        $row = sql_fetch(" select VERSION() as v, @@character_set_database as cs, @@collation_database as co ");
        if ($row) {
            $db_version = $row['v'];
            $db_charset = $row['cs'];
            $db_collation = $row['co'];
        }
    }

    return array(
        'php_version' => PHP_VERSION,
        'db_version' => $db_version,
        'db_charset_default' => $db_charset,
        'db_collation_default' => $db_collation,
        'connection_charset' => $link ? mysqli_character_set_name($link) : '',
        'mysqli_client_version' => function_exists('mysqli_get_client_info') ? mysqli_get_client_info() : '',
        'extensions' => bp_install_check_extensions(),
    );
}

$table_prefix = G5_TABLE_PREFIX;
$v1_installed = bp_install_table_exists($table_prefix, 'advertisers');
$v2_installed = bp_install_table_exists($table_prefix, 'content_title_candidates')
    && bp_install_table_exists($table_prefix, 'content_quality_checks');
$v3_installed = bp_install_table_exists($table_prefix, 'content_hashtags')
    && bp_install_table_exists($table_prefix, 'content_generation_logs');
$v4_installed = bp_install_table_exists($table_prefix, 'keywords');
$v5_installed = bp_install_column_exists($table_prefix, 'posts', 'deleted_at');
$v6_installed = bp_install_column_exists($table_prefix, 'publish_jobs', 'schedule_type');
$v7_installed = bp_install_table_exists($table_prefix, 'images');
$v8_installed = bp_install_table_exists($table_prefix, 'category_mappings');
$v9_installed = bp_install_table_exists($table_prefix, 'naver_packages');
$diagnostics = bp_install_diagnostics();

$did_install = false;
$created = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_admin_token();

    if (isset($_POST['run_all']) || !$v1_installed) {
        $created = array_merge($created, bp_install_run_sql_file($sql_file_v1, $table_prefix));
    }
    if (isset($_POST['run_all']) || !$v2_installed) {
        $created = array_merge($created, bp_install_run_sql_file($sql_file_v2, $table_prefix));
    }
    if (isset($_POST['run_all']) || !$v3_installed) {
        $created = array_merge($created, bp_install_run_sql_file($sql_file_v3, $table_prefix));
    }
    if (isset($_POST['run_all']) || !$v4_installed) {
        $created = array_merge($created, bp_install_run_sql_file($sql_file_v4, $table_prefix));
    }
    if (isset($_POST['run_all']) || !$v5_installed) {
        $created = array_merge($created, bp_install_run_sql_file($sql_file_v5, $table_prefix));
    }
    if (isset($_POST['run_all']) || !$v6_installed) {
        $created = array_merge($created, bp_install_run_sql_file($sql_file_v6, $table_prefix));
    }
    if (isset($_POST['run_all']) || !$v7_installed) {
        $created = array_merge($created, bp_install_run_sql_file($sql_file_v7, $table_prefix));
    }
    if (isset($_POST['run_all']) || $_POST['run_v8'] || !$v8_installed) {
        $created = array_merge($created, bp_install_run_sql_file($sql_file_v8, $table_prefix));
    }
    if (isset($_POST['run_all']) || $_POST['run_v9'] || !$v9_installed) {
        $created = array_merge($created, bp_install_run_sql_file($sql_file_v9, $table_prefix));
    }

    $did_install = true;
    
    $blog_img_dir = G5_DATA_PATH.'/blog_images';
    if (!is_dir($blog_img_dir)) {
        @mkdir($blog_img_dir, G5_DIR_PERMISSION);
        @chmod($blog_img_dir, G5_DIR_PERMISSION);
    }
}
$already_installed = $v1_installed && $v2_installed && $v3_installed && $v4_installed && $v5_installed && $v6_installed && $v7_installed && $v8_installed && $v9_installed;

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <h2>블로그 자동화 MVP — 테이블 설치</h2>
    <p>Phase 1: <?php echo $v1_installed ? '설치됨' : '<span style="color:#c00;">미설치</span>'; ?>
    &nbsp;&nbsp;Phase 2: <?php echo $v2_installed ? '설치됨' : '<span style="color:#c00;">미설치</span>'; ?>
    &nbsp;&nbsp;콘텐츠 파이프라인: <?php echo $v3_installed ? '설치됨' : '<span style="color:#c00;">미설치</span>'; ?>
    &nbsp;&nbsp;키워드: <?php echo $v4_installed ? '설치됨' : '<span style="color:#c00;">미설치</span>'; ?>
    &nbsp;&nbsp;포스트: <?php echo $v5_installed ? '설치됨' : '<span style="color:#c00;">미설치</span>'; ?>
    &nbsp;&nbsp;예약 발행: <?php echo $v6_installed ? '설치됨' : '<span style="color:#c00;">미설치</span>'; ?>
    &nbsp;&nbsp;이미지: <?php echo $v7_installed ? '설치됨' : '<span style="color:#c00;">미설치</span>'; ?>
    &nbsp;&nbsp;카테고리: <?php echo $v8_installed ? '설치됨' : '<span style="color:#c00;">미설치</span>'; ?>
    &nbsp;&nbsp;Naver Pack: <?php echo $v9_installed ? '설치됨' : '<span style="color:#c00;">미설치</span>'; ?></p>
</div>

<div class="tbl_frm01 tbl_wrap">
    <form method="post" action="./install.php">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <table>
            <caption>환경 진단</caption>
            <tbody>
                <tr><th scope="row">PHP 버전</th><td><?php echo get_text($diagnostics['php_version']); ?></td></tr>
                <tr><th scope="row">DB 서버 버전</th><td><?php echo get_text($diagnostics['db_version']); ?></td></tr>
                <tr><th scope="row">DB 기본 문자셋</th><td><?php echo get_text($diagnostics['db_charset_default']); ?> / <?php echo get_text($diagnostics['db_collation_default']); ?></td></tr>
                <tr><th scope="row">연결 문자셋</th><td><?php echo get_text($diagnostics['connection_charset']); ?><?php echo $diagnostics['connection_charset'] === 'utf8mb4' ? ' (정상)' : ' <span style="color:#c00;">— utf8mb4가 아닙니다</span>'; ?></td></tr>
                <tr><th scope="row">mysqli 클라이언트</th><td><?php echo get_text($diagnostics['mysqli_client_version']); ?></td></tr>
                <tr>
                    <th scope="row">V8 (Category Mappings) DB 업데이트</th>
                    <td>
                        <button type="submit" name="run_v8" value="1" class="btn btn_02">V8 업데이트 실행</button>
                        <span class="help_txt">category_mappings 테이블</span>
                    </td>
                </tr>
                <tr>
                    <th scope="row">V9 (Naver Packages) DB 업데이트</th>
                    <td>
                        <button type="submit" name="run_v9" value="1" class="btn btn_02">V9 업데이트 실행</button>
                        <span class="help_txt">naver_packages 테이블</span>
                    </td>
                </tr>
                <tr><th scope="row">필수 확장</th>
                    <td>
                        <?php foreach ($diagnostics['extensions'] as $ext => $loaded) { ?>
                            <?php echo get_text($ext) . ': ' . ($loaded ? '있음' : '<span style="color:#c00;">없음</span>') . '&nbsp;&nbsp;'; ?>
                        <?php } ?>
                    </td></tr>
            </tbody>
        </table>
    </form>
</div>

<?php if ($already_installed && !$did_install) { ?>
    <div class="local_desc01 local_desc" style="margin-top:15px;">
        <p>이미 설치되어 있습니다. 안전을 위해 재설치를 실행하지 않았습니다.</p>
        <p><a href="./project_list.php" class="btn btn_submit btn">콘텐츠 프로젝트 목록으로 이동</a></p>
    </div>
<?php } elseif ($did_install) { ?>
    <div class="local_desc01 local_desc" style="margin-top:15px;">
        <p>설치할 테이블이 있습니다. (원본 DDL: <code>adm/blog/sql/blog_automation_v1.sql</code> ~ <code>v8.sql</code>)</p>
        <ul>
            <?php foreach ($created as $t) { ?>
                <li><?php echo get_text($t); ?></li>
            <?php } ?>
        </ul>
        <p><a href="./project_list.php" class="btn btn_submit btn">콘텐츠 프로젝트 목록으로 이동</a></p>
    </div>
<?php } else { ?>
    <div class="local_desc01 local_desc" style="margin-top:15px;">
        <p><?php echo (!$v1_installed) ? '전체 파이프라인 및 기능을 설치합니다.' : '기존 설치 위에 누락된 부분만 추가로 설치합니다.'; ?>
        설치는 <code>CREATE TABLE IF NOT EXISTS</code> / <code>ADD COLUMN IF NOT EXISTS</code>만 실행하며 기존 데이터를 삭제하지 않습니다.
        운영 DB에 적용하기 전에는 반드시 백업을 먼저 받아 두세요.</p>
    </div>
    <form method="post" action="./install.php">
        <input type="hidden" name="token" value="<?php echo get_admin_token(); ?>">
        <div class="btn_confirm01 btn_confirm">
            <input type="submit" value="설치 진행" class="btn_submit btn" onclick="return confirm('블로그 자동화 테이블을 설치하시겠습니까?');">
        </div>
    </form>
<?php } ?>

<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
