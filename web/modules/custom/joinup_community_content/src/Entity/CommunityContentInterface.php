<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Entity;

use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for community content entities.
 */
interface CommunityContentInterface extends NodeInterface, GroupContentInterface, CollectionContentInterface, EntityWorkflowStateInterface {
}
