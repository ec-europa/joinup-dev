/**
 * @file
 * Attaches the behaviours to mark tiles as featured site-wide.
 */

(function ($, Drupal) {

  /**
   * Theme function that renders a visual cue for featured content.
   *
   * @returns {string}
   *   The HTML for the featured content visual cue.
   */
  Drupal.theme.featuredContentCue = function () {
    return '<div class="listing__corner listing__corner--featured"><span class="icon icon--star"></span></div>';
  };

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

      $(context).find('[data-drupal-featured]').once('featured').each(function () {
        var $this = $(this);
        $this.addClass('is-featured');
        $this.addClass('listing__card--corner');

        if (!$this.find('.listing__image').length) {
          $this.addClass('listing__card--corner-title');
        }
        $this.prepend(Drupal.theme('featuredContentCue'));
      });
    }
  };

})(jQuery, Drupal);
