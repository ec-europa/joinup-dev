<?php

namespace Drupal\joinup_migrate;

use Drupal\migrate\Row;

/**
 * Facilitates field translation for source plugins.
 */
interface FieldTranslationInterface {

  /**
   * Sets the source 'i18n' property in the migration row.
   *
   * @param \Drupal\migrate\Row $row
   *   The migration row.
   */
  public function setFieldTranslations(Row &$row);

  /**
   * Returns the fields that need translation, in a structured way.
   *
   * @return array[]
   *   Associative array keyed by destination field. Each value is an
   *   associative array with the next keys:
   *   - table: The table to be queried for this field translations.
   *   - field: The field in 'table' that contains the serialized translation.
   *   - sub_field: The field containing the translation in the serialized blob.
   */
  public function getTranslatableFields();

}
