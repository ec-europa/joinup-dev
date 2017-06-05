<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides a distribution file migration source plugin.
 *
 * @MigrateSource(
 *   id = "distribution_file"
 * )
 */
class DistributionFile extends DistributionBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'source_path' => $this->t('Source path'),
      'destination_uri' => $this->t('Destination URI'),
      'created' => $this->t('Created time'),
      'file_uid' => $this->t('File owner'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return parent::query()
      ->fields('d', ['file_path', 'file_timestamp', 'file_uid'])
      ->isNotNull('d.file_path')
      ->condition('d.file_path', '', '<>');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Assure a full-qualified path.
    $source_path = $this->getLegacySiteFiles() . '/' . $row->getSourceProperty('file_path');
    $row->setSourceProperty('source_path', $source_path);

    // Build the destination URI.
    $created = $row->getSourceProperty('file_timestamp') ?: REQUEST_TIME;
    $year = date('Y', $created);
    $month = date('m', $created);
    $basename = basename($source_path);
    $destination_uri = "public://distribution/$year-$month/$basename";
    $row->setSourceProperty('destination_uri', $destination_uri);

    // The file creation timestamp.
    $row->setSourceProperty('created', $created);

    // Don't let files belong to anonymous.
    if (($file_uid = $row->getSourceProperty('file_uid')) == 0) {
      // Will be replaced with 1 by the default_value processor.
      $row->setSourceProperty('file_uid', -1);
    }

    return parent::prepareRow($row);
  }

}
