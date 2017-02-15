<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;
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
    $query = parent::query();

    $query->leftJoin('content_field_project_soft_logo', 'sl', 's.vid = sl.vid');
    $query->leftJoin('files', 'f', 'sl.field_project_soft_logo_fid = f.fid');

    $is_asset_release = (new Condition('AND'))
      ->condition('s.type', 'asset_release')
      ->isNotNull('s.docs_filepath')
      ->condition('s.docs_filepath', '', '<>');
    $is_project_project = (new Condition('AND'))
      ->condition('s.type', 'project_project')
      ->isNotNull('f.filepath')
      ->condition('f.filepath', '', '<>');

    $or = (new Condition('OR'))
      ->condition($is_asset_release)
      ->condition($is_project_project)
      ->isNotNull('s.logo');

    $query->condition($or);

    $query->addExpression("IF(s.logo IS NOT NULL, '', IF(s.type = 'asset_release', s.docs_filepath, f.filepath))", 'file_path');
    $query->addExpression("FROM_UNIXTIME(IF(s.type = 'asset_release', s.timestamp, f.timestamp), '%Y-%m-%dT%H:%i:%s')", 'created');
    $query->addExpression("IF(s.type = 'asset_release', s.uid, f.uid)", 'file_uid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Assure a created date.
    if (!$row->getSourceProperty('created')) {
      $row->setSourceProperty('created', date('Y-m-d\TH:i:s', \Drupal::time()->getRequestTime()));
    }

    if ($basename = $row->getSourceProperty('logo')) {
      $source_path = "../resources/migrate/solution/logo/$basename";
    }
    elseif ($filepath = $row->getSourceProperty('file_path')) {
      $source_path = $this->getLegacySiteWebRoot() . '/' . $filepath;
      $basename = basename($filepath);
    }
    else {
      // Skip this row if there's no file.
      return FALSE;
    }

    // Set the source path.
    $row->setSourceProperty('source_path', $source_path);

    // Build de destination URI.
    $destination_uri = "public://solution/logo/$basename";
    $row->setSourceProperty('destination_uri', $destination_uri);

    // Don't let images belong to anonymous.
    if (empty($row->getSourceProperty('file_uid'))) {
      // Will be replaced with 1 by the default_value process.
      $row->setSourceProperty('file_uid', -1);
    }

    return parent::prepareRow($row);
  }

}
