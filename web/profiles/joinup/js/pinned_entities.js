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
    return '<div class="listing__corner"><span class="icon icon--pin"></span></div>';
  };

  /**
   * Adds visual cues for content pinned inside the parent collection.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches pinned entities behaviors.
   */
  Drupal.behaviors.pinnedEntities = {
    attach: function (context) {
      var group = $('[data-drupal-group-context]').data('drupal-group-context');

      // If there is no global collection context, bail out.
      if (!group) {
        return;
      }

      $(context).find('.is-pinned').once('pinned-cue').each(function () {
        var $this = $(this);
        var parent = $this.data('drupal-parent-id');
        var affiliated = $this.data('drupal-pinned-in') || '';

        // Show the cue only when the global collection context is the same
        // as the parent of the tile.
        if (parent !== group && affiliated.split(',').indexOf(group) === -1) {
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
