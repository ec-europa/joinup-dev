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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mail configuration has been changed during tests run in order to prevent
    // sending emails outside and catch messages for test assertion purpose. As
    // this setup creates a difference between the active and the sync store, we
    // restore the active store mail configuration for the purpose of this test.
    // @see \Drupal\Tests\joinup_test\ExistingSite\JoinupExistingSiteTestBase::setUp()
    $this->restoreMailSettings();
  }

  /**
   * Tests that the active and sync stores are the same.
   */
  public function testConfig(): void {
    $this->drush('config:status', [], ['format' => 'json']);
    $diff = array_keys((array) $this->getOutputFromJSON());

    // Check that there are no differences between database and config sync.
    $this->assertEmpty($diff, 'Differences between active and sync stores for: ' . implode(', ', $diff));
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    // Re-enable mail collection.
    $this->startMailCollection();
    parent::tearDown();
  }

}
