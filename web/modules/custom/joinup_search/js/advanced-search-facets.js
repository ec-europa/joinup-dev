/**
 * @file
 * Use Slim Select JS.
 */

(function ($, Drupal) {
  Drupal.behaviors.advancedSearchFacets = {
    attach(context) {
      $(context).find('select[data-drupal-facet-id]').once('advancedSearchFacets').each(function () {
        const $selectElement = $(this);
        const facetId = $selectElement.attr('data-drupal-facet-id');
        if (facetId === 'topic') {
          $selectElement.find('option').each(function () {
            let $option = $(this);
            if ($option.text().substr(0, 1) === ' ') {
              // @todo According to https://slimselectjs.com/options > options,
              //   the widget should inherit classes from the <select>, but this
              //   seems not to work.
              $option.addClass('parent');
            }
          })
        }

        new SlimSelect({
          select: 'select[data-drupal-facet-id="' + facetId + '"]',
          showSearch: false
        })
      });
    }
  };
})(jQuery, Drupal);
