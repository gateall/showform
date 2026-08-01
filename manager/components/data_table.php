<?php
if (!defined('_GNUBOARD_')) exit;

// $columns = array(array('key'=>'title','label'=>'제목'), ...)
// 열 정의에 'raw' => true를 추가하면 그 열의 label을 이스케이프하지 않고 그대로 출력한다
// (체크박스 열의 헤더에 "전체 선택" <input type="checkbox">를 넣는 용도 - 라벨은 항상
// 개발자가 하드코딩하는 값이라 신뢰할 수 있고, 기본값은 false라 기존 호출부는 전부
// 그대로 이스케이프된다).
// $rows = array(array('title'=>'...', ...), ...)  — 값에 이미 원하는 HTML을 넣어도 된다(escape는 호출부 책임).
function mgr_data_table($columns, $rows, $opts = array())
{
    $empty_title = isset($opts['empty_title']) ? $opts['empty_title'] : '데이터가 없습니다';
    $empty_desc = isset($opts['empty_desc']) ? $opts['empty_desc'] : '';

    if (empty($rows)) {
        require_once __DIR__ . '/empty_state.php';
        return '<div class="mgr-card mgr-table-card">' . mgr_empty_state($empty_title, $empty_desc) . '</div>';
    }

    ob_start();
?>
<div class="mgr-card mgr-table-card">
    <div class="mgr-table-scroll">
        <table class="mgr-table">
            <thead>
                <tr>
                    <?php foreach ($columns as $col): ?>
                    <th><?php echo !empty($col['raw']) ? $col['label'] : get_text($col['label']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $row): ?>
                <tr>
                    <?php foreach ($columns as $col): ?>
                    <td><?php echo isset($row[$col['key']]) ? $row[$col['key']] : ''; ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
    return ob_get_clean();
}
