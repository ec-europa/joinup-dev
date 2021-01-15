<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Entity;

use Drupal\collection\Entity\NodeCollectionContentTrait;
use Drupal\joinup_bundle_class\JoinupBundleClassMetaEntityTrait;
use Drupal\joinup_featured\FeaturedContentTrait;
use Drupal\joinup_group\Entity\PinnableGroupContentTrait;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\joinup_stats\Entity\StatisticsAwareTrait;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\node\Entity\Node;

/**
 * Base class for community content entities.
 *
 * @todo Once we are on PHP 7.3 we should no longer include
 *   JoinupBundleClassMetaEntityTrait.
 */
abstract class CommunityContentBase extends Node implements CommunityContentInterface {

  use EntityWorkflowStateTrait;
  use FeaturedContentTrait;
  use NodeCollectionContentTrait;
  use JoinupBundleClassMetaEntityTrait;
  use PinnableGroupContentTrait;
  use StatisticsAwareTrait;

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_state';
  }

  /**
   * {@inheritdoc}
   */
  public function getPinnableGroups(): array {
    try {
      $group = $this->getGroup();
    }
    catch (MissingGroupException $e) {
      return [];
    }
    return [$group->id() => $group];
  }

  /**
   * {@inheritdoc}
   */
  public function getPinnableGroupIds(): array {
    try {
      $id = $this->getGroupId();
    }
    catch (MissingGroupException $e) {
      return [];
    }
    return [$id];
  }

}
