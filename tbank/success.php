<?php
if (!defined('ABSPATH')) {
    $wp_load = dirname(__DIR__, 4) . '/wp-load.php';
    if (!file_exists($wp_load)) {
        http_response_code(500);
        exit;
    }
    require_once $wp_load;
}

if (!class_exists('TBank_Webhook')) {
    require_once __DIR__ . '/helper.php';
    require_once __DIR__ . '/api.php';
    require_once __DIR__ . '/webhook.php';
}

TBank_Webhook::handle();
