<?php

declare(strict_types = 1);

namespace Drupal\workflow_state_permission;

use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for plugins that determine permission to update workflow states.
 */
interface WorkflowStatePermissionPluginInterface extends WorkflowStatePermissionInterface {

  /**
   * Determines whether the plugin handles the given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the plugin handles the given entity, FALSE otherwise.
   */
  public function applies(EntityInterface $entity): bool;

}
