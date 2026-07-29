<?php
$sub_menu = '360900';
include_once('./_common.php');
require_once __DIR__ . '/lib/blog_install.lib.php';

$admin_token = get_admin_token();

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$table_prefix = G5_TABLE_PREFIX;
$versions = bp_install_get_versions($table_prefix);

foreach ($versions as $v => $info) {
    if (!is_file($info['file'])) {
        alert('SQL 파일을 찾을 수 없습니다: ' . $info['file']);
    }
}

$diagnostics = bp_install_diagnostics();

$already_installed = true;
foreach ($versions as $info) {
    if (!$info['installed']) {
        $already_installed = false;
        break;
    }
}

// install.php(처리 전용)가 실행을 마친 뒤 세션에 남겨둔 결과를 1회만 표시하고 지운다.
$install_result = get_session('bp_install_result');
set_session('bp_install_result', '');
$did_install = is_array($install_result);
$created = $did_install && isset($install_result['created']) ? $install_result['created'] : array();
$install_error = $did_install && isset($install_result['error']) ? $install_result['error'] : '';

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <h2>블로그 자동화 MVP — 테이블 설치</h2>
    <p>
        <?php foreach ($versions as $v => $info) { ?>
            <?php echo get_text($info['label']); ?>: <?php echo $info['installed'] ? '설치됨' : '<span style="color:#c00;">미설치</span>'; ?>&nbsp;&nbsp;
        <?php } ?>
    </p>
</div>

<?php if ($install_error !== '') { ?>
    <div class="local_desc01 local_desc" style="margin-top:15px; border:1px solid #c00;">
        <p style="color:#c00;">설치 중 오류가 발생했습니다: <?php echo get_text($install_error); ?></p>
    </div>
<?php } elseif ($did_install) { ?>
    <div class="local_desc01 local_desc" style="margin-top:15px;">
        <p>설치가 완료되었습니다.</p>
        <ul>
            <?php foreach ($created as $t) { ?>
                <li><?php echo get_text($t); ?> 테이블 생성됨</li>
            <?php } ?>
            <?php if (!$created) { ?>
                <li>새로 생성된 테이블은 없습니다(컬럼 추가이거나 이미 최신 상태).</li>
            <?php } ?>
        </ul>
        <p><a href="./project_list.php" class="btn btn_submit btn">콘텐츠 프로젝트 목록으로 이동</a></p>
    </div>
<?php } ?>

<div class="tbl_frm01 tbl_wrap">
    <table>
        <caption>환경 진단</caption>
        <tbody>
            <tr><th scope="row">PHP 버전</th><td><?php echo get_text($diagnostics['php_version']); ?></td></tr>
            <tr><th scope="row">DB 서버 버전</th><td><?php echo get_text($diagnostics['db_version']); ?></td></tr>
            <tr><th scope="row">DB 기본 문자셋</th><td><?php echo get_text($diagnostics['db_charset_default']); ?> / <?php echo get_text($diagnostics['db_collation_default']); ?></td></tr>
            <tr><th scope="row">연결 문자셋</th><td><?php echo get_text($diagnostics['connection_charset']); ?><?php echo $diagnostics['connection_charset'] === 'utf8mb4' ? ' (정상)' : ' <span style="color:#c00;">— utf8mb4가 아닙니다</span>'; ?></td></tr>
            <tr><th scope="row">mysqli 클라이언트</th><td><?php echo get_text($diagnostics['mysqli_client_version']); ?></td></tr>
            <tr><th scope="row">필수 확장</th>
                <td>
                    <?php foreach ($diagnostics['extensions'] as $ext => $loaded) { ?>
                        <?php echo get_text($ext) . ': ' . ($loaded ? '있음' : '<span style="color:#c00;">없음</span>') . '&nbsp;&nbsp;'; ?>
                    <?php } ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="tbl_frm01 tbl_wrap" style="margin-top:15px;">
    <table>
        <caption>버전별 업데이트</caption>
        <thead>
            <tr>
                <th scope="col">버전</th>
                <th scope="col">설명</th>
                <th scope="col">상태</th>
                <th scope="col">실행</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($versions as $v => $info) { ?>
                <tr>
                    <th scope="row">V<?php echo (int) $v; ?> (<?php echo get_text($info['label']); ?>)</th>
                    <td><?php echo get_text($info['desc']); ?></td>
                    <td><?php echo $info['installed'] ? '설치됨' : '<span style="color:#c00;">미설치</span>'; ?></td>
                    <td>
                        <form method="post" action="./install.php" style="display:inline;">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($admin_token, ENT_QUOTES); ?>">
                            <input type="hidden" name="mode" value="run_version">
                            <input type="hidden" name="version" value="<?php echo (int) $v; ?>">
                            <button type="submit" class="btn btn_02" onclick="return confirm('V<?php echo (int) $v; ?> 업데이트를 실행하시겠습니까?');">V<?php echo (int) $v; ?> 업데이트 실행</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php if ($already_installed) { ?>
    <div class="local_desc01 local_desc" style="margin-top:15px;">
        <p>모든 버전이 이미 설치되어 있습니다. 안전을 위해 개별 버전 재실행만 지원합니다.</p>
    </div>
<?php } else { ?>
    <div class="local_desc01 local_desc" style="margin-top:15px;">
        <p>미설치 항목이 있습니다. 아래 버튼으로 누락된 버전을 한 번에 설치할 수 있습니다.
        설치는 <code>CREATE TABLE IF NOT EXISTS</code> / <code>ADD COLUMN IF NOT EXISTS</code>만 실행하며 기존 데이터를 삭제하지 않습니다.
        운영 DB에 적용하기 전에는 반드시 백업을 먼저 받아 두세요.</p>
    </div>
<?php } ?>

<form method="post" action="./install.php">
    <input type="hidden" name="token" value="<?php echo htmlspecialchars($admin_token, ENT_QUOTES); ?>">
    <input type="hidden" name="mode" value="run_all">
    <div class="btn_confirm01 btn_confirm">
        <input type="submit" value="전체 설치(누락분 포함 V1~V<?php echo count($versions); ?> 전체 재실행)" class="btn_submit btn" onclick="return confirm('블로그 자동화 테이블 전체(V1~V<?php echo count($versions); ?>)를 설치하시겠습니까?');">
    </div>
</form>

<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
