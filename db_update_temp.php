<?php
include_once('./_common.php');

$queries = array(
    "ALTER TABLE landing_page ADD hero_title VARCHAR(255) NOT NULL DEFAULT ''",
    "ALTER TABLE landing_page ADD problem_1 VARCHAR(255) NOT NULL DEFAULT ''",
    "ALTER TABLE landing_page ADD problem_2 VARCHAR(255) NOT NULL DEFAULT ''",
    "ALTER TABLE landing_page ADD problem_3 VARCHAR(255) NOT NULL DEFAULT ''",
    "ALTER TABLE landing_page ADD service_1 VARCHAR(255) NOT NULL DEFAULT ''",
    "ALTER TABLE landing_page ADD service_2 VARCHAR(255) NOT NULL DEFAULT ''",
    "ALTER TABLE landing_page ADD service_3 VARCHAR(255) NOT NULL DEFAULT ''",
    "ALTER TABLE landing_page ADD service_4 VARCHAR(255) NOT NULL DEFAULT ''",
    "ALTER TABLE landing_page ADD phone VARCHAR(50) NOT NULL DEFAULT ''",
    "ALTER TABLE landing_page ADD kakao_link VARCHAR(255) NOT NULL DEFAULT ''",
    "ALTER TABLE landing_page ADD privacy_text TEXT NULL"
);

foreach ($queries as $sql) {
    @sql_query($sql, false);
}

echo "DB Update Complete";
?>
