<?php

declare(strict_types = 1);

namespace Drupal\solution\Plugin\WorkflowStatePermission;

use Drupal\Core\Entity\EntityInterface;
use Drupal\joinup_group\Plugin\WorkflowStatePermission\GroupWorkflowStatePermissionBase;
use Drupal\solution\Entity\SolutionInterface;

/**
 * Checks whether changing workflow states is permitted for a given user.
 *
 * Depending on the user role some workflow states are not available. For
 * example if a solution is in the 'validated' state a facilitator can only
 * change the state to 'proposed' or 'draft', while a moderator can change to
 * any state.
 *
 * @WorkflowStatePermission(
 *   id = "solution",
 * )
 */
class SolutionWorkflowStatePermission extends GroupWorkflowStatePermissionBase {

  /**
   * {@inheritdoc}
   */
  public function applies(EntityInterface $entity): bool {
    return $entity instanceof SolutionInterface;
  }

}
