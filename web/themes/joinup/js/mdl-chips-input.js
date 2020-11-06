/**
 * @file
 * Support for chips in input fields.
 *
 * Based on `dkarv/mdl-chip-input`.
 *
 * @see https://github.com/dkarv/mdl-chip-input
 */

(function () {
  'use strict';

    var MaterialChipInput = function MaterialChipInput(element) {
        this.element_ = element;

        this.init();
    };
    window['MaterialChipInput'] = MaterialChipInput;

    MaterialChipInput.prototype.addChip_ = function (id, text) {
        let currentChipIds = this.getChipIds();
        if (currentChipIds.indexOf(id) > -1) {
            // Ignore duplicates.
            return;
        }
        let chip = document.createElement('span');
        chip.setAttribute('class', 'mdl-chip mdl-chip--deletable');
        chip.innerHTML =
            '<span class="mdl-chip__text" data-id="' + id + '">' + text + '</span>' +
            '<button type="button" class="mdl-chip__action">' +
            '<i class="material-icons">close</i></button>';
        let update = this.updateTargets_.bind(this);
        chip.getElementsByClassName('mdl-chip__action')[0].onclick = function () {
            chip.remove();
            update();
        };
        this.element_.insertBefore(chip, this.inputs_);
    };

    /**
     * Returns the IDs of the currently active chips.
     */
    MaterialChipInput.prototype.getChipIds = function () {
        var currentChipIds = [];
        var children = this.element_.children;
        for (var i = children.length; i--;) {
            if (children[i].classList.contains('mdl-chip')) {
                currentChipIds.unshift(children[i].children[0].getAttribute('data-id'));
            }
        }
        return currentChipIds;
    };

    /**
     * Updates the hidden value fields to match the currently active chips.
     *
     * This currently only supports deleting previously existing values if a
     * chip is deleted by the user.
     */
    MaterialChipInput.prototype.updateTargets_ = function () {
        var currentChipIds = this.getChipIds();
        var numTargets = this.targets_.length;
        var target;
        for (var i = numTargets - 1; i >= 0; i--) {
            target = this.targets_[i];
            if (!(currentChipIds.indexOf(target.value) > -1)) {
                target.remove();
            }
        }
        if (currentChipIds.length >= this.options_.maximum) {
            this.input_.style.display = 'none';
        }
        else {
            this.input_.style.display = 'block';
        }
    };

    MaterialChipInput.prototype.keyDown_ = function (event) {
        var code = event.which || event.keyCode;
        if (code === 8 && !this.input_.value) {
            // Remove last tag if input is empty.
            if (this.element_.children.length > 1) {
                this.element_.children[this.element_.children.length - 2].remove();
                this.updateTargets_();
            }
        }
    };

    MaterialChipInput.prototype.init = function () {
        if (this.element_) {
            this.inputs_ = this.element_.getElementsByClassName('inputs')[0];

            this.input_ = this.element_.getElementsByClassName('mdl-textfield__input')[0];
            this.input_.addEventListener('keydown', this.keyDown_.bind(this));
            this.targets_ = this.element_.getElementsByClassName('mdl-chipfield__input');

            // Set the default options.
            this.options_ = {
                maximum: Number.MAX_VALUE,
                separator: ','
            };

            // Initialize the chips.
            var length = this.targets_.length;
            var target;
            for (var i = 0; i < length; i++) {
                target = this.targets_[i];
                this.addChip_(target.value, target.getAttribute('data-description'));
            }
        }
    };
})();
