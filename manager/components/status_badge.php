<?php
if (!defined('_GNUBOARD_')) exit;

// tone: success|warning|danger|muted|ai|primary
function mgr_status_badge($text, $tone = 'muted')
{
    return '<span class="mgr-badge mgr-tone-' . $tone . '">' . get_text($text) . '</span>';
}
