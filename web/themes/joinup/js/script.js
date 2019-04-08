/**
 * @file
 * Joinup theme scripts.
 */

(function ($) {
  $(function () {
    $('.sidebar-menu__button--desktop').on('click', function () {
      $(this).siblings('.mdl-menu__container').toggleClass('is-hidden');
    });

    // Add classes to support menu button.
    $('.support-menu .support-button').each(function () {
      if (!$(this).closest('div').hasClass('hidden')) {
        $('.search-bar--header').addClass('search-bar--header-shifted');
      }
    });

    // Always use the fullscreen indicator for ajax throbbers in the frontend.
    if (typeof Drupal !== 'undefined' && Drupal.Ajax) {
      // Sets the fullscreen progress indicator.
      Drupal.Ajax.prototype.setProgressIndicatorFullscreen = function () {
        this.progress.element = $(
          '<div class="mdl-spinner mdl-js-spinner mdl-spinner--single-color is-active"></div>'
        );
        $('body').after(this.progress.element);
        componentHandler.upgradeAllRegistered();
      };
      Drupal.Ajax.prototype.setProgressIndicatorThrobber =
        Drupal.Ajax.prototype.setProgressIndicatorFullscreen;
    }
  });
})(jQuery);

(function ($) {
  "use strict";
  function checkLicenseCategories() {
    var $licenseTile = $('.license-tile');

    // Remove hidden class before further processing.
    $licenseTile.each(function () {
      $(this).removeClass('is-hidden');
    });

    // Check every active filter item
    // and hide tiles which don't contain proper data-license-category
    $('.license-filter__item a.is-active').each(function () {
      var currentlicenseCategory = $(this).attr('data-license-category');
      if (typeof currentlicenseCategory !== 'undefined') {
        $licenseTile.each(function () {
          var licenseCategory = $(this).attr('data-license-category');
          if (typeof licenseCategory !== 'undefined') {
            var licenseCategoryArray = licenseCategory.split(' ');
            if ($.inArray(currentlicenseCategory, licenseCategoryArray) < 0 && !$(this).hasClass('is-hidden')) {
              $(this).addClass('is-hidden');
            }
          }
        });
      }
    });

    // Check license search field value
    // and hide tiles which don't contain proper data-spdx
    var licenseTiles = 0;
    var currentSpdxId = $('#license-search').val();
    $licenseTile.each(function () {
      if (currentSpdxId.length > 0) {
        var spdxId = $(this).attr('data-spdx');
        if (!spdxId.includes(currentSpdxId) && !$(this).hasClass('is-hidden')) {
          $(this).addClass('is-hidden');
        }
      }

      // Count not hidden tiles.
      if (!$(this).hasClass('is-hidden')) {
        licenseTiles++;
      }
    });

    // Show calculated number of tiles
    $('.license-counter__number').text(licenseTiles);
  }

  // Trigger if license filter is clicked.
  $('.license-filter__item a').each(function () {
    $(this).on('click', function (event) {
      event.preventDefault();

      $(this).toggleClass('is-active');

      checkLicenseCategories();

    });
  });

  // Trigger if enter key is pressed in license search
  $('#license-search').on('keypress', function (event) {
    if (event.which === 13) {
      checkLicenseCategories();
    }
  });

  // Filter on window load
  // Needed for license search filter.
  $(window).load(function() {
    checkLicenseCategories();
  });

})(jQuery);
