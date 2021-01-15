/**
 * @file
 * JavaScript behaviors for the global search bar.
 */

(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.globalSearch = {
    attach: function (context, settings) {
      // Attach the JS behaviour from mdl-chip-input to the search bar.
      new window['MaterialChipInput'](jQuery('.search-bar.mdl-chipfield')[0]);

      // Position input cursor if chip is available.
      $('.search-bar--header, .search-bar__block').each(function () {
        var $searchBar = $(this);
        var chipWidth = $(this).find('.mdl-chip').width();
        if (chipWidth !== 0) {
          chipWidth = chipWidth + 25;
          $(this).find('.search-bar__input').css('padding-left', chipWidth + 'px');
        }

        $('.mdl-chip').on('mousedown', function (event) {
          event.preventDefault();
          event.stopPropagation();
        });

        $('.mdl-chip__action').on('click', function () {
          var chipWidth = $searchBar.find('.mdl-chip').width();
          if (chipWidth === 0 || chipWidth === undefined) {
            $searchBar.find('.search-bar__input').css('padding-left', '0px');
          }
        });

        $('.search-bar__input').on('keydown', function (event) {
          if (event.which === 8) {
            var chipWidth = $searchBar.find('.mdl-chip').width();
            if (chipWidth === 0 || chipWidth === undefined) {
              $searchBar.find('.search-bar__input').css('padding-left', '0px');
            }
          }
        });
      });

      // Advanced search page submit action.
      $('.search-bar__submit').on('click', function () {
        event.preventDefault();
        $(this).parent().submit();
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
