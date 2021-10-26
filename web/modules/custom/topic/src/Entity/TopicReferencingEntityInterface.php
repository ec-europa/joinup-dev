<?php

declare(strict_types = 1);

namespace Drupal\topic\Entity;

/**
 * Interface for entities that reference topics.
 */
interface TopicReferencingEntityInterface {

  /**
   * Returns the topics which are referenced by the entity.
   *
   * @return \Drupal\topic\Entity\TopicInterface[]
   *   The topics.
   */
  public function getTopics(): array;

}
