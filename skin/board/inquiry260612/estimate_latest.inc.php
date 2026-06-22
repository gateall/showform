<?php
if (!defined('_GNUBOARD_')) exit; // 개별 페이지 접근 불가

$bo_table_n = "inquiry";	
$table = $g5['write_prefix'] . $bo_table_n;
$limit = 10;

// 지시서에 명시된 쿼리 조건: wr_is_comment = 0 및 order by wr_id desc
$rs = sql_query(" select * from {$table} where wr_is_comment = 0 order by wr_id desc limit {$limit} ");
?>
<div class="estimate-latest-wrap">
<table style="width:100%; border:0px solid red; border-collapse:collapse;">
    <?php
    for ($i = 0; $i < $limit; $i++) {
        $row = sql_fetch_array($rs);
        if ($row) {
            $newday = 3;
            if (time() < strtotime($row['wr_datetime']) + (86400 * $newday)) {
                $new_icon = "<span style='font-size:10px;color:#fff;background:red;padding:1px 3px;border-radius:2px;vertical-align:middle;'>N</span>";
            } else {
                $new_icon = "";
            }

            // 진행상태: wr_8
            $status = isset($row['wr_8']) && trim((string)$row['wr_8']) !== '' ? trim((string)$row['wr_8']) : '견적접수';

            // 상태 색상 매핑
            $status_bg = '#01b6eb'; // 견적접수
            if ($status == '견적확인') {
                $status_bg = '#f39c12';
            } else if ($status == '견적제출') {
                $status_bg = '#073567';
            } else if ($status == '견적접수중') {
                $status_bg = '#10b981';
            }

            // 고객명: wr_name 사용
            $cust_name = isset($row['wr_name']) && trim((string)$row['wr_name']) !== '' ? trim((string)$row['wr_name']) : '';
            $masked_name = '';
            if ($cust_name) {
                $len = mb_strlen($cust_name, 'UTF-8');
                if ($len > 2) {
                    $masked_name = mb_substr($cust_name, 0, 1, 'UTF-8') . str_repeat('O', $len - 2) . mb_substr($cust_name, $len - 1, 1, 'UTF-8');
                } else if ($len == 2) {
                    $masked_name = mb_substr($cust_name, 0, 1, 'UTF-8') . 'O';
                } else {
                    $masked_name = $cust_name;
                }
            }
            ?>
            <tr style="height:40px; border-bottom:1px solid #f1f1f1;">
                <td style="width:30%; font-size:0.9em; font-weight:600; text-align:left;">
                    [<?php echo htmlspecialchars($row['wr_3']); ?>] <?php echo htmlspecialchars($masked_name); ?>
                </td>
                <td style="font-size:0.9em; text-align:left; padding-left:10px;">
                    <?php echo htmlspecialchars($row['wr_4']); ?> / <?php echo htmlspecialchars($row['wr_5']); ?> <?php echo $new_icon; ?>
                </td>
                <td style="width:20%; font-size:0.9em; text-align:center;">
                    <a href="/bbs/board.php?bo_table=<?php echo $bo_table_n; ?>&wr_id=<?php echo $row['wr_id']; ?>" style="text-decoration:none;">
                        <span class="bbok" style="background-color:<?php echo $status_bg; ?> !important; color:#fff !important; margin:0 auto;"><?php echo htmlspecialchars($status); ?></span>
                    </a>
                </td>
            </tr>
            <?php		
        }
    }
    ?>
</table>
</div>
