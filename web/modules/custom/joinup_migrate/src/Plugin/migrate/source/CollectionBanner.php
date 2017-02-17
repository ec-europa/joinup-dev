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
    return parent::query()
      ->fields('c', ['banner'])
      ->isNotNull('c.banner');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $source_path = "../resources/migrate/collection/banner/{$row->getSourceProperty('banner')}";
    $row->setSourceProperty('source_path', $source_path);
    $basename = basename($source_path);
    $row->setSourceProperty('destination_uri', "public://collection/banner/$basename");

    return parent::prepareRow($row);
  }

}
