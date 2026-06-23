<?php
$sub_menu = '900500';
include_once('./_common.php');

if ($is_admin != 'super') {
    alert('최고관리자만 접근 가능합니다.');
}

$g5['title'] = '프롬프트 템플릿 관리';

$table = G5_TABLE_PREFIX . 'landing_ai_prompt_template';

// 테이블이 아직 없을 수 있으므로 화면은 동작 가능한 골격만 제공한다.
$sql = "
CREATE TABLE IF NOT EXISTS `{$table}` (
  `prompt_idx` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `group_code` VARCHAR(50) NOT NULL DEFAULT '',
  `template_title` VARCHAR(150) NOT NULL DEFAULT '',
  `template_desc` TEXT NULL,
  `system_prompt` LONGTEXT NULL,
  `user_prompt` LONGTEXT NULL,
  `is_active` CHAR(1) NOT NULL DEFAULT 'Y',
  `reg_date` DATETIME NOT NULL,
  `upd_date` DATETIME NULL,
  PRIMARY KEY (`prompt_idx`),
  KEY `idx_group_code` (`group_code`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_reg_date` (`reg_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
";
@sql_query($sql, false);

$mode = isset($_GET['mode']) ? trim($_GET['mode']) : 'list';
$prompt_idx = isset($_GET['prompt_idx']) ? (int) $_GET['prompt_idx'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$group_code = isset($_GET['group_code']) ? trim($_GET['group_code']) : '';
$is_active = isset($_GET['is_active']) ? trim($_GET['is_active']) : '';

$group_list = array(
    'head_copy' => '헤드카피 추천',
    'body_text' => '상세 본문 작성',
    'popup' => '이벤트 팝업용',
    'image_tag' => '이미지 생성 태그',
);

include_once(G5_ADMIN_PATH . '/admin.head.php');

if ($mode === 'form') {
    $row = array(
        'prompt_idx' => 0,
        'group_code' => 'head_copy',
        'template_title' => '',
        'template_desc' => '',
        'system_prompt' => '',
        'user_prompt' => '',
        'is_active' => 'Y',
        'reg_date' => '',
        'upd_date' => '',
    );

    if ($prompt_idx > 0) {
        $row = sql_fetch(" select * from {$table} where prompt_idx = '{$prompt_idx}' ");
        if (!$row) {
            alert('템플릿 정보를 찾을 수 없습니다.', G5_ADMIN_URL . '/landing/ai_prompt.php');
        }
    }
    ?>

    <div class="local_desc01 local_desc">
        <p>프롬프트 템플릿을 등록하거나 수정합니다. 변수는 `{{PRODUCT_NAME}}`, `{{TARGET_AUDIENCE}}`, `{{LOCATION}}`, `{{BENEFIT}}` 형식으로 사용합니다.</p>
    </div>

    <form name="fpromptform" method="post" action="./ai_prompt_update.php" onsubmit="return fpromptform_submit(this);">
        <input type="hidden" name="prompt_idx" value="<?php echo (int) $row['prompt_idx']; ?>">
        <div class="tbl_frm01 tbl_wrap">
            <table>
                <caption>프롬프트 템플릿 등록/수정</caption>
                <tbody>
                    <tr>
                        <th scope="row"><label for="group_code">분류 그룹</label></th>
                        <td>
                            <select name="group_code" id="group_code" required>
                                <?php foreach ($group_list as $code => $label) { ?>
                                    <option value="<?php echo $code; ?>" <?php echo isset($row['group_code']) && $row['group_code'] === $code ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="template_title">템플릿명</label></th>
                        <td><input type="text" name="template_title" id="template_title" value="<?php echo get_text(isset($row['template_title']) ? $row['template_title'] : ''); ?>" class="frm_input" maxlength="150" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="template_desc">설명</label></th>
                        <td><textarea name="template_desc" id="template_desc" rows="3" style="width:100%;"><?php echo get_text(isset($row['template_desc']) ? $row['template_desc'] : ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="system_prompt">시스템 프롬프트</label></th>
                        <td><textarea name="system_prompt" id="system_prompt" rows="8" style="width:100%; font-family:monospace; line-height:1.6;" placeholder="AI 역할과 규칙을 입력하세요."><?php echo get_text(isset($row['system_prompt']) ? $row['system_prompt'] : ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="user_prompt">유저 프롬프트</label></th>
                        <td><textarea name="user_prompt" id="user_prompt" rows="14" style="width:100%; font-family:monospace; line-height:1.6;" placeholder="치환 변수를 포함한 실제 프롬프트를 입력하세요."><?php echo get_text(isset($row['user_prompt']) ? $row['user_prompt'] : ''); ?></textarea></td>
                    </tr>
                    <tr>
                        <th scope="row">치환 변수 안내</th>
                        <td>
                            <div style="background:#f8fafc; border:1px solid #e5e7eb; padding:12px; line-height:1.8;">
                                <div><code>{{PRODUCT_NAME}}</code> : 랜딩페이지 상품/서비스명</div>
                                <div><code>{{TARGET_AUDIENCE}}</code> : 주요 타깃 고객층</div>
                                <div><code>{{LOCATION}}</code> : 주요 지역/위치 정보</div>
                                <div><code>{{BENEFIT}}</code> : 핵심 소구점/혜택</div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">사용 여부</th>
                        <td>
                            <label><input type="radio" name="is_active" value="Y" <?php echo (isset($row['is_active']) && $row['is_active'] === 'Y') ? 'checked' : ''; ?>> 사용 중</label>
                            <label style="margin-left:15px;"><input type="radio" name="is_active" value="N" <?php echo (isset($row['is_active']) && $row['is_active'] === 'N') ? 'checked' : ''; ?>> 중지</label>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="btn_confirm01 btn_confirm">
            <input type="submit" value="저장" class="btn_submit btn">
            <a href="./ai_prompt.php" class="btn btn_02">목록</a>
        </div>
    </form>

    <script>
    function fpromptform_submit(f) {
        if (!f.template_title.value.trim()) {
            alert('템플릿명을 입력해 주세요.');
            f.template_title.focus();
            return false;
        }
        if (!f.system_prompt.value.trim()) {
            alert('시스템 프롬프트를 입력해 주세요.');
            f.system_prompt.focus();
            return false;
        }
        if (!f.user_prompt.value.trim()) {
            alert('유저 프롬프트를 입력해 주세요.');
            f.user_prompt.focus();
            return false;
        }
        return true;
    }
    </script>

    <?php
} else {
    $where = array('1=1');
    if ($search !== '') {
        $safe = sql_real_escape_string($search);
        $where[] = "(template_title like '%{$safe}%' or template_desc like '%{$safe}%' or system_prompt like '%{$safe}%' or user_prompt like '%{$safe}%')";
    }
    if (isset($group_list[$group_code])) {
        $where[] = "group_code = '" . sql_real_escape_string($group_code) . "'";
    }
    if ($is_active === 'Y' || $is_active === 'N') {
        $where[] = "is_active = '" . sql_real_escape_string($is_active) . "'";
    }
    $where_sql = ' where ' . implode(' and ', $where);

    $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
    if ($page < 1) {
        $page = 1;
    }
    $rows = isset($_GET['rows']) ? (int) $_GET['rows'] : 20;
    if (!in_array($rows, array(20, 50, 100), true)) {
        $rows = 20;
    }
    $total_row = sql_fetch(" select count(*) as cnt from {$table} {$where_sql} ");
    $total_count = isset($total_row['cnt']) ? (int) $total_row['cnt'] : 0;
    $total_page = $rows > 0 ? (int) ceil($total_count / $rows) : 1;
    if ($total_page < 1) {
        $total_page = 1;
    }
    if ($page > $total_page) {
        $page = $total_page;
    }
    $from_record = ($page - 1) * $rows;

    $sql = " select *, left(replace(replace(user_prompt, '\r', ' '), '\n', ' '), 80) as prompt_preview from {$table} {$where_sql} order by prompt_idx desc limit {$from_record}, {$rows} ";
    $result = sql_query($sql);
    ?>

    <div class="local_desc01 local_desc">
        <p>AI 프롬프트 템플릿을 등록하고 재사용하는 관리 화면입니다.</p>
    </div>

    <form id="fsearch" method="get" class="local_sch03 local_sch">
        <input type="hidden" name="mode" value="list">
        <select name="group_code">
            <option value="">전체 분류</option>
            <?php foreach ($group_list as $code => $label) { ?>
            <option value="<?php echo $code; ?>" <?php echo $group_code === $code ? 'selected' : ''; ?>><?php echo $label; ?></option>
            <?php } ?>
        </select>
        <select name="is_active">
            <option value="">전체 상태</option>
            <option value="Y" <?php echo $is_active === 'Y' ? 'selected' : ''; ?>>사용 중</option>
            <option value="N" <?php echo $is_active === 'N' ? 'selected' : ''; ?>>중지</option>
        </select>
        <input type="text" name="search" value="<?php echo get_text($search); ?>" class="frm_input" placeholder="템플릿명, 설명, 프롬프트 검색">
        <select name="rows">
            <option value="20" <?php echo $rows === 20 ? 'selected' : ''; ?>>20개씩</option>
            <option value="50" <?php echo $rows === 50 ? 'selected' : ''; ?>>50개씩</option>
            <option value="100" <?php echo $rows === 100 ? 'selected' : ''; ?>>100개씩</option>
        </select>
        <button type="submit" class="btn btn_submit">검색</button>
        <a href="./ai_prompt.php" class="btn btn_02">초기화</a>
        <a href="./ai_prompt.php?mode=form" class="btn btn_01">템플릿 등록</a>
    </form>

    <form id="fpromptlist" method="post" action="./ai_prompt_delete.php">
        <input type="hidden" name="page" value="<?php echo (int) $page; ?>">
        <input type="hidden" name="rows" value="<?php echo (int) $rows; ?>">
        <div class="tbl_head01 tbl_wrap">
            <table>
                <caption>AI 프롬프트 템플릿 목록</caption>
                <thead>
                    <tr>
                        <th scope="col"><input type="checkbox" id="chkall" onclick="if (this.checked) all_checked(true); else all_checked(false);"></th>
                        <th scope="col">번호</th>
                        <th scope="col">분류 그룹</th>
                        <th scope="col">템플릿명</th>
                        <th scope="col">치환 변수</th>
                        <th scope="col">상태</th>
                        <th scope="col">최종 수정일</th>
                        <th scope="col">관리</th>
                    </tr>
                </thead>
                <tbody>
                <?php if ($total_count > 0) { ?>
                    <?php for ($i = 0; $row = sql_fetch_array($result); $i++) { ?>
                        <?php
                        $group_label = isset($group_list[$row['group_code']]) ? $group_list[$row['group_code']] : $row['group_code'];
                        $prompt_preview = isset($row['prompt_preview']) ? trim($row['prompt_preview']) : '';
                        ?>
                        <tr>
                            <td class="td_chk"><input type="checkbox" name="chk[]" value="<?php echo (int) $row['prompt_idx']; ?>"></td>
                            <td><?php echo $total_count - (($page - 1) * $rows) - $i; ?></td>
                            <td><?php echo get_text($group_label); ?></td>
                            <td style="text-align:left;"><a href="./ai_prompt.php?mode=form&amp;prompt_idx=<?php echo (int) $row['prompt_idx']; ?>"><strong><?php echo get_text($row['template_title']); ?></strong></a><div style="font-size:12px;color:#666;margin-top:4px;"><?php echo get_text($row['template_desc']); ?></div></td>
                            <td style="text-align:left;font-size:12px;color:#444;"><?php echo $prompt_preview ? get_text($prompt_preview) : '-'; ?></td>
                            <td><a href="#" class="btn btn_03"><?php echo $row['is_active'] === 'Y' ? '사용 중' : '중지'; ?></a></td>
                            <td><?php echo get_text(isset($row['upd_date']) && $row['upd_date'] ? $row['upd_date'] : $row['reg_date']); ?></td>
                            <td>
                                <a href="./ai_prompt.php?mode=form&amp;prompt_idx=<?php echo (int) $row['prompt_idx']; ?>" class="btn btn_02">수정</a>
                            </td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="8" class="empty_table">등록된 프롬프트 템플릿이 없습니다.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </form>

    <?php echo get_paging(G5_IS_MOBILE ? $config['cf_mobile_pages'] : $config['cf_write_pages'], $page, $total_page, './ai_prompt.php?' . http_build_query(array('mode' => 'list', 'search' => $search, 'group_code' => $group_code, 'is_active' => $is_active, 'rows' => $rows))); ?>

    <script>
    function all_checked(sw) {
        var f = document.getElementById('fpromptlist');
        if (!f) return;
        for (var i = 0; i < f.elements.length; i++) {
            if (f.elements[i].name === 'chk[]') {
                f.elements[i].checked = sw;
            }
        }
    }
    </script>

    <?php
}

include_once(G5_ADMIN_PATH . '/admin.tail.php');