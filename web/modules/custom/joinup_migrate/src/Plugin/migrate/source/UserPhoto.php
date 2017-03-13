<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides a user photo migration source plugin.
 *
 * @MigrateSource(
 *   id = "user_photo"
 * )
 */
class UserPhoto extends UserBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'source_path' => $this->t('Source path'),
      'destination_uri' => $this->t('Destination URI'),
      'photo_timestamp' => $this->t('Created time'),
      'file_uid' => $this->t('File owner'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return parent::query()->fields('u', [
      'photo_path',
      'photo_timestamp',
      'photo_uid',
    ])->isNotNull('u.photo_path');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Assure a full-qualified path.
    $source_path = $this->getLegacySiteWebRoot() . '/' . $row->getSourceProperty('photo_path');
    $row->setSourceProperty('source_path', $source_path);

    // Build the destination URI.
    $created = $row->getSourceProperty('photo_timestamp') ?: REQUEST_TIME;
    $year = date('Y', $created);
    $month = date('m', $created);
    $basename = basename($source_path);
    $destination_uri = "public://user/$year-$month/$basename";
    $row->setSourceProperty('destination_uri', $destination_uri);

    return parent::prepareRow($row);
  }

}
