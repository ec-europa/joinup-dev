/**
 * @file
 * Attaches the behaviours to allow collapsing/expading of extra items in fieldset(like) elements.
 *
 * @see templates/field/field--fieldset--show-more.html.twig
 */

(function ($, Drupal) {

  /**
   * Attaches the show more functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the "show more" behaviors.
   */
  Drupal.behaviors.fieldsetShowMore = {
    attach: function (context) {
      $('.fieldset__field--show-more', context).once('fieldset-show-more').each(function () {
        var $fieldset = $(this);
        var $wrapper = $('.fieldset__extra-items', $fieldset);
        var $link = $('.fieldset__show-more-link', $fieldset);
        var $text = $('.fieldset__show-more-text', $link);
        var showLess = Drupal.t('Show less');
        var originalText = $text.text();

        // When JS is enabled, the extra items are hidden by default.
        $link.attr('aria-expanded', false);

        $link.on('click.show-more', function () {
          // If the "is-open" class is not present, the wrapper will be
          // opened.
          var toOpen = !$wrapper.hasClass('is-open');

          $text.text(toOpen ? showLess : originalText);
          $link
              .attr('aria-expanded', toOpen)
              .toggleClass('is-expanded', toOpen);
          $wrapper
            .toggleClass('is-open', toOpen)
            .toggle(300);

          return false;
        });
      });
    }
  };

})(jQuery, Drupal);
