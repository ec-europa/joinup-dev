<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Database\Query\Condition;
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

    $this->alias['community_files'] = $query->leftJoin('files', 'community_files', "{$this->alias['community']}.field_community_logo_fid = %alias.fid");
    $this->alias['repository_files'] = $query->leftJoin('files', 'repository_files', "{$this->alias['repository']}.field_repository_logo_fid = %alias.fid");

    $query->addExpression("{$this->alias['community_files']}.filepath", 'community_file');
    $query->addExpression("{$this->alias['community_files']}.timestamp", 'community_time');
    $query->addExpression("{$this->alias['repository_files']}.filepath", 'repository_file');
    $query->addExpression("{$this->alias['repository_files']}.timestamp", 'repository_time');

    $or = (new Condition('OR'))
      ->isNotNull("{$this->alias['community_files']}.filepath")
      ->isNotNull("{$this->alias['repository_files']}.filepath")
      ->isNotNull("j.logo");

    return $query
      ->fields('j', ['logo'])
      ->condition($or);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Build source path. A new logo proposal in the mapping table wins.
    $source_path = NULL;
    $timestamp = REQUEST_TIME;

    if ($logo = $row->getSourceProperty('logo')) {
      $source_path = "../resources/migrate/collection/logo/$logo";
    }
    elseif ($community_file = $row->getSourceProperty('community_file')) {
      $source_path = "{$this->getLegacySiteWebRoot()}/$community_file";
      $timestamp = $row->getSourceProperty('community_time');
    }
    elseif ($repository_file = $row->getSourceProperty('repository_file')) {
      $source_path = "{$this->getLegacySiteWebRoot()}/$repository_file";
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
