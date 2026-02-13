<?php
if (!defined('ABSPATH')) exit;

class TBank_Helper {
    public static function generate_token(array $data, string $secret): string {
        $data['Password'] = $secret;

        foreach ($data as $key => $value) {
            if (is_array($value) || is_object($value)) {
                unset($data[$key]);
            } elseif (is_bool($value)) {
                $data[$key] = $value ? 'true' : 'false';
            } else {
                $data[$key] = (string) $value;
            }
        }

        ksort($data);

        return hash('sha256', implode('', $data));
    }

    public static function validate_token(array $data, string $secret): bool {
        $original = $data['Token'] ?? '';

        unset($data['Token']);

        $generated = self::generate_token($data, $secret);

        return hash_equals($generated, (string)$original);
    }

    public static function log(string $message, string $level = 'info'): void {
        $logger = wc_get_logger();
        $logger->$level($message, ['source' => 'modern_tbank']);
    }

    public static function maybe_auto_complete_order(WC_Order $order, array $settings): void {
        if (($settings['auto_complete'] ?? 'no') !== 'yes') {
            return;
        }

        foreach ($order->get_items() as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $product = $item->get_product();
            if (!$product) {
                return;
            }

            if (!($product->is_virtual() && $product->is_downloadable())) {
                return;
            }
        }

        if ($order->is_paid() && $order->get_status() !== 'completed') {
            $order->update_status('completed');
        }
    }
}
