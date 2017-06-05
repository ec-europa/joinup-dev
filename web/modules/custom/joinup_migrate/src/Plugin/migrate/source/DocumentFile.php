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
class DocumentFile extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'df',
      ],
      'delta' => [
        'type' => 'integer',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('Parent document node ID'),
      'vid' => $this->t('Parent document node revision ID'),
      'delta' => $this->t('Field item delta'),
      'fid' => $this->t('File ID'),
      'path' => $this->t('File path'),
      'timestamp' => $this->t('Created time'),
      'uid' => $this->t('File owner'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_document_file', 'df')
      ->fields('df', array_keys($this->fields()));
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Assure a full-qualified path.
    $source_path = $this->getLegacySiteFiles() . '/' . $row->getSourceProperty('path');
    $row->setSourceProperty('path', $source_path);

    // Build the destination URI.
    $date = date('Y-m', $row->getSourceProperty('timestamp'));
    $basename = basename($source_path);
    $destination_uri = "public://document/$date/$basename";
    $row->setSourceProperty('destination_uri', $destination_uri);

    return parent::prepareRow($row);
  }

}
