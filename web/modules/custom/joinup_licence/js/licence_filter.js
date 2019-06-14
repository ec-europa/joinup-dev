/**
 * @file
 * Licence filter functionality.
 */

(function ($) {
  "use strict";
  function checkLicenceCategories() {
    var $licenceTile = $('.licence-tile');

    // Remove hidden class before further processing.
    $licenceTile.each(function () {
      $(this).removeClass('is-hidden');
    });

    // Check every active filter item
    // and hide tiles which don't contain proper data-licence-category
    $('.licence-filter__item a.is-active').each(function () {
      var currentlicenceCategory = $(this).attr('data-licence-category');
      if (typeof currentlicenceCategory !== 'undefined') {
        $licenceTile.each(function () {
          var licenceCategory = $(this).attr('data-licence-category');
          if (typeof licenceCategory !== 'undefined') {
            var licenceCategoryArray = licenceCategory.split(' ');
            if ($.inArray(currentlicenceCategory, licenceCategoryArray) < 0 && !$(this).hasClass('is-hidden')) {
              $(this).addClass('is-hidden');
            }
          }
        });
      }
    });

    // Check licence search field value
    // and hide tiles which don't contain proper data-spdx
    var licenceTiles = 0;
    var currentSpdxId = $('#licence-search').val().toLowerCase();
    $licenceTile.each(function () {
      if (currentSpdxId.length > 0) {
        var spdxId = $(this).attr('data-spdx').toLowerCase();
        if (spdxId.indexOf(currentSpdxId) === -1 && !$(this).hasClass('is-hidden')) {
          $(this).addClass('is-hidden');
        }
      }

      // Count not hidden tiles.
      if (!$(this).hasClass('is-hidden')) {
        licenceTiles++;
      }
    });

    // Show calculated number of tiles
    $('.licence-counter__number').text(licenceTiles);
  }

  // Trigger if licence filter is clicked.
  $('.licence-filter__item a').each(function () {
    $(this).on('click', function (event) {
      event.preventDefault();

      $(this).toggleClass('is-active');

      checkLicenceCategories();

    });
  });

  // Trigger if enter key is pressed in licence search
  $('#licence-search').on('keyup', function (event) {
    checkLicenceCategories();
  });

  // Reset licence listing
  $('#licence-reset').on('click', function (event) {
    $('.licence-filter__item a.is-active').removeClass('is-active');
    $('.licence-search__input input').val('');
    $('.licence-search__input .mdl-js-textfield')[0].MaterialTextfield.checkDirty();
    checkLicenceCategories();
  });

  // Filter on window load
  // Needed for licence search filter.
  $(window).on('load', function() {
    checkLicenceCategories();
  });

})(jQuery);
