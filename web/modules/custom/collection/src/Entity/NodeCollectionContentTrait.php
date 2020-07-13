<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\joinup_group\Entity\GroupContentTrait;

/**
 * Reusable methods for node collection content.
 */
trait NodeCollectionContentTrait {

  use GroupContentTrait;

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
