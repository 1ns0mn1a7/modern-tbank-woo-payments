<?php

include_once(dirname(__FILE__) . '/../LanguageTBank.php');

class RuLanguageTBank extends LanguageTBank
{

    public function __construct()
    {

        $this->language['PAYMENT_METHOD_FFD']               = 'Признак способа расчёта';
        $this->language['PAYMENT_METHOD_FFD_DESCRIPTION']   = 'Выберете признак статуса оплаты по позициям чека. </br> Если принимаете предоплату, аванс или продаете в рассрочку, то в момент оплаты будет формироваться чек соответствующего типа. После окончательного расчета вы сможете сформировать закрывающий чек в разделе “Операции” в Личном кабинете интернет-эквайринга';
        $this->language['FULL_PREPAYMENT']                  = 'Предоплата 100%';
        $this->language['PREPAYMENT']                       = 'Предоплата';
        $this->language['ADVANCE']                          = 'Аванс';
        $this->language['FULL_PAYMENT']                     = 'Полный расчет';
        $this->language['PARTIAL_PAYMENT']                  = 'Частичный расчет и кредит';
        $this->language['CREDIT']                           = 'Передача в кредит';
        $this->language['CREDIT_PAYMENT']                   = 'Оплата кредита';
        $this->language['PAYMENT_OBJECT_FFD']               = 'Признак предмета расчёта';
        $this->language['PAYMENT_OBJECT_FFD_DESCRIPTION']   = 'Выберите признак того, за что вы получаете платежи';
        $this->language['COMMODITY']                        = 'Товар';
        $this->language['EXCISE']                           = 'Подакцизный товар';
        $this->language['JOB']                              = 'Работа';
        $this->language['SERVICE']                          = 'Услуга';
        $this->language['GAMBLING_BET']                     = 'Ставка азартной игры';
        $this->language['GAMBLING_PRIZE']                   = 'Выигрыш азартной игры';
        $this->language['LOTTERY']                          = 'Лотерейный билет';
        $this->language['LOTTERY_PRIZE']                    = 'Выигрыш лотереи';
        $this->language['INTELLECTUAL_ACTIVITY']            = 'Предоставление результатов интеллектуальной деятельности';
        $this->language['PAYMENT']                          = 'Платеж';
        $this->language['AGENT_COMMISSION']                 = 'Агентское вознаграждение';
        $this->language['COMPOSITE']                        = 'Составной предмет расчета';
        $this->language['ANOTHER']                          = 'Иной предмет расчета';
        $this->language['EMAIL_COMPANY_PERSONAL']           = 'Введите email компании';
        $this->language['EMAIL_COMPANY']                    = 'Email компании';
        $this->language['PAYMENT_METHOD']                   = 'Активность способа оплаты';
        $this->language['ACTIVE']                           = 'Активен';
        $this->language['PAYMENT_METHOD_NAME']              = 'Название способа оплаты';
        $this->language['PAYMENT_METHOD_DESCRIPTION']       = 'Описание способа оплаты';
        $this->language['PAYMENT_METHOD_USER']              = 'Название способа оплаты, которое увидит пользователь при оформлении заказа';
        $this->language['T_BANK']                           = 'Т-Банк';
        $this->language['TERMINAL']                         = 'Терминал';
        $this->language['SPECIFIED_PERSONAL']               = 'Указан в Личном кабинете интернет-эквайринга Т-Банк в настройках магазина в разделе “Терминалы”';
        $this->language['PASSWORD']                         = 'Пароль терминала';
        $this->language['DESCRIPTION_PAYMENT_METHOD']       = 'Описание способа оплаты, которое пользователь увидит при оформлении заказа';
        $this->language['PAYMENT_THROUGH']                  = 'Оплата через Т-Банк';
        $this->language['ORDER_COMPLETION']                 = 'Автозавершение заказа';
        $this->language['AUTOMATIC_SUCCESSFUL']             = 'Автоматический перевод заказа в статус "Выполнен" после успешной оплаты';
        $this->language['AUTOMATIC_DESCRIPTION']            = 'Только для товаров, которые одновременно являются виртуальными и скачиваемыми';
        $this->language['REDUCE_STOCK_LEVELS']              = 'Уменьшение уровня запасов';
        $this->language['REDUCE_STOCK_LEVELS_AFTER_PAYMENT']= 'Снижать запасы только после получения оплаты';
        $this->language['REDUCE_STOCK_LEVELS_DESCRIPTION']  = 'Если чекбокс не отмечен, запасы будут снижаться сразу после оформления заказа';
        $this->language['SEND_DATA_CHECK']                  = 'Формировать чек';
        $this->language['DATA_TRANSFER']                    = 'Передача данных';
        $this->language['SEND_DATA_CHECK_DESCRIPTON']       = '1) Включите передачу данных </br> 2) Арендуйте облачную кассу. Список доступных касс есть на сайте банка в разделе “Облачные кассы” </br> 3) Протестируйте формирование чека и настройте облачную кассу в разделе “Онлайн-касса” в Личном кабинете';
        $this->language['TAX_SYSTEM']                       = 'Система налогообложения (СНО)';
        $this->language['CHOOSE_SYSTEM_STORE']              = 'Выберите СНО юридического лица вашего интернет-магазина';
        $this->language['TOTAL_CH']                         = 'Общая СН';
        $this->language['SIMPLIFIED_CH']                    = 'Упрощенная СН (доходы)';
        $this->language['SIMPLIFIED__COSTS']                = 'Упрощенная СН (доходы минус расходы)';
        $this->language['SINGLE_IMPUTED_INCOME']            = 'Единый налог на вмененный доход';
        $this->language['UNIFIED_AGRICULTURAL_TAX']         = 'Единый сельскохозяйственный налог';
        $this->language['PATENT_CH']                        = 'Патентная СН';
        $this->language['PAYMENT_LANGUAGE']                 = 'Язык платежной формы';
        $this->language['CHOOSE_PAYMENT_LANGUAGE']          = 'Выберите язык платежной формы для показа пользователю';
        $this->language['RUSSIA']                           = 'Русский';
        $this->language['ENGLISH']                          = 'Английский';
        $this->language['PAYMENT_SUCCESS']                  = 'Платеж успешно оплачен';
        $this->language['PAYMENT_NOT_SUCCESS']              = 'Платеж не оплачен';
        $this->language['PAYMENT_THANK']                    = 'Благодарим вас за покупку!';
        $this->language['PAYMENT_ERROR']                    = 'Во время платежа произошла ошибка. Повторите попытку или обратитесь к администратору';
        $this->language['REQUEST_TO_PAYMENT']               = 'Запрос к платежному сервису был отправлен некорректно';
        $this->language['SETUP_OF_RECEIVING']               = 'Настройка приема электронных платежей через Т-Банк';
        $this->language['GENERAL_SETTINGS_SECTION']         = 'Общие настройки';
        $this->language['PAYMENT_FORM_SETTINGS']            = 'Настройки платежной формы';
        $this->language['SETTING_CHECK_SENDING']            = 'Настройки отправки чеков';
        $this->language['T_DOES_NOT_SUPPORT']               = 'Т-Банк не поддерживает валюты Вашего магазина';
        $this->language['GATEWAY_IS_DISABLED']              = 'Шлюз отключен';
        $this->language['FFD']                              = 'Формат фискальных документов (ФФД)';
        $this->language['FFD_DESCRIPTION']                  = 'Передавать данные для формирования чека ФФД 1.2';
        $this->language['FFD_ADVICE']                       = 'ФФД вы можете узнать в сервисе, в котором вы арендовали облачную кассу.';
        $this->language['FFD12']                            = '1.2';
        $this->language['FFD105']                           = '1.05';
    }
}

