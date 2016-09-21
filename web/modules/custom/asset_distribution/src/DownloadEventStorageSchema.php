<?php

namespace Drupal\asset_distribution;

use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the download_event schema handler.
 */
class DownloadEventStorageSchema extends SqlContentEntityStorageSchema {

  /**
   * {@inheritdoc}
   */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);

    if ($table_name === 'joinup_download_event') {
      $field_name = $storage_definition->getName();
      switch ($field_name) {
        case 'mail':
          $schema['fields'][$field_name]['not null'] = TRUE;
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;

        case 'file':
          $schema['fields'][$field_name]['not null'] = TRUE;
          break;

        case 'created':
          $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
          break;
      }
    }
    return $schema;
  }

}
