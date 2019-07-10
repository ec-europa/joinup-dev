(function ($, Drupal) {
  Drupal.behaviors.joinupSubscriptionDashboardBehavior = {
    attach: function (context, settings) {
      // Whenever any of the checkboxes for a collection is clicked, enable the
      // submit button.
      $(context).find('input.form-checkbox').once('dashboard').each(function () {
        $(this).on('click', function () {
          $(this)
            .closest('.form__subscribe-types-inner')
            .find('input.button')
            .prop('disabled', false)
            .removeClass('is-disabled');
        });
      });
    }
  };
})(jQuery, Drupal);
