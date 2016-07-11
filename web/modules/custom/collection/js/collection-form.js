(function ($) {
    $(document).ready(function() {
        if ($('#edit-field-ar-closed-value').length && $('#edit-field-ar-elibrary-creation').length) {

            var option = null;
            var label = null;

            $('#edit-field-ar-closed-value').change(function () {

                // Append the previously removed option to the select, if any.
                if(option != null) {
                    option.appendTo('#edit-field-ar-elibrary-creation');
                }
                if(label != null) {
                    label.appendTo('div.slider__labels');
                }
                if ($(this).prop('checked') === true) {
                    // If the collection is closed only options 0 and 1 should be available.
                    // Disabling option 2 if it exists.
                    var option_text = $("option[value='2']", "#edit-field-ar-elibrary-creation").text();
                    // Select option 1 if option 2 is selected.
                    if($("option[value='2']", "#edit-field-ar-elibrary-creation").is(":selected")) {
                        $("option[value='1']", "#edit-field-ar-elibrary-creation").attr("selected", "selected");
                    }
                    option = $("option[value='2']", "#edit-field-ar-elibrary-creation").remove();
                    label = $(".slider__labels").find(":contains('" + option_text + "')").remove();
                } else {
                    // If the collection is opened only options 1 and 2 should be available.
                    // Disabling option 0 if it exists.
                    var option_text = $("option[value='0']", "#edit-field-ar-elibrary-creation").text();
                    // Select option 1 if option 0 is selected
                    if ($("option[value='0']", "#edit-field-ar-elibrary-creation").is(":selected")) {
                        $("option[value='1']", "#edit-field-ar-elibrary-creation").attr("selected", "selected");
                    }
                    option = $("option[value='0']", "#edit-field-ar-elibrary-creation").remove();
                    label = $(".slider__labels").find(":contains('" + option_text + "')").remove();
                }

                // Update the select slider to reflect only the available options.
                $("#slider").empty();
                var select = $("#edit-field-ar-elibrary-creation");
                var selectLength = select.find('option').length;
                var slider = $("#slider").slider({
                    min: 1,
                    max: selectLength,
                    range: "min",
                    value: select[0].selectedIndex + 1,
                    slide: function (event, ui) {
                        select.find("option").removeAttr("selected");
                        $(select.find("option")[ui.value -1]).attr("selected", "selected");
                    }
                });
            }).trigger('change');
        }
    });
})(jQuery);
