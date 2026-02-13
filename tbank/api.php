<?php
if (!defined('ABSPATH')) exit;

class TBank_API {

    private string $terminal;
    private string $secret;
    private string $base_url = 'https://securepay.tinkoff.ru/v2/';
    private bool $debug;

    public function __construct(string $terminal, string $secret) {
        $this->terminal = $terminal;
        $this->secret   = $secret;
        $this->debug    = defined('WP_DEBUG') && WP_DEBUG;
    }


    public function init_payment(WC_Order $order, array $settings = []) {

        if (empty($settings)) {
            $settings = get_option('woocommerce_modern_tbank_settings', []);
        }

        $amount = (int) round($order->get_total() * 100);

        if ($amount <= 0) {
            return new WP_Error(
                'tbank_invalid_amount',
                'Order amount must be greater than zero.'
            );
        }

        $data = [
            'TerminalKey' => $this->terminal,
            'Amount'      => $amount,
            'OrderId'     => (string) $order->get_id(),
            'Description' => $this->build_description($order),
        ];

        if (!empty($settings['notification_url'])) {
            $data['NotificationURL'] = (string) $settings['notification_url'];
        }

        if (!empty($settings['success_url'])) {
            $data['SuccessURL'] = (string) $settings['success_url'];
        }

        if (!empty($settings['fail_url'])) {
            $data['FailURL'] = (string) $settings['fail_url'];
        }

        if (($settings['payment_form_language'] ?? 'ru') === 'en') {
            $data['Language'] = 'en';
        }

        $data['DATA'] = [
            'Email'           => $order->get_billing_email(),
            'Phone'           => $order->get_billing_phone(),
            'Connection_type' => 'wp-woocommerce-modern-' . MODERN_TBANK_VERSION,
        ];

        // Subscriptions
        if (
            class_exists('WC_Subscriptions') &&
            function_exists('wcs_order_contains_subscription') &&
            wcs_order_contains_subscription($order)
        ) {
            $data['Recurrent']   = 'Y';
            $data['CustomerKey'] = 'user_' . (string) $order->get_user_id();
        }

        if (!empty($settings['check_data_tax']) && $settings['check_data_tax'] === 'yes') {
            $data['Receipt'] = TBank_Receipt::build($order, $settings);
            if ($this->debug) {
                TBank_Helper::log('TBank Init Receipt: ' . wp_json_encode($data['Receipt']));
            }
        }

        $data['Token'] = TBank_Helper::generate_token($data, $this->secret);

        return $this->request('Init', $data);
    }

    public function get_state(string $payment_id) {

        if (empty($payment_id)) {
            return new WP_Error(
                'tbank_missing_payment_id',
                'PaymentId is required.'
            );
        }

        $data = [
            'TerminalKey' => $this->terminal,
            'PaymentId'   => $payment_id,
        ];

        $data['Token'] = TBank_Helper::generate_token($data, $this->secret);

        return $this->request('GetState', $data);
    }

    public function refund(string $payment_id, int $amount, ?array $receipt = null) {

        if ($amount <= 0) {
            return new WP_Error(
                'tbank_invalid_amount',
                'Refund amount must be greater than zero.'
            );
        }

        $data = [
            'TerminalKey' => $this->terminal,
            'PaymentId'   => $payment_id,
            'Amount'      => $amount,
        ];

        if (!empty($receipt)) {
            $data['Receipt'] = $receipt;
        }

        $data['Token'] = TBank_Helper::generate_token($data, $this->secret);

        return $this->request('Cancel', $data);
    }

    public function charge(string $rebill_id, int $amount, string $payment_id) {

        $data = [
            'TerminalKey' => $this->terminal,
            'RebillId'    => $rebill_id,
            'PaymentId'   => $payment_id,
            'Amount'      => $amount,
        ];

        $data['Token'] = TBank_Helper::generate_token($data, $this->secret);

        return $this->request('Charge', $data);
    }

    private function request(string $endpoint, array $data) {

        if ($this->debug) {
            TBank_Helper::log(
                'TBank Request [' . $endpoint . ']: ' . wp_json_encode($data)
            );
        }

        $response = wp_remote_post(
            $this->base_url . $endpoint,
            [
                'body'        => wp_json_encode($data),
                'headers'     => ['Content-Type' => 'application/json'],
                'timeout'     => 20,
                'data_format' => 'body',
            ]
        );

        if (is_wp_error($response)) {
            return new WP_Error(
                'tbank_connection_error',
                $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code($response);

        if ($code !== 200) {
            return new WP_Error(
                'tbank_http_error',
                'HTTP status: ' . $code
            );
        }

        $body = wp_remote_retrieve_body($response);

        if ($this->debug) {
            TBank_Helper::log(
                'TBank Response [' . $endpoint . ']: ' . $body
            );
        }

        $json = json_decode($body);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error(
                'tbank_invalid_json',
                'Invalid JSON response'
            );
        }

        if (isset($json->Success) && $json->Success === false) {
            return new WP_Error(
                'tbank_api_error',
                $json->Message ?? 'Unknown T-Bank error'
            );
        }

        return $json;
    }

    private function build_description(WC_Order $order): string {
        $parts = [];

        foreach ($order->get_items('line_item') as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $name = (string) $item->get_name();
            $qty = (int) $item->get_quantity();
            $parts[] = $qty > 1 ? ($name . '×' . $qty) : $name;
        }

        $description = trim(implode(', ', $parts));
        if ($description === '') {
            return 'Order #' . $order->get_id();
        }

        if (mb_strlen($description) > 140) {
            $description = mb_substr($description, 0, 137) . '...';
        }

        return $description;
    }
}
