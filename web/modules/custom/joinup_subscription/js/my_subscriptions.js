(function ($, Drupal) {
  Drupal.behaviors.joinupSubscriptionMySubscriptionsBehavior = {
    attach: function (context, settings) {
      // Whenever any of the checkboxes for a collection is clicked, enable the
      // submit button.
      $(context).find('.form-select').each(function () {
        $(this)
          .on('change', function () {
            $(this)
              .closest('.field--name-field-user-frequency')
              .find('input.button')
              .prop('disabled', false)
              .prop('value', 'Save changes')
              .removeClass('is-disabled');
          });
      });
      $(context).find('input.form-checkbox').once('dashboard').each(function () {
        $(this)
            .on('click', function () {
              $(this)
                  .closest('.form__subscribe-types-inner')
                  .find('input.button')
                  .prop('disabled', false)
                  .prop('value', 'Save changes')
                  .removeClass('is-disabled');
            });
      });
    }
  };
})(jQuery, Drupal);
