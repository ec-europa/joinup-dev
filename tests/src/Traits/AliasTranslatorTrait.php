<?php

declare(strict_types = 1);

namespace Drupal\joinup\Traits;

/**
 * Helper methods for translating human readable aliases to machine names.
 */
trait AliasTranslatorTrait {

  /**
   * Translates human readable field names to machine names.
   *
   * @param string $field_name
   *   The human readable field name. Case insensitive.
   * @param array $aliases
   *   An array mapping aliases to field names.
   *
   * @return string
   *   The machine name of the field.
   *
   * @throws \Exception
   *   Thrown when an unknown field name is passed.
   */
  protected static function translateFieldNameAlias(string $field_name, array $aliases): string {
    $field_name = strtolower($field_name);
    if (array_key_exists($field_name, $aliases)) {
      $field_name = $aliases[$field_name];
    }
    else {
      throw new \Exception("Unknown field name '$field_name'.");
    }

    return $field_name;
  }

}
