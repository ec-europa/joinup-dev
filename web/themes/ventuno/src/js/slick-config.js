/**
 * Slick.
 */
(function ($, Drupal) {
  Drupal.behaviors.splide = {
    attach: function (context) {
      var elms = document.getElementsByClassName('explore-slider');
      for (var i = 0, len = elms.length; i < len; i++) {
        $(elms[i]).slick({
          infinite: false,
          speed: 300,
          slidesToShow: 5,
          slidesToScroll: 1,
          responsive: [
            {
              breakpoint: 1024,
              settings: {
                slidesToShow: 4,
                slidesToScroll: 1,
                infinite: true,
                dots: true
              }
            },
            {
              breakpoint: 600,
              settings: {
                slidesToShow: 3,
                slidesToScroll: 1
              }
            },
            {
              breakpoint: 480,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 1
              }
            }
            // You can unslick at a given breakpoint now by adding:
            // settings: "unslick"
            // instead of a settings object
          ]
        });
      };
      // Refresh carousels position on tabs click.
      // This is a workaround to get correct size and positions on slides even on hidden elements.
      $(function(){
        $(".explore-block .nav-link").click(function(){
          $('.explore-slider').slick('setPosition');
        });  
      });

    }
  };
})(jQuery, Drupal);
