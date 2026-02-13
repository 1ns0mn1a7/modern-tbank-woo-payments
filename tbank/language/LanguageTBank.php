<?php

class LanguageTBank
{
    const PAYMENT_METHOD_FFD             = 'PAYMENT_METHOD_FFD';
    const PAYMENT_METHOD_FFD_DESCRIPTION = 'PAYMENT_METHOD_FFD_DESCRIPTION';
    const FULL_PREPAYMENT                = 'FULL_PREPAYMENT';
    const PREPAYMENT                     = 'PREPAYMENT';
    const ADVANCE                        = 'ADVANCE';
    const FULL_PAYMENT                   = 'FULL_PAYMENT';
    const PARTIAL_PAYMENT                = 'PARTIAL_PAYMENT';
    const CREDIT                         = 'CREDIT';
    const CREDIT_PAYMENT                 = 'CREDIT_PAYMENT';
    const PAYMENT_OBJECT_FFD             = 'PAYMENT_OBJECT_FFD';
    const PAYMENT_OBJECT_FFD_DESCRIPTION = 'PAYMENT_OBJECT_FFD_DESCRIPTION';
    const COMMODITY                      = 'COMMODITY';
    const EXCISE                         = 'EXCISE';
    const JOB                            = 'JOB';
    const SERVICE                        = 'SERVICE';
    const GAMBLING_BET                   = 'GAMBLING_BET';
    const GAMBLING_PRIZE                 = 'GAMBLING_PRIZE';
    const LOTTERY                        = 'LOTTERY';
    const LOTTERY_PRIZE                  = 'LOTTERY_PRIZE';
    const INTELLECTUAL_ACTIVITY          = 'INTELLECTUAL_ACTIVITY';
    const PAYMENT                        = 'PAYMENT';
    const AGENT_COMMISSION               = 'AGENT_COMMISSION';
    const COMPOSITE                      = 'COMPOSITE';
    const ANOTHER                        = 'ANOTHER';
    const EMAIL_COMPANY_PERSONAL         = 'EMAIL_COMPANY_PERSONAL';
    const EMAIL_COMPANY                  = 'EMAIL_COMPANY';
    const PAYMENT_METHOD                 = 'PAYMENT_METHOD';
    const ACTIVE                         = 'ACTIVE';
    const PAYMENT_METHOD_NAME            = 'PAYMENT_METHOD_NAME';
    const PAYMENT_METHOD_DESCRIPTION     = 'PAYMENT_METHOD_DESCRIPTION';
    const PAYMENT_METHOD_USER            = 'PAYMENT_METHOD_USER';
    const T_BANK                         = 'T_BANK';
    const TERMINAL                       = 'TERMINAL';
    const SPECIFIED_PERSONAL             = 'SPECIFIED_PERSONAL';
    const PASSWORD                       = 'PASSWORD';
    const DESCRIPTION_PAYMENT_METHOD     = 'DESCRIPTION_PAYMENT_METHOD';
    const PAYMENT_THROUGH                = 'PAYMENT_THROUGH';
    const ORDER_COMPLETION               = 'ORDER_COMPLETION';
    const AUTOMATIC_SUCCESSFUL           = 'AUTOMATIC_SUCCESSFUL';
    const AUTOMATIC_DESCRIPTION          = 'AUTOMATIC_DESCRIPTION';
    const REDUCE_STOCK_LEVELS               = 'REDUCE_STOCK_LEVELS';
    const REDUCE_STOCK_LEVELS_AFTER_PAYMENT = 'REDUCE_STOCK_LEVELS_AFTER_PAYMENT';
    const REDUCE_STOCK_LEVELS_DESCRIPTION   = 'REDUCE_STOCK_LEVELS_DESCRIPTION';
    const SEND_DATA_CHECK                = 'SEND_DATA_CHECK';
    const DATA_TRANSFER                  = 'DATA_TRANSFER';
    const SEND_DATA_CHECK_DESCRIPTON     = 'SEND_DATA_CHECK_DESCRIPTON';
    const TAX_SYSTEM                     = 'TAX_SYSTEM';
    const CHOOSE_SYSTEM_STORE            = 'CHOOSE_SYSTEM_STORE';
    const TOTAL_CH                       = 'TOTAL_CH';
    const SIMPLIFIED_CH                  = 'SIMPLIFIED_CH';
    const SIMPLIFIED__COSTS              = 'SIMPLIFIED__COSTS';
    const SINGLE_IMPUTED_INCOME          = 'SINGLE_IMPUTED_INCOME';
    const UNIFIED_AGRICULTURAL_TAX       = 'UNIFIED_AGRICULTURAL_TAX';
    const PATENT_CH                      = 'PATENT_CH';
    const PAYMENT_LANGUAGE               = 'PAYMENT_LANGUAGE';
    const CHOOSE_PAYMENT_LANGUAGE        = 'CHOOSE_PAYMENT_LANGUAGE';
    const RUSSIA                         = 'RUSSIA';
    const ENGLISH                        = 'ENGLISH';
    const PAYMENT_SUCCESS                = 'PAYMENT_SUCCESS';
    const PAYMENT_NOT_SUCCESS            = 'PAYMENT_NOT_SUCCESS';
    const PAYMENT_THANK                  = 'PAYMENT_THANK';
    const PAYMENT_ERROR                  = 'PAYMENT_ERROR';
    const REQUEST_TO_PAYMENT             = 'REQUEST_TO_PAYMENT';
    const SETUP_OF_RECEIVING             = 'SETUP_OF_RECEIVING';
    const GENERAL_SETTINGS_SECTION       = 'GENERAL_SETTINGS_SECTION';
    const PAYMENT_FORM_SETTINGS          = 'PAYMENT_FORM_SETTINGS';
    const SETTING_CHECK_SENDING          = 'SETTING_CHECK_SENDING';
    const T_DOES_NOT_SUPPORT             = 'T_DOES_NOT_SUPPORT';
    const GATEWAY_IS_DISABLED            = 'GATEWAY_IS_DISABLED';
    const FFD                            = 'FFD';
    const FFD_DESCRIPTION                = 'FFD_DESCRIPTION';
    const FFD_ADVICE                     = 'FFD_ADVICE';
    const FFD12                          = 'FFD12';
    const FFD105                         = 'FFD105';

    public $language = [];

    protected static $instance = null;

    public static function getInstance()
    {
        if (null === self::$instance)
        {
            self::$instance = new static();
        }
        return self::$instance;
    }

    private function __clone() {}

    public function __construct(){}

    public static function get($name)
    {
        $instance = RuLanguageTBank::getInstance();

        if (!isset($instance->language[$name])) {
            return 'Error';
        }

        return $instance->language[$name];
    }
}
