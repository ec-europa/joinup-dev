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

  $(function () {
    $('.tab--more').on('click', function () {
      $(this).closest('.tabs--content-type').siblings('.filters').toggleClass('is-visible');
      $(this).find('.tab__icon').toggleClass('icon--arrow-down icon--arrow-up');
    });
  });
})(jQuery);
