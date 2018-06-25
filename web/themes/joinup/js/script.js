/**
 * @file
 * Joinup theme scripts.
 */

(function ($) {
  $(function () {
    $('.sidebar-menu__button--desktop').on('click', function () {
      $(this).siblings('.mdl-menu__container').toggleClass('is-hidden');
    });

    // Add classes to tour button.
    $('.block-tour-button-block .tour-button').each(function () {
      $(this).addClass('mdl-button mdl-js-button mdl-button--icon');
      $(this).html('<span class="icon icon--help"></span>');
      $(this).attr('title', 'Tour');
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
