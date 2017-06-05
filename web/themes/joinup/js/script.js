/**
 * @file
 * Joinup theme scripts.
 */

(function ($) {
  $(function () {
    $('.sidebar-menu__button--desktop').on('click', function () {
      $(this).siblings('.mdl-menu__container').toggleClass('desktop-hidden');
    });

    // Always use the fullscreen indicator for ajax throbbers in the frontend.
    if (Drupal.Ajax) {
      Drupal.Ajax.prototype.setProgressIndicatorThrobber = Drupal.Ajax.prototype.setProgressIndicatorFullscreen;
    }
  });
})(jQuery);
