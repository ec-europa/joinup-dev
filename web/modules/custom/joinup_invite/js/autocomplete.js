/**
 * @file
 * Autocomplete additional behaviors for invite forms.
 */

(function ($, Drupal) {

  /**
   * Attaches the invite autocomplete enhancements to all required fields.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the invite autocomplete behaviors.
   * @prop {Drupal~behaviorDetach} detach
   *   Detaches the invite autocomplete behaviors.
   */
  Drupal.behaviors.inviteAutocomplete = {
    attach: function (context) {
      $('.invite-autocomplete', context).once('invite-autocomplete').each(function () {
        var $element = $(this);

        // Give focus to the autocomplete element to allow typing straight away.
        $element.trigger('focus');

        // Replace the select handler with a custom one that drops the supports
        // for multi-value selections and automatically presses the add button.
        $element.autocomplete('option', 'select', function (event, ui) {
          event.target.value = ui.item.value;
          clickAddButton($element);

          return false;
        });
        $element.off('keydown.invite').on('keydown.invite', function (event) {
          if (event.which === 13) {
            clickAddButton($element);
          }
        });
      });
    },
    detach: function (context) {
      $('.invite-autocomplete', context).removeOnce('invite-autocomplete').off('keydown.invite');
    }
  };

  /**
   * Triggers the click on the add user button for the autocomplete.
   *
   * @param {jQuery} $element
   *   The jQuery collection representing the autocomplete field.
   */
  function clickAddButton($element) {
    $element.closest('form').find('input[name="add_user"]').trigger('mousedown');
  }

}(jQuery, Drupal));
