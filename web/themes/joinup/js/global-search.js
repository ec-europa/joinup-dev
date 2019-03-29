(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.globalSearch = {
    attach: function (context, settings) {
      // Attach the JS behaviour from mdl-chip-input to the search bar.
      new window['MaterialChipInput'](jQuery('.search-bar.mdl-chipfield')[0]);

      // Position input cursor if chip is available.
      $('.search-bar--header').each(function () {
        var chipWidth = $(this).find('.mdl-chip').width();
        if (chipWidth !== 0) {
          chipWidth = chipWidth + 30;
          $(this).find('.search-bar__input').css('padding-left', chipWidth + 'px');
        }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
