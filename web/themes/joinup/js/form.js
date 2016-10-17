/**
 * @file
 * Joinup theme scripts.
 */

(function ($, Drupal) {
  Drupal.behaviors.deleteButton = {
    attach: function (context, settings) {
      $(context).find('#edit-delete').once('deleteButton').each(function () {
        $(this).addClass('button--blue mdl-button mdl-js-button mdl-button--raised mdl-js-ripple-effect mdl-button--accent');
      });
    }
  };

  Drupal.behaviors.silderSelect = {
    attach: function (context, settings) {
      $(context).find('.slider__select').once('sliderSelect').each(function () {
        var $select = $(this);
        var selectLength = $select.find('option').length;

        var $slider = $("<div id='slider' class='slider__slider'></div>").insertAfter($select).slider({
          min: 1,
          max: selectLength,
          range: "min",
          value: $select[ 0 ].selectedIndex + 1,
          change: function (event, ui) {
            $select.find('option').removeAttr('selected');
            $($select.find('option')[ui.value - 1]).attr('selected', 'selected');
          }
        });
      });
    }
  };

  Drupal.behaviors.alterTableDrag = {
    attach: function (context, settings) {
      $(context).find('.tabledrag-handle').once('alterTableDrag').each(function () {
        $(this).addClass('draggable__icon icon icon--draggable');
      });
    }
  };

  Drupal.behaviors.ajaxReload = {
    attach: function (context, settings) {
      $(context).find('form').once('ajaxReload').each(function () {
        $(document).ajaxComplete(function (event, xhr, settings) {
          componentHandler.upgradeAllRegistered();
        });
      });
    }
  }
})(jQuery, Drupal);
