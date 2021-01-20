<?php

declare(strict_types = 1);

namespace Drupal\collection\Plugin\WorkflowStatePermission;

use Drupal\Core\Entity\EntityInterface;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\joinup_group\Plugin\WorkflowStatePermission\GroupWorkflowStatePermissionBase;

/**
 * Checks whether changing workflow states is permitted for a given user.
 *
 * Depending on the user role some workflow states are not available. For
 * example if a collection is in the 'validated' state a facilitator can only
 * change the state to 'proposed' or 'draft', while a moderator can change to
 * any state.
 *
 * @WorkflowStatePermission(
 *   id = "collection",
 * )
 */
class CollectionWorkflowStatePermission extends GroupWorkflowStatePermissionBase {

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity): bool {
    return $entity instanceof CollectionInterface;
  }

}
