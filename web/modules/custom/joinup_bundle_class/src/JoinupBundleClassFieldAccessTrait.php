<?php

declare(strict_types = 1);

namespace Drupal\joinup_bundle_class;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\Exception\MissingDataException;

/**
 * Reusable methods for accessing fields in entity bundle classes.
 */
trait JoinupBundleClassFieldAccessTrait {

  /**
   * Returns the first field item for the field with the given name.
   *
   * @param string $field_name
   *   The name of the field for which to return the item.
   *
   * @return \Drupal\Core\Field\FieldItemInterface|null
   *   The field item.
   */
  protected function getFirstItem(string $field_name): ?FieldItemInterface {
    try {
      $item = $this->get($field_name)->first();
      if (!empty($item) && $item instanceof FieldItemInterface && !$item->isEmpty()) {
        return $item;
      }
    }
    catch (MissingDataException $e) {
      return NULL;
    }

    return NULL;
  }

  /**
   * Returns the main value of the first field item for the given field.
   *
   * @param string $field_name
   *   The name of the field for which to return the main value.
   *
   * @return mixed|null
   *   The value, or NULL if no value has been set.
   */
  protected function getMainPropertyValue(string $field_name) {
    $item = $this->getFirstItem($field_name);

    if (!empty($item)) {
      $property = $item->mainPropertyName();
      return $item->$property;
    }

    return NULL;
  }

}
