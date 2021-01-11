/**
 * @file
 * JavaScript code for the redirections.
 */

(function ($, Drupal) {
  $(document).ready(function() {
    window.setTimeout(function() {
      window.location.href = drupalSettings.easme_helper.redirect.location;
    }, drupalSettings.easme_helper.redirect.timeout);
  });
})(jQuery, Drupal);
