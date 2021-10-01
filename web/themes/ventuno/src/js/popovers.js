/**
 * Popovers.
 */
(function ($, Drupal) {
  Drupal.behaviors.popovers = {
    attach: function (context) {
      $(function () {
        $('.popover-getstarted').popover({ trigger: "click" });
      })
    }
  };
})(jQuery, Drupal);
