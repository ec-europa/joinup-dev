/**
 * Splide.
 */

(function ($, Drupal) {
  Drupal.behaviors.splide = {
    attach: function (context) {
      var elms = document.getElementsByClassName( 'splide' );
      for ( var i = 0, len = elms.length; i < len; i++ ) {
        new Splide( '.splide', {
           perPage: 3,
           perMove: 1,
           pagination: false,
           breakpoints: {
            980: {
              perPage: 2,
            },
            640: {
              perPage: 1,
            },
          }
        } ).mount();
      }
    }
  };
})(jQuery, Drupal);
