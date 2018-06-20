<?php

namespace Drupal\Tests\joinup_news\Functional;

use Drupal\Tests\joinup_core\Functional\NodeWorkflowTestBase;

/**
 * Tests CRUD operations and workflow transitions for the news node.
 *
 * @group workflow
 */
class NewsWorkflowTest extends NodeWorkflowTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle() {
    return 'news';
  }

}
