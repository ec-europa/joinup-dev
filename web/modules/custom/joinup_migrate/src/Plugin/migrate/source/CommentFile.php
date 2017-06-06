<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Provides a files migration source plugin for comments.
 *
 * @MigrateSource(
 *   id = "comment_file"
 * )
 */
class CommentFile extends JoinupSqlBase {

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'fid' => [
        'type' => 'integer',
        'alias' => 'f',
      ],
    ];
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
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_comment_file', 'f')->fields('f');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Assure a full-qualified path.
    $source_path = $this->getLegacySiteFiles() . '/' . $row->getSourceProperty('path');
    $row->setSourceProperty('path', $source_path);

    // Build the destination URI.
    $basename = basename($source_path);
    $destination_uri = "public://discussion/attachment/$basename";
    $row->setSourceProperty('destination_uri', $destination_uri);

    return parent::prepareRow($row);
  }

}
