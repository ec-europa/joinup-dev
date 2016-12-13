/**
 * @file
 * Joinup theme scripts.
 */

(function ($) {
  $(function () {
    $('.sidebar-menu__button--desktop').on('click', function () {
      $(this).siblings('.mdl-menu__container').toggleClass('desktop-hidden');
    });
  });
})(jQuery);
