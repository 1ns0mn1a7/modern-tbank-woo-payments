<?php
if (!defined('ABSPATH')) exit;

class TBank_Receipt {

    public static function build(WC_Order $order, array $settings): array {
        $payment_method = self::normalize_payment_method($settings['payment_method_ffd'] ?? '');
        $payment_object = self::normalize_payment_object($settings['payment_object_ffd'] ?? '');

        $items = [];
        $total_amount = (int) round($order->get_total() * 100);

        foreach ($order->get_items('line_item') as $item) {

            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            $quantity = (float) $item->get_quantity();
            [$price, $vat] = self::get_product_price_and_vat($product);

            $receipt_item = [
                'Name'          => mb_substr($product->get_name(), 0, 128),
                'Price'         => (int) round($price * 100),
                'Quantity'      => round($quantity, 2),
                'Amount'        => (int) round($price * $quantity * 100),
                'PaymentMethod' => $payment_method,
                'PaymentObject' => $payment_object,
                'Tax'           => $vat,
            ];

            if (($settings['ffd'] ?? '') === 'ffd12') {
                $receipt_item['MeasurementUnit'] = 'pc';
            }

            $items[] = $receipt_item;
        }

        if ((float)$order->get_shipping_total() > 0) {

            $shipping_total = (float)$order->get_shipping_total();
            $shipping_tax   = (float)$order->get_shipping_tax();

            $shipping_sum = $shipping_total + $shipping_tax;

            $shipping_vat = self::detect_shipping_tax($order);

            $shipping_item = [
                'Name'          => mb_substr($order->get_shipping_method(), 0, 128),
                'Price'         => (int) round($shipping_sum * 100),
                'Quantity'      => 1,
                'Amount'        => (int) round($shipping_sum * 100),
                'PaymentMethod' => $payment_method,
                'PaymentObject' => 'service',
                'Tax'           => $shipping_vat,
            ];

            if (($settings['ffd'] ?? '') === 'ffd12') {
                $shipping_item['MeasurementUnit'] = 'pc';
            }

            $items[] = $shipping_item;
        }

        $items = self::balance_amount($items, $total_amount);

        $receipt = [
            'EmailCompany' => mb_substr($settings['email_company'] ?? '', 0, 64) ?: null,
            'Email'        => $order->get_billing_email(),
            'Phone'        => $order->get_billing_phone(),
            'Taxation'     => $settings['taxation'] ?? 'osn',
            'Items'        => $items,
        ];

        if (($settings['ffd'] ?? '') === 'ffd12') {
            $receipt['FfdVersion'] = '1.2';
        }

        if (($settings['ffd'] ?? '') === 'ffd105') {
            $receipt['FfdVersion'] = '1.05';
        }

        return $receipt;
    }

    public static function build_refund(
        WC_Order $order,
        WC_Order_Refund $refund,
        array $settings,
        int $total_amount
    ): array {
        $payment_method = self::normalize_payment_method($settings['payment_method_ffd'] ?? '');
        $payment_object = self::normalize_payment_object($settings['payment_object_ffd'] ?? '');

        $items = [];

        foreach ($refund->get_items('line_item') as $item) {
            if (!$item instanceof WC_Order_Item_Product) {
                continue;
            }

            $product = $item->get_product();
            if (!$product) {
                continue;
            }

            $quantity   = abs((float) $item->get_quantity());
            $line_total = abs((float) $item->get_total());
            $line_tax   = abs((float) $item->get_total_tax());
            $price      = ($line_total + $line_tax) / max($quantity, 1);
            $vat        = self::detect_tax_from_item($item);

            $items[] = [
                'Name'          => mb_substr($product->get_name(), 0, 128),
                'Price'         => (int) round($price * 100),
                'Quantity'      => round($quantity, 2),
                'Amount'        => (int) round($price * $quantity * 100),
                'PaymentMethod' => $payment_method,
                'PaymentObject' => $payment_object,
                'Tax'           => $vat,
            ];
        }

        $shipping_total = abs((float) $refund->get_shipping_total());
        $shipping_tax   = abs((float) $refund->get_shipping_tax());
        if ($shipping_total + $shipping_tax > 0) {
            $shipping_sum = $shipping_total + $shipping_tax;
            $shipping_vat = self::detect_shipping_tax($order);

            $shipping_item = [
                'Name'          => mb_substr($order->get_shipping_method(), 0, 128),
                'Price'         => (int) round($shipping_sum * 100),
                'Quantity'      => 1,
                'Amount'        => (int) round($shipping_sum * 100),
                'PaymentMethod' => $payment_method,
                'PaymentObject' => 'service',
                'Tax'           => $shipping_vat,
            ];

            if (($settings['ffd'] ?? '') === 'ffd12') {
                $shipping_item['MeasurementUnit'] = 'pc';
            }

            $items[] = $shipping_item;
        }

        if (empty($items)) {
            $items[] = [
                'Name'          => 'Возврат',
                'Price'         => $total_amount,
                'Quantity'      => 1,
                'Amount'        => $total_amount,
                'PaymentMethod' => $payment_method,
                'PaymentObject' => 'payment',
                'Tax'           => 'none',
            ];
        }

        if (($settings['ffd'] ?? '') === 'ffd12') {
            foreach ($items as $index => $item) {
                if (empty($item['MeasurementUnit'])) {
                    $items[$index]['MeasurementUnit'] = 'pc';
                }
            }
        }

        $items = self::balance_amount($items, $total_amount);

        $receipt = [
            'EmailCompany' => mb_substr($settings['email_company'] ?? '', 0, 64) ?: null,
            'Email'        => $order->get_billing_email(),
            'Phone'        => $order->get_billing_phone(),
            'Taxation'     => $settings['taxation'] ?? 'osn',
            'Items'        => $items,
        ];

        if (($settings['ffd'] ?? '') === 'ffd12') {
            $receipt['FfdVersion'] = '1.2';
        }

        if (($settings['ffd'] ?? '') === 'ffd105') {
            $receipt['FfdVersion'] = '1.05';
        }

        return $receipt;
    }

    private static function detect_tax_from_item(WC_Order_Item_Product $item): string {

        $taxes = $item->get_taxes();

        if (empty($taxes['total'])) {
            return 'none';
        }

        foreach ($taxes['total'] as $rate_id => $amount) {

            $rate = WC_Tax::_get_tax_rate($rate_id);

            if (!empty($rate['tax_rate'])) {

                $rate_percent = (int) round($rate['tax_rate']);

                return self::format_vat($rate_percent);
            }
        }

        return 'none';
    }

    private static function detect_shipping_tax(WC_Order $order): string {

        foreach ($order->get_items('tax') as $tax_item) {

            if (!$tax_item instanceof WC_Order_Item_Tax) {
                continue;
            }

            $rate_id = $tax_item->get_rate_id();
            if (!$rate_id) {
                continue;
            }

            $rate = WC_Tax::_get_tax_rate($rate_id);
            if (empty($rate)) {
                continue;
            }

            if ((int) $rate['tax_rate_shipping'] !== 1) {
                continue;
            }

            $rate_percent = (int) round((float) $rate['tax_rate']);

            return self::format_vat($rate_percent);
        }

        return 'none';
    }

    private static function format_vat(int $rate): string {

        if ($rate <= 0) {
            return 'none';
        }

        return 'vat' . $rate;
    }

    private static function balance_amount(array $items, int $total_amount): array {

        $sum = 0;
        foreach ($items as $item) {
            $sum += $item['Amount'];
        }

        if ($sum === $total_amount) {
            return $items;
        }

        $difference = $total_amount - $sum;
        $sum_amount_new = 0;
        $amount_news = [];

        foreach ($items as $key => $item) {
            $new_amount = $item['Amount'] + floor($difference * $item['Amount'] / $sum);
            $amount_news[$key] = $new_amount;
            $sum_amount_new += $new_amount;
        }

        if ($sum_amount_new !== $total_amount) {
            $max_key = array_keys($amount_news, max($amount_news))[0];
            $amount_news[$max_key] += ($total_amount - $sum_amount_new);
        }

        foreach ($amount_news as $key => $amount) {
            $items[$key]['Amount'] = $amount;
            $items[$key]['Price']  = (int) round($amount / max($items[$key]['Quantity'], 1));
        }

        return $items;
    }

    private static function normalize_payment_method(string $value): string {
        $value = trim($value);
        if ($value === '' || $value === 'error') {
            return 'full_payment';
        }

        return $value;
    }

    private static function normalize_payment_object(string $value): string {
        $value = trim($value);
        if ($value === '' || $value === 'error') {
            return 'commodity';
        }

        return $value;
    }

    private static function get_product_price_and_vat(WC_Product $product): array {
        $tax_status = $product->get_tax_status();
        $price = (float) $product->get_price();
        $vat = 'none';

        if ($tax_status !== 'none') {
            $rates_data = WC_Tax::get_rates($product->get_tax_class());
            $rates = array_shift($rates_data);
            if (!empty($rates) && isset($rates['rate'])) {
                $rate_percent = (int) round((float) $rates['rate']);
                $vat = self::format_vat($rate_percent);

                if (!wc_prices_include_tax()) {
                    $rate = [
                        [
                            'rate' => $rates['rate'],
                            'compound' => $rates['compound'] ?? 'no',
                        ],
                    ];
                    $taxes = WC_Tax::calc_tax($price, $rate, false);
                    foreach ($taxes as $tax) {
                        $price += (float) $tax;
                    }
                }
            }
        }

        return [$price, $vat];
    }
}
