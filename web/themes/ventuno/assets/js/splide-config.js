/**
 * Splide.
 */

(function ($, Drupal) {
  Drupal.behaviors.splide = {
    attach: function (context) {
      var elms = document.getElementsByClassName( 'splide' );
      for ( var i = 0, len = elms.length; i < len; i++ ) {
        new Splide( elms[i], {
           type   : 'loop',
           perPage: 4,
           perMove: 1,
           pagination: false,
        } ).mount();
      }
    }
  };
})(jQuery, Drupal);
