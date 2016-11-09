<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Site\Settings;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;

/**
 * Provides a user photo migration source plugin.
 *
 * @MigrateSource(
 *   id = "user_photo"
 * )
 */
class UserPhoto extends UserBase {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return parent::fields() + [
      'source_path' => $this->t('Source path'),
      'destination_uri' => $this->t('Destination URI'),
      'created' => $this->t('Created time'),
      'file_uid' => $this->t('File owner'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = parent::query();

    $this->alias['node'] = $query->leftJoin('node', 'node', "u.uid = %alias.uid AND %alias.type = 'profile'");
    $this->alias['profile'] = $query->leftJoin('content_type_profile', 'profile', "{$this->alias['node']}.vid = %alias.vid");
    $this->alias['files'] = $query->leftJoin('files', 'files', "{$this->alias['profile']}.field_photo_fid = %alias.fid AND %alias.filepath <> ''");
    $query->addExpression("{$this->alias['files']}.filepath", 'source_path');
    $query->addExpression("{$this->alias['files']}.timestamp", 'created');
    $query->addExpression("{$this->alias['files']}.uid", 'file_uid');

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    if (!$source_path = $row->getSourceProperty('source_path')) {
      // Skip this row if there's no file.
      return FALSE;
    }

    // Assure a full-qualified path.
    $root = Settings::get('joinup_migrate.source.root');
    if (empty($root)) {
      throw new MigrateException('The web root of the D6 site is not configured. Please run `phing setup-migration`.');
    }
    $row->setSourceProperty('source_path', $root . '/' . $source_path);

    // Build the destination URI.
    $created = $row->getSourceProperty('created') ?: REQUEST_TIME;
    $year = date('Y', $created);
    $month = date('m', $created);
    $basename = basename($source_path);
    $destination_uri = "public://user/$year-$month/$basename";

    $row->setSourceProperty('destination_uri', $destination_uri);
    $row->setSourceProperty('created', $created);

    // Don't let photos belong to anonymous.
    if (($file_uid = $row->getSourceProperty('file_uid')) == 0) {
      // Will be replaced with 1 by the default_value process.
      $row->setSourceProperty('file_uid', -1);
    }

    return parent::prepareRow($row);
  }

}
