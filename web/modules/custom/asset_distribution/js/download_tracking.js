/**
 * @file
 * Attaches the behaviors for the asset distribution download tracking module.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Tracks downloads of distributions.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches distribution download tracking functionality.
   */
  Drupal.behaviors.assetDistributionDownloadTracking = {
    attach: function (context, settings) {
      $(context).find('a.track-download').once('track-download').each(function () {
        var $this = $(this);
        var url = $this.data('tracking');

        Drupal.ajax({
          url: url,
          base: $this.attr('id'),
          event: 'track_download.joinup',
          progress: false,
          element: this,
          dialogType: 'modal'
        });

        $this.on('click', function (event) {
          $(this).trigger('track_download.joinup');
          // IE11 misbehaves when clicking the link and the default behaviour
          // is not working.
          // For IE11, force the click. The following check ensures that this is
          // IE and fails for Edge (where the click event behaves properly).
          // @see https://stackoverflow.com/a/21825207.
          if (!!window.MSInputMethodContext && !!document.documentMode) {
            window.location.href = $(this).prop('href');
          }
        });
      });
    }
  };

})(jQuery, Drupal);
