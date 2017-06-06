<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Base plugin for files migration.
 *
 * @MigrateSource(
 *   id = "file"
 * )
 */
class File extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['fid' => ['type' => 'string']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fid' => $this->t('File ID'),
      'path' => $this->t('File path'),
      'timestamp' => $this->t('Created time'),
      'uid' => $this->t('File owner'),
      'destination_uri' => $this->t('Destination URI'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table = 'd8_file_' . $this->configuration['derivative'];
    return $this->select($table)->fields($table);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Assure a full-qualified path.
    $source_path = $this->getLegacySiteFiles() . '/' . $row->getSourceProperty('path');
    $row->setSourceProperty('path', $source_path);

    return parent::prepareRow($row);
  }

}
