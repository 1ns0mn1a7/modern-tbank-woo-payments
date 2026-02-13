<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

delete_transient('modern_tbank_cron_lock');
