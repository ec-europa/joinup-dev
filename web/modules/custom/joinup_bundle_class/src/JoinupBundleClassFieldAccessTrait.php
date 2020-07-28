<?php

declare(strict_types = 1);

namespace Drupal\joinup_bundle_class;

use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceFieldItemList;

/**
 * Reusable methods for accessing fields in entity bundle classes.
 */
trait JoinupBundleClassFieldAccessTrait {

  /**
   * Returns the entities that are referenced by the field with the given name.
   *
   * @param string $field_name
   *   The name of the field for which to return the entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects keyed by field item deltas.
   */
  protected function getReferencedEntities(string $field_name): array {
    try {
      $item_list = $this->getEntityReferenceFieldItemList($field_name);
    }
    catch (\InvalidArgumentException $e) {
      $this->logException($e);
      return [];
    }

    return $item_list->referencedEntities();
  }

  /**
   * Returns the entity IDs that are referenced by the given field.
   *
   * This currently only supports the basic entity reference fields from Drupal
   * core. Support for dynamic entity reference fields still needs to be added.
   *
   * @param string $field_name
   *   The name of the field for which to return the entity IDs.
   *
   * @return array[]
   *   An array of entity IDs, keyed by entity type ID.
   */
  protected function getReferencedEntityIds(string $field_name): array {
    try {
      $item_list = $this->getEntityReferenceFieldItemList($field_name);
    }
    catch (\InvalidArgumentException $e) {
      $this->logException($e);
      return [];
    }

    if ($item_list->isEmpty()) {
      return [];
    }

    $ids = [];

    if ($item_list instanceof DynamicEntityReferenceFieldItemList) {
      // @todo Add support for dynamic entity references when needed.
      // @see \Drupal\dynamic_entity_reference\Plugin\Field\FieldType\DynamicEntityReferenceFieldItemList::referencedEntities()
      throw new \InvalidArgumentException(__METHOD__ . ' does not support dynamic entity references yet.');
    }

    $target_type = $item_list->getFieldDefinition()->getSetting('target_type');

    foreach ($item_list as $item) {
      if ($item instanceof EntityReferenceItem) {
        try {
          if ($target_id = $item->get('target_id')->getValue() ?? NULL) {
            $ids[$target_type][] = $target_id;
          }
        }
        catch (MissingDataException $e) {
        }
      }
    }

    return $ids;
  }

  /**
   * Returns the item list for the entity reference field with the given name.
   *
   * @param string $field_name
   *   The name of the field for which to return the item list.
   *
   * @return \Drupal\Core\Field\EntityReferenceFieldItemListInterface
   *   The field item list.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the field is not an entity reference field.
   */
  protected function getEntityReferenceFieldItemList(string $field_name): EntityReferenceFieldItemListInterface {
    $item_list = $this->get($field_name);
    assert($item_list, sprintf('Field name %s is not defined.', $field_name));

    if (!$item_list instanceof EntityReferenceFieldItemListInterface) {
      $type = gettype($item_list) === 'object' ? get_class($item_list) : gettype($item_list);
      $message = sprintf('The field %s on collection %s is expected to return an EntityReferenceFieldItemList but got a %s', $field_name, $this->id(), $type);
      throw new \InvalidArgumentException($message);
    }

    return $item_list;
  }

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
    $item_list = $this->get($field_name);
    assert($item_list, sprintf('Field name %s is not defined.', $field_name));

    try {
      $item = $item_list->first();
      if (!empty($item) && $item instanceof FieldItemInterface && !$item->isEmpty()) {
        return $item;
      }
    }
    catch (MissingDataException $e) {
      $this->logException($e);
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

  /**
   * Logs an error containing the message from the given exception.
   *
   * @param \Exception $e
   *   The exception for which to log an error.
   */
  protected function logException(\Exception $e): void {
    \Drupal::logger($this->getEntityTypeId())->error($e->getMessage());
  }

}
