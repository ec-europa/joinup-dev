<?php

declare(strict_types = 1);

namespace Drupal\joinup_workflow;

/**
 * Provide an interface for entities that can be moved in an 'archived' state.
 */
trait ArchivableEntityTrait {

  /**
   * {@inheritdoc}
   */
  public function isArchived(): bool {
    assert($this instanceof ArchivableEntityInterface, 'Classes using ' . __TRAIT__ . ' should implement \Drupal\joinup_workflow\ArchivableEntityInterface');
    return $this->getWorkflowState() === 'archived';
  }

}
