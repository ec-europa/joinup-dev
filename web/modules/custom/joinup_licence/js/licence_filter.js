/**
 * @file
 * Licence filter functionality.
 */

(function ($, drupalSettings) {
  "use strict";
  function checkLicenceCategories() {
    var $licenceTile = $('.licence-tile');

    // Remove hidden class before further processing.
    $licenceTile.each(function () {
      $(this).removeClass('is-hidden');
    });

    // Check every active filter item and hide tiles which don't contain proper
    // data-licence-category.
    $('.licence-filter__item a.is-active').each(function () {
      var currentlicenceCategory = $(this).attr('data-licence-category');
      if (typeof currentlicenceCategory !== 'undefined') {
        $licenceTile.each(function () {
          var licenceCategory = $(this).attr('data-licence-category');
          if (typeof licenceCategory !== 'undefined') {
            var licenceCategoryArray = licenceCategory.split(' ');
            if ($.inArray(currentlicenceCategory, licenceCategoryArray) < 0 && !$(this).hasClass('is-hidden')) {
              $(this).addClass('is-hidden');
            }
          }
        });
      }
    });

    // Check licence search field value and hide tiles which don't contain
    // proper data-spdx.
    var licenceTiles = 0;
    var currentSpdxId = $('#licence-search').val().toLowerCase();
    $licenceTile.each(function () {
      if (currentSpdxId.length > 0) {
        var spdxId = $(this).attr('data-spdx').toLowerCase();
        if (spdxId.indexOf(currentSpdxId) === -1 && !$(this).hasClass('is-hidden')) {
          $(this).addClass('is-hidden');
        }
      }

      // Count not hidden tiles.
      if (!$(this).hasClass('is-hidden')) {
        licenceTiles++;
      }
    });

    // Show calculated number of tiles.
    $('.licence-counter__number').text(licenceTiles);
  }

  // Disable or enable all compare checkboxes.
  function disableCheckboxes(state) {
    $('.licence-tile .mdl-checkbox').each(function (event, element) {
      if (!$(this).find('input').prop('checked')) {
        $(this).find('input').prop('disabled', state);
        element.MaterialCheckbox.updateClasses_();
      }
    });
  }

  // Enable or disable all compare buttons.
  function enableButtons(state) {
    var btnSelector = $( ".licence-tile__button--compatible" ).length ?
                      '.licence-tile__button--compatible' :
                      '.licence-tile__button--compare';
    if (state) {
      $(btnSelector + '.licence-tile__button--disabled').each(function () {
        $(this).removeClass('licence-tile__button--disabled');
      });
    }
    else {
      $(btnSelector).each(function () {
        $(this).addClass('licence-tile__button--disabled');
      });
    }
  }

  // Check status of compare elements and refresh.
  function checkCompareStatus() {
    var licencesString = "";
    var maxCompare = 5;
    var licencesArray = [];
    var $licenceListing = $('.listing--licences');
    var licenceName = "";

    $('.licence-tile .mdl-checkbox__input').each(function () {
      if ($(this).prop('checked')) {

        licenceName = $(this).attr('data-licence-name');
        licencesArray.push(licenceName);
        licencesString = JSON.stringify(licencesArray);

        if (licencesArray.length >= maxCompare) {
          disableCheckboxes(true);
        }

        if (licencesArray.length >= 2) {
          enableButtons(true);
        }
      }
    });

    $licenceListing.attr('data-licence-compare', licencesString);
  }

  // Trigger if licence filter is clicked.
  $('.licence-filter__item a').each(function () {
    $(this).on('click', function (event) {
      event.preventDefault();

      $(this).toggleClass('is-active');

      checkLicenceCategories();
    });
  });

  // Cancel the 'Enter' key of the filter input. Entered key is cancelled on
  // keypress, not on keyup.
  $('#licence-search').on('keypress', function (event) {
    var keyCode = event.keyCode || event.which;
    if (keyCode === 13) {
      event.preventDefault();
      return false;
    }
  })
  // Trigger the update on any key.
  .on('keyup', function (event) {
    checkLicenceCategories();
  });

  // Reset licence listing.
  $('#licence-reset').on('click', function (event) {
    $('.licence-filter__item a.is-active').removeClass('is-active');
    $('.licence-search__input input').val('');
    $('.licence-search__input .mdl-js-textfield')[0].MaterialTextfield.checkDirty();
    checkLicenceCategories();
    // Also, uncheck any selected licences checked for comparison and disable
    // the "Compare" buttons. The 'data-licence-compare' attribute which is set
    // to [], is the attribute that stores the licences set-up for comparison.

    if ($('.licence-tile__button--compatible').length) {
      $('.licence-tile .mdl-js-radio').each(function (event, element) {
        $(this)[0].MaterialRadio.uncheck();
      });
    } else {
      $('.licence-tile .mdl-js-checkbox').each(function () {
        $(this)[0].MaterialCheckbox.uncheck();
      });
    }
    $('.listing--licences').attr('data-licence-compare', '[]');
    enableButtons(false);
  });

  // Change compare elements if the checkbox is clicked.
  $('.licence-tile .mdl-checkbox__input').each(function () {
    $(this).on('click', function () {
      var $licenceListing = $('.listing--licences');
      var licences = $licenceListing.attr('data-licence-compare');
      var licenceName = $(this).attr('data-licence-name');
      var licencesString = "";
      var licencesArray = [];
      var maxCompare = drupalSettings.licenceComparer.maxLicenceCount;
      if (licences.length > 0) {
        licencesArray = JSON.parse(licences);
      }

      if ($(this).prop('checked')) {
          licencesArray.push(licenceName);
          licencesString = JSON.stringify(licencesArray);

        if (licencesArray.length === maxCompare) {
          disableCheckboxes(true);
        }

        if (licencesArray.length === 2) {
          enableButtons(true);
        }
      }
      else {
        if (licencesArray.length > 0) {
          licencesArray = JSON.parse(licences);
          licencesArray = licencesArray.filter(function (value) {
            return value !== licenceName;
          });
          licencesString = JSON.stringify(licencesArray);
        }

        if (licencesArray.length === maxCompare - 1) {
          disableCheckboxes(false);
        }

        if (licencesArray.length < 2) {
          enableButtons(false);
        }
      }

      $licenceListing.attr('data-licence-compare', licencesString);
    });
  });

  $('.licence-tile__button--compare').each(function () {
    $(this).on('click', function (event) {
      event.preventDefault();
      var licences = $('.listing--licences').attr('data-licence-compare');
      if (licences.length > 0) {
        var licencesArray = JSON.parse(licences);
        window.location.href = drupalSettings.licenceComparer.path + '/' + licencesArray.join(';');
      }
    });
  });

  function initCompatibleRadios() {
    $('.listing__item--compatibility .mdl-js-radio').each(function () {
      // UnCheck radios on page load.
      $(this).find('input').prop('checked', false);

      // Change compatible elements if the radio is clicked.
      $(this).on('click', function () {
        var inbound_licence = $("input[name='inbound-licence']:checked").attr('data-licence-name');
        var distribute_licence = $("input[name='outbound-licence']:checked").attr('data-licence-name');

        if(inbound_licence && distribute_licence) {
          var compatible_url = drupalSettings.path.baseUrl + "licence/compatibility-check/" + inbound_licence + "/" + distribute_licence;
          $('.licence-tile__button--compatible').attr('href', compatible_url);
          enableButtons(true);
        }
      });
    });
  }

  // Filter on window load. Needed for licence search filter.
  $(window).on('load', function () {
    checkLicenceCategories();
    checkCompareStatus();
    initCompatibleRadios();
  });

})(jQuery, drupalSettings);
