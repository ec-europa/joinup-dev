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

    $this->alias['content_type_asset_release'] = $query->leftJoin('content_type_asset_release', 'content_type_asset_release', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['node_documentation'] = $query->leftJoin('node', 'node_documentation', "{$this->alias['content_type_asset_release']}.field_asset_sw_logo_nid = %alias.nid");
    $this->alias['content_type_documentation'] = $query->leftJoin('content_type_documentation', 'content_type_documentation', "{$this->alias['node_documentation']}.vid = %alias.vid");
    $this->alias['files'] = $query->leftJoin('files', 'files', "{$this->alias['content_type_documentation']}.field_documentation_access_url_fid = %alias.fid");

    $and = (new Condition('AND'))
      ->isNotNull("{$this->alias['files']}.filepath")
      ->condition("{$this->alias['files']}.filepath", '', '<>');

    $or = (new Condition('OR'))
      ->condition($and)
      ->isNotNull('m.logo');

    $query
      ->fields('m', ['logo'])
      ->condition($or);

    $query->addExpression("FROM_UNIXTIME({$this->alias['files']}.timestamp, '%Y-%m-%dT%H:%i:%s')", 'created');
    $query->addExpression("{$this->alias['files']}.uid", 'file_uid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Assure a created date.
    if (!$row->getSourceProperty('created')) {
      $row->setSourceProperty('created', date('Y-m-d\TH:i:s', REQUEST_TIME));
    }

    if ($basename = $row->getSourceProperty('logo')) {
      $source_path = "../resources/migrate/solution/logo/$basename";
    }
    elseif ($filepath = $row->getSourceProperty('filepath')) {
      $source_path = $this->getLegacySiteWebRoot() . '/' . $filepath;
      $basename = $basename($filepath);
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
    if (($file_uid = $row->getSourceProperty('file_uid')) == 0) {
      // Will be replaced with 1 by the default_value process.
      $row->setSourceProperty('file_uid', -1);
    }

    return parent::prepareRow($row);
  }

}
