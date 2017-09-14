/**
 * @file
 * Attaches the behaviours to show visual cues on pinned community content.
 */

(function ($, Drupal) {

  /**
   * Theme function that renders a visual cue for pinned content.
   *
   * @returns {string}
   *   The HTML for the pinned content visual cue.
   */
  Drupal.theme.pinnedContentCue = function () {
    return '<div class="listing__corner listing__corner--pin"><span class="icon icon--pin"></span></div>';
  };

  /**
   * Adds visual cues for content pinned inside the parent collection.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches pinned content behaviors.
   */
  Drupal.behaviors.pinnedContent = {
    attach: function (context) {
      var collection = $('[data-drupal-collection-context]').data('drupal-collection-context');

      // If there is no global collection context, bail out.
      if (!collection) {
        return;
      }

      $(context).find('[data-drupal-parent-id].is-sticky').once('pinned-cue').each(function () {
        var $this = $(this);
        var parent = $this.data('drupal-parent-id');

        // Show the cue only when the global collection context is the same
        // as the parent of the tile.
        if (parent !== collection) {
          return;
        }

        $this.addClass('listing__card--corner');
        // It is needed for listing title padding if image doesn't exist.
        if (!$this.find('.listing__image').length) {
          $this.addClass('listing__card--corner-title');
        }
        $this.prepend(Drupal.theme('pinnedContentCue'));
      });
    }
  };

})(jQuery, Drupal);
