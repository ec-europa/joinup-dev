<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_document\ExistingSite;

use Drupal\Tests\joinup_core\ExistingSite\NodeWorkflowTestBase;

/**
 * Tests CRUD operations and workflow transitions for the document node.
 *
 * @group workflow
 */
class DocumentWorkflowTest extends NodeWorkflowTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle(): string {
    return 'document';
  }

}
