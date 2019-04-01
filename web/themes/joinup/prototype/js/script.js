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
  $('.license-filter__item a').each(function () {
    $(this).on('click', function (event) {
      event.preventDefault();

      var currentClass = $(this).attr('class');
      if (typeof currentClass !== 'undefined') {
        var classes = currentClass.split(' ');
        $('.license-tile').each(function () {
          if ($(this).find('.' + classes[0]).length === 0) {
            if (classes.length > 1) {
              $(this).removeClass('is-hidden');
            }
            else if (!$(this).hasClass('is-hidden')) {
              $(this).addClass('is-hidden');
            }
          }
        });
      }

      $('.license-filter__item a.is-active').each(function () {
        var currentClass = $(this).attr('class');
        if (typeof currentClass !== 'undefined') {
          var classes = currentClass.split(' ');

          $('.license-tile').each(function () {
            if ($(this).find('.' + classes[0]).length === 0) {
              if (classes.length <= 1 && !$(this).hasClass('is-hidden')) {
                $(this).addClass('is-hidden');
              }
            }
          });
        }
      });

      $(this).toggleClass('is-active');
      var licenseTiles = 0;
      $('.license-tile').each(function () {
        if (!$(this).hasClass('is-hidden')) {
          licenseTiles++;
        }
      });

      $('.license-counter__number').text(licenseTiles);

    });
  });

})(jQuery);
