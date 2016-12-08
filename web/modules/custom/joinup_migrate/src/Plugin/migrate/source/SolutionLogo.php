<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

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

    $this->alias['content_type_asset_release'] = $query->join("{$this->getSourceDbName()}.content_type_asset_release", 'content_type_asset_release', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['node_documentation'] = $query->join("{$this->getSourceDbName()}.content_type_documentation", 'node_documentation', "{$this->alias['content_type_asset_release']}.field_asset_sw_logo_nid = %alias.nid");
    $this->alias['content_type_documentation'] = $query->join("{$this->getSourceDbName()}.content_type_documentation", 'content_type_documentation', "{$this->alias['node_documentation']}.vid = %alias.vid");
    $this->alias['files'] = $query->join("{$this->getSourceDbName()}.files", 'files', "{$this->alias['content_type_documentation']}.field_documentation_access_url_fid = %alias.fid");

    $query
      ->isNotNull("{$this->alias['files']}.filepath")
      ->condition("{$this->alias['files']}.filepath", '', '<>');

    $query->addExpression("CONCAT_WS('\/', '{$this->getLegacySiteWebRoot()}', {$this->alias['files']}.filepath)", 'source_path');
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

    $uri = NULL;
    if ($source_path = $row->getSourceProperty('source_path')) {
      // Build de destination URI.
      $basename = basename($source_path);
      $uri = "public://solution/logo/$basename";
    }
    $row->setSourceProperty('destination_uri', $uri);

    // Don't let photos belong to anonymous.
    if (($file_uid = $row->getSourceProperty('file_uid')) == 0) {
      // Will be replaced with 1 by the default_value process.
      $row->setSourceProperty('file_uid', -1);
    }

    return parent::prepareRow($row);
  }

}
