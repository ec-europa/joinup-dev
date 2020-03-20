<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_event\ExistingSite;

use Drupal\Tests\joinup_community_content\ExistingSite\NodeWorkflowTestBase;

/**
 * Tests CRUD operations and workflow transitions for the event node.
 *
 * @group workflow
 */
class EventWorkflowTest extends NodeWorkflowTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle(): string {
    return 'event';
  }

}
