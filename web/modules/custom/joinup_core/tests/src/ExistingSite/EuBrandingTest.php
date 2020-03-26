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
   * Tests that EU webtools global banner appears only on the home page.
   */
  public function testEuGlobalBanner(): void {
    $this->drupalGet('<front>');
    $this->assertSession()->responseContains('<script src="//europa.eu/webtools/load.js?globan=111" defer></script>');

    // Random picked up pages.
    $pages = [
      '/contact',
      '/search',
      '/collections',
      '/solutions',
      '/keep-up-to-date',
      '/search',
    ];
    foreach ($pages as $page) {
      $this->drupalGet($page);
      $this->assertSession()->responseNotContains('?globan=111" defer></script>');
    }
  }

}
