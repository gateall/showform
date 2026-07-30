<?php
define('G5_IS_ADMIN', true);
include_once('../../common.php');
include_once(G5_ADMIN_PATH.'/admin.lib.php');
if (!defined('SF_MANAGER_URL')) {
    define('SF_MANAGER_URL', G5_URL . '/manager');
}
