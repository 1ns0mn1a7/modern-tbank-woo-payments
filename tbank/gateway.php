<?php
if (!defined('ABSPATH')) exit;

class WC_Gateway_Modern_TBank extends WC_Payment_Gateway {

    private string $terminal;
    private string $secret;
    private bool $debug = false;
    private string $payment_form_language = 'ru';
    private const ALLOWED_FFD = ['error', 'ffd105', 'ffd12'];
    private const ALLOWED_TAXATION = [
        'error',
        'osn',
        'usn_income',
        'usn_income_outcome',
        'envd',
        'esn',
        'patent',
    ];
    private const ALLOWED_PAYMENT_OBJECT = [
        'error',
        'commodity',
        'excise',
        'job',
        'service',
        'gambling_bet',
        'gambling_prize',
        'lottery',
        'lottery_prize',
        'intellectual_activity',
        'payment',
        'agent_commission',
        'composite',
        'another',
    ];
    private const ALLOWED_PAYMENT_METHOD = [
        'error',
        'full_prepayment',
        'prepayment',
        'advance',
        'full_payment',
        'partial_payment',
        'credit',
        'credit_payment',
    ];

    public function __construct() {

        $this->id = 'modern_tbank';
        $this->method_title = 'Т-Банк';
        $this->method_description = 'Оплата через T-Bank';
        $this->has_fields = false;

        $this->supports = [
            'products',
            'refunds',
            'subscriptions',
            'subscription_cancellation',
            'subscription_reactivation',
            'subscription_suspension',
            'subscription_amount_changes',
            'subscription_date_changes',
            'multiple_subscriptions',
        ];

        $this->init_form_fields();
        $this->init_settings();

        $this->payment_form_language = (string) $this->get_option(
            'payment_form_language',
            'ru'
        );

        $icon = ($this->payment_form_language === 'en')
            ? 'tbank/tbank-en.png'
            : 'tbank/tbank.png';
        $this->icon = apply_filters(
            'woocommerce_modern_tbank_icon',
            MODERN_TBANK_URL . $icon
        );

        $this->terminal = (string) $this->get_option('production_terminal');
        $this->secret   = (string) $this->get_option('production_secret');

        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');

        $this->debug = $this->get_option('debug') === 'yes';

        add_action(
            'woocommerce_update_options_payment_gateways_' . $this->id,
            [$this, 'process_admin_options']
        );

        add_action(
            'woocommerce_scheduled_subscription_payment_' . $this->id,
            [$this, 'process_subscription_payment'],
            10,
            2
        );
    }

    public function admin_options() {

        $webhook_url = MODERN_TBANK_URL . 'tbank/success.php';

        echo '<div class="modern-tbank-settings">';
        echo '<h2>' . esc_html($this->method_title) . '</h2>';

        echo '<p>' . esc_html__(
            'Настройка интернет-эквайринга Т-Банк.',
            'modern-tbank'
        ) . '</p>';

        echo '<div class="modern-tbank-callout">';
        echo '<strong>Подсказка по настройке</strong><br>';
        echo 'Webhook URL для терминала: ';
        if ($webhook_url) {
            echo '<code>' . esc_html($webhook_url) . '</code>';
        } else {
            echo '<code>—</code>';
        }
        echo '<br>Success/Fail URL выставляются автоматически при создании платежа.';
        echo '<br>Чеки формируются только если включить “Формировать чек” и заполнить ФФД/СНО.';
        echo '</div>';

        echo '<table class="form-table">';
        $this->generate_settings_html();
        echo '</table>';
        echo '</div>';
    }

    public function init_form_fields() {
        $this->form_fields = [

            'general_section' => [
                'title' => 'Общие настройки',
                'type'  => 'title'
            ],

            'enabled' => [
                'title'   => 'Активность способа оплаты',
                'type'    => 'checkbox',
                'label'   => 'Активен',
                'default' => 'yes'
            ],

            'title' => [
                'title'       => 'Название способа оплаты',
                'type'        => 'text',
                'description' => 'Название способа оплаты, которое увидит пользователь при оформлении заказа',
                'default'     => 'Т-Банк',
                'desc_tip'    => true
            ],

            'description' => [
                'title'       => 'Описание способа оплаты',
                'type'        => 'textarea',
                'description' => 'Описание способа оплаты, которое пользователь увидит при оформлении заказа',
                'default'     => 'Оплата через Т-Банк',
                'desc_tip'    => true
            ],

            'payment_form_language' => [
                'title'       => 'Язык платежной формы',
                'type'        => 'select',
                'description' => 'Выберите язык платежной формы для показа пользователю',
                'default'     => 'ru',
                'desc_tip'    => true,
                'options'     => [
                    'ru' => 'Русский',
                    'en' => 'Английский',
                ],
            ],

            'auto_complete' => [
                'title'       => 'Автозавершение заказа',
                'type'        => 'checkbox',
                'label'       => 'Автоматический перевод заказа в статус "Выполнен" после успешной оплаты',
                'description' => 'Только для товаров, которые одновременно являются виртуальными и скачиваемыми',
                'default'     => 'no'
            ],

            'reduce_stock_levels' => [
                'title'       => 'Уменьшение уровня запасов',
                'type'        => 'checkbox',
                'label'       => 'Снижать запасы только после получения оплаты',
                'description' => 'Если чекбокс не отмечен, запасы будут снижаться сразу после оформления заказа',
                'default'     => 'yes'
            ],

            // TERMINAL SECTION
            'production_section' => [
                'title' => 'Терминал',
                'type'  => 'title'
            ],

            'production_terminal' => [
                'title'       => 'Терминал',
                'type'        => 'text',
                'description' => 'Указан в Личном кабинете в разделе интернет-эквайринга Т-Банк в настройках магазина в разделе “Терминалы”',
                'desc_tip'    => true
            ],

            'production_secret' => [
                'title'       => 'Пароль',
                'type'        => 'password',
                'description' => 'Указан в Личном кабинете в разделе интернет-эквайринга Т-Банк в настройках магазина в разделе “Терминалы”',
                'desc_tip'    => true
            ],

            'receipt_section' => [
                'title' => 'Настройки отправки чеков <span class="tbank-receipt-badge">—</span>',
                'type'  => 'title'
            ],

            'check_data_tax' => [
                'title'       => 'Формировать чек',
                'type'        => 'checkbox',
                'label'       => 'Передача данных',
                'description' => '1) Включите передачу данных </br> 2) Арендуйте облачную кассу. Список доступных касс есть на сайте банка в разделе “Облачные кассы” </br> 3) Протестируйте формирование чека и настройте облачную кассу в разделе “Онлайн-касса” в Личном кабинете',
                'default'     => 'no'
            ],

            'ffd' => [
                'title'       => 'Формат фискальных документов (ФФД)',
                'type'        => 'select',
                'description' => 'ФФД вы можете узнать в сервисе, в котором вы арендовали облачную кассу.',
                'default'     => 'error',
                'desc_tip'    => true,
                'options'     => [
                    'error'  => '',
                    'ffd105' => '1.05',
                    'ffd12'  => '1.2',
                ],
            ],

            'taxation' => [
                'title'       => 'Система налогообложения (СНО)',
                'type'        => 'select',
                'description' => 'Выберите СНО юридического лица вашего интернет-магазина',
                'default'     => 'error',
                'desc_tip'    => true,
                'options'     => [
                    'error'               => '',
                    'osn'                 => 'Общая СН',
                    'usn_income'          => 'Упрощенная СН (доходы)',
                    'usn_income_outcome'  => 'Упрощенная СН (доходы минус расходы)',
                    'envd'                => 'Единый налог на вмененный доход',
                    'esn'                 => 'Единый сельскохозяйственный налог',
                    'patent'              => 'Патентная СН',
                ],
            ],

            'email_company' => [
                'type'        => 'text',
                'title'       => 'Email компании',
                'description' => 'Используется в чеке в качестве email продавца',
                'desc_tip'    => true,
                'default'     => '',
            ],

            'payment_object_ffd' => [
                'type'        => 'select',
                'title'       => 'Признак предмета расчёта',
                'description' => 'Выберите признак того, за что вы получаете платежи',
                'default'     => 'error',
                'desc_tip'    => true,
                'options'     => [
                    'error'                 => '',
                    'commodity'             => 'Товар',
                    'excise'                => 'Подакцизный товар',
                    'job'                   => 'Работа',
                    'service'               => 'Услуга',
                    'gambling_bet'          => 'Ставка азартной игры',
                    'gambling_prize'        => 'Выигрыш азартной игры',
                    'lottery'               => 'Лотерейный билет',
                    'lottery_prize'         => 'Выигрыш лотереи',
                    'intellectual_activity' => 'Предоставление результатов интеллектуальной деятельности',
                    'payment'               => 'Платеж',
                    'agent_commission'      => 'Агентское вознаграждение',
                    'composite'             => 'Составной предмет расчета',
                    'another'               => 'Иной предмет расчета',
                ],
            ],

            'payment_method_ffd' => [
                'type'        => 'select',
                'title'       => 'Признак способа расчёта',
                'description' => 'Выберете признак статуса оплаты по позициям чека. Если принимаете предоплату, аванс или продаете в рассрочку, будет сформирован чек соответствующего типа.',
                'default'     => 'error',
                'desc_tip'    => true,
                'options'     => [
                    'error'            => '',
                    'full_prepayment'  => 'Предоплата 100%',
                    'prepayment'       => 'Предоплата',
                    'advance'          => 'Аванс',
                    'full_payment'     => 'Полный расчет',
                    'partial_payment'  => 'Частичный расчет и кредит',
                    'credit'           => 'Передача в кредит',
                    'credit_payment'   => 'Оплата кредита',
                ],
            ],

            'debug' => [
                'title'   => 'Режим отладки',
                'type'    => 'checkbox',
                'label'   => 'Включить логирование',
                'default' => 'no'
            ],
        ];
    }

    public function process_admin_options(): bool {
        $saved = parent::process_admin_options();

        $settings = get_option('woocommerce_modern_tbank_settings', []);
        $errors = [];

        $email_company = trim((string) ($settings['email_company'] ?? ''));
        if ($email_company !== '' && !is_email($email_company)) {
            $errors[] = 'Email компании заполнен некорректно.';
            $settings['email_company'] = '';
        }

        if (!in_array($settings['ffd'] ?? 'error', self::ALLOWED_FFD, true)) {
            $settings['ffd'] = 'error';
        }

        if (!in_array($settings['taxation'] ?? 'error', self::ALLOWED_TAXATION, true)) {
            $settings['taxation'] = 'error';
        }

        if (!in_array($settings['payment_object_ffd'] ?? 'error', self::ALLOWED_PAYMENT_OBJECT, true)) {
            $settings['payment_object_ffd'] = 'error';
        }

        if (!in_array($settings['payment_method_ffd'] ?? 'error', self::ALLOWED_PAYMENT_METHOD, true)) {
            $settings['payment_method_ffd'] = 'error';
        }

        if (($settings['check_data_tax'] ?? 'no') === 'yes') {
            if (($settings['ffd'] ?? 'error') === 'error') {
                $errors[] = 'Для чеков нужно выбрать ФФД.';
            }
            if (($settings['taxation'] ?? 'error') === 'error') {
                $errors[] = 'Для чеков нужно выбрать систему налогообложения.';
            }
            if (($settings['payment_object_ffd'] ?? 'error') === 'error') {
                $errors[] = 'Для чеков нужно выбрать признак предмета расчёта.';
            }
            if (($settings['payment_method_ffd'] ?? 'error') === 'error') {
                $errors[] = 'Для чеков нужно выбрать признак способа расчёта.';
            }
        }

        if (!empty($errors) && class_exists('WC_Admin_Settings')) {
            foreach ($errors as $error) {
                WC_Admin_Settings::add_error($error);
            }
        }

        update_option('woocommerce_modern_tbank_settings', $settings);

        return $saved && empty($errors);
    }

    public function payment_fields() {
        if ($this->description) {
            echo wpautop(wp_kses_post($this->description));
        }
    }

    public function process_payment($order_id): ?array {

        if ($this->debug) {
            TBank_Helper::log('Start payment. Order: ' . $order_id);
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wc_add_notice('Order not found', 'error');
            return null;
        }

        if ($order->is_paid()) {
            return [
                'result'   => 'success',
                'redirect' => $order->get_checkout_payment_url()
            ];
        }

        if (empty($this->terminal) || empty($this->secret)) {
            wc_add_notice('Не заполнены данные терминала.', 'error');
            return null;
        }

        $api = new TBank_API($this->terminal, $this->secret);

        $payment_id  = $order->get_meta('_tbank_payment_id');
        $payment_url = $order->get_meta('_tbank_payment_url');

        // Idempotency
        if ($payment_id) {

            $state = $api->get_state($payment_id);

            if ($this->debug && !is_wp_error($state)) {
                TBank_Helper::log('GetState: ' . wp_json_encode($state));
            }

            if (!is_wp_error($state) && !empty($state->Status)) {

                switch ($state->Status) {

                    case 'CONFIRMED':
                        $order->payment_complete($payment_id);
                        $order->add_order_note(
                            'Оплата подтверждена Т-Банком. PaymentId: ' . $payment_id
                        );
                        $order->save();
                        TBank_Helper::maybe_auto_complete_order(
                            $order,
                            $this->settings
                        );

                        return [
                            'result'   => 'success',
                            'redirect' => $order->get_checkout_order_received_url()
                        ];

                        case 'AUTHORIZED':
                            return [
                                'result'   => 'success',
                                'redirect' => $payment_url ?: $order->get_checkout_payment_url()
                            ];
                        
                        case 'NEW':
                            return [
                                'result'   => 'success',
                                'redirect' => $payment_url
                            ];
                        
                        case 'REJECTED':
                        case 'CANCELED':
                        case 'DEADLINE_EXPIRED':
                            $order->update_status('failed');
                            wc_add_notice('Payment failed.', 'error');

                            if ($this->debug) {
                                TBank_Helper::log('Payment failed. Status: ' . $state->Status);
                            }

                            return null;
                }
            }
        }

        // New Init
        $request_settings = $this->settings;
        $request_settings['notification_url'] = MODERN_TBANK_URL . 'tbank/success.php';
        $request_settings['payment_form_language'] = $this->payment_form_language;

        $response = $api->init_payment($order, $request_settings);

        if ($this->debug) {
            if (is_wp_error($response)) {
                TBank_Helper::log('Payment init error: ' . $response->get_error_message());
            } else {
                TBank_Helper::log('Payment init response: ' . wp_json_encode($response));
            }
        }

        if (is_wp_error($response)) {
            wc_add_notice($response->get_error_message(), 'error');
            return null;
        }

        if (empty($response->Success) || empty($response->PaymentURL)) {
            wc_add_notice($response->Message ?? 'Payment error', 'error');
            return null;
        }

        $order->update_meta_data('_tbank_payment_id', $response->PaymentId);
        $order->update_meta_data('_tbank_payment_url', $response->PaymentURL);
        $order->set_transaction_id($response->PaymentId);
        $order->save();

        if ($this->get_option('reduce_stock_levels') !== 'yes') {
            if (!$order->get_meta('_order_stock_reduced')) {
                try {
                    wc_reduce_stock_levels($order_id);
                } catch (Exception $e) {
                    if ($this->debug) {
                        TBank_Helper::log('Stock reduce error: ' . $e->getMessage(), 'error');
                    }
                }
            }
        }

        
        return [
            'result'   => 'success',
            'redirect' => $response->PaymentURL
        ];
    }

    public function process_refund($order_id, $amount = null, $reason = '') {

        $order = wc_get_order($order_id);

        if (!$order) {
            return new WP_Error('invalid_order', 'Invalid order');
        }

        if (!$order->is_paid()) {
            return new WP_Error('not_paid', 'Order is not paid');
        }

        if (!$amount) {
            return new WP_Error('invalid_amount', 'Refund amount missing');
        }

        if ($amount > $order->get_remaining_refund_amount()) {
            return new WP_Error('invalid_amount', 'Refund exceeds remaining amount');
        }

        $payment_id = $order->get_meta('_tbank_payment_id');

        if (!$payment_id) {
            return new WP_Error('no_payment_id', 'Payment ID not found');
        }

        $refund_amount = (int) round($amount * 100);

        $api = new TBank_API($this->terminal, $this->secret);

        $receipt = null;
        if (!empty($this->settings['check_data_tax']) && $this->settings['check_data_tax'] === 'yes') {
            $refund = $this->get_refund_for_amount($order, (float) $amount);
            if ($refund) {
                $receipt = TBank_Receipt::build_refund(
                    $order,
                    $refund,
                    $this->settings,
                    $refund_amount
                );
            }
        }

        $response = $api->refund($payment_id, $refund_amount, $receipt);

        if ($this->debug) {
            if (is_wp_error($response)) {
                TBank_Helper::log('Refund error: ' . $response->get_error_message());
            } else {
                TBank_Helper::log('Refund response: ' . wp_json_encode($response));
            }
        }

        if (is_wp_error($response)) {
            return $response;
        }

        if (empty($response->Success)) {
            return new WP_Error(
                'refund_failed',
                $response->Message ?? 'Refund failed'
            );
        }

        $order->add_order_note(
            sprintf(
                'Возврат подтвержден Т-Банком. Сумма: %s. Причина: %s',
                wc_price($amount),
                $reason ?: '—'
            )
        );

        return true;
    }

    private function get_refund_for_amount(WC_Order $order, float $amount): ?WC_Order_Refund {
        $refunds = $order->get_refunds();
        if (empty($refunds)) {
            return null;
        }

        foreach ($refunds as $refund) {
            if (!$refund instanceof WC_Order_Refund) {
                continue;
            }

            if (abs((float) $refund->get_amount() - $amount) < 0.01) {
                return $refund;
            }
        }

        return $refunds[0] instanceof WC_Order_Refund ? $refunds[0] : null;
    }

    public function process_subscription_payment($amount, $renewal_order) {

        if ($renewal_order->is_paid()) {
            return;
        }

        $subscriptions = wcs_get_subscriptions_for_renewal_order($renewal_order);

        if (empty($subscriptions)) {
            $renewal_order->update_status('failed', 'Subscription not found.');
            return;
        }

        $subscription = array_shift($subscriptions);

        $rebill_id = $subscription->get_meta('_tbank_rebill_id');

        if (empty($rebill_id)) {
            $renewal_order->update_status('failed', 'RebillId not found.');
            return;
        }

        $api = new TBank_API($this->terminal, $this->secret);

        $request_settings = $this->settings;
        $request_settings['notification_url'] = WC()->api_request_url($this->id);
        $request_settings['payment_form_language'] = $this->payment_form_language;

        $init = $api->init_payment($renewal_order, $request_settings);
        if (is_wp_error($init) || empty($init->PaymentId)) {
            $renewal_order->update_status(
                'failed',
                'TBank recurring payment init failed.'
            );
            return;
        }

        $renewal_order->update_meta_data('_tbank_payment_id', $init->PaymentId);
        $renewal_order->set_transaction_id($init->PaymentId);
        $renewal_order->save();

        $charge = $api->charge(
            $rebill_id,
            (int) round($amount * 100),
            (string) $init->PaymentId
        );

        if (is_wp_error($charge) || empty($charge->Success)) {
            $renewal_order->update_status(
                'failed',
                'TBank recurring payment failed.'
            );
            return;
        }

        $renewal_order->payment_complete($charge->PaymentId);
        $renewal_order->add_order_note(
            'Повторный платеж Т-Банка успешно проведен. PaymentId: ' . $charge->PaymentId
        );
        TBank_Helper::maybe_auto_complete_order(
            $renewal_order,
            $this->settings
        );
    }
}
