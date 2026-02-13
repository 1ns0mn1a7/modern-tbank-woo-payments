(function($) {
  function toggleReceiptFields() {
    var $checkbox = $('#woocommerce_modern_tbank_check_data_tax');
    var enabled = $checkbox.is(':checked');

    var receiptIds = [
      'woocommerce_modern_tbank_ffd',
      'woocommerce_modern_tbank_taxation',
      'woocommerce_modern_tbank_email_company',
      'woocommerce_modern_tbank_payment_object_ffd',
      'woocommerce_modern_tbank_payment_method_ffd'
    ];

    receiptIds.forEach(function(id) {
      var $row = $('#' + id).closest('tr');
      $row.toggleClass('tbank-receipt-disabled', !enabled);
      $row.find('input, select, textarea').prop('disabled', !enabled);
    });

    var $badge = $('.tbank-receipt-badge');
    if ($badge.length) {
      $badge.text(enabled ? 'Включено' : 'Выключено');
    }
  }

  $(document).ready(function() {
    toggleReceiptFields();
    $(document).on('change', '#woocommerce_modern_tbank_check_data_tax', toggleReceiptFields);
  });
})(jQuery);
