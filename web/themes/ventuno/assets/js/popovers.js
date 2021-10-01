(function (factory) {
  typeof define === 'function' && define.amd ? define(factory) :
  factory();
}((function () { 'use strict';

  /**
   * Popovers.
   */
  (function ($, Drupal) {
    Drupal.behaviors.popovers = {
      attach: function (context) {
        $(function () {
          $('.popover-getstarted').popover({
            trigger: "click"
          });
        });
      }
    };
  })(jQuery, Drupal);

})));
//# sourceMappingURL=popovers.js.map
