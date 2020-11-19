<?php

declare(strict_types = 1);

namespace Drupal\eif;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * A helper class for EIF.
 */
class Eif implements EifInterface {

  /**
   * {@inheritdoc}
   */
  public static function getCategories(FieldStorageDefinitionInterface $definition, ?FieldableEntityInterface $entity = NULL, bool &$cacheable = TRUE): array {
    return static::EIF_CATEGORIES;
  }

}
