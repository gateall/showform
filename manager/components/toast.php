<?php
if (!defined('_GNUBOARD_')) exit;

// footer.php 직전에 한 번만 호출한다. 실제 토스트 표시는 manager.js의 mgrToast(message, tone)가 담당.
function mgr_toast_container()
{
    return '<div class="mgr-toast-container" id="mgrToastContainer" aria-live="polite" aria-atomic="true"></div>';
}
