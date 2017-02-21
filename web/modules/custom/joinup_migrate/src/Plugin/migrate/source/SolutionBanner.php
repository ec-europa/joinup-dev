<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates solution banner files.
 *
 * @MigrateSource(
 *   id = "solution_banner"
 * )
 */
class SolutionBanner extends SolutionBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'source_path' => $this->t('Source path'),
      'destination_uri' => $this->t('Destination URI'),
      'created' => $this->t('Created time'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();
    return $query
      ->fields('m', ['banner'])
      ->isNotNull('m.banner');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Build source path.
    $source_path = NULL;
    $timestamp = NULL;

    if ($banner = $row->getSourceProperty('banner')) {
      $source_path = "../resources/migrate/solution/banner/$banner";
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
      $uri = "public://solution/banner/$basename";
    }
    $row->setSourceProperty('destination_uri', $uri);
    $row->setSourceProperty('created', $timestamp);

    return parent::prepareRow($row);
  }

}
