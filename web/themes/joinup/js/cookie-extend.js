/**
 * @file
 * Cookie banner extended script.
 */

(function ($) {
  // Add custom class if page has cookie banner.
  $(window).bind("load", function() {
    if (window.bannerDisplayed) {
      $('body').addClass('has-cookie-consent-banner');
    }
  });
})(jQuery);