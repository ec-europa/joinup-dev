/**
 * @file
 * Attaches a destination parameter to the sign in link to the current page.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Alters the sign in link to have a destination parameter to the source page.
   *
   * The home page 'Sign in' link and the 'Sign in' page 'Sign in' link are not
   * altered.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches shared content behaviors.
   */
  Drupal.behaviors.signInDestination = {
    attach: function (context) {
      var path = drupalSettings.path;
      // In these urls, only the query parameters will be copied if there is
      // no destination parameter set.
      var user_urls = ['user/login', 'user/register', 'user/password', 'user/logout'];
      var is_user_url = $.inArray(path.currentPath, user_urls) === 0;
      // By default, login will lead to the homepage.
      var query_string = 'destination=/homepage';
      // The query parameters of the page as an inline string.
      var inline_query_string = path.currentQuery ? $.param(path.currentQuery) : '';
      var has_destination = path.currentQuery && path.currentQuery['destination'];

      // For each link that points to the user login page, append the existing
      // destination or the page depending on where the user comes from.
      $(context).find('[href^="/user/login"]').once('sign-in-redirect').each(function () {
        // If the user is not in one of the user_urls, and there is no
        // destination already set, set the current url as destination.
        if (is_user_url && has_destination) {
          query_string = $.param(path.currentQuery);
        }
        else if (!is_user_url && !has_destination) {
          query_string = 'destination=/' + encodeURIComponent(path.currentPath + inline_query_string);
        }
        // The .search is the query string of the url.
        this.search = query_string;
      });
    }
  };

})(jQuery, Drupal, drupalSettings);
