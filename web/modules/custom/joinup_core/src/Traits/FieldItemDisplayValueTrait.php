<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Traits;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Trait to obtain the display value for field items.
 */
trait FieldItemDisplayValueTrait {

  /**
   * Gets the display value for a field item.
   *
   * For entity references, it returns the referenced entity label.
   *
   * @todo Add support for options fields.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The entity field item.
   *
   * @return string
   *   The value to be used in the violation message.
   */
  protected function getFieldItemDisplayValue(FieldItemInterface $item): string {
    if ($item instanceof EntityReferenceItem && !empty($item->entity)) {
      return $item->entity->label();
    }

    return (string) $item->{$item::mainPropertyName()};
  }

}
