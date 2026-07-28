<?php
$sub_menu = '360900';
include_once('./_common.php');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$sql_file = __DIR__ . '/sql/blog_automation_v1.sql';
if (!is_file($sql_file)) {
    alert('SQL 파일을 찾을 수 없습니다: ' . $sql_file);
}

$sql_content = file_get_contents($sql_file);
$sql_content = str_replace('{prefix}', G5_TABLE_PREFIX, $sql_content);

// 주석 라인 제거 후 세미콜론 기준으로 문장 분리
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

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <h2>블로그 자동화 MVP Phase 1 — 테이블 설치 결과</h2>
    <p>다음 테이블을 생성했습니다(이미 존재하면 건너뜁니다). 원본 DDL: <code>adm/blog/sql/blog_automation_v1.sql</code></p>
    <ul>
        <?php foreach ($created as $t) { ?>
            <li><?php echo get_text($t); ?></li>
        <?php } ?>
    </ul>
    <p><a href="./project_list.php" class="btn btn_submit btn">콘텐츠 프로젝트 목록으로 이동</a></p>
</div>
<?php
include_once(G5_ADMIN_PATH . '/admin.tail.php');
