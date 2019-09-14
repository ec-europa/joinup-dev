<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\ExistingSite;

use weitzman\DrupalTestTraits\ExistingSiteBase;

/**
 * Base class for Joinup  ExistingSite tests.
 */
class JoinupExistingSiteTestBase extends ExistingSiteBase {

  /**
   * {@inheritdoc}
   */
  public function tearDown(): void {
    // Make sure we don't send any notifications during test entities cleanup.
    foreach ($this->cleanupEntities as $delta => $entity) {
      $this->cleanupEntities[$delta]->skip_notification = TRUE;
    }
    parent::tearDown();
  }

}
