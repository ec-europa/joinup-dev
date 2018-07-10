/**
 * @file
 * Pipeline module batch support.
 *
 * This file is a slightly-changed copy of web/core/misc/batch.js file, adapted
 * to the needs of Pipeline module.
 *
 * @see web/core/misc/batch.js
 */

(function ($, Drupal, drupalSettings) {
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

      function errorCallback(message) {
        $progress.append($('<p class="error"></p>').html(message));
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

  $.extend(Drupal.ProgressBar.prototype, {
    // Override ProgressBar.displayError() to handle messages in our way.
    displayError(string) {
      $('.page-title').text(drupalSettings.batch.errorPageTitle);
      var message = drupalSettings.batch.errorMessage;
      var error = $('<div class="messages messages--error"></div>').html(message);
      $(this.element).before(error).hide();

      if (this.errorCallback) {
        this.errorCallback(string);
      }
    }
  });

})(jQuery, Drupal, drupalSettings);
