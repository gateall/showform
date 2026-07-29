<?php
include_once('./_common.php');

$sub_menu = '360400';
auth_check_menu($auth, $sub_menu, 'r');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$g5['title'] = 'AI 공급자 관리';

$table = bp_table('ai_providers');
$result = sql_query(" select * from {$table} order by id asc ");

include_once(G5_ADMIN_PATH . '/admin.head.php');
?>
<div class="local_desc01 local_desc">
    <p>AI 콘텐츠 생성에 사용할 공급자 설정입니다. API 키는 저장 후 화면에 다시 표시되지 않으며, 마스킹된 값만 노출됩니다. 활성 공급자가 없거나 키가 없으면 템플릿 생성기로 자동 전환됩니다.</p>
</div>

<a href="./ai_provider_form.php" class="btn btn_01">공급자 등록</a>

<?php
$list = array();
if ($result && sql_num_rows($result) > 0) {
    while ($row = sql_fetch_array($result)) {
        $list[] = $row;
    }
}
?>

<!-- 모바일: 카드 뷰 -->
<div class="bp-project-cards" id="mobile_card_view" style="margin-top:15px;">
    <?php if (count($list) > 0) { ?>
        <?php foreach ($list as $row) { ?>
        <div class="bp-project-card">
            <div class="bp-project-card-head">
                <a href="./ai_provider_form.php?id=<?php echo (int) $row['id']; ?>" class="bp-project-title">
                    <?php echo get_text($row['display_name']); ?>
                </a>
                <span class="bp-status-badge bp-status-badge-<?php echo $row['is_active'] === 'Y' ? 'published' : 'failed'; ?>">
                    <?php echo $row['is_active'] === 'Y' ? '사용' : '중지'; ?>
                </span>
            </div>
            <div class="bp-project-meta">
                <span>번호: <?php echo (int) $row['id']; ?></span>
                <span>코드: <?php echo get_text($row['provider_code']); ?></span>
                <span>기본 모델: <?php echo get_text($row['default_model']); ?></span>
                <span>API 키: <?php echo $row['masked_hint'] ? get_text($row['masked_hint']) : '미설정'; ?></span>
                <span>수정일: <?php echo $row['updated_at'] ? get_text($row['updated_at']) : get_text($row['created_at']); ?></span>
            </div>
            <div class="bp-project-actions">
                <a href="./ai_provider_form.php?id=<?php echo (int) $row['id']; ?>" class="btn btn_02">수정</a>
            </div>
        </div>
        <?php } ?>
    <?php } else { ?>
        <div class="bp-empty">등록된 AI 공급자가 없습니다. 먼저 공급자를 등록해 주세요.</div>
    <?php } ?>
</div>

<!-- PC: 테이블 뷰 -->
<div class="tbl_head01 tbl_wrap" id="pc_table_view" style="margin-top:10px;">
    <table>
        <caption>AI 공급자 목록</caption>
        <thead>
            <tr>
                <th scope="col">번호</th>
                <th scope="col">코드</th>
                <th scope="col">공급자명</th>
                <th scope="col">기본 모델</th>
                <th scope="col">API 키</th>
                <th scope="col">활성 상태</th>
                <th scope="col">수정일</th>
                <th scope="col">관리</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($list) > 0) { ?>
                <?php foreach ($list as $row) { ?>
                    <tr>
                        <td><?php echo (int)$row['id']; ?></td>
                        <td><code><?php echo get_text($row['provider_code']); ?></code></td>
                        <td style="text-align:left;"><a href="./ai_provider_form.php?id=<?php echo (int)$row['id']; ?>"><strong><?php echo get_text($row['display_name']); ?></strong></a></td>
                        <td><?php echo get_text($row['default_model']); ?></td>
                        <td><?php echo $row['masked_hint'] ? get_text($row['masked_hint']) : '<span style="color:#999;">미설정</span>'; ?></td>
                        <td><?php echo $row['is_active'] === 'Y' ? '사용' : '중지'; ?></td>
                        <td><?php echo $row['updated_at'] ? get_text($row['updated_at']) : get_text($row['created_at']); ?></td>
                        <td>
                            <a href="./ai_provider_form.php?id=<?php echo (int)$row['id']; ?>" class="btn btn_02">수정</a>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr><td colspan="8" class="empty_table">등록된 AI 공급자가 없습니다. 먼저 공급자를 등록해 주세요.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>

<?php include_once(G5_ADMIN_PATH . '/admin.tail.php');
