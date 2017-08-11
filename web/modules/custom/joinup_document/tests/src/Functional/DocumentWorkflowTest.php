<?php

namespace Drupal\Tests\joinup_document\Functional;

use Drupal\Tests\joinup_core\Functional\NodeWorkflowTestBase;

/**
 * Tests CRUD operations and workflow transitions for the document node.
 *
 * @group workflow
 */
class DocumentWorkflowTest extends NodeWorkflowTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle() {
    return 'document';
  }

}
