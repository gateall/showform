<?php
// 미니웹 시스템 설정 / 설치 화면.
//
// /adm/landing/  = 설치·마이그레이션 등 내부 시스템 작업
// /manager/landing/ = 실제 제작·편집·운영
// 역할을 이렇게 나눠 두었으므로 설치는 이쪽에만 둔다.
//
// 규칙: 이 페이지를 여는 것만으로는 DB 구조가 바뀌지 않는다.
// 상태 표시는 SELECT/SHOW 만 하고, CREATE/ALTER 는 아래 POST 분기에서만 실행한다.
// _common.php는 $sub_menu를 세팅하지 않는다. 여기서 지정하지 않으면 auth_check_menu가
// 빈 코드로 검사해 권한 판정이 무의미해진다(같은 폴더의 ai_setting.php 등과 같은 방식).
// 900900은 adm/sms_admin/num_book_file*.php가 이미 쓰는 코드라 그대로 두면 미니웹
// 설치 권한이 SMS 번호북 권한과 한 몸이 된다. 전용 코드로 바꾸고
// adm/admin.menu900.php에 등록해 관리자 메뉴에서 들어올 수 있게 했다.
$sub_menu = '900060'; // 랜딩 > 미니웹 설정

include_once('./_common.php');

// DB 스키마를 만드는 화면이라 쓰기 권한 위에 최고관리자까지 요구한다.
auth_check_menu($auth, $sub_menu, 'w');
if ($is_admin !== 'super') {
    alert('미니웹 설치는 최고관리자만 실행할 수 있습니다.', G5_ADMIN_URL);
}

require_once __DIR__ . '/lib/miniweb_install.lib.php';

$tab = isset($_GET['tab']) ? preg_replace('/[^a-z_]/', '', $_GET['tab']) : 'install';
$prefix = G5_TABLE_PREFIX;
$msg = '';
$msg_type = 'info';

// ─── 설치 실행. 반드시 POST + 관리자 토큰이 있어야 한다. ───
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!mw_verify_token()) {
        $msg = '잘못된 접근입니다. 페이지를 새로고침한 뒤 다시 시도해 주세요.';
        $msg_type = 'error';
    } else {
        $action = isset($_POST['do']) ? $_POST['do'] : '';

        if ($action === 'schema') {
            $r = mw_install_run_sql_file(mw_install_sql_path('miniweb_v1.sql'), $prefix);
            if ($r['ok']) {
                $msg = "미니웹 테이블을 설치했습니다. (실행 {$r['ran']}건)";
                $msg_type = 'ok';
            } else {
                $msg = '설치 중 오류가 발생했습니다: ' . implode(' / ', $r['errors']);
                $msg_type = 'error';
            }
        } elseif ($action === 'seed') {
            // 실행할 파일은 관리자가 보낸 경로가 아니라, 서버가 sql/ 에서 찾아낸 목록에서 고른다.
            $seed = mw_seed_find(isset($_POST['seed_id']) ? $_POST['seed_id'] : '');

            if (!$seed) {
                $msg = '알 수 없는 시드입니다. 화면을 새로고침한 뒤 다시 시도해 주세요.';
                $msg_type = 'error';
            } elseif (!mw_table_exists($prefix, 'miniweb_block')) {
                $msg = '블록 테이블이 아직 없습니다. 미니웹 DB 설치를 먼저 실행해 주세요.';
                $msg_type = 'error';
            } else {
                $before = mw_section_block_count($prefix, $seed['section_type']);
                $r = mw_install_run_sql_file($seed['path'], $prefix);

                if ($r['ok']) {
                    $after = mw_section_block_count($prefix, $seed['section_type']);
                    $names = mw_section_block_names($prefix, $seed['section_type']);
                    $msg = $seed['label'] . ' 설치 완료';
                    if ($after >= 0) {
                        $added = ($before >= 0) ? max(0, $after - $before) : 0;
                        $msg .= " · 현재 블록 {$after}개(새로 추가 {$added}개)";
                    }
                    if ($names) {
                        $msg .= ' · ' . implode(' / ', $names);
                    }
                    $msg_type = 'ok';
                } else {
                    // 무엇을 실행하다 실패했는지는 알리되 SQL 전문이나 접속 정보는 내보내지 않는다.
                    $msg = $seed['label'] . ' 설치 실패 (' . $seed['file'] . ') — ' . implode(' / ', $r['errors']);
                    $msg_type = 'error';
                }
            }
        }
    }
}

// ─── 여기서부터는 조회만 한다 ───
$status = mw_install_status($prefix);

// 토큰은 화면당 한 번만 만든다.
// lib/common.lib.php:2312 의 get_token() 은 값을 돌려주는 함수가 아니라 매번 새 토큰을
// 만들어 세션(ss_token)을 덮어쓴다. 그래서 폼마다 부르면 마지막에 그려진 폼의 토큰만
// 세션과 맞고 나머지 폼은 전부 "잘못된 접근입니다"로 막힌다.
// 설치 화면을 시드별 폼으로 나누면서 실제로 그 일이 났다(Benefits 설치가 눌리지 않음).
$mw_token = get_token();

$g5['title'] = '미니웹 설정';
include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<style>
.mw-set { max-width: 860px; }
.mw-msg { padding:12px 14px; border-radius:10px; margin-bottom:16px; font-size:14px; line-height:1.6; }
.mw-msg.ok    { background:#dcfce7; color:#166534; border:1px solid #86efac; }
.mw-msg.error { background:#fef2f2; color:#b91c1c; border:1px solid #fecaca; }
.mw-msg.info  { background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; }

.mw-card { border:1px solid #e2e8f0; border-radius:12px; padding:18px; margin-bottom:16px; background:#fff; }
.mw-card h2 { margin:0 0 12px; font-size:16px; }

.mw-rows { display:grid; grid-template-columns:1fr; gap:0; }
.mw-row { display:flex; justify-content:space-between; align-items:center; gap:12px;
          padding:10px 0; border-top:1px solid #f1f5f9; }
.mw-row:first-child { border-top:0; }
.mw-badge { flex:0 0 auto; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; }
.mw-badge.on  { background:#dcfce7; color:#166534; }
.mw-badge.off { background:#fef3c7; color:#92400e; }

.mw-actions { display:grid; grid-template-columns:1fr; gap:8px; margin-top:14px; }
.mw-actions button { min-height:44px; padding:10px 16px; border-radius:10px; border:1px solid #cbd5e1;
                     background:#fff; font-weight:700; cursor:pointer; }
.mw-actions button.primary { background:#0f766e; border-color:#0f766e; color:#fff; }
.mw-actions button[disabled] { opacity:.5; cursor:not-allowed; }
.mw-note { margin-top:10px; font-size:13px; color:#64748b; line-height:1.6; }

.mw-seed { border-top:1px solid #f1f5f9; padding:14px 0; }
.mw-seed__head { display:flex; align-items:center; justify-content:space-between; gap:12px; }
.mw-seed__name { font-weight:700; font-size:14px; }
.mw-seed__meta { margin-top:6px; font-size:12px; color:#64748b; line-height:1.7; word-break:break-all; }
.mw-seed__form { margin-top:10px; }
.mw-seed__form button { width:100%; min-height:44px; padding:10px 16px; border-radius:10px;
                        border:1px solid #cbd5e1; background:#fff; font-weight:700; cursor:pointer; }
.mw-seed__form button.primary { background:#0f766e; border-color:#0f766e; color:#fff; }
.mw-seed__form button[disabled] { opacity:.5; cursor:not-allowed; }

@media (min-width: 768px) {
    .mw-seed__form button { width:auto; min-width:200px; }
}

@media (min-width: 768px) {
    .mw-actions { grid-template-columns:repeat(2, minmax(0,1fr)); }
}
</style>

<div class="mw-set">
    <?php if ($msg) { ?>
        <div class="mw-msg <?php echo $msg_type; ?>"><?php echo get_text($msg); ?></div>
    <?php } ?>

    <div class="mw-card">
        <h2>미니웹 DB</h2>
        <div class="mw-rows">
            <?php foreach ($status['tables'] as $table => $info) { ?>
                <div class="mw-row">
                    <span><?php echo get_text($info['label']); ?>
                        <span style="color:#94a3b8;font-size:12px;">(<?php echo $prefix . $table; ?>)</span>
                    </span>
                    <span class="mw-badge <?php echo $info['installed'] ? 'on' : 'off'; ?>">
                        <?php echo $info['installed'] ? '설치됨' : '미설치'; ?>
                    </span>
                </div>
            <?php } ?>
        </div>

        <form method="post" class="mw-actions">
            <?php // 위에서 한 번 만든 토큰을 모든 폼이 같이 쓴다(여기서 get_token()을 다시
                  // 부르면 세션이 바뀌어 다른 폼들이 전부 막힌다). ?>
            <input type="hidden" name="token" value="<?php echo $mw_token; ?>">
            <button type="submit" name="do" value="schema" class="primary"
                onclick="return confirm('미니웹 테이블을 생성합니다. 계속할까요?');">
                미니웹 DB 설치
            </button>
        </form>

        <p class="mw-note">
            이 화면을 여는 것만으로는 DB가 변경되지 않습니다. 버튼을 눌렀을 때만
            <code>adm/landing/sql/</code> 의 SQL이 실행됩니다
            (<code>CREATE TABLE IF NOT EXISTS</code>이라 여러 번 눌러도 안전합니다).
        </p>
    </div>

    <div class="mw-card">
        <h2>블록 시드</h2>
        <p class="mw-note" style="margin-top:0;">
            <code>adm/landing/sql/</code> 의 <code>miniweb_seed_*.sql</code> 을 그대로 읽어 만든 목록입니다.
            시드 파일을 추가하면 이 화면에 저절로 나타납니다 — PHP를 고칠 필요가 없습니다.
            <br>설치 여부는 파일이 아니라 <strong>DB에 실제로 들어 있는 블록 수</strong>로 판단합니다.
        </p>

        <?php if (!$status['seeds']) { ?>
            <div class="mw-msg info" style="margin:12px 0 0;">
                설치할 시드 파일이 없습니다. <code>adm/landing/sql/miniweb_seed_*.sql</code> 형식으로 올려 주세요.
            </div>
        <?php } ?>

        <?php foreach ($status['seeds'] as $seed) {
            $state = $seed['state'];
            $badge = array('installed' => array('on', '설치됨'),
                           'partial'   => array('off', '일부 설치'),
                           'none'      => array('off', '미설치'),
                           'unknown'   => array('off', '확인 불가'));
            list($badge_class, $badge_text) = isset($badge[$state]) ? $badge[$state] : $badge['unknown'];
            $ready = $status['tables']['miniweb_block']['installed'];
        ?>
            <div class="mw-seed">
                <div class="mw-seed__head">
                    <span class="mw-seed__name"><?php echo get_text($seed['label']); ?></span>
                    <span class="mw-badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
                </div>
                <div class="mw-seed__meta">
                    <code><?php echo get_text($seed['file']); ?></code>
                    <?php if ($seed['section_type'] !== '') { ?>
                        · section_type <code><?php echo get_text($seed['section_type']); ?></code>
                    <?php } ?>
                    <?php if ($seed['count'] >= 0) { ?>
                        · DB 블록 <?php echo (int) $seed['count']; ?><?php echo $seed['blocks'] > 0 ? '/' . (int) $seed['blocks'] : ''; ?>개
                    <?php } ?>
                </div>
                <form method="post" class="mw-seed__form">
                    <input type="hidden" name="token" value="<?php echo $mw_token; ?>">
                    <input type="hidden" name="seed_id" value="<?php echo get_text($seed['id']); ?>">
                    <button type="submit" name="do" value="seed" <?php echo $ready ? '' : 'disabled'; ?>
                        class="<?php echo $state === 'installed' ? '' : 'primary'; ?>"
                        <?php // 라벨은 시드 파일이 준 값이다. 따옴표가 들어 있어도 JS 문자열이
                              // 깨지지 않게 addslashes 를 먼저 걸고 HTML 이스케이프한다. ?>
                        onclick="return confirm('<?php echo get_text(addslashes($seed['label'])); ?> 을(를) 설치합니다. 계속할까요?');">
                        <?php echo $state === 'installed' ? '다시 설치(덮어쓰기)' : '설치'; ?>
                    </button>
                </form>
            </div>
        <?php } ?>
    </div>

    <div class="mw-card">
        <h2>스키마 변경 규칙</h2>
        <p class="mw-note" style="margin-top:0;">
            배포된 <code>miniweb_v1.sql</code>은 수정하지 않습니다. 스키마를 바꿔야 하면
            <code>miniweb_v2.sql</code>을 새로 추가해 순차 적용합니다.
            이미 설치한 환경과 새로 설치하는 환경의 결과가 달라지는 것을 막기 위해서입니다.
        </p>
    </div>
</div>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php'); ?>
