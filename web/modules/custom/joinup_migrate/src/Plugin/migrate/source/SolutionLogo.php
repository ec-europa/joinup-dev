<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\Unicode;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Migrates solution logo files.
 *
 * @MigrateSource(
 *   id = "solution_logo"
 * )
 */
class SolutionLogo extends SolutionBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'source_path' => $this->t('Source path'),
      'destination_uri' => $this->t('Destination URI'),
      'created' => $this->t('Created time'),
      'file_uid' => $this->t('File owner'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return parent::query()
      ->fields('s', [
        'logo',
        'logo_timestamp',
        'logo_uid',
      ])
      ->isNotNull('s.logo');
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
      $source_path = "../resources/migrate/solution/logo/$source_path";
    }
    // Set the source path.
    $row->setSourceProperty('source_path', $source_path);

    print_r("prepareRow\n");
    var_export($source_path);print_r("\n");
    var_export(\Drupal::httpClient()->request('GET', $source_path)->getStatusCode());print_r("\n");

    // Build de destination URI.
    $basename = basename($source_path);
    $row->setSourceProperty('destination_uri', "public://solution/logo/$basename");

    // File created time.
    $row->setSourceProperty('created', $row->getSourceProperty('logo_timestamp'));

    // Don't let images belong to anonymous.
    if (empty($row->getSourceProperty('file_uid'))) {
      // Will be replaced with 1 by the default_value process.
      $row->setSourceProperty('file_uid', -1);
    }

    return parent::prepareRow($row);
  }

}
