/**
 * Slick.
 */
(function ($, Drupal) {
  Drupal.behaviors.splide = {
    attach: function (context) {
      var elms = document.getElementsByClassName('explore-slider');
      for (var i = 0, len = elms.length; i < len; i++) {
        $(elms[i]).slick({
          infinite: true,
          speed: 300,
          slidesToShow: 5,
          swipeToSlide: true,
          arrows: true,
          appendArrows:  $(elms[i]).prev().prev('.append-buttons'),
          nextArrow: '<button type="button" class="slick-controls slick-next rounded-circle"><span class="icon">&nbsp;</span><span class="visually-hidden">Next</span></button>',
          prevArrow: '<button type="button" class="slick-controls slick-prev rounded-circle"><span class="icon">&nbsp;</span><span class="visually-hidden">Previous</span></button>',
          responsive: [
            {
              breakpoint: 1399,
              settings: {
                slidesToShow: 4,
                slidesToScroll: 4,
                swipeToSlide: false
              }
            },
            {
              breakpoint: 1199,
              settings: {
                slidesToShow: 3,
                slidesToScroll: 3,
                swipeToSlide: false
              }
            },
            {
              breakpoint: 991,
              settings: {
                slidesToShow: 2,
                slidesToScroll: 2,
                swipeToSlide: false
              }
            },
            {
              breakpoint: 767,
              settings: {
                slidesToShow: 1,
                slidesToScroll: 1,
                swipeToSlide: false
              }
            },
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
