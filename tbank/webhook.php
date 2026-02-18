<?php
if (!defined('ABSPATH')) exit;

class TBank_Webhook {

    public static function handle() {

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            TBank_Helper::log('Webhook rejected: method not allowed', 'warning');
            status_header(405);
            exit;
        }

        $raw_body = file_get_contents('php://input');

        if (empty($raw_body)) {
            TBank_Helper::log('Webhook rejected: empty body', 'warning');
            status_header(400);
            exit;
        }

        $data = json_decode($raw_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            TBank_Helper::log('Webhook rejected: invalid JSON', 'warning');
            status_header(400);
            exit;
        }

        if (empty($data['OrderId']) || empty($data['PaymentId']) || empty($data['Status'])) {
            TBank_Helper::log('Webhook rejected: missing fields ' . wp_json_encode($data), 'warning');
            status_header(400);
            exit;
        }

        $order = wc_get_order((int) $data['OrderId']);

        if (!$order) {
            TBank_Helper::log('Webhook rejected: order not found ' . (string) $data['OrderId'], 'warning');
            status_header(404);
            exit;
        }

        $settings = get_option('woocommerce_modern_tbank_settings', []);
        $debug = ($settings['debug'] ?? 'no') === 'yes';

        $secret = $settings['production_secret'] ?? '';
        $terminal = $settings['production_terminal'] ?? '';

        if (empty($secret) || empty($terminal)) {
            TBank_Helper::log('Webhook rejected: missing terminal/secret', 'error');
            status_header(500);
            exit;
        }

        if (!TBank_Helper::validate_token($data, $secret)) {
            TBank_Helper::log('Webhook rejected: invalid token', 'warning');
            status_header(403);
            exit;
        }

        $expected_amount = (int) round($order->get_total() * 100);

        if ((int) ($data['Amount'] ?? 0) !== $expected_amount) {
            TBank_Helper::log(
                'Webhook rejected: amount mismatch. Expected ' . $expected_amount .
                ', got ' . (string) ($data['Amount'] ?? 0),
                'warning'
            );
            status_header(400);
            exit;
        }

        $existing_payment_id = (string) $order->get_transaction_id();
        $incoming_payment_id = (string) ($data['PaymentId'] ?? '');
        if ($existing_payment_id !== '' && $existing_payment_id !== $incoming_payment_id) {
            TBank_Helper::log(
                'Webhook rejected: payment id mismatch. Existing ' . $existing_payment_id .
                ', got ' . $incoming_payment_id,
                'warning'
            );
            status_header(409);
            exit;
        }

        $status = strtoupper($data['Status']);

        if ($debug) {
            TBank_Helper::log('Webhook payload: ' . wp_json_encode($data));
        }
        
        // Idempotency
        if (
            in_array($status, ['CONFIRMED', 'AUTHORIZED'], true) &&
            ($order->is_paid() || $order->get_meta('_tbank_confirmed'))
        ) {
            if ($debug) {
                TBank_Helper::log('Webhook idempotent hit: ' . $status);
            }
            status_header(200);
            exit;
        }

        switch ($status) {

            case 'CONFIRMED':

                if (!$order->is_paid()) {
                    $order->set_transaction_id($data['PaymentId']);
                    $order->payment_complete($data['PaymentId']);
                    $order->add_order_note(
                        'Оплата подтверждена Т-Банком. PaymentId: ' . $data['PaymentId']
                    );
                    $order->save();
                    TBank_Helper::maybe_auto_complete_order($order, $settings);

                    if (
                        !empty($data['RebillId']) &&
                        function_exists('wcs_get_subscriptions_for_order')
                    ) {
                        $subscriptions = wcs_get_subscriptions_for_order(
                            $order->get_id(),
                            ['order_type' => 'any']
                        );

                        if (!empty($subscriptions)) {
                            foreach ($subscriptions as $subscription) {
                                $subscription->update_meta_data(
                                    '_tbank_rebill_id',
                                    $data['RebillId']
                                );
                                $subscription->save();
                            }
                        }
                    }
                }

                break;

            case 'AUTHORIZED':

                if ($order->get_meta('_tbank_confirmed')) {
                    break;
                }

                break;

            case 'AUTH_FAIL':
            case 'REJECTED':
            case 'DEADLINE_EXPIRED':

                $order->update_status(
                    'failed',
                    'Платеж Т-Банка неуспешен (' . $status . ')'
                );
                $order->delete_meta_data('_tbank_payment_id');
                $order->delete_meta_data('_tbank_payment_url');
                $order->save();
                break;

            case 'CANCELED':
            case 'REVERSED':

                $order->update_status(
                    'cancelled',
                    'Платеж Т-Банка отменен (' . $status . ')'
                );
                $order->save();
                break;

            case 'REFUNDED':

                if ($order->get_status() !== 'refunded') {
                    $order->update_status(
                        'refunded',
                        'Возврат подтвержден Т-Банком.'
                    );
                    $order->save();
                }

                break;


            default:
                TBank_Helper::log('Unknown TBank status: ' . $status);
        }

        if ($debug) {
            TBank_Helper::log('Webhook processed: ' . $status);
        }
        status_header(200);
        exit;
    }
}
