<?php

namespace Drupal\rdf_file\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'file' field type.
 *
 * @FieldType(
 *   id = "rdf_file",
 *   label = @Translation("RDF File"),
 *   description = @Translation("This field stores the ID of a file as an URI."),
 *   category = @Translation("Reference"),
 *   default_widget = "rdf_file_generic",
 *   default_formatter = "rdf_file_default",
 *   list_class = "\Drupal\rdf_file\Plugin\Field\FieldType\RdfFileFieldItemList",
 *
 * )
 */
class RdfFileItem extends FileItem {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return array(
      'columns' => array(
        'target_id' => array(
          'description' => 'The ID of the file entity.',
          'type' => 'varchar',
          'length' => 2048,
        ),
        'display' => array(
          'description' => 'Flag to control whether this file should be displayed when viewing content.',
          'type' => 'int',
          'size' => 'tiny',
          'unsigned' => TRUE,
          'default' => 1,
        ),
        'description' => array(
          'description' => 'A description of the file.',
          'type' => 'text',
        ),
      ),
      'indexes' => array(
        'target_id' => array('target_id'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['display'] = DataDefinition::create('boolean')
      ->setLabel(t('Display'))
      ->setDescription(t('Flag to control whether this file should be displayed when viewing content'));

    $properties['description'] = DataDefinition::create('string')
      ->setLabel(t('Description'));

    $target_id_definition = DataReferenceTargetDefinition::create('string')
      ->setLabel(new TranslatableMarkup('@label ID', ['@label' => 'file']));
    $target_id_definition->setRequired(TRUE);
    $properties['target_id'] = $target_id_definition;

    return $properties;
  }

}
