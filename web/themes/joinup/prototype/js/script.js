/**
 * @file
 * Joinup theme scripts.
 */
 var loadMore = loadMore || {};

 function itemWidth() {
   var itemsCounter = $('.listing--load-more').children('.listing__item').length;

   switch(itemsCounter % 3) {
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

 (function($, loadMore) {
   "use strict";

   var pageMore = "load-more",
   button = ".listing__item--load-more",
   loading = ".listing__loading-div",
   container = ".listing--load-more";

   loadMore.load = function() {
     var url = "./" + pageMore;
     $(button).hide();
     $(loading).show();

     $.ajax({
       url: url,
       success: function(response) {
         if (!response || response.trim() == "NONE") {
           $(button).fadeOut();
           return;
         }
         appendContent(response);
       },
       error: function(response) {
         $(loading).text("There was an error. Please refresh the page.");
       }
     });
   };

   var appendContent = function(response) {
     $(loading).hide();
     $(container).append($(response), $(button));
     itemWidth();
     $(button).show();
   };

 })(jQuery, loadMore);

(function ($) {
  $('.filter__dropdown-toggle').click(function () {
    $(this).toggleClass('is-active');
    $(this).siblings('.filter__dropdown').toggleClass('is-active');
  });

  $('.listing__item--load-more .load-more').click(function () {
    loadMore.load();
  });

})(jQuery);
