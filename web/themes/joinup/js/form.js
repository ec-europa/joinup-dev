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

  // Fix vertical tabs on the form pages.
  Drupal.behaviors.verticalTabsGrid = {
    attach: function (context, settings) {
      $(context).find('.vertical-tabs').once('verticalTabsGrid').each(function () {
        // Add mdl grid classes.
        $(this).find('.vertical-tabs__menu').addClass('mdl-cell mdl-cell--2-col mdl-cell--2-col-tablet mdl-cell--4-col-phone mdl-cell--order-2-phone');
        $(this).find('.vertical-tabs__panes').addClass('mdl-cell mdl-cell--8-col mdl-cell--6-col-tablet mdl-cell--4-col-phones mdl-cell--order-1-phone');
        $(this).addClass('mdl-grid mdl-grid--no-spacing');

        // Move description from pane to tab.
        $(this).find('.vertical-tabs__pane').each(
          function () {
            var summary = $(this).find('.vertical-tabs__details-summary').text();
            var summaryIndex = $(this).index();
            var $menuItem = $(this).closest('.vertical-tabs').find('.vertical-tabs__menu-item').get(summaryIndex - 1);
            $($menuItem).find('.vertical-tabs__menu-item-summary').text(summary);
          });
      });
    }
  };

  // Fix vertical tabs on the form pages.
  Drupal.behaviors.verticalTabsMobile = {
    attach: function (context, settings) {
      $(context).find('.vertical-tabs__menu-item--mobile').once('verticalTabsMobile').each(function () {
          $(this).first().addClass('is-selected');
          $(this).first().next('.vertical-tabs__pane').addClass('is-active');
          $(this).on('click', function(e) {
            e.preventDefault();
            $(this).toggleClass('is-selected');
            $(this).next('.vertical-tabs__pane').toggleClass('is-active');
          });
      });
    }
  };

  // Behaviors for tab validation.
  Drupal.behaviors.fieldGroupTabsValidation = {
    attach: function (context, settings) {
      // Keep a flag to focus only the first one in case of multiple tabs with
      // validation errors.
      var alreadyTriggered = false;

      $('.field-group-tabs-wrapper :input', context).once('tabValidation').each(function () {
        this.addEventListener('invalid', function (e) {
          // Open any hidden parents first.
          $(e.target).parents('details').each(function () {
            var $fieldGroup = $(this);
            if (!alreadyTriggered && $fieldGroup.data('verticalTab')) {
              $fieldGroup.data('verticalTab').tabShow();
              alreadyTriggered = true;
            }
          });
        }, false);
      });

      $('.field-group-tabs-wrapper', context).each(function () {
        $(this).siblings('.form-actions').find('.form-submit').on('click', function () {
          alreadyTriggered = false;
        });
      });
    }
  };

})(jQuery, Drupal);
