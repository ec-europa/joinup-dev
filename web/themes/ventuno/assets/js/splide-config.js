(function (factory) {
  typeof define === 'function' && define.amd ? define(factory) :
  factory();
}((function () { 'use strict';

  /**
   * Splide.
   */
  (function ($, Drupal) {
    Drupal.behaviors.splide = {
      attach: function (context) {
        var elms = document.getElementsByClassName('splide');

        for (var i = 0, len = elms.length; i < len; i++) {
          new Splide(elms[i], {
            fixedWidth: '22rem',
            autoHeight: true,
            type: 'loop',
            perPage: 4,
            perMove: 1,
            pagination: false,
            breakpoints: {
              980: {
                perPage: 2
              },
              640: {
                perPage: 1
              }
            }
          }).mount();
        }
      }
    };
  })(jQuery, Drupal);

})));
//# sourceMappingURL=splide-config.js.map
