(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.globalSearch = {
    attach: function (context, settings) {
      // Attach the JS behaviour from mdl-chip-input to the search bar.
      new window['MaterialChipInput'](jQuery('.search-bar.mdl-chipfield')[0]);

      // Position input cursor if chip is available.
      $('.search-bar--header').each(function () {
        var $searchBar = $(this);
        var chipWidth = $(this).find('.mdl-chip').width();
        if (chipWidth !== 0) {
          chipWidth = chipWidth + 30;
          $(this).find('.search-bar__input').css('padding-left', chipWidth + 'px');
        }

        var $chipAction = $('.mdl-chip__action');

        $chipAction.on('mousedown', function (event) {
          event.preventDefault();
          event.stopPropagation();
        });

        $chipAction.on('click', function (event) {
          var chipWidth = $searchBar.find('.mdl-chip').width();
          if (chipWidth === 0 || chipWidth === undefined) {
            $('.search-bar--header').find('.search-bar__input').css('padding-left', '0px');
          }
        });

        $('.search-bar__input').on('keydown', function (event) {
          if (event.which === 8) {
            var chipWidth = $searchBar.find('.mdl-chip').width();
            if (chipWidth === 0 || chipWidth === undefined) {
              $('.search-bar--header').find('.search-bar__input').css('padding-left', '0px');
            }
          }
        });
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
