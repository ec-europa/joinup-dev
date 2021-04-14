<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity;

use Drupal\Core\Field\FieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * Defines a field item list class for the rdf_entity 'uuid' field.
 */
class RdfEntityUuidFieldItemList extends FieldItemList {

  use ComputedItemListTrait;

  /**
   * {@inheritdoc}
   */
  protected function computeValue() {
    if ($this->getEntity()->id()) {
      $this->list[0] = $this->createItem(0, [
        'value' => $this->getEntity()->id(),
      ]);
    }
  }

}
