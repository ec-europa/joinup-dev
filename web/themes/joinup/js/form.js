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

  // Fix vertical tabs on the form pages.
  Drupal.behaviors.verticalTabsGrid = {
    attach: function (context, settings) {
      $(context).find('.vertical-tabs').once('verticalTabsGrid').each(function () {
        // Add mdl grid classes.
        $(this).find('.vertical-tabs__menu').addClass('mdl-cell mdl-cell--4-col mdl-cell--2-col-tablet mdl-cell--4-col-phone mdl-cell--order-2-phone');
        $(this).find('.vertical-tabs__panes').addClass('mdl-cell mdl-cell--8-col mdl-cell--6-col-tablet mdl-cell--4-col-phone mdl-cell--order-1-phone');
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

  // Autosize textareas.
  Drupal.behaviors.autosizeTextarea = {
    attach: function (context, settings) {
      $(context).find('textarea').once('autosizeTextarea').each(function () {
        autosize($(this));
      });
    }
  };

})(jQuery, Drupal);
