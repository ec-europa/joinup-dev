/**
 * @file
 * Joinup theme scripts.
 */

(function ($) {
  $('.filter__dropdown-toggle').click(function () {
    $(this).toggleClass('is-active');
    $(this).siblings('.filter__dropdown').toggleClass('is-active');
  });
})(jQuery);
