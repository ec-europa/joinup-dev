/**
 * @file
 * Eif module behaviors.
 */

(function ($, Drupal) {
  Drupal.behaviors.Eif = {
    attach: function (context, settings) {
      // Get the select element.
      const recommendationSelector = $(context)
        .find('select[data-drupal-eif-recommendation-selector="true"]');
      // Get the parent form.
      const form = recommendationSelector.closest('form');
      // Bind the event handler.
      recommendationSelector
        .once('eif-recommendations')
        .change(function (event) {
          form.submit();
      });
    }
  };
})(jQuery, Drupal);
