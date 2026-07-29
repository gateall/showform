<?php
// /advertiser/posts_published.php
include_once('./_common.php');
adv_check_login();

$g5['title'] = '발행 완료 글';
adv_head($g5['title']);

$adv_id = (int)$_SESSION['ss_adv_advertiser_id'];
$tbl_jobs = bp_table('publish_jobs');
$tbl_targets = bp_table('post_targets');
$tbl_posts = bp_table('posts');
$tbl_projects = bp_table('content_projects');
$tbl_sites = bp_table('sites');

$sql = " select j.completed_at, po.title, po.primary_keyword, s.name as site_name, t.published_url
         from {$tbl_jobs} j
         inner join {$tbl_targets} t on t.id = j.post_target_id
         inner join {$tbl_posts} po on po.id = t.post_id
         inner join {$tbl_projects} prj on prj.id = po.project_id
         left join {$tbl_sites} s on s.id = t.site_id
         where prj.advertiser_id = '{$adv_id}' and j.status = 'published'
         order by j.completed_at desc ";
$res = sql_query($sql);
?>
<div class="adv_section">
    <div class="adv_list">
        <?php
        $has_data = false;
        while ($r = sql_fetch_array($res)) {
            $has_data = true;
            echo '<div class="adv_list_item">';
            echo '  <div class="adv_list_meta">발행일시: <span>'.$r['completed_at'].'</span> | 사이트: <span>'.get_text($r['site_name']).'</span></div>';
            echo '  <div class="adv_list_title">'.get_text($r['title']).'</div>';
            echo '  <div class="adv_list_tags">키워드: '.get_text($r['primary_keyword']).'</div>';
            if ($r['published_url']) {
                echo '  <div class="adv_list_link"><a href="'.$r['published_url'].'" target="_blank">실제 게시물 보기 &rarr;</a></div>';
            }
            echo '</div>';
        }
        if (!$has_data) echo '<div class="adv_empty">발행 완료된 글이 없습니다.</div>';
        ?>
    </div>
</div>
<?php adv_tail(); ?>
