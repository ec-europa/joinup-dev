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
        var preventDoubleEnter = false;

        // Give focus to the autocomplete element to allow typing straight away.
        $element.trigger('focus');

        // Replace the select handler with a custom one that drops the supports
        // for multi-value selections and automatically presses the add button.
        $element.autocomplete('option', 'select', function (event, ui) {
          event.target.value = ui.item.value;
          // Avoid keydown events if the selection was submitted with the enter key.
          preventDoubleEnter = true;
          clickAddButton($element);

          return false;
        });
        $element.on('keydown.invite', function (event) {
          // 13 is the enter key.
          if (event.which === 13) {
            // When an autocomplete option is selected by pressing the enter key,
            // the add button has already been pressed in the select handler.
            // The original keydown event will be executed too, and there is no way to
            // understand if it is happening on the autocomplete text field or on the
            // suggestions. So we use a flag to avoid clicking twice on the button.
            // Note that Drupal.ajax should prevent this already, as the ajax request
            // should be still executing, but it's better to cover this explicitly.
            if (!preventDoubleEnter) {
              clickAddButton($element);
            }
            preventDoubleEnter = false;
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
