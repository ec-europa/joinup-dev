/**
 * @file
 * Attaches a destination parameter to the sign in link towards the current page.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Alters the sign in link to have a destination parameter to the source page.
   *
   * The home page 'Sign in' link and the 'Sign in' page 'Sign in' link are not altered.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches shared content behaviors.
   */
  Drupal.behaviors.signInDestination = {
    attach: function (context) {
      var path = drupalSettings.path;
      if (path.isFront || path.currentPath === 'user/login') {
          return;
      }

      $(context).find('[data-drupal-link-system-path="user/login"]').once('sign-in-redirect').each(function () {
          var $this = $(this);
          if ($this.prop('href').indexOf('?destination=') === -1) {
              var query_string = (drupalSettings.path.currentQuery) ? '?' + $.param(drupalSettings.path.currentQuery) : '';
              var destination = encodeURIComponent(drupalSettings.path.currentPath + query_string)
              $this.prop('href', $this.prop('href') + '?destination=/' + destination);
          }
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
