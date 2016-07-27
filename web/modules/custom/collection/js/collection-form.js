/**
 * @file
 * Joinup collection edit form script.
 */

(function ($) {
  "use strict";
  Drupal.behaviors.collection = {
    attach: function () {
      if ($('#edit-field-ar-closed-value').length && $('#edit-field-ar-elibrary-creation').length) {
        var option = null;
        var label = null;

        $('#edit-field-ar-closed-value').change(function () {
          // Append the previously removed option to the select, if any.
          if (option != null) {
            option.appendTo('#edit-field-ar-elibrary-creation');
          }

          if (label != null) {
            label.appendTo('div.slider__labels');
          }
          if ($(this).prop('checked') === true) {
            // If the collection is closed only options 0 and 1 should be
            // available.
            // Disabling option 2 if it exists.
            var option2 = $('option[value="2"]', '#edit-field-ar-elibrary-creation');
            var optionText = option2.text();
            // Select option 1 if option 2 is selected.
            if (option2.is(':selected')) {
              $('option[value="1"]', '#edit-field-ar-elibrary-creation').attr('selected', 'selected');
            }
            option2.attr('selected', false);
            option = option2.remove();
            if (optionText) {
              label = $('.slider__labels').find(':contains("' + optionText + '")').remove();
            }
          }
          else {
            // If the collection is opened only options 1 and 2 should be
            // available.
            // Disabling option 0 if it exists.
            var option0 = $('option[value="0"]', '#edit-field-ar-elibrary-creation');
            var optionText = option0.text();
            // Select option 1 if option 0 is selected.
            if (option0.is(':selected')) {
              $('option[value="1"]', '#edit-field-ar-elibrary-creation').attr('selected', 'selected');
            }
            option0.attr('selected', false);
            option = option0.remove();

            if (optionText) {
              label = $('.slider__labels').find(':contains("' + optionText + '")').remove();
            }
          }

          // Update the select slider to reflect only the available options.
          $('#slider').empty();
          var select = $('#edit-field-ar-elibrary-creation');
          var selectLength = select.find('option').length;
          var slider = $('#slider').slider({
            min: 1,
            max: selectLength,
            range: 'min',
            value: select[0].selectedIndex + 1,
            change: function (event, ui) {
              select.find('option').removeAttr('selected');
              $(select.find('option')[ui.value - 1]).attr('selected', 'selected');
            }
          });

          // Unbind click event from all slider labels.
          $(".slider__labels .slider__label").unbind("click");

          // Bind click to all sliderlabels.
          $(".slider__labels .slider__label").bind("click", function () {
            $("#slider").slider("value", $(this).index() + 1);
            $("#slider").trigger("slide");
          });
        }).trigger('change');
      }
    }
  };
})(jQuery);
