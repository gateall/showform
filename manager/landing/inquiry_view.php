<?php
// 이 화면은 landing_inquiry(단수)를 조회하고 tel/email/category/content 컬럼을 읽고
// 있었다. 실제 데이터가 쌓이는 곳은 landing_inquiries(복수)다 — 방문자 문의 저장
// (theme의 landing_inquiry_update.php), 목록, 상태변경, 삭제, 대시보드 통계가 모두
// 복수 테이블을 쓴다. 그래서 목록에서 상세를 눌러도 "존재하지 않는 문의 내역입니다"만
// 나왔다. 목록과 같은 테이블·같은 컬럼을 보도록 맞춘다.
include_once('./_common.php');

auth_check_menu($auth, '900200', 'r');

$g5['title'] = '문의 상세';

$inq_table = G5_TABLE_PREFIX . 'landing_inquiries';
$page_table = G5_TABLE_PREFIX . 'landing_pages';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) {
    alert('잘못된 접근입니다.', SF_MANAGER_URL . '/landing/inquiry_list.php');
}

$inquiry = sql_fetch(" select a.*, p.company_name, p.area_name
                       from {$inq_table} a
                       left join {$page_table} p on p.id = a.landing_id
                       where a.id = '{$id}' limit 1 ");
if (!$inquiry) {
    alert('존재하지 않는 문의입니다.', SF_MANAGER_URL . '/landing/inquiry_list.php');
}

$status_labels = array('new' => '접수', 'contacted' => '확인', 'completed' => '완료');
$status_class = 'inq-status-new';
if ($inquiry['status'] === 'contacted') {
    $status_class = 'inq-status-contacted';
} elseif ($inquiry['status'] === 'completed') {
    $status_class = 'inq-status-completed';
}

// tel: 에는 숫자와 +만 남긴다. 하이픈이 섞이면 일부 기기에서 다이얼러가 안 열린다.
$tel_raw = preg_replace('/[^0-9+]/', '', (string) $inquiry['phone']);

$page_title = '문의 상세';
include_once(__DIR__ . '/../layout/header.php');
?>
<style>
.inq-status-new       { background:#fef3c7; color:#92400e; }
.inq-status-contacted { background:#dbeafe; color:#1d4ed8; }
.inq-status-completed { background:#dcfce7; color:#166534; }

.inq-head { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; margin-bottom:1rem; }

/* 모바일 기본은 한 줄씩 쌓고, 768부터 라벨/값 두 칸으로 벌린다. */
.inq-def { display:grid; grid-template-columns:1fr; gap:0; }
.inq-def > div { display:grid; grid-template-columns:1fr; gap:.25rem; padding:.875rem 0; border-top:1px solid var(--mgr-border); }
.inq-def > div:first-child { border-top:0; }
.inq-def dt { margin:0; font-size:.8125rem; font-weight:700; color:var(--mgr-text-muted); }
.inq-def dd { margin:0; font-size:.9375rem; line-height:1.6; word-break:break-word; overflow-wrap:anywhere; }

.inq-actions { display:grid; grid-template-columns:1fr; gap:.5rem; margin-top:1.25rem; }
.inq-actions .mgr-btn, .inq-actions .inq-tel-btn { min-height:48px; justify-content:center; }

.inq-tel-btn { display:inline-flex; align-items:center; justify-content:center; gap:6px;
               padding:.5rem 1rem; font-weight:700; color:#fff; background:#0f766e;
               border:1px solid #0f766e; border-radius:8px; text-decoration:none; }

@media (min-width: 768px) {
    .inq-def > div { grid-template-columns:9rem 1fr; gap:1rem; align-items:start; }
    .inq-actions { grid-template-columns:repeat(auto-fit, minmax(9rem, auto)); justify-content:start; }
}
</style>

<div class="inq-head">
    <h2 style="margin:0;font-size:1.125rem;">문의 #<?php echo (int) $inquiry['id']; ?></h2>
    <span class="mgr-badge <?php echo $status_class; ?>">
        <?php echo isset($status_labels[$inquiry['status']]) ? $status_labels[$inquiry['status']] : get_text($inquiry['status']); ?>
    </span>
</div>

<div class="mgr-card" style="padding:1.25rem;">
    <dl class="inq-def">
        <div>
            <dt>랜딩페이지</dt>
            <dd>
                <?php if ((int) $inquiry['landing_id']) { ?>
                    <a href="/page/landing.php?id=<?php echo (int) $inquiry['landing_id']; ?>" target="_blank" rel="noopener">
                        <?php echo get_text($inquiry['company_name'] ? $inquiry['company_name'] : '랜딩 #' . (int) $inquiry['landing_id']); ?>
                    </a>
                    <?php if ($inquiry['area_name']) { ?>
                        <span style="color:var(--mgr-text-muted);">· <?php echo get_text($inquiry['area_name']); ?></span>
                    <?php } ?>
                <?php } else { ?>
                    <span style="color:var(--mgr-text-muted);">-</span>
                <?php } ?>
            </dd>
        </div>
        <div>
            <dt>이름</dt>
            <dd><?php echo get_text($inquiry['name']); ?></dd>
        </div>
        <div>
            <dt>연락처</dt>
            <dd>
                <?php if ($tel_raw !== '') { ?>
                    <a href="tel:<?php echo htmlspecialchars($tel_raw, ENT_QUOTES, 'UTF-8'); ?>" style="font-weight:700;color:#0f766e;text-decoration:none;">
                        <?php echo get_text($inquiry['phone']); ?>
                    </a>
                <?php } else { ?>
                    <span style="color:var(--mgr-text-muted);">-</span>
                <?php } ?>
            </dd>
        </div>
        <div>
            <dt>문의내용</dt>
            <dd><?php echo nl2br(get_text($inquiry['message'])); ?></dd>
        </div>
        <div>
            <dt>등록일</dt>
            <dd><?php echo get_text($inquiry['created_at']); ?></dd>
        </div>
    </dl>

    <div class="inq-actions">
        <?php if ($tel_raw !== '') { ?>
            <a class="inq-tel-btn" href="tel:<?php echo htmlspecialchars($tel_raw, ENT_QUOTES, 'UTF-8'); ?>">📞 전화하기</a>
        <?php } ?>
        <a class="mgr-btn" href="./inquiry_list.php">목록으로</a>
    </div>
</div>

<?php include_once(__DIR__ . '/../layout/footer.php'); ?>
