/**
 * @file
 * Attaches the behaviors for the Field UI module.
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  /**
   * Attaches the fieldUIOverview behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the fieldUIOverview behavior.
   *
   * @see Drupal.fieldUIOverview.attach
   */
  Drupal.behaviors.fieldUIDisplayOverview = {
    attach: function (context, settings) {
      $(context).find('table#field-display-overview').once('field-display-overview').each(function () {
        Drupal.fieldUIOverview.attach(this, settings.fieldUIRowsData, Drupal.fieldUIDisplayOverview);
      });
    }
  };

  /**
   * Namespace for the field UI overview.
   *
   * @namespace
   */
  Drupal.fieldUIOverview = {

    /**
     * Attaches the fieldUIOverview behavior.
     *
     * @param {HTMLTableElement} table
     *   The table element for the overview.
     * @param {object} rowsData
     *   The data of the rows in the table.
     * @param {object} rowHandlers
     *   Handlers to be added to the rows.
     */
    attach: function (table, rowsData, rowHandlers) {
      var tableDrag = Drupal.tableDrag[table.id];

      // Add custom tabledrag callbacks.
      tableDrag.onDrop = this.onDrop;
      tableDrag.row.prototype.onSwap = this.onSwap;

      // Create row handlers.
      $(table).find('tr.draggable').each(function () {
        // Extract server-side data for the row.
        var row = this;
        if (row.id in rowsData) {
          var data = rowsData[row.id];
          data.tableDrag = tableDrag;

          // Create the row handler, make it accessible from the DOM row
          // element.
          var rowHandler = new rowHandlers[data.rowHandler](row, data);
          $(row).data('fieldUIRowHandler', rowHandler);
        }
      });
    },

    /**
     * Event handler to be attached to form inputs triggering a region change.
     */
    onChange: function () {
      var $trigger = $(this);
      var $row = $trigger.closest('tr');
      var rowHandler = $row.data('fieldUIRowHandler');

      var refreshRows = {};
      refreshRows[rowHandler.name] = $trigger.get(0);

      // Handle region change.
      var region = rowHandler.getRegion();
      if (region !== rowHandler.region) {
        // Remove parenting.
        $row.find('select.js-field-parent').val('');
        // Let the row handler deal with the region change.
        $.extend(refreshRows, rowHandler.regionChange(region));
        // Update the row region.
        rowHandler.region = region;
      }

      // Ajax-update the rows.
    },

    /**
     * Lets row handlers react when a row is dropped into a new region.
     */
    onDrop: function () {
      var dragObject = this;
      var row = dragObject.rowObject.element;
      var $row = $(row);
      var rowHandler = $row.data('fieldUIRowHandler');
      if (typeof rowHandler !== 'undefined') {
        var regionRow = $row.prevAll('tr.region-message').get(0);
        var region = regionRow.className.replace(/([^ ]+[ ]+)*region-([^ ]+)-message([ ]+[^ ]+)*/, '$2');

        if (region !== rowHandler.region) {
          // Let the row handler deal with the region change.
          var refreshRows = rowHandler.regionChange(region);
          // Update the row region.
          rowHandler.region = region;
          // Ajax-update the rows.
        }
      }
    },

    /**
     * Refreshes placeholder rows in empty regions while a row is being dragged.
     *
     * Copied from block.js.
     *
     * @param {HTMLElement} draggedRow
     *   The tableDrag rowObject for the row being dragged.
     */
    onSwap: function (draggedRow) {
      var rowObject = this;
      $(rowObject.table).find('tr.region-message').each(function () {
        var $this = $(this);
        // If the dragged row is in this region, but above the message row, swap
        // it down one space.
        if ($this.prev('tr').get(0) === rowObject.group[rowObject.group.length - 1]) {
          // Prevent a recursion problem when using the keyboard to move rows
          // up.
          if ((rowObject.method !== 'keyboard' || rowObject.direction === 'down')) {
            rowObject.swap('after', this);
          }
        }
        // This region has become empty.
        if ($this.next('tr').is(':not(.draggable)') || $this.next('tr').length === 0) {
          $this.removeClass('region-populated').addClass('region-empty');
        }
        // This region has become populated.
        else if ($this.is('.region-empty')) {
          $this.removeClass('region-empty').addClass('region-populated');
        }
      });
    }
  };

  /**
   * Row handlers for the 'Manage display' screen.
   *
   * @namespace
   */
  Drupal.fieldUIDisplayOverview = {};

  /**
   * Constructor for a 'field' row handler.
   *
   * This handler is used for both fields and 'extra fields' rows.
   *
   * @constructor
   *
   * @param {HTMLTableRowElement} row
   *   The row DOM element.
   * @param {object} data
   *   Additional data to be populated in the constructed object.
   *
   * @return {Drupal.fieldUIDisplayOverview.field}
   *   The field row handler constructed.
   */
  Drupal.fieldUIDisplayOverview.field = function (row, data) {
    this.row = row;
    this.name = data.name;
    this.region = data.region;
    this.tableDrag = data.tableDrag;

    // Attach change listener to the 'plugin type' select.
    this.$pluginSelect = $(row).find('select.field-plugin-type');
    this.$pluginSelect.on('change', Drupal.fieldUIOverview.onChange);

    return this;
  };

  Drupal.fieldUIDisplayOverview.field.prototype = {

    /**
     * Returns the region corresponding to the current form values of the row.
     *
     * @return {string}
     *   Either 'hidden' or 'content'.
     */
    getRegion: function () {
      return (this.$pluginSelect.val() !== 'undefined') ? this.$pluginSelect.val() : 'hidden';
    },

    /**
     * Reacts to a row being changed regions.
     *
     * This function is called when the row is moved to a different region, as
     * a result of either :
     * - A drag-and-drop action (the row's form elements then probably need to
     *   be updated accordingly).
     * - User input in one of the form elements watched by the
     *   {@link Drupal.fieldUIOverview.onChange} change listener.
     *
     * @param {string} region
     *   The name of the new region for the row.
     *
     * @return {object}
     *   A hash object indicating which rows should be Ajax-updated as a result
     *   of the change, in the format expected by
     *   {@link Drupal.fieldUIOverview.AJAXRefreshRows}.
     */
    regionChange: function (region) {

      // Replace dashes with underscores.
      region = region.replace(/-/g, '_');

      // Set the region of the select list.
      this.$pluginSelect.val(region);
    }
  };

})(jQuery, Drupal, drupalSettings);
