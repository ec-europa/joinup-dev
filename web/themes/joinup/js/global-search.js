(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.globalSearch = {
    attach: function (context, settings) {
      // Attach the JS behaviour from mdl-chip-input to the search bar.
      new window['MaterialChipInput'](jQuery('.search-bar.mdl-chipfield')[0]);
    }
  };

})(jQuery, Drupal, drupalSettings);
