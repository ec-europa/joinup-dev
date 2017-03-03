<?php

namespace Drupal\file_url\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\file\Plugin\Field\FieldType\FileItem;

/**
 * Plugin implementation of the 'file' field type.
 *
 * @FieldType(
 *   id = "file_url",
 *   label = @Translation("File URL"),
 *   description = @Translation("This field stores the ID of a file as an URI."),
 *   category = @Translation("Reference"),
 *   default_widget = "file_url_generic",
 *   default_formatter = "file_url_default",
 *   list_class = "\Drupal\file_url\Plugin\Field\FieldType\FileUrlFieldItemList",
 *
 * )
 */
class FileUrlItem extends FileItem {

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
    // an file_url item is empty when the target ID is '' (empty string).
    if (!$is_empty && is_string($this->target_id)) {
      $target_id = trim($this->target_id);
      if ($target_id === '') {
        return TRUE;
      }
    }

    return $is_empty;
  }

}
