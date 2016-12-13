<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Site\Settings;
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
    $query = parent::query();

    $this->alias['community_files'] = $query->leftJoin("{$this->getSourceDbName()}.files", 'files', "{$this->alias['community']}.field_community_logo_fid = %alias.fid");
    $this->alias['repository_files'] = $query->leftJoin("{$this->getSourceDbName()}.files", 'files', "{$this->alias['repository']}.field_repository_logo_fid = %alias.fid");

    $query->addExpression("{$this->alias['community_files']}.filepath", 'community_file');
    $query->addExpression("{$this->alias['community_files']}.timestamp", 'community_time');
    $query->addExpression("{$this->alias['repository_files']}.filepath", 'repository_file');
    $query->addExpression("{$this->alias['repository_files']}.timestamp", 'repository_time');

    return $query->fields('j', ['logo']);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Build source path. A new logo proposal in the mapping table wins.
    $source_path = NULL;
    $timestamp = REQUEST_TIME;
    // If we don't have a copy of the source file-system, we use the live site
    // but this is not recommended because is slower and might trigger some
    // anti-crawler protection from the server.
    $source_root = Settings::get('joinup_migrate.source.root', 'https://joinup.ec.europa.eu');
    if ($logo = $row->getSourceProperty('logo')) {
      $source_path = "../resources/migrate/collection/logo/$logo";
    }
    elseif ($community_file = $row->getSourceProperty('community_file')) {
      $source_path = "$source_root/$community_file";
      $timestamp = $row->getSourceProperty('community_time');
    }
    elseif ($repository_file = $row->getSourceProperty('repository_file')) {
      $source_path = "$source_root/$repository_file";
      $timestamp = $row->getSourceProperty('repository_time');
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
      $uri = "public://collection/logo/$basename";
    }
    $row->setSourceProperty('destination_uri', $uri);
    $row->setSourceProperty('created', $timestamp);

    return parent::prepareRow($row);
  }

}
