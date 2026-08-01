<?php
include_once('../_common.php');

if ($is_admin !== 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

// 이 화면 자체가 "여기서 마스터 키를 만든다"는 사실조차 캐시/색인되지 않게 한다.
header('X-Robots-Tag: noindex, nofollow', true);
header('Cache-Control: no-store, no-cache, must-revalidate', true);
header('Pragma: no-cache', true);

$page_title = '운영 암호화 키 생성기';

// 생성 요청 하나마다 새로 발급하는 1회성 토큰이 아니라, 이 화면에 머무는 동안 여러 번
// 눌러 재생성해볼 수 있어야 하므로 세션 재사용 가능한 CSRF 토큰을 쓴다(그누보드 공용
// admin_token과는 별개 - 이 화면 전용).
$session_token_key = 'sys_crypto_key_gen_token';
$gen_token = get_session($session_token_key);
if (!$gen_token) {
    $gen_token = md5(uniqid((string) mt_rand(), true));
    set_session($session_token_key, $gen_token);
}

// 실제 키 값은 절대 확인하지 않는다 - "설정돼 있고 형식이 맞는지"만 불리언으로 판단한다.
$bp_key_defined = defined('BP_CRYPTO_KEY') && BP_CRYPTO_KEY !== '';
$bp_key_format_ok = $bp_key_defined && preg_match('/^[a-f0-9]{64}$/', BP_CRYPTO_KEY) === 1;

include_once(__DIR__ . '/../layout/header.php');
?>
<div class="mgr-card" style="padding:1.5rem;max-width:680px;">
    <h2 style="margin:0 0 0.5rem;font-size:1.25rem;">운영 암호화 키 생성기</h2>
    <p style="margin:0 0 1rem;color:var(--mgr-text-muted);font-size:0.9rem;line-height:1.6;">
        이 키는 API 키를 암호화하는 마스터 키(<code>BP_CRYPTO_KEY</code>)입니다. 생성 후 안전하게 보관하고 외부에 공유하지 마세요.<br>
        <strong>챗GPT·제미나이 등 개별 AI 공급자의 API 키는 여기서 만들지 않습니다.</strong> 그건 <a href="<?php echo SF_MANAGER_URL; ?>/blog/settings.php?tab=ai">설정 &gt; AI 설정</a>에서 등록·저장하세요. 이 도구는 최초 설정, 키 노출 사고, 서버 이전 같은 <strong>마스터 키 자체를 교체</strong>해야 하는 상황에만 씁니다.
    </p>

    <div style="padding:0.75rem 1rem;margin-bottom:1.25rem;border:1px solid var(--mgr-border);border-radius:8px;background:<?php echo $bp_key_format_ok ? '#f0fdf4' : '#fef2f2'; ?>;font-size:0.85rem;">
        BP_CRYPTO_KEY 상태:
        <strong>
        <?php if ($bp_key_format_ok) { ?>
            설정됨 (키 길이: 정상)
        <?php } elseif ($bp_key_defined) { ?>
            설정됨 (키 길이: 비정상 - 64자리 16진수 형식이 아닙니다)
        <?php } else { ?>
            미설정
        <?php } ?>
        </strong>
    </div>

    <button type="button" class="btn_submit btn" id="btn_generate_key">새 64자리 키 생성</button>

    <div id="key_result_wrap" style="margin-top:1.25rem;">
        <label style="font-size:0.85rem;color:var(--mgr-text-muted);display:block;margin-bottom:4px;">생성된 키</label>
        <div style="display:flex;gap:6px;align-items:center;flex-wrap:wrap;">
            <code id="key_display" style="flex:1;min-width:280px;padding:10px 12px;background:#f5f5f5;border:1px solid #ddd;border-radius:4px;letter-spacing:1px;word-break:break-all;">••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••</code>
            <button type="button" class="btn btn_02" id="btn_toggle_view" disabled>보기</button>
            <button type="button" class="btn btn_02" id="btn_copy_key" disabled>복사</button>
            <button type="button" class="btn btn_01" id="btn_clear_key" disabled>화면에서 지우기</button>
        </div>
        <p id="key_status_msg" style="margin-top:0.5rem;font-size:0.8rem;color:var(--mgr-text-muted);"></p>
    </div>

    <div style="margin-top:2rem;padding:1rem;border:1px solid var(--mgr-border);border-radius:8px;font-size:0.85rem;line-height:1.7;background:#fafafa;">
        <p style="margin:0 0 0.5rem;font-weight:bold;">적용 방법</p>
        <pre style="margin:0 0 0.75rem;padding:0.75rem;background:#1e293b;color:#e2e8f0;border-radius:6px;overflow-x:auto;">define('BP_CRYPTO_KEY', '복사한_64자리_키');</pre>
        <ol style="margin:0;padding-left:1.25rem;">
            <li>운영 서버 <code>data/dbconfig.php</code>를 백업합니다.</li>
            <li>기존 <code>BP_CRYPTO_KEY</code>가 있으면 그 값만 교체합니다(파일 전체 덮어쓰기 금지).</li>
            <li>같은 상수를 두 번 정의하지 않습니다.</li>
            <li><code>G5_TOKEN_ENCRYPTION_KEY</code>는 수정하지 않습니다.</li>
            <li>파일 저장 후 기존 AI API 키를 관리자 화면에서 다시 저장합니다.</li>
            <li>정상 복호화와 연결 테스트를 확인합니다.</li>
            <li>이후 일반 API 키 추가·수정 때는 이 키를 바꾸지 않습니다.</li>
        </ol>
    </div>
</div>

<script>
(function() {
    // 이 변수 하나에만 존재한다 - DB/세션/파일/로그/data-* 속성/URL/로컬스토리지 어디에도 저장하지 않는다.
    var currentKey = '';
    var remaskTimer = null;
    var GEN_TOKEN = <?php echo json_encode($gen_token); ?>;
    var MASK = '••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••••';
    var REMASK_TIMEOUT_MS = 20000;

    var btnGenerate = document.getElementById('btn_generate_key');
    var display = document.getElementById('key_display');
    var btnToggle = document.getElementById('btn_toggle_view');
    var btnCopy = document.getElementById('btn_copy_key');
    var btnClear = document.getElementById('btn_clear_key');
    var statusMsg = document.getElementById('key_status_msg');

    function setStatus(msg) {
        statusMsg.textContent = msg;
    }

    function remask() {
        if (remaskTimer) { clearTimeout(remaskTimer); remaskTimer = null; }
        display.textContent = MASK;
        btnToggle.textContent = '보기';
        btnToggle.dataset.revealed = '0';
    }

    btnGenerate.addEventListener('click', function() {
        btnGenerate.disabled = true;
        var origText = btnGenerate.textContent;
        btnGenerate.textContent = '생성 중...';

        var params = new URLSearchParams();
        params.set('token', GEN_TOKEN);

        fetch(<?php echo json_encode(SF_MANAGER_URL . '/system/crypto_key_generate.php'); ?>, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params.toString()
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            btnGenerate.disabled = false;
            btnGenerate.textContent = origText;
            if (data.ok) {
                currentKey = data.key;
                remask();
                btnToggle.disabled = false;
                btnCopy.disabled = false;
                btnClear.disabled = false;
                setStatus('새 키가 생성되었습니다. "보기"를 눌러 확인하거나 "복사"로 바로 복사하세요.');
            } else {
                var msg = data.error || '키 생성에 실패했습니다.';
                if (window.mgrToast) { window.mgrToast(msg, 'danger'); } else { alert(msg); }
            }
        })
        .catch(function() {
            btnGenerate.disabled = false;
            btnGenerate.textContent = origText;
            var msg = '서버와 통신 중 문제가 발생했습니다.';
            if (window.mgrToast) { window.mgrToast(msg, 'danger'); } else { alert(msg); }
        });
    });

    btnToggle.addEventListener('click', function() {
        if (!currentKey) return;
        if (btnToggle.dataset.revealed === '1') {
            remask();
            return;
        }
        display.textContent = currentKey;
        btnToggle.textContent = '숨기기';
        btnToggle.dataset.revealed = '1';
        if (remaskTimer) clearTimeout(remaskTimer);
        remaskTimer = setTimeout(remask, REMASK_TIMEOUT_MS);
    });

    btnCopy.addEventListener('click', function() {
        if (!currentKey) return;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(currentKey).then(function() {
                setStatus('암호화 키가 복사되었습니다. 운영 서버의 data/dbconfig.php에 한 번만 적용해 주세요. 보안을 위해 적용 후 다른 내용을 복사하여 클립보드의 키를 덮어쓰는 것을 권장합니다.');
            }).catch(function() {
                setStatus('자동 복사에 실패했습니다. "보기"를 눌러 직접 선택해 복사해 주세요.');
            });
        } else {
            setStatus('이 브라우저는 자동 복사를 지원하지 않습니다. "보기"를 눌러 직접 선택해 복사해 주세요.');
        }
    });

    btnClear.addEventListener('click', function() {
        currentKey = '';
        remask();
        btnToggle.disabled = true;
        btnCopy.disabled = true;
        btnClear.disabled = true;
        setStatus('화면에서 키를 제거했습니다. 이미 복사한 클립보드나 data/dbconfig.php의 값은 삭제되지 않습니다.');
    });

    // 페이지를 떠나면(새로고침 포함) 이 변수는 자연히 사라진다 - 명시적으로도 한 번 더 비운다.
    window.addEventListener('pagehide', function() {
        currentKey = '';
    });
})();
</script>

<?php include_once(__DIR__ . '/../layout/footer.php'); ?>
