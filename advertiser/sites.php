<?php
// /advertiser/sites.php
include_once('./_common.php');
adv_check_login();

$g5['title'] = '운영 사이트';
adv_head($g5['title']);

$adv_id = (int)$_SESSION['ss_adv_advertiser_id'];
$tbl_sites = bp_table('sites');

$sql = " select * from {$tbl_sites} where advertiser_id = '{$adv_id}' order by id desc ";
$res = sql_query($sql);
?>
<div class="adv_section">
    <div class="adv_grid">
        <?php
        $has_data = false;
        while ($r = sql_fetch_array($res)) {
            $has_data = true;
            $status_txt = $r['status'] === 'Y' ? '운영 중' : '중지';
            $status_class = $r['status'] === 'Y' ? 'good' : 'bad';
            echo '<div class="adv_grid_card">';
            echo '  <div class="card_header">';
            echo '    <strong>'.get_text($r['name']).'</strong>';
            echo '    <span class="badge badge_'.$status_class.'">'.$status_txt.'</span>';
            echo '  </div>';
            echo '  <div class="card_body">';
            echo '    <p>플랫폼: '.get_text($r['platform']).'</p>';
            if ($r['base_url']) {
                echo '    <p>URL: <a href="'.$r['base_url'].'" target="_blank">'.$r['base_url'].'</a></p>';
            }
            echo '  </div>';
            echo '</div>';
        }
        if (!$has_data) echo '<div class="adv_empty">등록된 운영 사이트가 없습니다.</div>';
        ?>
    </div>
</div>
<?php adv_tail(); ?>
