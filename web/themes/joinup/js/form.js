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

  // Overridden MDL Checkbox classes.
  MaterialCheckbox.prototype.CssClasses_ = {
    INPUT: 'mdl-checkbox__input',
    BOX_OUTLINE: 'mdl-checkbox__box-outline',
    FOCUS_HELPER: 'mdl-checkbox__focus-helper',
    TICK_OUTLINE: 'mdl-checkbox__tick-outline',
    RIPPLE_EFFECT: 'mdl-js-ripple-effect',
    RIPPLE_IGNORE_EVENTS: 'mdl-js-ripple-effect--ignore-events',
    RIPPLE_CONTAINER: 'mdl-checkbox__ripple-container',
    RIPPLE_CENTER: 'mdl-ripple--center',
    RIPPLE: 'mdl-ripple',
    IS_FOCUSED: '', // Overridden line.
    IS_DISABLED: 'is-disabled',
    IS_CHECKED: 'is-checked',
    IS_UPGRADED: 'is-upgraded'
  };

  Drupal.behaviors.ajaxReload = {
    attach: function (context, settings) {
      $(context).find('form').once('ajaxReload').each(function () {
        $(document).ajaxComplete(function (event, xhr, settings) {
          componentHandler.upgradeAllRegistered();
          $('.mdl-js-checkbox').each(function (index, element) {
            element.MaterialCheckbox.updateClasses_();
          })
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
            var summaryIndex = $(this).index() / 2;
            var $menuItem = $(this).closest('.vertical-tabs').find('.vertical-tabs__menu-item').get(summaryIndex - 1);

            $($menuItem).find('.vertical-tabs__menu-item-summary').text(summary);
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

              // Handle validation for mobile tabs.
              mobileTabSelected = $(this).prev('.vertical-tabs__menu-item--mobile');
              $(mobileTabSelected).addClass('is-selected');
              $('.vertical-tabs__menu-item--mobile').not(mobileTabSelected).removeClass('is-selected');

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

  // Handle vertical tabs on mobile.
  Drupal.behaviors.verticalTabsMobile = {
    attach: function (context, settings) {
      $(context).find('.vertical-tabs__menu-item--mobile').once('verticalTabsMobile').each(function () {
        var $this = $(this);
        var hrefSelected = $('.vertical-tabs__menu .vertical-tabs__menu-item.is-selected a').attr('href');

        if ($this.find('a').attr('href') == hrefSelected) {
          $this.addClass('is-selected');
        }

        $this.on('click', function (event) {
          var $this = $(this);
          var href = $this.find('a').attr('href');

          event.preventDefault();

          $('.vertical-tabs__menu .vertical-tabs__menu-item a[href="' + href + '"]').trigger('click');
          if (!$this.hasClass('is-selected')) {
            $this.addClass('is-selected');
            $('.vertical-tabs__menu-item--mobile').not(this).removeClass('is-selected');
          }
        });
      });

      $(context).find('.vertical-tabs__menu-item').not('.vertical-tabs__menu-item--mobile').once('verticalTabsDesktop').on('click', function () {
        var href = $(this).find('a').attr('href');
        var mobileTabSelected = $('.vertical-tabs__menu-item--mobile a[href="' + href + '"]').closest('div');

        // Synchronize mobile and desktop tabs.
        $(mobileTabSelected).addClass('is-selected');
        $('.vertical-tabs__menu-item--mobile').not(mobileTabSelected).removeClass('is-selected');
      });
    }
  };

  // Forwards a click on a share modal row to the related checkbox.
  Drupal.behaviors.checkShareBoxRow = {
    attach: function (context, settings) {
      $(context).find('.share-box__row').once('checkShareBoxRow').each(function () {
        $(this).on('click', function (event) {
          // Avoid calling click() twice due to event propagation.
          if ($(event.target).is('input[type="checkbox"]')) {
            return;
          }

          $(this).find('input[type="checkbox"]').click();
        });
      });
    }
  };

})(jQuery, Drupal);
