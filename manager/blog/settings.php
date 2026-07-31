<?php
$sub_menu = '360150';
include_once('./_common.php');
auth_check_menu($auth, $sub_menu, 'r');

$page_title = '운영 설정';
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'channels';

$tabs = array(
    'channels' => '사이트·채널',
    'ai' => 'AI 설정',
    'images' => '이미지 설정',
    'system' => '발행 시스템',
    'common' => '공통 설정',
    'install' => '설치 관리'
);

if (!isset($tabs[$tab])) {
    $tab = 'channels';
}

// 설치 관리 탭은 DB 스키마를 직접 변경하는 기능이라, adm 쪽 설치 화면과 동일하게
// 최고관리자만 접근을 허용한다(메뉴 권한과 별개로 더 엄격한 제한).
if ($tab === 'install') {
    require_once G5_ADMIN_PATH . '/blog/lib/blog_install.lib.php';
    if ($is_admin !== 'super') {
        alert('설치 관리는 최고관리자만 접근할 수 있습니다.');
    }
    $install_versions = bp_install_get_versions(G5_TABLE_PREFIX);
    $install_admin_token = get_admin_token();
    $install_result = get_session('bp_install_result');
    set_session('bp_install_result', '');
    $install_did_run = is_array($install_result);
    $install_created = $install_did_run && isset($install_result['created']) ? $install_result['created'] : array();
    $install_error = $install_did_run && isset($install_result['error']) ? $install_result['error'] : '';
    $install_error_data = $install_did_run && isset($install_result['error_data']) ? $install_result['error_data'] : null;
    $install_all_done = true;
    foreach ($install_versions as $info) {
        if (!$info['installed']) { $install_all_done = false; break; }
    }
}

// AI 설정 탭도 adm 쪽 ai_provider_list.php와 동일하게 최고관리자만 허용한다(API 키를 다루므로).
if ($tab === 'ai') {
    if ($is_admin !== 'super') {
        alert('AI 설정은 최고관리자만 접근할 수 있습니다.');
    }
    $ai_providers_table = bp_table('ai_providers');
    $ai_providers_res = sql_query(" select * from {$ai_providers_table} order by id asc ");
    $ai_providers = array();
    while ($ap = sql_fetch_array($ai_providers_res)) {
        $ai_providers[] = $ap;
    }
}

include_once(__DIR__ . '/../layout/header.php');
?>
<div class="mgr-card" style="padding:1.25rem;margin-bottom:1.5rem;">
    <h2 style="margin:0 0 1rem;font-size:1.25rem;"><?php echo $page_title; ?></h2>

    <!-- 탭 UI -->
    <div style="border-bottom:1px solid var(--mgr-border);margin-bottom:1.5rem;display:flex;gap:1rem;">
        <?php foreach ($tabs as $k => $v): ?>
            <a href="?tab=<?php echo $k; ?>" style="padding:0.5rem 1rem;text-decoration:none;color:<?php echo $tab === $k ? 'var(--mgr-primary)' : 'var(--mgr-text-muted)'; ?>;border-bottom:2px solid <?php echo $tab === $k ? 'var(--mgr-primary)' : 'transparent'; ?>;font-weight:<?php echo $tab === $k ? 'bold' : 'normal'; ?>;">
                <?php echo $v; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if ($tab === 'install'): ?>
    <!-- 설치 관리: adm/blog/install_form.php와 동일한 bp_install_* 라이브러리를 그대로 재사용 -->
    <?php if ($install_error !== '' || $install_error_data): ?>
        <div style="padding:1rem;margin-bottom:1.25rem;border:1px solid #dc2626;border-radius:6px;background:#fef2f2;">
            <?php if ($install_error_data): ?>
                <p style="color:#b91c1c;font-weight:bold;margin:0 0 0.5rem;">설치 실패 (<?php echo htmlspecialchars($install_error_data['version'], ENT_QUOTES, 'UTF-8'); ?>)</p>
                <p style="margin:0 0 0.25rem;"><?php echo htmlspecialchars($install_error_data['statement_no'], ENT_QUOTES, 'UTF-8'); ?>번째 SQL 문장에서 오류가 발생했습니다.</p>
                <p style="margin:0 0 0.25rem;"><strong>오류 코드:</strong> <?php echo htmlspecialchars($install_error_data['error_code'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p style="margin:0 0 0.25rem;"><strong>오류 내용:</strong> <?php echo htmlspecialchars($install_error_data['message'], ENT_QUOTES, 'UTF-8'); ?></p>
                <p style="margin:0;"><strong>SQL 요약:</strong> <?php echo htmlspecialchars($install_error_data['sql_summary'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php else: ?>
                <p style="color:#b91c1c;margin:0;">설치 중 오류가 발생했습니다: <?php echo htmlspecialchars($install_error, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        </div>
    <?php elseif ($install_did_run): ?>
        <div style="padding:1rem;margin-bottom:1.25rem;border:1px solid #10b981;border-radius:6px;background:#f0fdf4;">
            <p style="margin:0 0 0.5rem;color:#047857;font-weight:bold;">설치가 완료되었습니다.</p>
            <?php if ($install_created): ?>
                <ul style="margin:0;padding-left:1.25rem;">
                    <?php foreach ($install_created as $t): ?><li><?php echo get_text($t); ?> 테이블 생성됨</li><?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p style="margin:0;color:#047857;">새로 생성된 테이블은 없습니다(컬럼 추가이거나 이미 최신 상태).</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead>
                <tr style="border-bottom:2px solid var(--mgr-border);text-align:left;">
                    <th style="padding:0.5rem;">버전</th>
                    <th style="padding:0.5rem;">설명</th>
                    <th style="padding:0.5rem;">상태</th>
                    <th style="padding:0.5rem;">실행</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($install_versions as $v => $info): ?>
                <tr style="border-bottom:1px solid var(--mgr-border);">
                    <td style="padding:0.5rem;white-space:nowrap;">V<?php echo (int) $v; ?> (<?php echo get_text($info['label']); ?>)</td>
                    <td style="padding:0.5rem;color:var(--mgr-text-muted);"><?php echo get_text($info['desc']); ?></td>
                    <td style="padding:0.5rem;">
                        <?php echo $info['installed'] ? '<span style="color:#10b981;">설치됨</span>' : '<span style="color:#dc2626;">미설치</span>'; ?>
                    </td>
                    <td style="padding:0.5rem;">
                        <form method="post" action="./settings_install_action.php" style="display:inline;">
                            <input type="hidden" name="token" value="<?php echo htmlspecialchars($install_admin_token, ENT_QUOTES); ?>">
                            <input type="hidden" name="mode" value="run_version">
                            <input type="hidden" name="version" value="<?php echo (int) $v; ?>">
                            <button type="submit" class="btn btn_02" onclick="return confirm('V<?php echo (int) $v; ?> 업데이트를 실행하시겠습니까?');">실행</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($install_all_done): ?>
        <p style="margin-top:1rem;color:var(--mgr-text-muted);font-size:0.85rem;">모든 버전이 이미 설치되어 있습니다. 안전을 위해 개별 버전 재실행만 지원합니다.</p>
    <?php else: ?>
        <p style="margin-top:1rem;color:var(--mgr-text-muted);font-size:0.85rem;">미설치 항목이 있습니다. 아래 버튼으로 누락된 버전을 한 번에 설치할 수 있습니다. <code>CREATE TABLE IF NOT EXISTS</code> / <code>ADD COLUMN</code>만 실행하며 기존 데이터를 삭제하지 않습니다. 운영 DB에 적용하기 전에는 반드시 백업을 먼저 받아 두세요.</p>
    <?php endif; ?>

    <form method="post" action="./settings_install_action.php" style="margin-top:0.75rem;">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($install_admin_token, ENT_QUOTES); ?>">
        <input type="hidden" name="mode" value="run_all">
        <button type="submit" class="btn_submit btn" onclick="return confirm('블로그 자동화 테이블 전체(V1~V<?php echo count($install_versions); ?>)를 설치하시겠습니까?');">전체 설치(누락분 포함 V1~V<?php echo count($install_versions); ?> 전체 재실행)</button>
    </form>

    <?php elseif ($tab === 'ai'): ?>
    <!-- AI 설정: adm/blog/ai_provider_list.php와 같은 테이블을 조회, 등록/수정/연결테스트는 기존 ai_provider_form.php를 그대로 재사용 -->
    <p style="margin:0 0 1rem;color:var(--mgr-text-muted);font-size:0.9rem;">AI 콘텐츠 생성에 사용할 공급자 설정입니다. API 키는 저장 후 화면에 다시 표시되지 않으며, 마스킹된 값만 노출됩니다. 활성 공급자가 없거나 키가 없으면 템플릿 생성기로 자동 전환됩니다.</p>
    <a href="./ai_provider_form.php" class="btn btn_01" style="margin-bottom:1rem;display:inline-block;">+ 공급자 등록</a>

    <div style="overflow-x:auto;">
        <table style="width:100%;border-collapse:collapse;font-size:0.9rem;">
            <thead>
                <tr style="border-bottom:2px solid var(--mgr-border);text-align:left;">
                    <th style="padding:0.5rem;">코드</th>
                    <th style="padding:0.5rem;">공급자명</th>
                    <th style="padding:0.5rem;">기본 모델</th>
                    <th style="padding:0.5rem;">API 키</th>
                    <th style="padding:0.5rem;">활성</th>
                    <th style="padding:0.5rem;">관리</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($ai_providers) > 0): ?>
                    <?php foreach ($ai_providers as $ap): ?>
                    <tr style="border-bottom:1px solid var(--mgr-border);">
                        <td style="padding:0.5rem;"><code><?php echo get_text($ap['provider_code']); ?></code></td>
                        <td style="padding:0.5rem;"><a href="./ai_provider_form.php?id=<?php echo (int)$ap['id']; ?>"><strong><?php echo get_text($ap['display_name']); ?></strong></a></td>
                        <td style="padding:0.5rem;"><?php echo get_text($ap['default_model']); ?></td>
                        <td style="padding:0.5rem;"><?php echo $ap['masked_hint'] ? get_text($ap['masked_hint']) : '<span style="color:var(--mgr-text-muted);">미설정</span>'; ?></td>
                        <td style="padding:0.5rem;"><?php echo $ap['is_active'] === 'Y' ? '<span style="color:#10b981;">사용</span>' : '<span style="color:var(--mgr-text-muted);">중지</span>'; ?></td>
                        <td style="padding:0.5rem;"><a href="./ai_provider_form.php?id=<?php echo (int)$ap['id']; ?>" class="btn btn_02">수정</a></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="padding:1rem;text-align:center;color:var(--mgr-text-muted);">등록된 AI 공급자가 없습니다. 먼저 공급자를 등록해 주세요. 등록 전까지는 모든 AI 생성 요청이 템플릿 폴백으로 동작합니다.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
    <!-- 본문 영역 뼈대 -->
    <div style="padding: 2rem 0; text-align: center; color: var(--mgr-text-muted);">
        <p><strong><?php echo $tabs[$tab]; ?></strong> 탭 콘텐츠가 이곳에 통합될 예정입니다. (Phase 4)</p>
    </div>
    <?php endif; ?>
</div>
<?php include_once(__DIR__ . '/../layout/footer.php'); ?>
