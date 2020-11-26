<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow;

/**
 * Provide an interface for entities that can be moved in an 'archived' state.
 */
interface ArchivableEntityInterface extends EntityWorkflowStateInterface {

  /**
   * Checks whether the entity is archived.
   *
   * @return bool
   *   Whether this entity is archived.
   */
  public function isArchived(): bool;

}
