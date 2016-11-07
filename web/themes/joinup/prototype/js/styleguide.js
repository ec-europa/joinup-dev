/**
 * @file
 * Joinup theme scripts.
 */

(function ($) {
  $(window).on('styleguide:onRendered', function(e) {
    componentHandler.upgradeAllRegistered();
  });
})(jQuery);
