<?php
include_once('./_common.php');
if ($is_admin !== 'super') {
    alert('최고관리자만 접근할 수 있습니다.');
}
include_once(G5_ADMIN_PATH . '/blog/lib/blog_publisher.lib.php');

// 1. Run install to ensure v8 is applied
ob_start();
include(G5_ADMIN_PATH . '/blog/install.php');
$install_out = ob_get_clean();

// 2. Insert Dummy Site (PHP platform)
$site_table = bp_table('sites');
$adv_table = bp_table('advertisers');
$adv_id = sql_insert_id(); // if any
$adv = sql_fetch("SELECT id FROM {$adv_table} LIMIT 1");
if (!$adv) {
    sql_query("INSERT INTO {$adv_table} (name, status, created_at) VALUES ('Test Adv', 'Y', NOW())");
    $adv_id = sql_insert_id();
} else {
    $adv_id = $adv['id'];
}

sql_query("INSERT INTO {$site_table} (advertiser_id, name, platform, base_url, status, created_at) 
           VALUES ('{$adv_id}', 'PHP Mock Site', 'php', 'http://showform.kr', 'Y', NOW())");
$site_id = sql_insert_id();

// 3. Add API Secret
$cred_table = bp_table('site_credentials');
$key = 'mock_key_123';
$secret = 'mock_secret_abc';
$enc = bp_encrypt_secret($secret);
sql_query("INSERT INTO {$cred_table} (site_id, cred_type, cred_username, cred_value_enc, created_at)
           VALUES ('{$site_id}', 'php_api_key', '{$key}', '{$enc}', NOW())");

// 4. Create dummy project & post & target
$proj_table = bp_table('content_projects');
sql_query("INSERT INTO {$proj_table} (advertiser_id, topic, status, created_at) VALUES ('{$adv_id}', 'Test Topic', 'Y', NOW())");
$proj_id = sql_insert_id();

$post_table = bp_table('posts');
sql_query("INSERT INTO {$post_table} (project_id, title, body, status, created_at) VALUES ('{$proj_id}', 'Test PHP Post', '<p>Hello PHP API</p>', 'approved', NOW())");
$post_id = sql_insert_id();

$target_table = bp_table('post_targets');
sql_query("INSERT INTO {$target_table} (post_id, site_id, status, created_at) VALUES ('{$post_id}', '{$site_id}', 'ready', NOW())");
$target_id = sql_insert_id();

// 5. Create Publish Job
$job_table = bp_table('publish_jobs');
sql_query("INSERT INTO {$job_table} (post_target_id, priority, status, created_at) VALUES ('{$target_id}', 1, 'pending', NOW())");

echo "Test Data Created. Site ID: {$site_id}, Job Target ID: {$target_id}\n";
