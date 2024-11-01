(function($) {
  'use strict';

  function onDiscountTypeChanged() {
    let $input = $('#discount_type');
    if ($input.val() === 'wpcfg') {
      showHideTab('hide');
    } else {
      showHideTab('show');
    }
  }

  function showHideTab(event) {
    if (event === 'hide') {
      $('#wpcfg_free_gift_data .wpcfg-is-enable_field').hide();
      $('#general_coupon_data .coupon_amount_field').hide();
    } else if (event === 'show') {
      $('#wpcfg_free_gift_data .wpcfg-is-enable_field').show();
      $('#general_coupon_data .coupon_amount_field').show();
    }
  }

  onDiscountTypeChanged();
  $('#discount_type').on('change', onDiscountTypeChanged);
})(jQuery);
