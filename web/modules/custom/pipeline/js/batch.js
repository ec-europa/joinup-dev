/**
 * @file
 * Pipeline module batch support.
 *
 * This file is a slightly-changed copy of web/core/misc/batch.js file, adapted
 * to the needs of Pipeline module.
 *
 * @see web/core/misc/batch.js
 */

(function ($, Drupal) {
  Drupal.behaviors.batch = {
    attach: function attach(context, settings) {
      var batch = settings.batch;
      var $progress = $('[data-drupal-progress]').once('batch');
      var progressBar = void 0;

      function updateCallback(progress, status, pb) {
        if (progress === 100) {
          pb.stopMonitoring();
          window.location = batch.uri;
        }
      }

      function errorCallback(pb) {
        $progress.prepend($('<p class="error"></p>').html(batch.errorMessage));
        $('#wait').hide();
      }

      if ($progress.length) {
        progressBar = new Drupal.ProgressBar('updateprogress', updateCallback, 'GET', errorCallback);
        progressBar.setProgress(batch.percentage, batch.initMessage, batch.initLabel);
        progressBar.startMonitoring(batch.uri, 10);

        $progress.empty();

        $progress.append(progressBar.element);
      }
    }
  };
})(jQuery, Drupal);
