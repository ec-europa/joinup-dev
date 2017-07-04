/**
 * @file
 * Attaches the behaviours to show visual cues on shared community content.
 */

(function ($, Drupal) {

  /**
   * Theme function that renders a visual cue for shared content.
   *
   * @param {string} collection
   *   The collection name.
   *
   * @returns {string}
   *   The HTML for the shared content visual cue.
   */
  Drupal.theme.sharedContentCue = function (collection) {
    return '<div class="listing__stat" title="Shared from ' + collection + '"><div class="listing__icon icon icon--share"></div></div>'
  };

  /**
   * Adds visual cues for content shared from a collection to another.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches shared content behaviors.
   */
  Drupal.behaviors.sharedContent = {
    attach: function (context) {
      var collection = $('[data-drupal-collection-context]').data('drupal-collection-context');

      // If there is no global collection context, bail out.
      if (!collection) {
        return;
      }

      $(context).find('[data-drupal-shared-from-id]').once('shared-cue').each(function () {
        var $this = $(this);
        var parent = $this.data('drupal-shared-from-id');

        // If the content comes from the current global collection context,
        // do not add any cue.
        if (parent === collection) {
          return;
        }

        $(this).append(Drupal.theme('sharedContentCue', $this.data('drupal-shared-from-label')));
      });
    }
  };

})(jQuery, Drupal);
