<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;

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
      '/challenges',
      '/solutions',
      '/latest',
    ];
    foreach ($pages as $page) {
      $this->drupalGet($page);
      $this->assertSession()->responseContains('<script src="//europa.eu/webtools/load.js?globan=1110" defer></script>');
    }
  }

}
