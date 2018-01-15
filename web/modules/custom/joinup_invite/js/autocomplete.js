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
        $element.on('keydown.invite', function (event) {
          if (event.which === 13) {
            $element.closest('form').find('input[name="add_user"]').trigger('mousedown');
          }
        });
      });
    },
    detach: function (context) {
      $('.invite-autocomplete', context).removeOnce('invite-autocomplete').off('keydown.invite');
    }
  };

}(jQuery, Drupal));
