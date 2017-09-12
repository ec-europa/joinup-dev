/**
 * @file
 * Attaches the behaviours to mark tiles as featured site-wide.
 */

(function ($, Drupal) {

  /**
   * Adds a class to featured tiles when not in scope of a collection.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches site-wide featured behaviors.
   */
  Drupal.behaviors.siteWideFeatured = {
    attach: function (context) {
      var collection = $('[data-drupal-collection-context]').data('drupal-collection-context');

      // If there is a global collection context, bail out. We mark tiles as
      // globally featured only when we have no context, meaning we are in a
      // "global" page (search, collections, solutions, content).
      if (collection) {
        return;
      }

      $(context).find('[data-drupal-featured]').once('featured').addClass('is-featured');
    }
  };

})(jQuery, Drupal);
