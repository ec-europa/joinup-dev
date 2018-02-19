<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Traits;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Trait to obtain the display value for field items.
 */
trait FieldItemsTrait {

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
   *   A display value extracted from the field item.
   */
  protected function getFieldItemDisplayValue(FieldItemInterface $item): string {
    if ($item instanceof EntityReferenceItem && !empty($item->entity)) {
      return $item->entity->label();
    }

    return (string) $item->{$item::mainPropertyName()};
  }

  /**
   * Gets the display values of all items in a field item list.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The entity field item list.
   *
   * @return array
   *   A list of display values extracted from the field.
   */
  protected function getFieldItemListDisplayValues(FieldItemListInterface $items): array {
    return array_map([$this, 'getFieldItemDisplayValue'], iterator_to_array($items));
  }

  /**
   * Extracts the main property values of a field item list.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $list
   *   The field item list.
   *
   * @return array
   *   An array of values.
   */
  protected function getFieldItemListMainPropertyValues(FieldItemListInterface $list): array {
    $values = [];

    foreach ($list as $delta => $item) {
      /** @var \Drupal\Core\Field\FieldItemInterface $item */
      $values[$delta] = $item->get($item::mainPropertyName())->getValue();
    }

    return $values;
  }

  /**
   * Returns the value of the main property of a field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   The field item.
   *
   * @return mixed
   *   The field item value.
   */
  protected function getFieldItemMainPropertyValue(FieldItemInterface $item) {
    return $item->get($item::mainPropertyName())->getValue();
  }

}
