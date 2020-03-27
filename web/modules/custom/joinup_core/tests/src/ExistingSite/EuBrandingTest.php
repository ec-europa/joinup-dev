<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

/**
 * Tests EU branding elements.
 *
 * @group joinup_core
 */
class EuBrandingTest extends JoinupExistingSiteTestBase {

  /**
   * Tests that EU webtools global banner appears on all pages.
   */
  public function testEuGlobalBanner(): void {
    // Random picked up pages.
    $pages = [
      '<front>',
      '/contact',
      '/search',
      '/collections',
      '/solutions',
      '/keep-up-to-date',
    ];
    foreach ($pages as $page) {
      $this->drupalGet($page);
      $this->assertSession()->responseContains('<script src="//europa.eu/webtools/load.js?globan=111" defer></script>');
    }
  }

}
