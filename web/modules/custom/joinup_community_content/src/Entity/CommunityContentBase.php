<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Entity;

use Drupal\collection\Entity\NodeCollectionContentTrait;
use Drupal\joinup_bundle_class\JoinupBundleClassMetaEntityAccessTrait;
use Drupal\joinup_group\Entity\PinnableGroupContentTrait;
use Drupal\joinup_stats\Entity\StatisticsAwareTrait;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\node\Entity\Node;

/**
 * Base class for community content entities.
 *
 * @todo Once we are on PHP 7.3 we should no longer include
 *   JoinupBundleClassMetaEntityAccessTrait.
 */
class CommunityContentBase extends Node implements CommunityContentInterface {

  use EntityWorkflowStateTrait;
  use NodeCollectionContentTrait;
  use JoinupBundleClassMetaEntityAccessTrait;
  use PinnableGroupContentTrait;
  use StatisticsAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_state';
  }

}
