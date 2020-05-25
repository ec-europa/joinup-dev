<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

/**
 * Reusable methods for node collection content.
 */
trait NodeCollectionContentTrait {

  /**
   * {@inheritdoc}
   */
  public function getCollection(): CollectionInterface {
    $group = $this->getGroup();
    if (!$group instanceof CollectionInterface) {
      return $group->getCollection();
    }
    /** @var \Drupal\collection\Entity\CollectionInterface $group */
    return $group;
  }

}
