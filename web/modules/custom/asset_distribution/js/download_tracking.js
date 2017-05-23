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

        $this.on('click.joinup', function () {
          $(this).trigger('track_download.joinup');

          return false;
        });
      });
    }
  };

})(jQuery, Drupal);
