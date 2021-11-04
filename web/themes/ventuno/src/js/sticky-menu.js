/**
 * Sticky menu.
 */
Drupal.behaviors.stickyMenu = {
  attach: function () {
    // Make sure Webtools is done modifying the DOM
    window.addEventListener('wtReady', function () {
      const navbar = document.getElementById('joinup-navbar');
      let navbarOffsetTop = navbar.offsetTop;
      function recalculateOffset() {
        navbarOffsetTop = navbar.offsetTop;
        // Remove events related to cookie banner interaction
        window.removeEventListener('cck_all_accepted', recalculateOffset)
        window.removeEventListener('cck_technical_accepted', recalculateOffset)
        window.removeEventListener('cck_banner_hidden', recalculateOffset)
      }
      // Add events related to cookie banner interaction
      window.addEventListener('cck_all_accepted', recalculateOffset)
      window.addEventListener('cck_technical_accepted', recalculateOffset)
      window.addEventListener('cck_banner_hidden', recalculateOffset)
      // Run function on user scroll
      window.addEventListener('scroll', function() {
        if (window.pageYOffset > navbarOffsetTop) {
          navbar.classList.add('js-is--sticky');
          // add the required padding top to show content behind navbar
          document.body.style.paddingTop = navbar.offsetHeight + 'px';
        } else {
          navbar.classList.remove('js-is--sticky');
          // remove padding top from body
          document.body.style.paddingTop = '0';
        } 
      });
    }, { once: true });
  }
};
