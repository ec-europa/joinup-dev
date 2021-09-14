<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Tests the Joinup configuration.
 *
 * @group joinup_core
 */
class ConfigTest extends JoinupExistingSiteTestBase {

  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $disableSpamProtection = FALSE;

  /**
   * Tests that the active and sync stores are the same.
   */
  public function testConfig(): void {
    $this->drush('config:status', [], ['format' => 'json']);
    $diff = array_keys((array) $this->getOutputFromJSON());

    // Check that there are no differences between database and config sync.
    $this->assertEmpty($diff, 'Differences between active and sync stores for: ' . implode(', ', $diff));
  }

}
