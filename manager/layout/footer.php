<?php
if (!defined('_GNUBOARD_')) exit;
require_once __DIR__ . '/../components/toast.php';
?>
        </main>
        <?php include __DIR__ . '/bottom_nav.php'; ?>
    </div>
    <?php echo mgr_toast_container(); ?>
</div>
<script src="<?php echo SF_MANAGER_URL ?>/assets/js/sidebar.js"></script>
<script src="<?php echo SF_MANAGER_URL ?>/assets/js/manager.js"></script>
</body>
</html>
