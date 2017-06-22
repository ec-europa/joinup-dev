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
  public function isPublishedState($state) {
    $states = [
      'validated',
      'needs_update',
      'deletion_request',
    ];

    return in_array($state, $states);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle() {
    return 'news';
  }

}
