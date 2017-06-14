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
      // Sets the fullscreen progress indicator.
      Drupal.Ajax.prototype.setProgressIndicatorFullscreen = function () {
        this.progress.element = $('<div class="mdl-spinner mdl-js-spinner mdl-spinner--single-color is-active"></div>');
        $('body').after(this.progress.element);
        componentHandler.upgradeAllRegistered();
      }
      Drupal.Ajax.prototype.setProgressIndicatorThrobber = Drupal.Ajax.prototype.setProgressIndicatorFullscreen;
    }
  });
})(jQuery);
