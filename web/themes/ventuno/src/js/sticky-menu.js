/**
 * Sticky menu.
 */
Drupal.behaviors.stickyMenu = {
  attach: function () {
    // Wait for Webtools to finish their job modifying the DOM
    window.addEventListener('wtReady', function () {
      const 
        navbar = document.getElementById('joinup-navbar'),
        navbarInitialOffsetTop = navbar.offsetTop;
      window.addEventListener('scroll', function() {
        if (window.pageYOffset > navbarInitialOffsetTop) {
          navbar.classList.add('js-is--sticky');
          // add the required padding top to show content behind navbar
          document.body.style.paddingTop = navbar.offsetHeight + 'px';
        } else {
          navbar.classList.remove('js-is--sticky');
          // remove padding top from body
          document.body.style.paddingTop = '0';
        } 
      });
    });
  }
};
