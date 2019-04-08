/**
 * @file
 * Joinup theme scripts.
 */

var loadMore = loadMore || {};

 function itemWidth() {
   var itemsCounter = $('.listing--load-more .row .mdl-grid').children('.listing__item').length;

   switch (itemsCounter % 3) {
     case 0:
       $('.listing__item--load-more').removeClass('mdl-cell--8-col');
       $('.listing__item--load-more').removeClass('mdl-cell--12-col');
       $('.listing__item--load-more').addClass('mdl-cell--4-col');
     break;

     case 1:
       $('.listing__item--load-more').removeClass('mdl-cell--4-col');
       $('.listing__item--load-more').removeClass('mdl-cell--8-col');
       $('.listing__item--load-more').addClass('mdl-cell--12-col');
     break;

     case 2:
       $('.listing__item--load-more').removeClass('mdl-cell--4-col');
       $('.listing__item--load-more').removeClass('mdl-cell--12-col');
       $('.listing__item--load-more').addClass('mdl-cell--8-col');
     break;
   }
}

 (function ($, loadMore) {
   'use strict';

   var pageMore = 'load-more.html',
   button = '.listing__item--load-more',
   container = '.listing--load-more .row .mdl-grid';

   loadMore.load = function () {
     var url = './' + pageMore;

     $.ajax({
       url: url,
       success: function (response) {

         if (!response || response.trim() == 'NONE') {
           $(button).fadeOut();
           return;
         }
         appendContent(response);
       },
       error: function (response) {
         $(button).text('There was an error. Please refresh the page.');
       }
     });
   };

   var appendContent = function (response) {
     $(container).append($(response), $(button));
     itemWidth();
   };

 })(jQuery, loadMore);

(function ($) {
  $('.filter__dropdown-toggle').click(function () {
    $(this).toggleClass('is-active');
    $(this).siblings('.filter__dropdown').toggleClass('is-active');
  });

  $('.listing__item--load-more').click(function () {
    loadMore.load();
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
  $('#license-search').on('keyup', function (event) {
    checkLicenseCategories();
  });

  // Filter on window load
  // Needed for license search filter.
  $(window).load(function() {
    checkLicenseCategories();
  });

})(jQuery);
