<?php

declare(strict_types = 1);

namespace Drupal\joinup_bundle_class;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\meta_entity\Entity\MetaEntityInterface;

/**
 * Reusable methods for accessing fields in entity bundle classes.
 */
trait JoinupBundleClassMetaEntityAccessTrait {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * Returns the meta entity that is referenced in the given field.
   *
   * @param string $field_name
   *   The name of the computed field that references the meta entity.
   *
   * @return \Drupal\meta_entity\Entity\MetaEntityInterface
   *   The meta entity.
   *
   * @throws \InvalidArgumentException
   *   Thrown when the field does not reference a meta entity.
   */
  protected function getMetaEntity(string $field_name): MetaEntityInterface {
    $item = $this->getFirstItem($field_name);

    if (!$item instanceof EntityReferenceItem) {
      $type = gettype($item) === 'object' ? get_class($item) : gettype($item);
      $message = sprintf('The field %s on entity %s is expected to be a reference to a meta entity but it is a %s', $field_name, $this->id(), $type);
      throw new \InvalidArgumentException($message);
    }

    $target_type = $item->getDataDefinition()->getSetting('target_type') ?? 'unknown';
    if ($target_type !== 'meta_entity') {
      $message = sprintf('The field %s on entity %s is expected to reference a meta entity but it references an entity of type %s', $field_name, $this->id(), $target_type);
      throw new \InvalidArgumentException($message);
    }

    if ($item->isEmpty()) {
      $message = sprintf('The field %s on entity %s is expected to reference a meta entity but it is empty.', $field_name, $this->id());
      throw new \InvalidArgumentException($message);
    }

    return $item->entity;
  }

}
