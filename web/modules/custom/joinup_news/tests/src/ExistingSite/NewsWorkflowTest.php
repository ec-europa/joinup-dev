<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_news\ExistingSite;

use Drupal\Tests\joinup_community_content\ExistingSite\CommunityContentWorkflowTestBase;

/**
 * Tests CRUD operations and workflow transitions for the news node.
 *
 * @group workflow
 */
class NewsWorkflowTest extends CommunityContentWorkflowTestBase {

  /**
   * {@inheritdoc}
   */
  protected function getEntityBundle(): string {
    return 'news';
  }

}
