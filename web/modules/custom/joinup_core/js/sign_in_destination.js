/**
 * @file
 * Attaches a destination parameter to the sign in link to the current page.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Alters the sign in link to have a destination parameter to the source page.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the sign in destination behavior to the 'Sign in' link.
   */
  Drupal.behaviors.signInDestination = {
    attach: function (context) {
      var path = drupalSettings.path;
      // In these urls, if a destination is already set, we keep all query
      // parameters.
      var user_urls = ['user/login', 'user/register', 'user/password', 'user/logout'];
      var is_user_url = $.inArray(path.currentPath, user_urls) === 0;
      // By default, login will lead to the homepage.
      var query_string = 'destination=/homepage';
      // The query parameters of the page as an inline string.
      var inline_query_string = path.currentQuery ? '?' + $.param(path.currentQuery) : '';
      var has_destination = path.currentQuery && path.currentQuery['destination'];

      // For each link that points to the user login page, append the existing
      // destination or the page depending on where the user comes from.
      $(context).find('[href^="/user/login"]').once('sign-in-redirect').each(function () {
        if (has_destination) {
          // If a destination is already set and the url is not one of the user
          // urls, the destination is the only parameter maintained.
          // If the user is in one of the user_urls, or a destination
          // parameter exists, keep the current query parameters.
          query_string = is_user_url ? $.param(path.currentQuery) : 'destination=' + encodeURIComponent(path.currentQuery['destination']);
        }
        else {
          // If the user is not in one of the user urls, set the current page as
          // the destination parameter.
          query_string = 'destination=/' + encodeURIComponent(path.currentPath + inline_query_string);
        }
        // The 'search' link property is the query string of the url.
        this.search = query_string;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
