/**
 * @file
 * Joinup theme scripts.
 */

(function ($, Drupal) {
  Drupal.behaviors.sidebarMenu = {
    attach: function (context, settings) {
      $(context).find('.sidebar-menu__button--desktop').once('sidebarMenu').each(function () {
        $(this).on('click', function () {
          $(this).siblings('.mdl-menu__container').toggleClass('desktop-hidden');
        });
      });
    }
  };
})(jQuery, Drupal);
