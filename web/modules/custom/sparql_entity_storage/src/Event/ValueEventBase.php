<?php

namespace Drupal\sparql_entity_storage\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Base class for events dispatched when storing/loading values from storage.
 */
abstract class ValueEventBase extends Event {

  /**
   * The entity type id.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The field name.
   *
   * @var string
   */
  protected $field;

  /**
   * The field column value.
   *
   * @var string
   */
  protected $value;

  /**
   * The field language. Defaults to NULL.
   *
   * @var string|null
   */
  protected $lang;

  /**
   * The field column. Defaults to NULL.
   *
   * @var string|null
   */
  protected $column;

  /**
   * The entity bundle. Defaults to NULL.
   *
   * @var string|null
   */
  protected $bundle;

  /**
   * An associative array with information on the mapping of the field.
   *
   * @var array
   */
  protected $fieldMappingInfo;

  /**
   * Instantiates a new ValueEventBase event object.
   *
   * @param string $entity_type_id
   *   The entity type id.
   * @param string $field
   *   The field name.
   * @param string $value
   *   The field column value.
   * @param array $field_mapping_info
   *   An associative array with information on the mapping of the field.
   * @param null|string $lang
   *   The field language.
   * @param null|string $column
   *   The field column.
   * @param null|string $bundle
   *   The entity bundle.
   */
  public function __construct($entity_type_id, $field, $value, array $field_mapping_info, $lang = NULL, $column = NULL, $bundle = NULL) {
    $this->entityTypeId = $entity_type_id;
    $this->field = $field;
    $this->value = $value;
    $this->lang = $lang;
    $this->column = $column;
    $this->bundle = $bundle;
    $this->fieldMappingInfo = $field_mapping_info;
  }

  /**
   * Returns the entity type id.
   *
   * @return string
   *   The entity type id.
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * Returns the field name.
   *
   * @return string
   *   The field name.
   */
  public function getField() {
    return $this->field;
  }

  /**
   * Returns the field column value.
   *
   * @return string
   *   The field column value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Sets the field column value.
   *
   * @param string $value
   *   The value to set.
   */
  public function setValue($value) {
    $this->value = $value;
  }

  /**
   * Returns an associative array with information on the mapping of the field.
   *
   * @return array
   *   The array filled with information about the field mappings.
   */
  public function getFieldMappingInfo() {
    return $this->fieldMappingInfo;
  }

  /**
   * Returns the field language.
   *
   * @return null|string
   *   The field language, or NULL if not available.
   */
  public function getLang() {
    return $this->lang;
  }

  /**
   * Returns the field column.
   *
   * @return null|string
   *   The field column, or NULL if not available.
   */
  public function getColumn() {
    return $this->column;
  }

  /**
   * Returns the entity bundle.
   *
   * @return null|string
   *   The entity bundle, or NULL if not available.
   */
  public function getBundle() {
    return $this->bundle;
  }

}
