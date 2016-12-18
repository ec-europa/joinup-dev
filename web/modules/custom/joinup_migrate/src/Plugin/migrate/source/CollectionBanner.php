<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates collection banner file.
 *
 * @MigrateSource(
 *   id = "collection_banner"
 * )
 */
class CollectionBanner extends CollectionBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'source_path' => $this->t('Source path'),
      'destination_uri' => $this->t('Destination URI'),
      'created' => $this->t('Created time'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    return $query
      ->fields('j', ['banner'])
      ->isNotNull('j.banner');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Build source path.
    $source_path = NULL;
    $timestamp = NULL;
    if ($banner = $row->getSourceProperty('banner')) {
      $source_path = "../resources/migrate/collection/banner/$banner";
      $timestamp = REQUEST_TIME;
    }

    // Skip this row if there's no file.
    if (!$source_path) {
      return FALSE;
    }

    $row->setSourceProperty('source_path', $source_path);

    $uri = NULL;
    if ($source_path) {
      // Build de destination URI.
      $basename = basename($source_path);
      $uri = "public://collection/banner/$basename";
    }
    $row->setSourceProperty('destination_uri', $uri);
    $row->setSourceProperty('created', $timestamp);

    return parent::prepareRow($row);
  }

}
