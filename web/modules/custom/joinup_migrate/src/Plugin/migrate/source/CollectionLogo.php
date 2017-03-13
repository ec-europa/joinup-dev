<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\Unicode;
use Drupal\migrate\Row;

/**
 * Migrates collection logo file.
 *
 * @MigrateSource(
 *   id = "collection_logo"
 * )
 */
class CollectionLogo extends CollectionBase {

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
      ->fields('c', ['logo', 'logo_timestamp'])
      ->isNotNull('c.logo');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $source_path = $row->getSourceProperty('logo');
    // Qualify the path.
    if (Unicode::strpos($source_path, 'sites/default/files/') === 0) {
      // Existing logo. Prepend the path to legacy site root.
      $source_path = "{$this->getLegacySiteWebRoot()}/$source_path";
    }
    else {
      // New logo.
      $source_path = "../resources/migrate/collection/logo/$source_path";
    }

    $row->setSourceProperty('source_path', $source_path);

    // Build de destination URI.
    $basename = basename($source_path);
    $row->setSourceProperty('destination_uri', "public://collection/logo/$basename");

    // File created time.
    $row->setSourceProperty('created', $row->getSourceProperty('logo_timestamp'));

    return parent::prepareRow($row);
  }

}
