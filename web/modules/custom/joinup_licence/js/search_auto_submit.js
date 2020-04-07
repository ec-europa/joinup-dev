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
      $('.auto_submit', context).on('change', function (event) {
        var $value = $(event.target).val();

        // In order to ensure that the original values are maintained or at
        // least, that no malformed string is passed, check if the value
        // selected matches the same pattern as the requirements of the route.
        // Note: The + symbol is maintained here compared to the route
        // requirements where it is assumed that it has been converted to a
        // space character.
        var pattern = /^[a-zA-Z0-9][a-zA-Z0-9.\+-]+$/;
        if (pattern.test($value)) {
          var new_pathname = document.location.pathname;
          new_pathname += ';' + $value;
          document.location.href = document.location.href.replace(document.location.pathname, new_pathname);
        }
      });
    },
  };

}(jQuery, Drupal));
