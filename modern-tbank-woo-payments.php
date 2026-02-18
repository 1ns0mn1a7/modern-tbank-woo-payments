<?php
/*
Plugin Name: Modern TBank Woo Payments
Author: 1n0mn1a7
Description: Современный платежный шлюз Т‑Банк для новых версий WooCommerce.
Version: 1.1.0
*/

if (!defined('ABSPATH')) exit;

define('MODERN_TBANK_VERSION', '1.0.0');
define('MODERN_TBANK_PATH', plugin_dir_path(__FILE__));
define('MODERN_TBANK_URL', plugin_dir_url(__FILE__));

add_action('woocommerce_loaded', function () {
    
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    $base = plugin_dir_path(__FILE__);

    require_once $base . 'tbank/helper.php';
    require_once $base . 'tbank/api.php';
    require_once $base . 'tbank/webhook.php';
    require_once $base . 'tbank/gateway.php';
    require_once $base . 'tbank/receipt.php';

    add_filter('woocommerce_payment_gateways', function ($methods) {
        $methods[] = 'WC_Gateway_Modern_TBank';
        return $methods;
    });
});

add_action('before_woocommerce_init', function() {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
            'custom_order_tables',
            __FILE__,
            true
        );
    }
});

add_action('admin_enqueue_scripts', function (string $hook) {
    if ($hook !== 'woocommerce_page_wc-settings') {
        return;
    }

    $section = isset($_GET['section']) ? sanitize_text_field($_GET['section']) : '';
    if ($section !== 'modern_tbank') {
        return;
    }

    wp_enqueue_style(
        'modern-tbank-admin',
        MODERN_TBANK_URL . 'tbank/assets/admin.css',
        [],
        MODERN_TBANK_VERSION
    );

    wp_enqueue_script(
        'modern-tbank-admin',
        MODERN_TBANK_URL . 'tbank/assets/admin.js',
        ['jquery'],
        MODERN_TBANK_VERSION,
        true
    );
});
