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
    $this->alias['asset_release_files'] = $query->leftJoin('files', 'asset_release_files', "{$this->alias['content_type_documentation']}.field_documentation_access_url_fid = %alias.fid");
    $this->alias['content_field_project_soft_logo'] = $query->leftJoin('content_field_project_soft_logo', 'content_field_project_soft_logo', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['project_project_files'] = $query->leftJoin('files', 'project_project_files', "{$this->alias['content_field_project_soft_logo']}.field_project_soft_logo_fid = %alias.fid");

    $is_asset_release = (new Condition('AND'))
      ->condition('m.type', 'asset_release')
      ->isNotNull("{$this->alias['asset_release_files']}.filepath")
      ->condition("{$this->alias['asset_release_files']}.filepath", '', '<>');
    $is_project_project = (new Condition('AND'))
      ->condition('m.type', 'project_project')
      ->isNotNull("{$this->alias['project_project_files']}.filepath")
      ->condition("{$this->alias['project_project_files']}.filepath", '', '<>');

    $or = (new Condition('OR'))
      ->condition($is_asset_release)
      ->condition($is_project_project)
      ->isNotNull('m.logo');

    $query
      ->fields('m', ['logo'])
      ->condition($or);

    $query->addExpression("IF(m.logo IS NOT NULL, '', IF(m.type = 'asset_release', {$this->alias['asset_release_files']}.filepath, {$this->alias['project_project_files']}.filepath))", 'file_path');
    $query->addExpression("FROM_UNIXTIME(IF(m.type = 'asset_release', {$this->alias['asset_release_files']}.timestamp, {$this->alias['project_project_files']}.timestamp), '%Y-%m-%dT%H:%i:%s')", 'created');
    $query->addExpression("IF(m.type = 'asset_release', {$this->alias['asset_release_files']}.uid, {$this->alias['project_project_files']}.uid)", 'file_uid');

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
