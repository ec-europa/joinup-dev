<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drupal\joinup\Traits\ConfigReadOnlyTrait;
use Drupal\joinup\Traits\ErrorLevelTrait;

/**
 * Tests the Joinup error pages.
 *
 * @group joinup_core
 */
class JoinupErrorPageTest extends JoinupExistingSiteTestBase {

  use ConfigReadOnlyTrait;
  use ErrorLevelTrait;

  /**
   * Tests the Joinup error pages.
   */
  public function testErrorPage(): void {
    \Drupal::service('module_installer')->install(['error_page_test']);
    $session = $this->getSession();

    $this->setSiteErrorLevel('hide');

    $session->visit('/error_page_test/exception');
    $this->assertSession()->statusCodeEquals(500);

    \Drupal::service('module_installer')->uninstall(['error_page_test']);
  }

}
