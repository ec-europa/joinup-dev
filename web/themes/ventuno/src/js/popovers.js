/**
 * Popovers.
 */
(function ($, Drupal) {
  Drupal.behaviors.popovers = {
    attach: function (context) {
      $(function () {
        $('.popover-getstarted--sm').popover();
        $('.popover-getstarted--lg').popover();
      })
    }
  };
})(jQuery, Drupal);
