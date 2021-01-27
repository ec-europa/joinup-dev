<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

/**
 * Common tasks for testing entity creation and cleanup.
 */
trait TestingEntitiesTrait {

  use SearchTrait;

  /**
   * Testing entities.
   *
   * Associative array keyed by entity type IDs and having a list of testing
   * entity instances as values. Each list is keyed by entity ID.
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface[][]
   */
  protected $entities = [];

  /**
   * Remove any created test entities.
   *
   * @AfterScenario
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   Thrown when any of the test entities could not be deleted.
   */
  public function cleanTestingEntities(): void {
    if (empty($this->entities)) {
      return;
    }

    // Since we might be cleaning up many entities, temporarily disable the
    // feature to commit the index after every query.
    $this->disableCommitOnUpdate();

    foreach ($this->entities as $entities) {
      if (!empty($entities)) {
        foreach ($entities as $entity) {
          $entity->skip_notification = TRUE;
          @$entity->delete();
        }
      }
    }
    $this->entities = [];
    $this->enableCommitOnUpdate();
  }

}
