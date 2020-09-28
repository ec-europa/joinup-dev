<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Plugin\Field;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\TypedData\ComputedItemListTrait;

/**
 * A computed entity reference field item list.
 */
class CompatibilityDocumentLicenceFieldItemList extends EntityReferenceFieldItemList {

  use ComputedItemListTrait;

  /**
   * Returns the label as the field item.
   */
  protected function computeValue() {
    $this->list[0] = $this->createItem(0, $this->getEntity()->value);
  }

}
