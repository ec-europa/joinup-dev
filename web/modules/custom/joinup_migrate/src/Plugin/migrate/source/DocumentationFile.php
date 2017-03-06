<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides the documentation file migration source plugin.
 *
 * @MigrateSource(
 *   id = "documentation_file"
 * )
 */
class DocumentationFile extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'd',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'path' => $this->t('Source path'),
      'destination_uri' => $this->t('Destination URI'),
      'timestamp' => $this->t('Created time'),
      'uid' => $this->t('File owner'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_documentation_file', 'd')->fields('d');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Assure a full-qualified path.
    $source_path = $this->getLegacySiteWebRoot() . '/' . $row->getSourceProperty('path');
    $row->setSourceProperty('path', $source_path);

    // Build the destination URI.
    $basename = basename($source_path);
    $destination_uri = "public://documentation/$basename";
    $row->setSourceProperty('destination_uri', $destination_uri);

    // Don't let files belong to anonymous.
    if (empty($row->getSourceProperty('uid'))) {
      // Will be replaced with 1 by the default_value processor.
      $row->setSourceProperty('uid', -1);
    }

    return parent::prepareRow($row);
  }

}
