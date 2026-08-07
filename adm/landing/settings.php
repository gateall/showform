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
$sub_menu = '900900'; // 랜딩 > 미니웹 설정

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
    if (!check_token()) {
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
            // 시드는 블록 테이블이 있어야 넣을 수 있다.
            if (!mw_table_exists($prefix, 'miniweb_block')) {
                $msg = '블록 테이블이 아직 없습니다. 미니웹 DB 설치를 먼저 실행해 주세요.';
                $msg_type = 'error';
            } else {
                $r = mw_install_run_sql_file(mw_install_sql_path('miniweb_seed_v1.sql'), $prefix);
                if ($r['ok']) {
                    $msg = "Hero 기본 샘플을 설치했습니다. (실행 {$r['ran']}건)";
                    $msg_type = 'ok';
                } else {
                    $msg = '샘플 설치 중 오류가 발생했습니다: ' . implode(' / ', $r['errors']);
                    $msg_type = 'error';
                }
            }
        }
    }
}

// ─── 여기서부터는 조회만 한다 ───
$status = mw_install_status($prefix);

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

@media (min-width: 768px) {
    .mw-actions { grid-template-columns:repeat(2, minmax(0,1fr)); }
}
</style>

<div class="mw-set">
    <?php if ($msg) { ?>
        <div class="mw-msg <?php echo $msg_type; ?>"><?php echo get_text($msg); ?></div>
    <?php } ?>

    <div class="mw-card">
        <h2>미니웹 DB 상태</h2>
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
            <div class="mw-row">
                <span>Hero 기본 샘플
                    <span style="color:#94a3b8;font-size:12px;">(<?php echo (int) $status['hero_seed']; ?>/<?php echo (int) $status['hero_expected']; ?>)</span>
                </span>
                <span class="mw-badge <?php echo $status['hero_seed'] >= $status['hero_expected'] ? 'on' : 'off'; ?>">
                    <?php echo $status['hero_seed'] >= $status['hero_expected'] ? '설치됨' : '미설치'; ?>
                </span>
            </div>
        </div>

        <form method="post" class="mw-actions">
            <?php echo get_token(); ?>
            <button type="submit" name="do" value="schema" class="primary"
                onclick="return confirm('미니웹 테이블을 생성합니다. 계속할까요?');">
                미니웹 DB 설치
            </button>
            <button type="submit" name="do" value="seed"
                <?php echo $status['tables']['miniweb_block']['installed'] ? '' : 'disabled'; ?>
                onclick="return confirm('Hero 기본 샘플 3종을 등록합니다. 계속할까요?');">
                Hero 기본 샘플 설치
            </button>
        </form>

        <p class="mw-note">
            이 화면을 여는 것만으로는 DB가 변경되지 않습니다. 위 버튼을 눌렀을 때만
            <code>adm/landing/sql/</code> 의 SQL이 실행됩니다.
            <br>두 SQL 모두 여러 번 실행해도 안전합니다
            (<code>CREATE TABLE IF NOT EXISTS</code> · <code>ON DUPLICATE KEY UPDATE</code>).
        </p>
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
