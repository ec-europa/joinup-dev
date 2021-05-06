import 'popper.js';
import 'bootstrap';

(function () {

  'use strict';

  Drupal.behaviors.helloWorld = {
    attach: function (context) {
      console.log('Hello World');
    }
  };

})(jQuery, Drupal);
