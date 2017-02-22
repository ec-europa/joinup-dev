<?php

namespace Drupal\rdf_file\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
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
    $schema = parent::schema($field_definition);
    $schema['columns']['target_id'] = [
      'description' => 'The URI of the file entity.',
      'type' => 'varchar',
      'length' => 2048,
    ];
    unset($schema['foreign keys']);
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $target_id_definition = DataReferenceTargetDefinition::create('string')
      ->setLabel(new TranslatableMarkup('@label ID', ['@label' => 'file']));
    $target_id_definition->setRequired(TRUE);
    $properties['target_id'] = $target_id_definition;

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $is_empty = parent::isEmpty();

    // An entity reference item is empty when the target ID is strictly NULL in
    // order to allow pointing to 0 (integer zero) or '' (empty string). That
    // makes sense if you think that a user reference can point to Anonymous
    // user (uid === 0) or a taxonomy term points to the root (tid === 0). But
    // an rdf_file item is empty when the target ID is '' (empty string).
    if (!$is_empty && is_string($this->target_id)) {
      $target_id = trim($this->target_id);
      if ($target_id === '') {
        return TRUE;
      }
    }

    return $is_empty;
  }

}
