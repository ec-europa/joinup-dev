/**
 * @file
 * Display an 'outdated content' notice if case.
 */

(function ($, Drupal, drupalSettings) {

  /**
   * Shows a notice if the page displays an entity whose content is outdated.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Shows a notice if the page displays an entity whose content is outdated.
   */
  Drupal.behaviors.outdatedContent = {
    attach: function (context) {
      const outdatedTime = drupalSettings.outdatedContent.outdatedTime;
      if (outdatedTime !== null) {
        const currentTime = Math.round(Date.now() / 1000);
        if (currentTime > outdatedTime) {
          // One year: 60 * 60 * 24 * 365 = 31536000.
          const yearsOld = Math.floor((currentTime - drupalSettings.outdatedContent.publicationTime) / 31536000);
          const notice = $('<div>')
            .addClass('details__element')
            .addClass('outdated-content-notice')
            .text(Drupal.formatPlural(yearsOld,
              'This @bundle is more than 1 year old',
              'This @bundle is more than @count years old',
              {
                "@bundle": drupalSettings.outdatedContent.bundle,
              })
            );
          $('.page__title-wrapper').find('.details.after-title').append(notice);
        }
      }
    }
  };

})(jQuery, Drupal, drupalSettings);
