<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

/**
 * Interface for entities that are collection content.
 *
 * This comprises community content, custom pages, and solutions.
 */
interface CollectionContentInterface {

  /**
   * Returns the collection to which this entity belongs.
   *
   * @return \Drupal\collection\Entity\CollectionInterface
   *   The collection.
   *
   * @throws \Drupal\collection\Exception\MissingCollectionException
   *   Thrown when the collection has not been set on the entity.
   */
  public function getCollection(): CollectionInterface;

}
