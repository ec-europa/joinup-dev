<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides a files migration source plugin for 'document' nodes.
 *
 * @MigrateSource(
 *   id = "document_file"
 * )
 */
class DocumentFile extends Document {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('ID'),
      'file_path' => $this->t('Source path'),
      'destination_uri' => $this->t('Destination URI'),
      'file_timestamp' => $this->t('Created time'),
      'file_uid' => $this->t('File owner'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return parent::query()->fields('n', [
      'file_id',
      'file_path',
      'file_timestamp',
      'file_uid',
    ])->isNotNull('file_path')
      ->condition('file_path', '', '<>');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Assure a full-qualified path.
    $source_path = $this->getLegacySiteWebRoot() . '/' . $row->getSourceProperty('file_path');
    $row->setSourceProperty('file_path', $source_path);

    // Build the destination URI.
    $date = date('Y-m', $row->getSourceProperty('file_timestamp'));
    $basename = basename($source_path);
    $destination_uri = "public://document/$date/$basename";
    $row->setSourceProperty('destination_uri', $destination_uri);

    return parent::prepareRow($row);
  }

}
