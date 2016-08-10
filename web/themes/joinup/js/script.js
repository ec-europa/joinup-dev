/**
 * @file
 * Joinup theme scripts.
 */

 jQuery(document).ready(function ($) {
   $('#edit-delete').addClass('button--default button--blue mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--accent');

   $.each($('.slider__select'), function (index, value) {
     var select = $(this);
     var selectLength = $(this).find('option').length;

     var slider = $("<div id='slider' class='slider__slider'></div>").insertAfter(select).slider({
        min: 1,
        max: selectLength,
        range: "min",
        value: select[ 0 ].selectedIndex + 1,
        change: function (event, ui) {
          select.find('option').removeAttr('selected');
          $(select.find('option')[ui.value - 1]).attr('selected', 'selected');
        }
      });
   });
 });
