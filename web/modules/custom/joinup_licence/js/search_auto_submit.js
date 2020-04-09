/**
 * @file
 * Autocomplete additional behaviors for invite forms.
 */

(function ($, Drupal) {

  /**
   * Attaches the auto submit enhancements to the licence search select list.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the auto submit behaviors.
   */
  Drupal.behaviors.searchAutoSubmit = {
    attach: function (context) {
      $('.auto-submit', context).on('change', function (event) {
        const $value = $(event.target).val();
        if ($value !== '') {
          let new_pathname = document.location.pathname;
          new_pathname += ';' + $value;
          document.location.href = document.location.href.replace(document.location.pathname, new_pathname);
        }
      });
    },
  };

}(jQuery, Drupal));
