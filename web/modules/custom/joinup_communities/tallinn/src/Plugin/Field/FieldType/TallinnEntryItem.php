<?php

namespace Drupal\tallinn\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'tallinn_entry' field type.
 *
 * @FieldType(
 *   id = "tallinn_entry",
 *   label = @Translation("Tallinn entry"),
 *   description = @Translation("Stores data about a tallinn section."),
 *   default_widget = "tallinn_entry_default",
 *   default_formatter = "tallinn_entry"
 * )
 */
class TallinnEntryItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Properties related to the options value.
    $properties['status'] = DataDefinition::create('string')
      ->setLabel('Implementation status')
      ->addConstraint('Length', ['max' => 255])
      ->setRequired(FALSE);

    // Properties related to the explanation value.
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Explanation'))
      ->setRequired(FALSE);
    $properties['format'] = DataDefinition::create('filter_format')
      ->setLabel(t('Text format'));

    // Properties related to the link value.
    $properties['uri'] = DataDefinition::create('uri')
      ->setLabel(t('Related website'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        // Schema related to the options value.
        'status' => [
          'description' => 'The status of the action',
          'type' => 'varchar',
          'length' => 255,
        ],
        // Schema related to the explanation value.
        'value' => [
          'description' => 'The explanation value.',
          'type' => 'text',
          'size' => 'big',
        ],
        'format' => [
          'description' => 'The format of the explanation value.',
          'type' => 'varchar_ascii',
          'length' => 255,
        ],
        // Schema related to the link value.
        'uri' => [
          'description' => 'The URI of the link.',
          'type' => 'varchar',
          'length' => 2048,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function mainPropertyName() {
    return 'value';
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return empty($this->getValue()['value']) && empty($this->getValue()['status']) && empty($this->getValue()['uri']);
  }

  /**
   * Returns a list of options available for the status field.
   *
   * @return array
   *   An array of options.
   */
  public static function getStatusOptions() {
    return [
      'no_data' => t('No data'),
      'no_progress' => t('No progress'),
      'in_progress' => t('In progress'),
      'completed' => t('Completed'),
    ];
  }

}
