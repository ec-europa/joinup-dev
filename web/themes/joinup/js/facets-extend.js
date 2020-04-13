/**
 * @file
 * Extend facet javascript.
 */

(function ($, Drupal) {
  Drupal.behaviors.alterFactes = {
    attach: function (context, settings) {
      $(context).find('.facet-item').once('alterFacets').each(function () {
        var $label = $(this).find('label');
        var $input = $(this).find('.facets-checkbox');
        var $link = $(this).find('a');
        $label.prepend($input);
        $label.append($link);

        $label.addClass('mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect');
        $input.addClass('mdl-checkbox__input');
      });
    }
  };

})(jQuery, Drupal);
