/**
 * @file
 * Licence comparer functionality.
 */

(function ($, drupalSettings) {
  "use strict";

  var settingsElement = document.querySelector('script[type="application/json"][data-drupal-selector="licence-comparer-data"]');
  var dataLicenceComparer = JSON.parse(settingsElement.textContent);

  function buildComparerModal() {
    var modal = $('<div class="comparer-modal"></div>');
    modal.hide();
    $('body').append(modal);
  }

  function showComparerModal(content) {
    $('.comparer-modal').html(content['description']);
    $(".comparer-modal").dialog({
      modal: true,
      title: content['title'],
      buttons: {
        "Licence text": function () {window.location.href = content['spdxUrl'];}
      },
      width: 'auto',
      resizable: false,
      draggable: false,
      create: function (event, ui) {
        $(event.target).parent().css('position', 'fixed');
      },
      classes: {
        "ui-dialog": "licence-comparer__dialog"
      }
    });
  }

  $('.licence-comparer__header .icon--info').on('click', function (event) {
    var dataId = $(this).parent().attr('data-licence-id');
    var content = dataLicenceComparer[dataId];
    showComparerModal(content);
  });

  $(window).on('load', function () {
    buildComparerModal();
  });

})(jQuery, drupalSettings);
