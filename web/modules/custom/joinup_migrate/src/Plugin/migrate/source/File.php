<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Core\Entity\EntityInterface;
use Drupal\joinup_migrate\FileUtility;
use Drupal\joinup_migrate\RedirectImportInterface;
use Drupal\migrate\Row;

/**
 * Base plugin for files migration.
 *
 * @MigrateSource(
 *   id = "file"
 * )
 */
class File extends JoinupSqlBase implements RedirectImportInterface {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return ['fid' => ['type' => 'string']];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'fid' => $this->t('File ID'),
      'path' => $this->t('File path'),
      'timestamp' => $this->t('Created time'),
      'uid' => $this->t('File owner'),
      'destination_uri' => $this->t('Destination URI'),
      'numeric_fid' => $this->t('Numeric file ID'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table = 'd8_file_' . $this->migration->getDerivativeId();
    return $this->select($table)
      ->fields($table)
      // Extra precaution for views that are returning also non-file records.
      // @see web/modules/custom/joinup_migrate/fixture/1.file_documentation.sql
      ->isNotNull("$table.fid");
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Assure a full-qualified path for managed files.
    $fid = $row->getSourceProperty('fid');
    if (ctype_digit($fid)) {
      $source_path = FileUtility::getLegacySiteFiles() . '/' . $row->getSourceProperty('path');
      $row->setSourceProperty('path', $source_path);
      // If there's a migrated managed file, we use this to assign the same FID.
      $row->setSourceProperty('numeric_fid', (int) $fid);
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectSources(Row $row) {
    $sources = [];

    if ($fid = (int) $row->getSourceProperty('numeric_fid')) {
      $sql = "SELECT filepath FROM files WHERE fid = :fid";
      if ($path = $this->getDatabase()->query($sql, [':fid' => $fid])->fetchField()) {
        $sources[] = $path;
      }
    }

    return $sources;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUri(EntityInterface $entity) {
    /** @var \Drupal\file\FileInterface $entity */
    // Such redirects are not cleared automatically by the Redirect module, when
    // the source file entity is deleted. Thus, we are fulfilling this task in
    // our custom module, in joinup_core_file_delete().
    // @see joinup_core_file_delete()
    return 'base:/sites/default/files/' . file_uri_target($entity->getFileUri());
  }

}
