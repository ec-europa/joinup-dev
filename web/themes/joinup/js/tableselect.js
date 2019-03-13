/**
 * @file
 * Attaches behaviour to handle the core tableselect functionality with the Material Design Lite library.
 */

(function ($, Drupal) {

  /**
   * Attaches the tableselect MDL functionality.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the "tableselect extra" behaviors.
   */
  Drupal.behaviors.tableSelectMDL = {
    attach: function (context, settings) {
      $(context).find('th.select-all').closest('table').once('table-select-mdl').each(function () {
        var $table = $(this);

        componentHandler.upgradeAllRegistered();

        $table.find('th.select-all')
          .find('input[type="checkbox"]')
          .addClass('mdl-checkbox__input')
          .wrap('<label class="mdl-checkbox mdl-js-checkbox mdl-js-ripple-effect"></label>')
          .on('change', function () {
            $table.find('.mdl-js-checkbox').each(function (index, element) {
              element.MaterialCheckbox.updateClasses_();
            });
          });
      });
    }
  };

})(jQuery, Drupal);
